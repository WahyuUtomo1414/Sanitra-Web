<?php

namespace App\Services\Project;

use App\Enums\AutoCodePrefix;
use App\Enums\Status\InvoiceStatus;
use App\Enums\Status\OrderStatus;
use App\Enums\Status\ProjectStatus;
use App\Models\MsEmployee;
use App\Models\MsProjectWorkflow;
use App\Models\MsStatus;
use App\Models\TransProjectHd;
use App\Models\TransSalesOrderHd;
use App\Services\SalesOrder\CheckTermReadyToBillService;
use App\Services\Shared\GenerateCodeService;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CreateProjectFromSalesOrderService
{
    public function __construct(
        public GenerateCodeService $generateCode,
        public CreateProjectStagesService $createStages,
        public CheckTermReadyToBillService $checkTerms,
    ) {}

    /** @param array{pic_employee_id: int|string, project_name: string, start_date: string, end_date: string, notes?: string|null} $data */
    public function execute(TransSalesOrderHd $order, array $data): TransProjectHd
    {
        $data = Validator::make($data, [
            'pic_employee_id' => ['required', 'integer'],
            'project_name' => ['required', 'string', 'max:128'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string'],
        ])->validate();

        return DB::transaction(function () use ($order, $data): TransProjectHd {
            $order = TransSalesOrderHd::query()->lockForUpdate()->findOrFail($order->id);

            if ($order->status?->code !== OrderStatus::WaitingDp->value || $order->project()->exists()) {
                throw new DomainException('Project hanya dapat dibuat sekali dari Sales Order yang menunggu DP.');
            }

            if (! $this->hasPaidDownPayment($order)) {
                throw new DomainException('Invoice DP harus lunas sebelum Project dibuat.');
            }

            if (! MsEmployee::query()->whereKey($data['pic_employee_id'])
                ->where('company_id', $order->company_id)->where('active', true)->exists()) {
                throw new DomainException('PIC harus karyawan aktif dari perusahaan penerbit.');
            }

            $workflows = MsProjectWorkflow::query()
                ->where('type_id', $order->project_type_id)
                ->where('active', true)
                ->orderBy('sequence')
                ->get();

            if ($workflows->isEmpty() || (int) $workflows->sum('progress_weight') !== 100) {
                throw new DomainException('Workflow project aktif harus memiliki total bobot 100%.');
            }

            if ($workflows->contains(fn (MsProjectWorkflow $workflow): bool => Str::length($workflow->name) > 128)) {
                throw new DomainException('Nama tahap workflow tidak boleh melebihi 128 karakter.');
            }

            $project = TransProjectHd::query()->create([
                'code' => $this->generateCode->next(AutoCodePrefix::TransProjectHd),
                'transaction_date' => now()->toDateString(),
                'order_hd_id' => $order->id,
                'client_id' => $order->client_id,
                'company_id' => $order->company_id,
                'project_type_id' => $order->project_type_id,
                'pic_employee_id' => $data['pic_employee_id'],
                'project_name' => $data['project_name'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'progress_percentage' => 0,
                'notes' => $data['notes'] ?? null,
                'status_id' => MsStatus::query()
                    ->where('type', ProjectStatus::type()->value)
                    ->where('code', ProjectStatus::Open->value)
                    ->where('active', true)
                    ->firstOrFail()->id,
            ]);

            $this->createStages->execute($project, $workflows);

            $order->update([
                'status_id' => MsStatus::query()
                    ->where('type', OrderStatus::type()->value)
                    ->where('code', OrderStatus::OnProgress->value)
                    ->where('active', true)
                    ->firstOrFail()->id,
            ]);

            $this->checkTerms->execute($order, 0);

            return $project;
        });
    }

    public function hasPaidDownPayment(TransSalesOrderHd $order): bool
    {
        return $order->invoices()
            ->whereHas('status', fn (Builder $query): Builder => $query
                ->where('type', InvoiceStatus::type()->value)
                ->where('code', InvoiceStatus::Paid->value))
            ->whereHas('items.salesTerm', fn (Builder $query): Builder => $query
                ->where('progress_threshold', 0))
            ->exists();
    }
}
