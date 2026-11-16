<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        return [
            'reference' => 'LEAD-'.now()->format('ymd').'-'.Str::upper(Str::random(5)),
            'customer_id' => Customer::factory(),
            'product_id' => null,
            'lead_source_id' => null,
            'campaign' => fake()->optional()->word(),
            'description' => fake()->optional()->sentence(),
            'assigned_to' => null,
            'priority' => 'medium',
            'status' => 'new',
            'follow_up_at' => null,
            'consent_contact' => true,
            'notes' => null,
            'county_id' => null,
            'town' => fake()->optional()->city(),
            'created_by' => null,
            'channel' => 'manual',
        ];
    }
}
