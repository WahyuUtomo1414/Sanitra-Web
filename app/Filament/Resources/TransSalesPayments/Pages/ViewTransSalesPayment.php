<?php

namespace App\Filament\Resources\TransSalesPayments\Pages;

use App\Enums\Status\PaymentStatus;
use App\Filament\Resources\TransSalesPayments\TransSalesPaymentResource;
use App\Services\Payment\ConfirmPaymentService;
use App\Services\Payment\RejectPaymentService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Gate;

class ViewTransSalesPayment extends ViewRecord
{
    protected static string $resource = TransSalesPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('confirm')->label('Konfirmasi Pembayaran')
                ->icon('heroicon-o-check-circle')->color('success')
                ->visible(fn (): bool => $this->record->status?->code === PaymentStatus::Pending->value
                    && Gate::allows('Confirm:TransSalesPayment'))
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi pembayaran?')
                ->modalDescription('Invoice dan termin terkait akan ditandai lunas.')
                ->action(function () {
                    Gate::authorize('Confirm:TransSalesPayment');

                    return app(ConfirmPaymentService::class)->execute($this->record);
                })
                ->successNotificationTitle('Pembayaran dikonfirmasi.')
                ->successRedirectUrl(fn (): string => static::getResource()::getUrl('view', ['record' => $this->record])),
            Action::make('reject')->label('Tolak Pembayaran')
                ->icon('heroicon-o-x-circle')->color('danger')->outlined()
                ->visible(fn (): bool => $this->record->status?->code === PaymentStatus::Pending->value
                    && Gate::allows('Reject:TransSalesPayment'))
                ->schema([Textarea::make('reason')->label('Alasan Penolakan')->required()->rows(4)])
                ->modalHeading('Tolak pembayaran')
                ->action(function (array $data) {
                    Gate::authorize('Reject:TransSalesPayment');

                    return app(RejectPaymentService::class)->execute($this->record, $data['reason']);
                })
                ->successNotificationTitle('Pembayaran ditolak.')
                ->successRedirectUrl(fn (): string => static::getResource()::getUrl('view', ['record' => $this->record])),
        ];
    }
}
