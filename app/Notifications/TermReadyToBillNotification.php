<?php

namespace App\Notifications;

use App\Filament\Resources\TransSalesOrderHds\TransSalesOrderHdResource;
use App\Models\TransSalesTerm;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TermReadyToBillNotification extends Notification
{
    public function __construct(public readonly TransSalesTerm $term) {}

    /** @return array<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->term->loadMissing('order');

        return (new MailMessage)
            ->subject('Termin Siap Ditagih - '.$this->term->term_name)
            ->greeting('Halo,')
            ->line('Termin '.$this->term->term_name.' pada Sales Order '.$this->term->order?->code.' sudah siap ditagih.')
            ->line('Silakan buat invoice untuk termin ini.')
            ->action('Lihat Sales Order', TransSalesOrderHdResource::getUrl('view', ['record' => $this->term->order_hd_id]));
    }
}
