<?php

namespace App\Console\Commands;

use App\Models\FollowUp;
use App\Models\Task;
use App\Notifications\FollowUpDueNotification;
use App\Services\FollowUpService;
use App\Services\UncontactedLeadReminderService;
use Illuminate\Console\Command;

class ProcessFollowUpReminders extends Command
{
    protected $signature = 'phoenix:process-follow-up-reminders';

    protected $description = 'Mark missed follow-ups, notify due items, and remind about uncontacted leads';

    public function handle(
        FollowUpService $followUpService,
        UncontactedLeadReminderService $uncontactedReminder,
    ): int {
        $missed = FollowUp::query()
            ->with(['assignee', 'lead'])
            ->where('status', FollowUp::STATUS_SCHEDULED)
            ->where('scheduled_at', '<', now())
            ->get();

        foreach ($missed as $followUp) {
            $followUpService->markMissed($followUp);
            if ($followUp->assignee) {
                $followUp->assignee->notify(new FollowUpDueNotification($followUp->fresh(), 'overdue'));
            }
        }

        $dueToday = FollowUp::query()
            ->with(['assignee', 'lead'])
            ->dueToday()
            ->get();

        foreach ($dueToday as $followUp) {
            if ($followUp->assignee) {
                $followUp->assignee->notify(new FollowUpDueNotification($followUp, 'today'));
            }
        }

        $reminded = $uncontactedReminder->remind();

        $overdueTasks = Task::query()->overdue()->count();

        $this->info(sprintf(
            'Missed follow-ups: %d; due today notified: %d; uncontacted reminders: %d; overdue tasks: %d',
            $missed->count(),
            $dueToday->count(),
            $reminded->count(),
            $overdueTasks
        ));

        return self::SUCCESS;
    }
}
