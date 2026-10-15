<?php

namespace App\Filament\Resources\TransSalesInvoiceHds\RelationManagers;

use App\Enums\Status\InvoiceStatus;
use App\Enums\Status\PaymentStatus;
use App\Filament\Resources\TransSalesInvoiceHds\TransSalesInvoiceHdResource;
use App\Filament\Resources\TransSalesPayments\Schemas\TransSalesPaymentForm;
use App\Filament\Resources\TransSalesPayments\TransSalesPaymentResource;
use App\Models\TransSalesPayment;
use App\Services\Payment\ConfirmPaymentService;
use App\Services\Payment\RecordPaymentService;
use App\Services\Payment\RejectPaymentService;
use App\Support\Formatting\IndonesianMoneyFormatter;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Pembayaran';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('code')
            ->columns([
                TextColumn::make('code')->label('Nomor')->searchable(),
                TextColumn::make('transaction_date')->label('Tanggal')->date('d M Y'),
                TextColumn::make('amount')->label('Nominal')
                    ->formatStateUsing(fn (int|float|string|null $state): string => IndonesianMoneyFormatter::formatRupiah($state)),
                TextColumn::make('status.name')->label('Status')->badge()
                    ->color(fn (TransSalesPayment $record): string => PaymentStatus::tryFrom($record->status?->code ?? '')?->color() ?? 'gray'),
                TextColumn::make('reference_no')->label('Referensi')->placeholder('-'),
            ])
            ->headerActions([
                CreateAction::make()->label('Catat Pembayaran')
                    ->visible(fn (): bool => TransSalesPaymentResource::canCreate()
                        && $this->getOwnerRecord()->status?->code === InvoiceStatus::Unpaid->value
                        && ! $this->getOwnerRecord()->payments()->whereHas('status', fn (Builder $query): Builder => $query
                            ->whereIn('code', [PaymentStatus::Pending->value, PaymentStatus::Confirmed->value]))->exists())
                    ->schema(TransSalesPaymentForm::components(false))
                    ->fillForm(fn (): array => [
                        'transaction_date' => now()->toDateString(),
                        'amount' => $this->getOwnerRecord()->grand_total,
                    ])
                    ->using(fn (array $data): TransSalesPayment => app(RecordPaymentService::class)
                        ->execute($this->getOwnerRecord(), $data))
                    ->createAnother(false),
            ])
            ->recordActions([
                Action::make('viewPayment')->label('Lihat')->icon('heroicon-o-eye')
                    ->url(fn (TransSalesPayment $record): string => TransSalesPaymentResource::getUrl('view', ['record' => $record])),
                Action::make('confirm')->label('Konfirmasi')->icon('heroicon-o-check-circle')->color('success')
                    ->visible(fn (TransSalesPayment $record): bool => $record->status?->code === PaymentStatus::Pending->value
                        && Gate::allows('update', $record))
                    ->requiresConfirmation()
                    ->action(fn (TransSalesPayment $record) => app(ConfirmPaymentService::class)->execute($record))
                    ->successNotificationTitle('Pembayaran dikonfirmasi.')
                    ->successRedirectUrl(fn (): string => TransSalesInvoiceHdResource::getUrl('view', ['record' => $this->getOwnerRecord()])),
                Action::make('reject')->label('Tolak')->icon('heroicon-o-x-circle')->color('danger')
                    ->visible(fn (TransSalesPayment $record): bool => $record->status?->code === PaymentStatus::Pending->value
                        && Gate::allows('update', $record))
                    ->schema([Textarea::make('reason')->label('Alasan Penolakan')->required()->rows(4)])
                    ->action(fn (TransSalesPayment $record, array $data) => app(RejectPaymentService::class)->execute($record, $data['reason']))
                    ->successNotificationTitle('Pembayaran ditolak.')
                    ->successRedirectUrl(fn (): string => TransSalesInvoiceHdResource::getUrl('view', ['record' => $this->getOwnerRecord()])),
            ]);
    }
}
