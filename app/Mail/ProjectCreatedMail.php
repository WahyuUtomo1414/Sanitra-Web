<?php

namespace App\Mail;

use App\Filament\Resources\TransSalesOrderHds\TransSalesOrderHdResource;
use App\Models\TransProjectHd;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ProjectCreatedMail extends SanitraMail
{
    public function __construct(public readonly TransProjectHd $project)
    {
        parent::__construct();
        $this->project->loadMissing('order.client', 'picEmployee');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Sanitra] Project Baru Dibuat — '.$this->project->code,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.project-created',
            with: [
                'company' => $this->company,
                'logoData' => $this->logoData,
                'project' => $this->project,
                'order' => $this->project->order,
                'actionUrl' => TransSalesOrderHdResource::getUrl('view', [
                    'record' => $this->project->order_hd_id,
                ]),
            ],
        );
    }
}
