<?php

namespace App\Services\Item;

use App\Models\MsItem;
use App\Models\MsItemUnit;
use App\Models\MsUnit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManageItemUnitsService
{
    public function add(MsItem $item, MsUnit $unit, int $conversion = 1): MsItemUnit
    {
        return DB::transaction(function () use ($item, $unit, $conversion): MsItemUnit {
            $item = MsItem::query()->whereKey($item->getKey())->lockForUpdate()->firstOrFail();

            if (! $unit->active) {
                throw ValidationException::withMessages(['unit_id' => 'Unit yang dipilih harus aktif.']);
            }

            if ($conversion < 1) {
                throw ValidationException::withMessages(['conversion' => 'Konversi minimal 1.']);
            }

            $existing = $item->units()->withTrashed()->where('unit_id', $unit->getKey())->first();

            if ($existing !== null && ! $existing->trashed()) {
                throw ValidationException::withMessages(['unit_id' => 'Unit ini sudah terdaftar pada item.']);
            }

            $isBase = ! $item->units()->where('is_base', true)->exists();
            $attributes = [
                'is_base' => $isBase,
                'conversion' => $isBase ? 1 : $conversion,
                'active' => true,
            ];

            if ($existing !== null) {
                $existing->restore();
                $existing->update($attributes);

                return $existing;
            }

            return $item->units()->create(['unit_id' => $unit->getKey(), ...$attributes]);
        });
    }

    public function updateConversion(MsItemUnit $itemUnit, int $conversion): MsItemUnit
    {
        return DB::transaction(function () use ($itemUnit, $conversion): MsItemUnit {
            $item = MsItem::query()->whereKey($itemUnit->item_id)->lockForUpdate()->firstOrFail();
            $itemUnit = $item->units()->whereKey($itemUnit->getKey())->firstOrFail();

            if ($conversion < 1 || ($itemUnit->is_base && $conversion !== 1)) {
                throw ValidationException::withMessages(['conversion' => 'Konversi unit dasar harus 1; unit lain minimal 1.']);
            }

            $itemUnit->update(['conversion' => $conversion]);

            return $itemUnit;
        });
    }

    public function remove(MsItemUnit $itemUnit): bool
    {
        return DB::transaction(function () use ($itemUnit): bool {
            $item = MsItem::query()->whereKey($itemUnit->item_id)->lockForUpdate()->firstOrFail();
            $itemUnit = $item->units()->whereKey($itemUnit->getKey())->firstOrFail();

            if ($itemUnit->is_base) {
                throw ValidationException::withMessages(['unit_id' => 'Unit dasar tidak dapat dihapus.']);
            }

            return (bool) $itemUnit->delete();
        });
    }
}
