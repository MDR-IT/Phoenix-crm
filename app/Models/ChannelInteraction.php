<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChannelInteraction extends Model
{
    protected $fillable = [
        'lead_id',
        'customer_id',
        'webhook_event_id',
        'channel',
        'direction',
        'external_id',
        'message_type',
        'body',
        'sender_handle',
        'payload',
        'is_unsupported',
        'unsupported_reason',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'is_unsupported' => 'boolean',
            'occurred_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function webhookEvent(): BelongsTo
    {
        return $this->belongsTo(WebhookEvent::class);
    }

    public function channelLabel(): string
    {
        return config('phoenix.integration_channels.'.$this->channel, ucfirst($this->channel));
    }
}
