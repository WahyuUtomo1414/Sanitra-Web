<?php

namespace App\Filament\Resources\TransSalesOrderHds\Pages;

use App\Enums\Status\OrderStatus;
use App\Filament\Resources\TransSalesOrderHds\TransSalesOrderHdResource;
use App\Models\MsEmployee;
use App\Models\TransSalesOrderHd;
use App\Services\Project\CreateProjectFromSalesOrderService;
use DomainException;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CreateOrderProject extends Page
{
    protected static string $resource = TransSalesOrderHdResource::class;

    protected string $view = 'filament.resources.trans-sales-order-hds.pages.create-order-project';

    public TransSalesOrderHd $order;

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(int|string $record): void
    {
        $this->order = TransSalesOrderHdResource::getRecordRouteBindingEloquentQuery()->findOrFail($record);
        abort_unless(TransSalesOrderHdResource::canView($this->order), 403);
        abort_unless(TransSalesOrderHdResource::canEdit($this->order), 403);
        abort_unless($this->order->status?->code === OrderStatus::Open->value
            && ! $this->order->project()->exists(), 404);

        $this->form->fill(['start_date' => now()->toDateString()]);
    }

    public function getTitle(): string
    {
        return 'Buat Project · '.$this->order->code;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Section::make('Informasi Project')
                ->description('Project dibuat setelah invoice DP lunas. Tahap pekerjaan mengikuti workflow tipe project.')
                ->columnSpanFull()->columns(2)->schema([
                    TextInput::make('project_name')->label('Nama Project')->required()->maxLength(128)->columnSpanFull(),
                    Select::make('pic_employee_id')->label('PIC Karyawan')
                        ->options(fn () => MsEmployee::query()
                            ->where('company_id', $this->order->company_id)
                            ->where('active', true)
                            ->orderBy('name')
                            ->pluck('name', 'id'))
                        ->searchable()->preload()->required(),
                    DatePicker::make('start_date')->label('Tanggal Mulai')->required(),
                    DatePicker::make('end_date')->label('Tanggal Selesai')->required()->afterOrEqual('start_date'),
                    Textarea::make('notes')->label('Catatan')->nullable()->columnSpanFull(),
                ]),
        ]);
    }

    public function create(): void
    {
        $data = $this->form->getState();

        try {
            $project = app(CreateProjectFromSalesOrderService::class)->execute($this->order, $data);
        } catch (DomainException $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();

            return;
        }

        Notification::make()->title('Project '.$project->code.' berhasil dibuat.')->success()->send();
        $this->redirect(TransSalesOrderHdResource::getUrl('view', ['record' => $this->order]));
    }
}
