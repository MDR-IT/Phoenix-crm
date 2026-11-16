<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\Note;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Note>
 */
class NoteFactory extends Factory
{
    protected $model = Note::class;

    public function definition(): array
    {
        return [
            'notable_type' => Lead::class,
            'notable_id' => Lead::factory(),
            'user_id' => User::factory(),
            'body' => fake()->paragraph(),
            'is_pinned' => false,
        ];
    }
}
