<?php

namespace App\Filament\Resources\TransSalesQuotationHds\Pages;

use App\Enums\Status\QuotationStatus;
use App\Filament\Resources\TransSalesQuotationHds\TransSalesQuotationHdResource;
use App\Models\MsProjectType;
use App\Services\Quotation\QuotationPdfDataService;
use App\Services\Quotation\RecalculateQuotationTotalsService;
use Barryvdh\DomPDF\Facade\Pdf;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;

class EditTransSalesQuotationHd extends EditRecord
{
    protected static string $resource = TransSalesQuotationHdResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            Action::make('generatePdf')->label('Generate PDF')->icon('heroicon-o-document-arrow-down')
                ->visible(fn (): bool => $this->record->status?->code === QuotationStatus::Draft->value)
                ->action(fn () => Pdf::loadView(
                    'pdf.quotation',
                    app(QuotationPdfDataService::class)->execute($this->record),
                )->download(str_replace('/', '-', $this->record->code).'.pdf')),
        ];
    }

    #[On('quotation-totals-updated')]
    public function refreshQuotationTotals(): void
    {
        $this->record->refresh();
        $this->refreshFormData(['discount_total', 'subtotal', 'vat_amount', 'grand_total']);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data): Model {
            $record = $record->newQuery()->lockForUpdate()->findOrFail($record->getKey());

            if ($record->status?->code !== QuotationStatus::Draft->value) {
                throw new DomainException('Quotation hanya dapat diedit saat Draft.');
            }

            $data['company_id'] = MsProjectType::query()->findOrFail($data['project_type_id'])->company_id;
            $record->update($data);

            return app(RecalculateQuotationTotalsService::class)->execute($record);
        });
    }
}
