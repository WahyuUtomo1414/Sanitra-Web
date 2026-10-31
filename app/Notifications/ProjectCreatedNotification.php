<?php

namespace App\Notifications;

use App\Mail\ProjectCreatedMail;
use App\Models\TransProjectHd;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;

class ProjectCreatedNotification extends Notification
{
    public function __construct(public readonly TransProjectHd $project) {}

    /** @return array<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): Mailable
    {
        return new ProjectCreatedMail($this->project);
    }
}
