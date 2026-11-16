<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Task $task,
        public User $assignedBy,
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
        $url = $this->task->taskable_type === \App\Models\Lead::class && $this->task->taskable_id
            ? route('leads.show', $this->task->taskable_id)
            : route('tasks.index');

        return [
            'type' => 'task_assigned',
            'task_id' => $this->task->id,
            'title' => $this->task->title,
            'assigned_by' => $this->assignedBy->name,
            'due_at' => optional($this->task->due_at)?->toDateTimeString(),
            'message' => sprintf(
                'Task "%s" was assigned to you by %s.',
                $this->task->title,
                $this->assignedBy->name
            ),
            'url' => $url,
        ];
    }
}
