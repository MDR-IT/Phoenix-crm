<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    /**
     * @param  array<string, mixed>|null  $meta
     */
    public function log(
        Model $subject,
        string $type,
        string $title,
        ?User $actor = null,
        ?string $body = null,
        ?Model $related = null,
        ?array $meta = null,
        ?\DateTimeInterface $occurredAt = null,
    ): Activity {
        return Activity::create([
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'user_id' => $actor?->id,
            'related_type' => $related?->getMorphClass(),
            'related_id' => $related?->getKey(),
            'meta' => $meta,
            'occurred_at' => $occurredAt ?? now(),
        ]);
    }
}
