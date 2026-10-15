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
                    && Gate::allows('update', $this->record))
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi pembayaran?')
                ->modalDescription('Invoice dan termin terkait akan ditandai lunas.')
                ->action(fn () => app(ConfirmPaymentService::class)->execute($this->record))
                ->successNotificationTitle('Pembayaran dikonfirmasi.')
                ->successRedirectUrl(fn (): string => static::getResource()::getUrl('view', ['record' => $this->record])),
            Action::make('reject')->label('Tolak Pembayaran')
                ->icon('heroicon-o-x-circle')->color('danger')->outlined()
                ->visible(fn (): bool => $this->record->status?->code === PaymentStatus::Pending->value
                    && Gate::allows('update', $this->record))
                ->schema([Textarea::make('reason')->label('Alasan Penolakan')->required()->rows(4)])
                ->modalHeading('Tolak pembayaran')
                ->action(fn (array $data) => app(RejectPaymentService::class)->execute($this->record, $data['reason']))
                ->successNotificationTitle('Pembayaran ditolak.')
                ->successRedirectUrl(fn (): string => static::getResource()::getUrl('view', ['record' => $this->record])),
        ];
    }
}
