<?php

namespace Database\Factories;

use App\Models\MsClient;
use App\Models\MsClientDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MsClientDocument>
 */
class MsClientDocumentFactory extends Factory
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
            'name' => 'Akta Pendirian',
            'file_path' => 'client-documents/'.fake()->uuid().'.pdf',
            'active' => true,
        ];
    }
}
