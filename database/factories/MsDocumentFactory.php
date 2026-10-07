<?php

namespace Database\Factories;

use App\Models\MsClient;
use App\Models\MsDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MsDocument>
 */
class MsDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ref_type' => 'client',
            'ref_id' => MsClient::factory(),
            'name' => 'Akta Pendirian',
            'file_path' => fn (array $attributes): string => 'documents/client/'.$attributes['ref_id'].'/'.fake()->uuid().'.pdf',
            'notes' => null,
            'active' => true,
        ];
    }
}
