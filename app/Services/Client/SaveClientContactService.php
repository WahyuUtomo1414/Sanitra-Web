<?php

namespace App\Services\Client;

use App\Models\MsClient;
use App\Models\MsClientContact;
use Illuminate\Support\Facades\DB;

class SaveClientContactService
{
    /** @param array{name: string, position?: ?string, email?: ?string, phone?: ?string, is_primary?: bool} $data */
    public function create(MsClient $client, array $data): MsClientContact
    {
        return DB::transaction(function () use ($client, $data): MsClientContact {
            MsClient::query()->whereKey($client->getKey())->lockForUpdate()->firstOrFail();

            if ($data['is_primary'] ?? false) {
                $this->clearPrimaryContact($client);
            }

            return $client->contacts()->create($data);
        });
    }

    /** @param array{name: string, position?: ?string, email?: ?string, phone?: ?string, is_primary?: bool} $data */
    public function update(MsClientContact $contact, array $data): MsClientContact
    {
        return DB::transaction(function () use ($contact, $data): MsClientContact {
            $client = MsClient::query()->whereKey($contact->client_id)->lockForUpdate()->firstOrFail();

            if ($data['is_primary'] ?? false) {
                $this->clearPrimaryContact($client, $contact->getKey());
            }

            $contact->update($data);

            return $contact;
        });
    }

    private function clearPrimaryContact(MsClient $client, ?int $exceptId = null): void
    {
        $client->contacts()
            ->where('is_primary', true)
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->get()
            ->each(fn (MsClientContact $contact) => $contact->update(['is_primary' => false]));
    }
}
