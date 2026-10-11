<?php

namespace App\Filament\Resources\TransSalesQuotationHds\Schemas;

use App\Enums\AutoCodePrefix;
use App\Enums\VatType;
use App\Filament\Forms\Components\MoneyInput;
use App\Filament\Resources\MsClients\Schemas\MsClientForm;
use App\Models\MsClient;
use App\Models\MsCompany;
use App\Models\MsProjectType;
use App\Services\Shared\GenerateCodeService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class TransSalesQuotationHdForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Quotation')->columnSpanFull()->columns(2)->schema([
                Select::make('project_type_id')->label('Tipe Proyek')
                    ->options(fn () => MsProjectType::query()->where('active', true)
                        ->whereNotNull('company_id')
                        ->whereHas('company', fn (Builder $query): Builder => $query->where('active', true))
                        ->orderBy('name')->pluck('name', 'id'))
                    ->searchable()->preload()->required()->live()
                    ->afterStateUpdated(function (Set $set, ?string $state): void {
                        $set('company_id', $state ? MsProjectType::query()->find($state)?->company_id : null);
                    }),
                Select::make('company_id')->label('Perusahaan Penerbit')
                    ->options(fn () => MsCompany::query()->orderBy('name')->pluck('name', 'id'))
                    ->disabled()->dehydrated(false)->placeholder('Terisi dari tipe proyek'),
                TextInput::make('code')->label('Nomor Quotation')->disabled()->dehydrated(false)
                    ->placeholder('Dibuat otomatis saat disimpan'),
                DatePicker::make('transaction_date')->label('Tanggal')->default(now())->required(),
                Select::make('client_id')->label('Klien')
                    ->relationship(
                        name: 'client',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query): Builder => $query->where('active', true),
                    )
                    ->searchable()->preload()->required()
                    ->createOptionForm(fn (Schema $schema): Schema => MsClientForm::configure($schema))
                    ->createOptionAction(fn (Action $action): Action => $action
                        ->label('Tambah Klien')->tooltip('Tambah klien baru'))
                    ->createOptionUsing(function (array $data): int {
                        $data['code'] = app(GenerateCodeService::class)->next(AutoCodePrefix::MsClient);

                        return MsClient::query()->create($data)->getKey();
                    }),
                Textarea::make('notes')->label('Catatan')->rows(3)->columnSpanFull(),
            ]),
            Section::make('Ringkasan Keuangan')->columnSpanFull()->columns(2)->schema([
                Select::make('vat_type')->label('PPN')
                    ->options(collect(VatType::cases())->mapWithKeys(
                        fn (VatType $type): array => [$type->value => $type->label()],
                    )->all())
                    ->default(VatType::None->value)->required(),
                MoneyInput::display('discount_total')->label('Total Diskon')->disabled()->dehydrated(false),
                MoneyInput::display('subtotal')->label('Subtotal Setelah Diskon')->disabled()->dehydrated(false),
                MoneyInput::display('vat_amount')->label('Nilai PPN')->disabled()->dehydrated(false),
                MoneyInput::display('grand_total')->label('Grand Total')->disabled()->dehydrated(false),
            ]),
        ]);
    }
}
