<?php

namespace App\Filament\Resources\TransSalesInvoiceHds\Pages;

use App\Enums\Status\InvoiceStatus;
use App\Filament\Resources\TransSalesInvoiceHds\TransSalesInvoiceHdResource;
use App\Models\MsBankAccount;
use DomainException;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditTransSalesInvoiceHd extends EditRecord
{
    protected static string $resource = TransSalesInvoiceHdResource::class;

    protected function getHeaderActions(): array
    {
        return [ViewAction::make()];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data): Model {
            $record = $record->newQuery()->lockForUpdate()->findOrFail($record->getKey());

            if ($record->status?->code !== InvoiceStatus::Unpaid->value || $record->payments()->exists()) {
                throw new DomainException('Invoice tidak dapat diubah setelah ada pembayaran.');
            }

            if (! MsBankAccount::query()->whereKey($data['bank_account_id'])->where('active', true)->exists()) {
                throw new DomainException('Rekening bank aktif wajib dipilih.');
            }

            $record->update([
                'transaction_date' => $data['transaction_date'],
                'due_date' => $data['due_date'],
                'bank_account_id' => $data['bank_account_id'],
                'notes' => $data['notes'] ?? null,
            ]);

            return $record;
        });
    }
}
