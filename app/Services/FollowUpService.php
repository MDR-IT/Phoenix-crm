<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\User;
use App\Notifications\FollowUpScheduledNotification;
use Illuminate\Support\Facades\DB;

class FollowUpService
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function schedule(Lead $lead, array $data, User $actor): FollowUp
    {
        return DB::transaction(function () use ($lead, $data, $actor) {
            $followUp = FollowUp::create([
                'lead_id' => $lead->id,
                'customer_id' => $lead->customer_id,
                'assigned_to' => $data['assigned_to'] ?? $lead->assigned_to ?? $actor->id,
                'created_by' => $actor->id,
                'type' => $data['type'] ?? 'callback',
                'status' => FollowUp::STATUS_SCHEDULED,
                'scheduled_at' => $data['scheduled_at'],
                'notes' => $data['notes'] ?? null,
            ]);

            $lead->follow_up_at = $followUp->scheduled_at;
            $lead->save();

            $typeLabel = config('phoenix.follow_up_types.'.$followUp->type, $followUp->type);
            $activityType = $followUp->type === 'meeting'
                ? Activity::TYPE_MEETING
                : Activity::TYPE_FOLLOW_UP;

            $this->activityLogger->log(
                $lead,
                $activityType,
                $typeLabel.' scheduled',
                $actor,
                $followUp->notes,
                $followUp,
                [
                    'scheduled_at' => $followUp->scheduled_at->toIso8601String(),
                    'type' => $followUp->type,
                    'assigned_to' => $followUp->assigned_to,
                ]
            );

            $assignee = $followUp->assignee;
            if ($assignee && (int) $assignee->id !== (int) $actor->id) {
                $assignee->notify(new FollowUpScheduledNotification($followUp, $actor));
            }

            return $followUp->fresh(['lead.customer', 'assignee', 'creator']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(FollowUp $followUp, array $data, User $actor): FollowUp
    {
        $followUp->fill([
            'assigned_to' => array_key_exists('assigned_to', $data) ? $data['assigned_to'] : $followUp->assigned_to,
            'type' => $data['type'] ?? $followUp->type,
            'scheduled_at' => $data['scheduled_at'] ?? $followUp->scheduled_at,
            'notes' => array_key_exists('notes', $data) ? $data['notes'] : $followUp->notes,
        ]);

        if ($followUp->status === FollowUp::STATUS_MISSED && $followUp->scheduled_at->isFuture()) {
            $followUp->status = FollowUp::STATUS_SCHEDULED;
        }

        $followUp->save();

        $lead = $followUp->lead;
        if ($lead && $followUp->status === FollowUp::STATUS_SCHEDULED) {
            $lead->follow_up_at = $followUp->scheduled_at;
            $lead->save();
        }

        return $followUp->fresh(['lead.customer', 'assignee', 'creator']);
    }

    public function complete(FollowUp $followUp, User $actor, ?string $outcome = null): FollowUp
    {
        return DB::transaction(function () use ($followUp, $actor, $outcome) {
            $followUp->status = FollowUp::STATUS_COMPLETED;
            $followUp->completed_at = now();
            $followUp->outcome = $outcome;
            $followUp->save();

            $lead = $followUp->lead;
            if ($lead) {
                $lead->last_contacted_at = now();
                if ($lead->status === 'new' || $lead->status === 'assigned') {
                    $oldStatus = $lead->status;
                    $lead->status = 'contacted';
                    $lead->save();
                    $lead->statusHistories()->create([
                        'from_status' => $oldStatus,
                        'to_status' => 'contacted',
                        'user_id' => $actor->id,
                        'notes' => 'Auto-updated on follow-up completion',
                        'created_at' => now(),
                    ]);
                    $this->activityLogger->log(
                        $lead,
                        Activity::TYPE_STATUS_CHANGE,
                        'Status: '.config('phoenix.lead_statuses.'.$oldStatus, $oldStatus).' → Contacted',
                        $actor,
                        'Auto-updated on follow-up completion',
                    );
                } else {
                    $lead->save();
                }

                $this->activityLogger->log(
                    $lead,
                    Activity::TYPE_FOLLOW_UP_COMPLETED,
                    $followUp->typeLabel().' completed',
                    $actor,
                    $outcome,
                    $followUp,
                );
            }

            return $followUp->fresh(['lead.customer', 'assignee', 'creator']);
        });
    }

    public function cancel(FollowUp $followUp, ?string $outcome = null): FollowUp
    {
        $followUp->status = FollowUp::STATUS_CANCELLED;
        $followUp->outcome = $outcome;
        $followUp->save();

        return $followUp;
    }

    public function markMissed(FollowUp $followUp): FollowUp
    {
        if ($followUp->status !== FollowUp::STATUS_SCHEDULED) {
            return $followUp;
        }

        $followUp->status = FollowUp::STATUS_MISSED;
        $followUp->save();

        return $followUp;
    }
}
