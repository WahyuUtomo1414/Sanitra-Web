<?php

namespace App\Filament\Resources\TransSalesOrderHds\Pages;

use App\Enums\Status\TermStatus;
use App\Filament\Resources\TransSalesInvoiceHds\TransSalesInvoiceHdResource;
use App\Filament\Resources\TransSalesOrderHds\TransSalesOrderHdResource;
use App\Models\MsBankAccount;
use App\Models\TransSalesOrderHd;
use App\Services\Invoice\CreateInvoiceFromTermsService;
use App\Support\Formatting\IndonesianMoneyFormatter;
use DomainException;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class CreateOrderInvoice extends Page
{
    protected static string $resource = TransSalesOrderHdResource::class;

    protected string $view = 'filament.resources.trans-sales-order-hds.pages.create-order-invoice';

    public TransSalesOrderHd $order;

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(int|string $record): void
    {
        $this->order = TransSalesOrderHdResource::getRecordRouteBindingEloquentQuery()->findOrFail($record);
        abort_unless(TransSalesOrderHdResource::canView($this->order), 403);
        abort_unless(TransSalesOrderHdResource::canEdit($this->order), 403);
        abort_unless($this->order->terms()->whereHas('status', fn ($query) => $query
            ->where('code', TermStatus::ReadyToBill->value))->exists(), 404);

        $this->form->fill([
            'transaction_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'term_ids' => [],
        ]);
    }

    public function getTitle(): string
    {
        return 'Buat Invoice · '.$this->order->code;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Section::make('Termin Siap Tagih')
                ->columnSpanFull()
                ->schema([
                    CheckboxList::make('term_ids')
                        ->label('Termin')
                        ->options(fn (): array => $this->order->terms()
                            ->whereHas('status', fn ($query) => $query->where('code', TermStatus::ReadyToBill->value))
                            ->orderBy('sequence')->get()
                            ->mapWithKeys(fn ($term): array => [
                                $term->id => $term->term_name.' · '.IndonesianMoneyFormatter::formatRupiah($term->amount),
                            ])->all())
                        ->required()
                        ->live(),
                ]),
            Section::make('Ringkasan Tagihan')->columnSpanFull()->columns(3)->schema([
                Placeholder::make('subtotal_preview')->label('Subtotal')
                    ->content(fn (Get $get): string => IndonesianMoneyFormatter::formatRupiah($this->previewAmounts($get('term_ids'))['subtotal'])),
                Placeholder::make('vat_preview')->label('PPN')
                    ->content(fn (Get $get): string => IndonesianMoneyFormatter::formatRupiah($this->previewAmounts($get('term_ids'))['vat_amount'])),
                Placeholder::make('total_preview')->label('Total Invoice')
                    ->content(fn (Get $get): string => IndonesianMoneyFormatter::formatRupiah($this->previewAmounts($get('term_ids'))['grand_total'])),
            ]),
            Section::make('Informasi Invoice')->columnSpanFull()->columns(2)->schema([
                DatePicker::make('transaction_date')->label('Tanggal Invoice')->required()->native(false)
                    ->minDate($this->order->transaction_date?->toDateString()),
                DatePicker::make('due_date')->label('Jatuh Tempo')->required()->afterOrEqual('transaction_date')->native(false),
                Select::make('bank_account_id')->label('Rekening Tujuan')
                    ->options(fn () => MsBankAccount::query()->where('active', true)->orderBy('name')->get()
                        ->mapWithKeys(fn (MsBankAccount $account): array => [
                            $account->id => $account->name.' · '.$account->bank_name,
                        ])->all())
                    ->searchable()->preload()->required(),
                Textarea::make('notes')->label('Catatan')->nullable()->columnSpanFull(),
            ]),
        ]);
    }

    public function create(): void
    {
        $data = $this->form->getState();

        try {
            $invoice = app(CreateInvoiceFromTermsService::class)->execute($this->order, $data);
        } catch (DomainException $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();

            return;
        }

        Notification::make()->title('Invoice berhasil dibuat.')->success()->send();
        $this->redirect(TransSalesInvoiceHdResource::getUrl('view', ['record' => $invoice]));
    }

    /**
     * @param  array<int, int|string>|null  $termIds
     * @return array{subtotal: string, vat_amount: string, grand_total: string}
     */
    private function previewAmounts(?array $termIds): array
    {
        return app(CreateInvoiceFromTermsService::class)->preview($this->order, $termIds ?? []);
    }
}
