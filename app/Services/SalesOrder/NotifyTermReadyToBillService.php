<?php

namespace App\Services\SalesOrder;

use App\Models\TransSalesTerm;
use App\Models\User;
use App\Notifications\TermReadyToBillNotification;
use Throwable;

class NotifyTermReadyToBillService
{
    public function execute(TransSalesTerm $term): void
    {
        $term->loadMissing('order');

        foreach (User::role('Finance')->whereNotNull('email')->get()->unique('email') as $recipient) {
            try {
                $recipient->notify(new TermReadyToBillNotification($term));
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }
}
