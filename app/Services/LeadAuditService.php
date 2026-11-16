<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadAudit;
use App\Models\User;
use Illuminate\Support\Arr;

class LeadAuditService
{
    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    public function recordChanges(Lead $lead, array $before, array $after, ?User $actor = null, array $fields = []): void
    {
        $watch = $fields ?: [
            'product_id',
            'lead_source_id',
            'campaign',
            'description',
            'assigned_to',
            'priority',
            'status',
            'follow_up_at',
            'consent_contact',
            'notes',
            'county_id',
            'town',
            'channel',
        ];

        foreach ($watch as $field) {
            $old = Arr::get($before, $field);
            $new = Arr::get($after, $field);

            $oldString = $this->stringify($old);
            $newString = $this->stringify($new);

            if ($oldString === $newString) {
                continue;
            }

            LeadAudit::create([
                'lead_id' => $lead->id,
                'user_id' => $actor?->id,
                'field' => $field,
                'old_value' => $oldString,
                'new_value' => $newString,
                'created_at' => now(),
            ]);
        }
    }

    private function stringify(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return (string) $value;
    }
}
