<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\Note;
use App\Models\User;

class NotePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('notes.view');
    }

    public function view(User $user, Note $note): bool
    {
        if (! $user->can('notes.view')) {
            return false;
        }

        return $this->canAccessNotable($user, $note);
    }

    public function create(User $user): bool
    {
        return $user->can('notes.create');
    }

    public function update(User $user, Note $note): bool
    {
        if (! $user->can('notes.update')) {
            return false;
        }

        if ((int) $note->user_id === (int) $user->id) {
            return true;
        }

        return $user->can('notes.delete') || $user->can('leads.view_all');
    }

    public function delete(User $user, Note $note): bool
    {
        if (! $user->can('notes.delete') && ! $user->can('notes.update')) {
            return false;
        }

        if ((int) $note->user_id === (int) $user->id && $user->can('notes.update')) {
            return true;
        }

        return $user->can('notes.delete');
    }

    private function canAccessNotable(User $user, Note $note): bool
    {
        $notable = $note->notable;

        if ($notable instanceof Lead) {
            if ($user->can('leads.view_all') || $user->hasRole('Super Administrator')) {
                return true;
            }

            return (int) $notable->assigned_to === (int) $user->id;
        }

        return true;
    }
}
