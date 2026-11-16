<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Activity extends Model
{
    /** @use HasFactory<\Database\Factories\ActivityFactory> */
    use HasFactory;

    public const TYPE_CREATED = 'created';

    public const TYPE_SOURCE = 'source';

    public const TYPE_NOTE = 'note';

    public const TYPE_STATUS_CHANGE = 'status_change';

    public const TYPE_ASSIGNMENT = 'assignment';

    public const TYPE_TASK = 'task';

    public const TYPE_TASK_COMPLETED = 'task_completed';

    public const TYPE_MEETING = 'meeting';

    public const TYPE_FOLLOW_UP = 'follow_up';

    public const TYPE_FOLLOW_UP_COMPLETED = 'follow_up_completed';

    public const TYPE_REMINDER = 'reminder';

    /** Placeholder for Phase 4 channel interactions (WhatsApp / Meta / website). */
    public const TYPE_CHANNEL_INTERACTION = 'channel_interaction';

    protected $fillable = [
        'subject_type',
        'subject_id',
        'type',
        'title',
        'body',
        'user_id',
        'related_type',
        'related_id',
        'meta',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function typeLabel(): string
    {
        return config("phoenix.activity_types.{$this->type}", str_replace('_', ' ', ucfirst($this->type)));
    }
}
