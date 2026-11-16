<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\LeadAudit;
use App\Models\User;
use App\Support\PhoneNormalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LeadService
{
    public function __construct(
        private readonly LeadAuditService $auditService,
        private readonly LeadAssignmentService $assignmentService,
        private readonly DuplicateLeadDetector $duplicateDetector,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{lead: Lead, duplicates: \Illuminate\Support\Collection}
     */
    public function createManual(array $data, User $actor): array
    {
        return DB::transaction(function () use ($data, $actor) {
            $customer = $this->resolveCustomer($data);

            $lead = Lead::create([
                'reference' => $this->nextReference(),
                'customer_id' => $customer->id,
                'product_id' => $data['product_id'] ?? null,
                'lead_source_id' => $data['lead_source_id'] ?? null,
                'campaign' => $data['campaign'] ?? null,
                'description' => $data['description'] ?? null,
                'priority' => $data['priority'] ?? 'medium',
                'status' => $data['status'] ?? 'new',
                'follow_up_at' => $data['follow_up_at'] ?? null,
                'consent_contact' => (bool) ($data['consent_contact'] ?? false),
                'notes' => $data['notes'] ?? null,
                'county_id' => $data['county_id'] ?? $customer->county_id,
                'town' => $data['town'] ?? $customer->town,
                'created_by' => $actor->id,
                'channel' => 'manual',
            ]);

            $lead->statusHistories()->create([
                'from_status' => null,
                'to_status' => $lead->status,
                'user_id' => $actor->id,
                'notes' => 'Lead created',
                'created_at' => now(),
            ]);

            $this->auditService->recordChanges($lead, [], $lead->getAttributes(), $actor);

            if (! empty($data['assigned_to'])) {
                $assignee = User::find($data['assigned_to']);
                if ($assignee) {
                    $this->assignmentService->assign($lead, $assignee, $actor, 'manual');
                }
            } elseif (! empty($data['assignment_strategy'])) {
                $this->assignmentService->assignByStrategy(
                    $lead,
                    $actor,
                    $data['assignment_strategy'],
                    isset($data['team_id']) ? (int) $data['team_id'] : null
                );
            }

            $duplicates = $this->duplicateDetector->findPossibleDuplicates(
                $customer->phone,
                $customer->phone_alt,
                $customer->email,
                $lead->id
            );

            return [
                'lead' => $lead->fresh(['customer', 'assignee', 'product', 'leadSource', 'county']),
                'duplicates' => $duplicates,
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateLead(Lead $lead, array $data, User $actor): Lead
    {
        return DB::transaction(function () use ($lead, $data, $actor) {
            $before = $lead->getAttributes();
            $customer = $lead->customer;

            $customerData = [
                'first_name' => $data['first_name'] ?? $customer->first_name,
                'last_name' => $data['last_name'] ?? $customer->last_name,
                'phone' => $data['phone'] ?? $customer->phone,
                'phone_alt' => $data['phone_alt'] ?? $customer->phone_alt,
                'email' => $data['email'] ?? $customer->email,
                'gender' => $data['gender'] ?? $customer->gender,
                'county_id' => $data['county_id'] ?? $customer->county_id,
                'town' => $data['town'] ?? $customer->town,
                'consent_marketing' => array_key_exists('consent_contact', $data)
                    ? (bool) $data['consent_contact']
                    : $customer->consent_marketing,
                'notes' => $data['customer_notes'] ?? $customer->notes,
            ];

            $this->fillCustomer($customer, $customerData);
            $customer->save();

            $statusChanged = array_key_exists('status', $data) && $data['status'] !== $lead->status;
            $oldStatus = $lead->status;

            $lead->fill([
                'product_id' => $data['product_id'] ?? $lead->product_id,
                'lead_source_id' => $data['lead_source_id'] ?? $lead->lead_source_id,
                'campaign' => $data['campaign'] ?? $lead->campaign,
                'description' => $data['description'] ?? $lead->description,
                'priority' => $data['priority'] ?? $lead->priority,
                'status' => $data['status'] ?? $lead->status,
                'follow_up_at' => array_key_exists('follow_up_at', $data) ? $data['follow_up_at'] : $lead->follow_up_at,
                'consent_contact' => array_key_exists('consent_contact', $data)
                    ? (bool) $data['consent_contact']
                    : $lead->consent_contact,
                'notes' => $data['notes'] ?? $lead->notes,
                'county_id' => $data['county_id'] ?? $lead->county_id,
                'town' => $data['town'] ?? $lead->town,
            ]);
            $lead->save();

            $this->auditService->recordChanges($lead, $before, $lead->getAttributes(), $actor);

            if ($statusChanged) {
                $lead->statusHistories()->create([
                    'from_status' => $oldStatus,
                    'to_status' => $lead->status,
                    'user_id' => $actor->id,
                    'notes' => $data['status_notes'] ?? null,
                    'created_at' => now(),
                ]);
            }

            if (array_key_exists('assigned_to', $data) && (int) $data['assigned_to'] !== (int) $lead->assigned_to) {
                $assignee = ! empty($data['assigned_to']) ? User::find($data['assigned_to']) : null;
                $this->assignmentService->assign($lead, $assignee, $actor, 'manual');
            }

            return $lead->fresh(['customer', 'assignee', 'product', 'leadSource', 'county']);
        });
    }

    public function changeStatus(Lead $lead, string $status, User $actor, ?string $notes = null): Lead
    {
        return DB::transaction(function () use ($lead, $status, $actor, $notes) {
            $before = $lead->only(['status']);
            $oldStatus = $lead->status;

            if ($oldStatus === $status) {
                return $lead;
            }

            $lead->status = $status;
            $lead->save();

            $lead->statusHistories()->create([
                'from_status' => $oldStatus,
                'to_status' => $status,
                'user_id' => $actor->id,
                'notes' => $notes,
                'created_at' => now(),
            ]);

            $this->auditService->recordChanges($lead, $before, $lead->only(['status']), $actor);

            return $lead->fresh();
        });
    }

    public function merge(Lead $primary, Lead $secondary, User $actor): Lead
    {
        if ($primary->id === $secondary->id) {
            throw new \InvalidArgumentException('Cannot merge a lead into itself.');
        }

        return DB::transaction(function () use ($primary, $secondary, $actor) {
            $before = $primary->getAttributes();

            foreach (['product_id', 'lead_source_id', 'campaign', 'description', 'priority', 'follow_up_at', 'notes', 'county_id', 'town'] as $field) {
                if (blank($primary->{$field}) && filled($secondary->{$field})) {
                    $primary->{$field} = $secondary->{$field};
                }
            }

            if (! $primary->assigned_to && $secondary->assigned_to) {
                $primary->assigned_to = $secondary->assigned_to;
            }

            if (! $primary->consent_contact && $secondary->consent_contact) {
                $primary->consent_contact = true;
            }

            $primary->save();

            foreach ($secondary->audits as $audit) {
                $audit->lead_id = $primary->id;
                $audit->save();
            }

            foreach ($secondary->statusHistories as $history) {
                $history->lead_id = $primary->id;
                $history->save();
            }

            foreach ($secondary->assignmentHistories as $history) {
                $history->lead_id = $primary->id;
                $history->save();
            }

            $this->auditService->recordChanges($primary, $before, $primary->getAttributes(), $actor);

            LeadAudit::create([
                'lead_id' => $primary->id,
                'user_id' => $actor->id,
                'field' => 'merged_from',
                'old_value' => null,
                'new_value' => $secondary->reference,
                'created_at' => now(),
            ]);

            $secondaryCustomer = $secondary->customer;
            $secondary->delete();

            if ($secondaryCustomer && $secondaryCustomer->leads()->count() === 0) {
                // Keep customer record; soft-delete only if orphaned and different from primary.
                if ($secondaryCustomer->id !== $primary->customer_id) {
                    $secondaryCustomer->delete();
                }
            }

            return $primary->fresh(['customer', 'assignee', 'product', 'leadSource']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveCustomer(array $data): Customer
    {
        if (! empty($data['customer_id'])) {
            $customer = Customer::findOrFail($data['customer_id']);
            $this->fillCustomer($customer, $data);
            $customer->save();

            return $customer;
        }

        $normalized = PhoneNormalizer::normalize($data['phone'] ?? null);

        $existing = null;
        if ($normalized) {
            $existing = Customer::query()
                ->where('phone_normalized', $normalized)
                ->orWhere('phone_alt_normalized', $normalized)
                ->first();
        }

        if ($existing) {
            $this->fillCustomer($existing, $data);
            $existing->save();

            return $existing;
        }

        $customer = new Customer;
        $this->fillCustomer($customer, $data);
        $customer->save();

        return $customer;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function fillCustomer(Customer $customer, array $data): void
    {
        $first = $data['first_name'] ?? $customer->first_name;
        $last = $data['last_name'] ?? $customer->last_name;
        $phone = $data['phone'] ?? $customer->phone;
        $phoneAlt = $data['phone_alt'] ?? $customer->phone_alt;
        $consent = array_key_exists('consent_contact', $data)
            ? (bool) $data['consent_contact']
            : (array_key_exists('consent_marketing', $data) ? (bool) $data['consent_marketing'] : $customer->consent_marketing);

        $customer->fill([
            'first_name' => $first,
            'last_name' => $last,
            'full_name' => trim(($first ?? '').' '.($last ?? '')),
            'phone' => $phone,
            'phone_normalized' => PhoneNormalizer::normalize($phone),
            'phone_alt' => $phoneAlt,
            'phone_alt_normalized' => PhoneNormalizer::normalize($phoneAlt),
            'email' => $data['email'] ?? $customer->email,
            'gender' => $data['gender'] ?? $customer->gender,
            'county_id' => $data['county_id'] ?? $customer->county_id,
            'town' => $data['town'] ?? $customer->town,
            'consent_marketing' => (bool) $consent,
            'consent_at' => $consent ? ($customer->consent_at ?? now()) : $customer->consent_at,
            'notes' => $data['customer_notes'] ?? $data['notes'] ?? $customer->notes,
        ]);
    }

    private function nextReference(): string
    {
        do {
            $reference = 'LEAD-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
        } while (Lead::withTrashed()->where('reference', $reference)->exists());

        return $reference;
    }
}
