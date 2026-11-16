<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Support\PhoneNormalizer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        $first = fake()->firstName();
        $last = fake()->lastName();
        $phone = '07'.fake()->numerify('########');

        return [
            'first_name' => $first,
            'last_name' => $last,
            'full_name' => trim("{$first} {$last}"),
            'phone' => $phone,
            'phone_normalized' => PhoneNormalizer::normalize($phone),
            'phone_alt' => null,
            'phone_alt_normalized' => null,
            'email' => fake()->unique()->safeEmail(),
            'gender' => fake()->randomElement(['female', 'male', 'prefer_not_to_say']),
            'county_id' => null,
            'town' => fake()->city(),
            'consent_marketing' => true,
            'consent_at' => now(),
            'notes' => null,
        ];
    }
}
