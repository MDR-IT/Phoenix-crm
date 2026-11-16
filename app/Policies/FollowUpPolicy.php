<?php

namespace App\Policies;

use App\Models\FollowUp;
use App\Models\User;

class FollowUpPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('follow_ups.view');
    }

    public function view(User $user, FollowUp $followUp): bool
    {
        if (! $user->can('follow_ups.view')) {
            return false;
        }

        return $this->withinScope($user, $followUp);
    }

    public function create(User $user): bool
    {
        return $user->can('follow_ups.create');
    }

    public function update(User $user, FollowUp $followUp): bool
    {
        if (! $user->can('follow_ups.update')) {
            return false;
        }

        return $this->withinScope($user, $followUp);
    }

    public function complete(User $user, FollowUp $followUp): bool
    {
        if (! $user->can('follow_ups.complete') && ! $user->can('follow_ups.update')) {
            return false;
        }

        return $this->withinScope($user, $followUp);
    }

    private function withinScope(User $user, FollowUp $followUp): bool
    {
        if ($user->can('follow_ups.view_all') || $user->hasRole('Super Administrator')) {
            return true;
        }

        if ((int) $followUp->assigned_to === (int) $user->id
            || (int) $followUp->created_by === (int) $user->id) {
            return true;
        }

        return (int) $followUp->lead?->assigned_to === (int) $user->id;
    }
}
