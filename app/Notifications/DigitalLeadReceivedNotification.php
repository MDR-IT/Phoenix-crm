<?php

namespace App\Notifications;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DigitalLeadReceivedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Lead $lead,
        public string $channel,
        public ?string $snippet = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'digital_lead_received',
            'lead_id' => $this->lead->id,
            'lead_reference' => $this->lead->reference,
            'channel' => $this->channel,
            'customer_name' => $this->lead->customer?->full_name,
            'message' => sprintf(
                'New %s lead %s from %s.%s',
                $this->channel,
                $this->lead->reference,
                $this->lead->customer?->full_name ?: 'unknown contact',
                $this->snippet ? ' “'.\Illuminate\Support\Str::limit($this->snippet, 80).'”' : ''
            ),
            'url' => route('leads.show', $this->lead),
        ];
    }
}
