<?php

namespace App\Observers;

use App\Enums\Status\ProjectStageStatus;
use App\Enums\StatusType;
use App\Models\MsStatus;
use App\Models\TransProjectDt;
use Illuminate\Validation\ValidationException;

class TransProjectDtObserver
{
    public function saving(TransProjectDt $transProjectDt): void
    {
        $isBlocked = MsStatus::withTrashed()
            ->whereKey($transProjectDt->status_id)
            ->where('type', StatusType::ProjectDt->value)
            ->where('code', ProjectStageStatus::Blocked->value)
            ->exists();

        if ($isBlocked && blank($transProjectDt->notes)) {
            throw ValidationException::withMessages([
                'notes' => 'Catatan wajib diisi ketika status tahap Terkendala.',
            ]);
        }
    }
}
