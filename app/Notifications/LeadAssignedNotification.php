<?php

namespace App\Notifications;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LeadAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Lead $lead,
        public User $assignedBy,
        public string $method = 'manual',
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
            'type' => 'lead_assigned',
            'lead_id' => $this->lead->id,
            'lead_reference' => $this->lead->reference,
            'customer_name' => $this->lead->customer?->full_name,
            'assigned_by' => $this->assignedBy->name,
            'method' => $this->method,
            'message' => sprintf(
                'Lead %s was assigned to you by %s (%s).',
                $this->lead->reference,
                $this->assignedBy->name,
                str_replace('_', ' ', $this->method)
            ),
            'url' => route('leads.show', $this->lead),
        ];
    }
}
