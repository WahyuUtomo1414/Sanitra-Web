<?php

namespace App\Notifications;

use App\Mail\TermReadyToBillMail;
use App\Models\TransSalesTerm;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;

class TermReadyToBillNotification extends Notification
{
    public function __construct(public readonly TransSalesTerm $term) {}

    /** @return array<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): Mailable
    {
        return new TermReadyToBillMail($this->term);
    }
}
