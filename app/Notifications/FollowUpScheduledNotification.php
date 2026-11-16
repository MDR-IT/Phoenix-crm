<?php

namespace App\Notifications;

use App\Models\FollowUp;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FollowUpScheduledNotification extends Notification
{
    use Queueable;

    public function __construct(
        public FollowUp $followUp,
        public User $scheduledBy,
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
        $lead = $this->followUp->lead;

        return [
            'type' => 'follow_up_scheduled',
            'follow_up_id' => $this->followUp->id,
            'lead_id' => $lead?->id,
            'lead_reference' => $lead?->reference,
            'scheduled_at' => $this->followUp->scheduled_at?->toDateTimeString(),
            'scheduled_by' => $this->scheduledBy->name,
            'message' => sprintf(
                '%s for %s scheduled at %s by %s.',
                $this->followUp->typeLabel(),
                $lead?->reference ?? 'lead',
                $this->followUp->scheduled_at?->format('d M Y H:i'),
                $this->scheduledBy->name
            ),
            'url' => $lead ? route('leads.show', $lead) : route('follow-ups.index'),
        ];
    }
}
