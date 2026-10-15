<?php

namespace App\Filament\Resources\TransSalesInvoiceHds\Pages;

use App\Enums\Status\InvoiceStatus;
use App\Enums\Status\PaymentStatus;
use App\Filament\Resources\TransSalesInvoiceHds\TransSalesInvoiceHdResource;
use App\Services\Invoice\InvoicePdfDataService;
use App\Services\Invoice\VoidInvoiceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class ViewTransSalesInvoiceHd extends ViewRecord
{
    protected static string $resource = TransSalesInvoiceHdResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label('Ubah Invoice')->color('gray')->outlined()
                ->visible(fn (): bool => TransSalesInvoiceHdResource::canEdit($this->record)),
            Action::make('downloadPdf')
                ->label('Download PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->outlined()
                ->action(function () {
                    $pdfData = app(InvoicePdfDataService::class)->execute($this->record);

                    return response()->streamDownload(
                        fn () => print (Pdf::loadView('pdf.invoice', $pdfData)->setPaper('a4', 'portrait')->output()),
                        'invoice-'.str_replace('/', '-', $this->record->code).'.pdf',
                    );
                }),
            Action::make('void')->label('Batalkan Invoice')
                ->icon('heroicon-o-no-symbol')->color('danger')->outlined()
                ->visible(fn (): bool => $this->canVoid())
                ->schema([Textarea::make('reason')->label('Alasan Pembatalan')->required()->rows(4)])
                ->modalHeading('Batalkan invoice')
                ->modalDescription('Termin invoice akan kembali siap ditagih.')
                ->action(fn (array $data) => app(VoidInvoiceService::class)->execute($this->record, $data['reason']))
                ->successNotificationTitle('Invoice dibatalkan.')
                ->successRedirectUrl(fn (): string => static::getResource()::getUrl('view', ['record' => $this->record])),
        ];
    }

    private function canVoid(): bool
    {
        return Gate::allows('update', $this->record)
            && $this->record->status?->code === InvoiceStatus::Unpaid->value
            && $this->record->project_hd_id === null
            && ! $this->record->payments()->whereHas('status', fn (Builder $query): Builder => $query
                ->where('type', PaymentStatus::type()->value)
                ->whereIn('code', [PaymentStatus::Pending->value, PaymentStatus::Confirmed->value]))->exists();
    }
}
