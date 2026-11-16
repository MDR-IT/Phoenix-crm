<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    protected $model = Activity::class;

    public function definition(): array
    {
        return [
            'subject_type' => Lead::class,
            'subject_id' => Lead::factory(),
            'type' => Activity::TYPE_NOTE,
            'title' => fake()->sentence(3),
            'body' => fake()->optional()->sentence(),
            'user_id' => User::factory(),
            'related_type' => null,
            'related_id' => null,
            'meta' => null,
            'occurred_at' => now(),
        ];
    }
}
