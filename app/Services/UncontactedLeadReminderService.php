<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Lead;
use App\Models\User;
use App\Notifications\UncontactedLeadReminderNotification;
use Illuminate\Support\Collection;

class UncontactedLeadReminderService
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
    ) {}

    /**
     * Remind assignees (or managers) about leads that remain uncontacted past the threshold.
     *
     * @return Collection<int, Lead>
     */
    public function remind(): Collection
    {
        $hours = (int) config('phoenix.uncontacted_reminder_hours', 24);
        $threshold = now()->subHours($hours);

        $leads = Lead::query()
            ->with(['assignee', 'customer'])
            ->whereNull('deleted_at')
            ->whereIn('status', ['new', 'assigned'])
            ->whereNull('last_contacted_at')
            ->where('created_at', '<=', $threshold)
            ->where(function ($q) use ($threshold) {
                $q->whereNull('uncontacted_reminded_at')
                    ->orWhere('uncontacted_reminded_at', '<=', $threshold);
            })
            ->get();

        foreach ($leads as $lead) {
            $recipients = $this->recipientsFor($lead);

            foreach ($recipients as $user) {
                $user->notify(new UncontactedLeadReminderNotification($lead, $hours));
            }

            $this->activityLogger->log(
                $lead,
                Activity::TYPE_REMINDER,
                'Uncontacted lead reminder',
                null,
                "Lead has not been contacted within {$hours} hours of creation.",
                null,
                ['hours' => $hours, 'recipient_ids' => $recipients->pluck('id')->all()]
            );

            $lead->uncontacted_reminded_at = now();
            $lead->save();
        }

        return $leads;
    }

    /**
     * @return Collection<int, User>
     */
    private function recipientsFor(Lead $lead): Collection
    {
        $users = collect();

        if ($lead->assignee) {
            $users->push($lead->assignee);
        } else {
            $users = $users->merge(
                User::permission('follow_ups.view_all')->where('is_active', true)->get()
            );
        }

        return $users->unique('id')->values();
    }
}
