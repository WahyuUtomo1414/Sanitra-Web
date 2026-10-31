<?php

namespace App\Mail;

use App\Filament\Resources\TransSalesOrderHds\TransSalesOrderHdResource;
use App\Models\TransSalesTerm;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class TermReadyToBillMail extends SanitraMail
{
    public function __construct(public readonly TransSalesTerm $term)
    {
        parent::__construct();
        $this->term->loadMissing('order.client');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Sanitra] Termin Siap Ditagih — '.$this->term->term_name.' / '.$this->term->order?->code,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.term-ready-to-bill',
            with: [
                'company' => $this->company,
                'term' => $this->term,
                'order' => $this->term->order,
                'actionUrl' => TransSalesOrderHdResource::getUrl('view', [
                    'record' => $this->term->order_hd_id,
                ]),
            ],
        );
    }
}
