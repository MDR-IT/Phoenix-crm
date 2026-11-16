<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WebhookEvent extends Model
{
    public const STATUS_RECEIVED = 'received';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_PROCESSED = 'processed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_DUPLICATE = 'duplicate';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'provider',
        'event_type',
        'external_id',
        'idempotency_key',
        'signature_valid',
        'headers',
        'payload',
        'status',
        'attempts',
        'last_error',
        'lead_id',
        'channel_interaction_id',
        'used_sandbox',
        'processed_at',
        'next_retry_at',
    ];

    protected function casts(): array
    {
        return [
            'signature_valid' => 'boolean',
            'headers' => 'array',
            'payload' => 'array',
            'used_sandbox' => 'boolean',
            'processed_at' => 'datetime',
            'next_retry_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function channelInteraction(): BelongsTo
    {
        return $this->belongsTo(ChannelInteraction::class);
    }

    public function interaction(): HasOne
    {
        return $this->hasOne(ChannelInteraction::class);
    }

    public function statusLabel(): string
    {
        return str_replace('_', ' ', ucfirst($this->status));
    }

    public function markProcessing(): void
    {
        $this->forceFill([
            'status' => self::STATUS_PROCESSING,
            'attempts' => $this->attempts + 1,
        ])->save();
    }

    public function markProcessed(?int $leadId = null, ?int $interactionId = null): void
    {
        $this->forceFill([
            'status' => self::STATUS_PROCESSED,
            'lead_id' => $leadId ?? $this->lead_id,
            'channel_interaction_id' => $interactionId ?? $this->channel_interaction_id,
            'processed_at' => now(),
            'last_error' => null,
            'next_retry_at' => null,
        ])->save();
    }

    public function markDuplicate(?int $leadId = null): void
    {
        $this->forceFill([
            'status' => self::STATUS_DUPLICATE,
            'lead_id' => $leadId ?? $this->lead_id,
            'processed_at' => now(),
            'next_retry_at' => null,
        ])->save();
    }

    public function markFailed(string $error, ?int $retryMinutes = 5): void
    {
        $this->forceFill([
            'status' => self::STATUS_FAILED,
            'last_error' => $error,
            'next_retry_at' => $retryMinutes ? now()->addMinutes($retryMinutes) : null,
        ])->save();
    }

    public function markRejected(string $reason): void
    {
        $this->forceFill([
            'status' => self::STATUS_REJECTED,
            'last_error' => $reason,
            'processed_at' => now(),
            'next_retry_at' => null,
        ])->save();
    }
}
