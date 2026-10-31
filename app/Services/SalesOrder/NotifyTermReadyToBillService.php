<?php

namespace App\Services\SalesOrder;

use App\Enums\RoleName;
use App\Models\MsEmployee;
use App\Models\TransSalesTerm;
use App\Models\User;
use App\Notifications\TermReadyToBillNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Throwable;

class NotifyTermReadyToBillService
{
    public function execute(TransSalesTerm $term): void
    {
        $term->loadMissing('order');

        $recipients = User::role(RoleName::Finance->value)
            ->with('employee')
            ->get()
            ->pluck('employee')
            ->filter(fn (?MsEmployee $employee): bool => $employee !== null
                && filter_var((string) $employee->email, FILTER_VALIDATE_EMAIL) !== false)
            ->unique(fn (MsEmployee $employee): string => Str::lower($employee->email));

        if ($recipients->isEmpty()) {
            Log::warning('Notifikasi termin siap tagih tidak memiliki email employee Finance yang valid.', [
                'term_id' => $term->id,
            ]);
        }

        foreach ($recipients as $recipient) {
            try {
                Notification::route('mail', [$recipient->email => $recipient->name])
                    ->notify(new TermReadyToBillNotification($term));
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }
}
