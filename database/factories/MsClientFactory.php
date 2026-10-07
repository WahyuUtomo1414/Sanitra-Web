<?php

namespace Database\Factories;

use App\Models\MsClient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MsClient>
 */
class MsClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('CLI####'),
            'name' => fake()->company(),
            'email' => fake()->companyEmail(),
            'phone' => fake()->numerify('08##########'),
            'address' => fake()->address(),
            'active' => true,
        ];
    }
}
