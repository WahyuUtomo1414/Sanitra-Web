<?php

namespace App\Filament\Resources\TransSalesPayments\Pages;

use App\Filament\Resources\TransSalesPayments\TransSalesPaymentResource;
use App\Models\TransSalesInvoiceHd;
use App\Models\TransSalesPayment;
use App\Services\Payment\RecordPaymentService;
use DomainException;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateTransSalesPayment extends CreateRecord
{
    protected static string $resource = TransSalesPaymentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $invoice = TransSalesInvoiceHd::query()->findOrFail($data['invoice_hd_id']);

        try {
            return app(RecordPaymentService::class)->execute($invoice, $data);
        } catch (DomainException $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();

            $this->halt();

            return new TransSalesPayment;
        }
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->record]);
    }
}
