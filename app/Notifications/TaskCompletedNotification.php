<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskCompletedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Task $task,
        public User $completedBy,
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
            'type' => 'task_completed',
            'task_id' => $this->task->id,
            'title' => $this->task->title,
            'completed_by' => $this->completedBy->name,
            'message' => sprintf(
                'Task "%s" was completed by %s.',
                $this->task->title,
                $this->completedBy->name
            ),
            'url' => route('tasks.index'),
        ];
    }
}
