<?php

namespace App\Filament\Resources\TransSalesOrderHds\Pages;

use App\Enums\RoleName;
use App\Enums\Status\OrderCloseReason;
use App\Enums\Status\OrderStatus;
use App\Enums\Status\TermStatus;
use App\Filament\Resources\TransSalesOrderHds\TransSalesOrderHdResource;
use App\Mail\ProjectCreatedMail;
use App\Mail\TermReadyToBillMail;
use App\Models\MsEmployee;
use App\Models\TransProjectHd;
use App\Models\User;
use App\Services\SalesOrder\CloseOrderService;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class ViewTransSalesOrderHd extends ViewRecord
{
    protected static string $resource = TransSalesOrderHdResource::class;

    public function getSubheading(): ?string
    {
        if ($this->record->status?->code === OrderStatus::Open->value
            && ! $this->record->project()->exists()) {
            return 'DP lunas.';
        }

        return null;
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label('Ubah Catatan')->color('gray')->outlined(),
            Action::make('createInvoice')
                ->label('Buat Invoice')
                ->icon('heroicon-o-document-plus')
                ->color('info')
                ->visible(fn (): bool => Gate::allows('Create:TransSalesInvoiceHd') && $this->record->terms()
                    ->whereHas('status', fn ($query) => $query->where('code', TermStatus::ReadyToBill->value))
                    ->exists())
                ->url(fn (): string => static::getResource()::getUrl('createInvoice', ['record' => $this->record])),
            Action::make('testEmail')
                ->label('Test Email')
                ->icon('heroicon-o-paper-airplane')
                ->color('gray')
                ->outlined()
                ->visible(fn (): bool => auth()->user()?->hasRole(RoleName::SuperAdmin->value))
                ->action(function (): void {
                    $so = $this->record->load(['terms', 'project', 'client', 'quotation.items.item', 'quotation.client']);
                    $term = $so->terms->first();
                    $sent = [];
                    $errors = [];

                    // Kirim TermReadyToBillNotification ke role Finance
                    if ($term) {
                        $financeRecipients = User::role(RoleName::Finance->value)
                            ->with('employee')
                            ->get()
                            ->pluck('employee')
                            ->filter(fn (?MsEmployee $e): bool => $e !== null
                                && filter_var((string) $e->email, FILTER_VALIDATE_EMAIL) !== false)
                            ->unique(fn (MsEmployee $e): string => Str::lower($e->email));

                        foreach ($financeRecipients as $recipient) {
                            try {
                                Mail::to($recipient->email, $recipient->name)
                                    ->send(new TermReadyToBillMail($term));
                                $sent[] = 'Termin → '.$recipient->email;
                            } catch (Throwable $exception) {
                                report($exception);
                                $errors[] = $exception->getMessage();
                            }
                        }

                        if ($financeRecipients->isEmpty()) {
                            $errors[] = 'Tidak ada employee Finance dengan email valid.';
                        }
                    } else {
                        $errors[] = 'SO ini tidak memiliki termin.';
                    }

                    // Kirim ProjectCreatedNotification ke role Teknis
                    // Buat TransProjectHd sementara di-memory (tidak disimpan ke DB)
                    $fakeProject = new TransProjectHd([
                        'code' => 'TEST-'.$so->code,
                        'project_name' => '[TEST] Project dari '.$so->code,
                        'order_hd_id' => $so->id,
                        'pic_employee_id' => null,
                    ]);
                    $fakeProject->setRelation('order', $so);
                    $fakeProject->setRelation('picEmployee', null);
                    $fakeProject->created_at = now();

                    $teknisRecipients = User::role(RoleName::Teknis->value)
                        ->with('employee')
                        ->get()
                        ->pluck('employee')
                        ->filter(fn (?MsEmployee $e): bool => $e !== null
                            && filter_var((string) $e->email, FILTER_VALIDATE_EMAIL) !== false)
                        ->unique(fn (MsEmployee $e): string => Str::lower($e->email));

                    foreach ($teknisRecipients as $recipient) {
                        try {
                            Mail::to($recipient->email, $recipient->name)
                                ->send(new ProjectCreatedMail($fakeProject));
                            $sent[] = 'Project → '.$recipient->email;
                        } catch (Throwable $exception) {
                            report($exception);
                            $errors[] = $exception->getMessage();
                        }
                    }

                    if ($teknisRecipients->isEmpty()) {
                        $errors[] = 'Tidak ada employee Teknis dengan email valid.';
                    }

                    if (! empty($errors)) {
                        Notification::make()
                            ->title('Test email gagal sebagian')
                            ->body(implode("\n", $errors))
                            ->warning()
                            ->send();
                    }

                    if (! empty($sent)) {
                        Notification::make()
                            ->title('Test email terkirim')
                            ->body(implode("\n", $sent))
                            ->success()
                            ->send();
                    }
                }),
            Action::make('closeOrder')
                ->label('Tutup SO')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->outlined()
                ->visible(fn (): bool => $this->canClose())
                ->schema([
                    Select::make('close_reason')
                        ->label('Alasan Penutupan')
                        ->options(collect(OrderCloseReason::cases())->mapWithKeys(fn (OrderCloseReason $r): array => [$r->value => $r->label()]))
                        ->required(),
                    Textarea::make('close_notes')->label('Catatan Penutupan')->nullable()->rows(3),
                ])
                ->modalHeading('Tutup Sales Order')
                ->modalDescription('Pastikan tidak ada invoice yang belum dibayar. Project yang sedang berjalan akan dibatalkan.')
                ->action(function (array $data): void {
                    abort_unless($this->canClose(), 403);

                    try {
                        app(CloseOrderService::class)->execute($this->record, $data['close_reason'], $data['close_notes'] ?? null);
                    } catch (DomainException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();

                        return;
                    }
                    Notification::make()->title('Sales Order berhasil ditutup.')->success()->send();
                    $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));
                }),
        ];
    }

    private function canClose(): bool
    {
        return Gate::allows('Close:TransSalesOrderHd')
            && Gate::allows('update', $this->record)
            && in_array($this->record->status?->code, [OrderStatus::Draft->value, OrderStatus::Open->value]);
    }
}
