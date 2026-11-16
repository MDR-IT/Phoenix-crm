<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\TaskCompletedNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TaskService
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(?Model $taskable, array $data, User $actor): Task
    {
        return DB::transaction(function () use ($taskable, $data, $actor) {
            $task = Task::create([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'taskable_type' => $taskable?->getMorphClass(),
                'taskable_id' => $taskable?->getKey(),
                'assigned_to' => $data['assigned_to'] ?? $actor->id,
                'created_by' => $actor->id,
                'priority' => $data['priority'] ?? 'medium',
                'status' => Task::STATUS_OPEN,
                'due_at' => $data['due_at'] ?? null,
            ]);

            if ($taskable) {
                $this->activityLogger->log(
                    $taskable,
                    Activity::TYPE_TASK,
                    'Task created: '.$task->title,
                    $actor,
                    $task->description,
                    $task,
                    [
                        'due_at' => optional($task->due_at)?->toIso8601String(),
                        'assigned_to' => $task->assigned_to,
                        'priority' => $task->priority,
                    ]
                );
            }

            $assignee = $task->assignee;
            if ($assignee && (int) $assignee->id !== (int) $actor->id) {
                $assignee->notify(new TaskAssignedNotification($task, $actor));
            }

            return $task->fresh(['assignee', 'creator', 'taskable']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Task $task, array $data, User $actor): Task
    {
        $previousAssignee = $task->assigned_to;

        $task->fill([
            'title' => $data['title'] ?? $task->title,
            'description' => array_key_exists('description', $data) ? $data['description'] : $task->description,
            'assigned_to' => array_key_exists('assigned_to', $data) ? $data['assigned_to'] : $task->assigned_to,
            'priority' => $data['priority'] ?? $task->priority,
            'due_at' => array_key_exists('due_at', $data) ? $data['due_at'] : $task->due_at,
        ]);
        $task->save();

        if ($task->assigned_to && (int) $task->assigned_to !== (int) $previousAssignee) {
            $assignee = $task->assignee;
            if ($assignee && (int) $assignee->id !== (int) $actor->id) {
                $assignee->notify(new TaskAssignedNotification($task, $actor));
            }
        }

        return $task->fresh(['assignee', 'creator', 'taskable']);
    }

    public function complete(Task $task, User $actor): Task
    {
        return DB::transaction(function () use ($task, $actor) {
            if ($task->status === Task::STATUS_COMPLETED) {
                return $task;
            }

            $task->status = Task::STATUS_COMPLETED;
            $task->completed_at = now();
            $task->save();

            if ($task->taskable) {
                $this->activityLogger->log(
                    $task->taskable,
                    Activity::TYPE_TASK_COMPLETED,
                    'Task completed: '.$task->title,
                    $actor,
                    null,
                    $task,
                );
            }

            if ($task->creator && (int) $task->creator->id !== (int) $actor->id) {
                $task->creator->notify(new TaskCompletedNotification($task, $actor));
            }

            return $task->fresh(['assignee', 'creator', 'taskable']);
        });
    }

    public function cancel(Task $task): Task
    {
        $task->status = Task::STATUS_CANCELLED;
        $task->save();

        return $task;
    }
}
