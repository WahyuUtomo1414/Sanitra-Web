<?php

namespace Database\Factories;

use App\Models\MsClient;
use App\Models\MsClientContact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MsClientContact>
 */
class MsClientContactFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => MsClient::factory(),
            'name' => fake()->name(),
            'position' => fake()->jobTitle(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->numerify('08##########'),
            'is_primary' => false,
            'active' => true,
        ];
    }
}
