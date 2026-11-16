<?php

namespace App\Notifications;

use App\Models\FollowUp;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FollowUpDueNotification extends Notification
{
    use Queueable;

    public function __construct(
        public FollowUp $followUp,
        public string $bucket = 'due',
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
        $label = $this->bucket === 'overdue' ? 'overdue' : 'due today';

        return [
            'type' => 'follow_up_due',
            'bucket' => $this->bucket,
            'follow_up_id' => $this->followUp->id,
            'lead_id' => $lead?->id,
            'lead_reference' => $lead?->reference,
            'scheduled_at' => $this->followUp->scheduled_at?->toDateTimeString(),
            'message' => sprintf(
                'Follow-up for %s is %s (%s).',
                $lead?->reference ?? 'a lead',
                $label,
                $this->followUp->scheduled_at?->format('d M Y H:i')
            ),
            'url' => route('follow-ups.index', ['bucket' => $this->bucket === 'overdue' ? 'overdue' : 'today']),
        ];
    }
}
