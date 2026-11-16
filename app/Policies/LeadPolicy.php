<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;

class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('leads.view') || $user->can('leads.manage');
    }

    public function view(User $user, Lead $lead): bool
    {
        if (! $user->can('leads.view') && ! $user->can('leads.manage')) {
            return false;
        }

        return $this->withinScope($user, $lead);
    }

    public function create(User $user): bool
    {
        return $user->can('leads.create') || $user->can('leads.manage');
    }

    public function update(User $user, Lead $lead): bool
    {
        if (! $user->can('leads.update') && ! $user->can('leads.manage')) {
            return false;
        }

        return $this->withinScope($user, $lead);
    }

    public function archive(User $user, Lead $lead): bool
    {
        if (! $user->can('leads.archive') && ! $user->can('leads.manage')) {
            return false;
        }

        return $this->withinScope($user, $lead);
    }

    public function assign(User $user, Lead $lead): bool
    {
        return $user->can('leads.assign') || $user->can('leads.manage');
    }

    public function export(User $user): bool
    {
        return $user->can('leads.export') || $user->can('leads.manage');
    }

    public function merge(User $user): bool
    {
        return $user->can('leads.merge');
    }

    private function withinScope(User $user, Lead $lead): bool
    {
        if ($user->can('leads.view_all') || $user->hasRole('Super Administrator')) {
            return true;
        }

        return (int) $lead->assigned_to === (int) $user->id;
    }
}
