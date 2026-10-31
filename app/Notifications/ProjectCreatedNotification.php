<?php

namespace App\Notifications;

use App\Filament\Resources\TransSalesOrderHds\TransSalesOrderHdResource;
use App\Models\TransProjectHd;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjectCreatedNotification extends Notification
{
    public function __construct(public readonly TransProjectHd $project) {}

    /** @return array<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->project->loadMissing('order');

        return (new MailMessage)
            ->subject('Project Baru - '.$this->project->code)
            ->greeting('Halo,')
            ->line('Project '.$this->project->project_name.' ('.$this->project->code.') telah dibuat dari Sales Order '.$this->project->order?->code.'.')
            ->line('Silakan lihat detail Sales Order untuk memulai pekerjaan project.')
            ->action('Lihat Sales Order', TransSalesOrderHdResource::getUrl('view', ['record' => $this->project->order_hd_id]));
    }
}
