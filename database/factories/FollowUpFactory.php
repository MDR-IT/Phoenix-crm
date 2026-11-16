<?php

namespace Database\Factories;

use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FollowUp>
 */
class FollowUpFactory extends Factory
{
    protected $model = FollowUp::class;

    public function definition(): array
    {
        return [
            'lead_id' => Lead::factory(),
            'customer_id' => null,
            'assigned_to' => User::factory(),
            'created_by' => User::factory(),
            'type' => 'callback',
            'status' => FollowUp::STATUS_SCHEDULED,
            'scheduled_at' => now()->addDay(),
            'completed_at' => null,
            'notes' => fake()->optional()->sentence(),
            'outcome' => null,
        ];
    }
}
