<?php

namespace App\Mail;

use App\Filament\Resources\TransSalesOrderHds\TransSalesOrderHdResource;
use App\Models\TransProjectHd;
use App\Services\Quotation\QuotationPdfDataService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ProjectCreatedMail extends SanitraMail
{
    public function __construct(public readonly TransProjectHd $project)
    {
        parent::__construct();
        $this->project->loadMissing('order.client', 'order.quotation.items.item', 'order.quotation.client', 'picEmployee');
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
                'project' => $this->project,
                'order' => $this->project->order,
                'actionUrl' => TransSalesOrderHdResource::getUrl('view', [
                    'record' => $this->project->order_hd_id,
                ]),
            ],
        );
    }

    /** @return array<Attachment> */
    public function attachments(): array
    {
        $quotation = $this->project->order?->quotation;

        if (! $quotation) {
            return [];
        }

        $data = app(QuotationPdfDataService::class)->execute($quotation);
        $pdf = Pdf::loadView('pdf.quotation', $data);

        return [
            Attachment::fromData(
                fn () => $pdf->output(),
                'Penawaran-'.$quotation->code.'.pdf',
            )->withMime('application/pdf'),
        ];
    }
}
