<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Note;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class NoteService
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
    ) {}

    /**
     * @param  array{body: string, is_pinned?: bool}  $data
     */
    public function create(Model $notable, array $data, User $actor): Note
    {
        return DB::transaction(function () use ($notable, $data, $actor) {
            $relation = method_exists($notable, 'noteEntries')
                ? $notable->noteEntries()
                : $notable->morphMany(Note::class, 'notable');

            $note = $relation->create([
                'user_id' => $actor->id,
                'body' => $data['body'],
                'is_pinned' => (bool) ($data['is_pinned'] ?? false),
            ]);

            $this->activityLogger->log(
                $notable,
                Activity::TYPE_NOTE,
                'Note added',
                $actor,
                $note->body,
                $note,
            );

            return $note;
        });
    }

    /**
     * @param  array{body?: string, is_pinned?: bool}  $data
     */
    public function update(Note $note, array $data, User $actor): Note
    {
        $note->fill([
            'body' => $data['body'] ?? $note->body,
            'is_pinned' => array_key_exists('is_pinned', $data)
                ? (bool) $data['is_pinned']
                : $note->is_pinned,
        ]);
        $note->save();

        return $note->fresh();
    }

    public function delete(Note $note): void
    {
        $note->delete();
    }
}
