<?php

namespace App\Notifications;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class UncontactedLeadReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Lead $lead,
        public int $hours,
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
            'type' => 'uncontacted_lead',
            'lead_id' => $this->lead->id,
            'lead_reference' => $this->lead->reference,
            'customer_name' => $this->lead->customer?->full_name,
            'hours' => $this->hours,
            'message' => sprintf(
                'Lead %s (%s) has not been contacted within %d hours.',
                $this->lead->reference,
                $this->lead->customer?->full_name ?? 'unknown',
                $this->hours
            ),
            'url' => route('leads.show', $this->lead),
        ];
    }
}
