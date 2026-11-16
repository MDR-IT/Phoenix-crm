<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('tasks.view');
    }

    public function view(User $user, Task $task): bool
    {
        if (! $user->can('tasks.view')) {
            return false;
        }

        return $this->withinScope($user, $task);
    }

    public function create(User $user): bool
    {
        return $user->can('tasks.create');
    }

    public function update(User $user, Task $task): bool
    {
        if (! $user->can('tasks.update')) {
            return false;
        }

        return $this->withinScope($user, $task);
    }

    public function complete(User $user, Task $task): bool
    {
        if (! $user->can('tasks.complete') && ! $user->can('tasks.update')) {
            return false;
        }

        return $this->withinScope($user, $task);
    }

    private function withinScope(User $user, Task $task): bool
    {
        if ($user->can('tasks.view_all') || $user->hasRole('Super Administrator')) {
            return true;
        }

        return (int) $task->assigned_to === (int) $user->id
            || (int) $task->created_by === (int) $user->id;
    }
}
