<?php

namespace App\Filament\Resources\TransSalesPayments\Schemas;

use App\Enums\PaymentMethod;
use App\Enums\Status\InvoiceStatus;
use App\Enums\Status\PaymentStatus;
use App\Models\TransSalesInvoiceHd;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class TransSalesPaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(self::components());
    }

    /** @return array<int, Section> */
    public static function components(bool $includeInvoice = true): array
    {
        $fields = [
            TextInput::make('code')->label('Nomor Pembayaran')
                ->disabled()->dehydrated(false)->placeholder('Dibuat otomatis saat disimpan'),
        ];

        if ($includeInvoice) {
            $fields[] = Select::make('invoice_hd_id')->label('Invoice')
                ->relationship('invoice', 'code', modifyQueryUsing: fn (Builder $query): Builder => $query
                    ->whereHas('status', fn (Builder $statusQuery): Builder => $statusQuery
                        ->where('code', InvoiceStatus::Unpaid->value))
                    ->whereDoesntHave('payments', fn (Builder $paymentQuery): Builder => $paymentQuery
                        ->whereHas('status', fn (Builder $statusQuery): Builder => $statusQuery
                            ->whereIn('code', [PaymentStatus::Pending->value, PaymentStatus::Confirmed->value]))))
                ->searchable()->preload()->required()->live()
                ->afterStateUpdated(function (int|string|null $state, Set $set): void {
                    $invoice = TransSalesInvoiceHd::query()->find($state);
                    $set('amount', $invoice?->grand_total);
                });
        }

        $fields[] = DatePicker::make('transaction_date')->label('Tanggal Pembayaran')
            ->default(now()->toDateString())->required();
        $fields[] = TextInput::make('amount')->label('Nominal Pembayaran')
            ->numeric()->required()->minValue(0.01)
            ->helperText('Harus sama dengan total invoice; diverifikasi ulang saat disimpan.');
        $fields[] = Select::make('payment_method')->label('Metode Pembayaran')
            ->options(collect(PaymentMethod::cases())->mapWithKeys(fn (PaymentMethod $method): array => [$method->value => $method->label()])->all())
            ->required()->live();
        $fields[] = TextInput::make('reference_no')->label('Nomor Referensi')
            ->maxLength(50)
            ->visible(fn (Get $get): bool => in_array($get('payment_method'), [PaymentMethod::Transfer->value, PaymentMethod::Giro->value], true));
        $fields[] = FileUpload::make('payment_proof')->label('Bukti Pembayaran')
            ->disk('public')->directory('payments/proof')
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'application/pdf'])
            ->maxSize(5120)->openable()->downloadable();
        $fields[] = Textarea::make('notes')->label('Catatan')->nullable()->columnSpanFull();

        return [
            Section::make('Pembayaran Invoice')->columnSpanFull()->columns(2)->schema($fields),
        ];
    }
}
