<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->sentence(),
            'taskable_type' => Lead::class,
            'taskable_id' => Lead::factory(),
            'assigned_to' => User::factory(),
            'created_by' => User::factory(),
            'priority' => 'medium',
            'status' => Task::STATUS_OPEN,
            'due_at' => now()->addDay(),
            'completed_at' => null,
        ];
    }
}
