<?php

namespace Database\Seeders;

use App\Enums\AutoCodePrefix;
use App\Models\MsBankAccount;
use App\Models\MsClient;
use App\Models\MsCompany;
use App\Models\MsItemUnit;
use App\Models\MsProjectType;
use App\Models\MsStatus;
use App\Models\TransSalesInvoiceDt;
use App\Models\TransSalesInvoiceHd;
use App\Models\TransSalesOrderDt;
use App\Models\TransSalesOrderHd;
use App\Models\TransSalesPayment;
use App\Models\TransSalesQuotationDt;
use App\Models\TransSalesQuotationHd;
use App\Models\TransSalesTerm;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SanitraTransactionSampleSeeder extends SanitraSeeder
{
    public function run(): void
    {
        $previousUser = Auth::user();

        try {
            Auth::setUser(User::query()->where('email', 'marketing@gmail.com')->firstOrFail());

            DB::transaction(function (): void {
                $company = MsCompany::query()->findOrFail(1);
                $client = MsClient::query()->where('active', true)->firstOrFail();
                $projectType = MsProjectType::query()->where('active', true)->firstOrFail();
                $itemUnit = MsItemUnit::query()
                    ->where('is_base', true)
                    ->where('active', true)
                    ->whereHas('item', fn ($query) => $query->where('active', true)->where('name', 'like', 'Jasa%'))
                    ->firstOrFail();
                $bankAccount = MsBankAccount::query()->where('active', true)->firstOrFail();

                foreach (['Draft', 'Sent', 'Approved'] as $label) {
                    $quotation = $this->coded(TransSalesQuotationHd::class, AutoCodePrefix::TransSalesQuotationHd,
                        ['notes' => "Contoh UAT: Quotation {$label}"],
                        [
                            'transaction_date' => now()->toDateString(),
                            'client_id' => $client->id,
                            'project_type_id' => $projectType->id,
                            'company_id' => $company->id,
                            'payment_type' => 'DP 100%',
                            'vat_type' => 'none',
                            'vat_amount' => 0,
                            'discount_total' => 0,
                            'subtotal' => 1000000,
                            'grand_total' => 1000000,
                            'status_id' => $this->statusId('quotation', strtolower($label)),
                        ]);

                    $quotationItem = $this->uncoded(TransSalesQuotationDt::class,
                        ['quotation_hd_id' => $quotation->id, 'item_id' => $itemUnit->item_id],
                        [
                            'unit_id' => $itemUnit->unit_id,
                            'qty' => 1,
                            'unit_price' => 1000000,
                            'discount' => 0,
                            'total_price' => 1000000,
                        ]);

                    $term = $this->uncoded(TransSalesTerm::class,
                        ['quotation_hd_id' => $quotation->id, 'sequence' => 1],
                        [
                            'term_name' => 'DP',
                            'percentage' => 100,
                            'amount' => 0,
                            'progress_threshold' => 0,
                            'status_id' => $this->statusId('term', 'draft'),
                        ]);

                    if ($label !== 'Approved') {
                        continue;
                    }

                    $order = $this->coded(TransSalesOrderHd::class, AutoCodePrefix::TransSalesOrderHd,
                        ['quotation_hd_id' => $quotation->id],
                        [
                            'transaction_date' => now()->toDateString(),
                            'client_id' => $client->id,
                            'project_type_id' => $projectType->id,
                            'company_id' => $company->id,
                            'payment_type' => $quotation->payment_type,
                            'vat_type' => $quotation->vat_type,
                            'vat_amount' => $quotation->vat_amount,
                            'discount_total' => $quotation->discount_total,
                            'subtotal' => $quotation->subtotal,
                            'grand_total' => $quotation->grand_total,
                            'status_id' => $this->statusId('order', 'waiting_dp'),
                            'payment_status_id' => $this->statusId('order_payment', 'unpaid'),
                        ]);

                    $this->uncoded(TransSalesOrderDt::class,
                        ['order_hd_id' => $order->id, 'quotation_dt_id' => $quotationItem->id],
                        [
                            'item_id' => $quotationItem->item_id,
                            'unit_id' => $quotationItem->unit_id,
                            'qty' => $quotationItem->qty,
                            'unit_price' => $quotationItem->unit_price,
                            'discount' => $quotationItem->discount,
                            'total_price' => $quotationItem->total_price,
                        ]);

                    $term->update(['order_hd_id' => $order->id, 'amount' => 1000000]);

                    $invoice = $this->coded(TransSalesInvoiceHd::class, AutoCodePrefix::TransSalesInvoiceHd,
                        ['order_hd_id' => $order->id],
                        [
                            'transaction_date' => now()->toDateString(),
                            'project_hd_id' => null,
                            'quotation_hd_id' => $quotation->id,
                            'client_id' => $client->id,
                            'company_id' => $company->id,
                            'bank_account_id' => $bankAccount->id,
                            'due_date' => now()->addDays(14)->toDateString(),
                            'subtotal' => 1000000,
                            'vat_type' => 'none',
                            'vat_amount' => 0,
                            'grand_total' => 1000000,
                            'status_id' => $this->statusId('invoice', 'unpaid'),
                            'notes' => 'Contoh UAT: Invoice DP',
                        ]);

                    $this->uncoded(TransSalesInvoiceDt::class,
                        ['invoice_hd_id' => $invoice->id, 'sales_term_id' => $term->id],
                        ['sequence' => 1, 'percentage' => 100, 'amount' => 1000000]);

                    $term->update(['status_id' => $this->statusId('term', 'invoiced')]);

                    $this->coded(TransSalesPayment::class, AutoCodePrefix::TransSalesPayment,
                        ['invoice_hd_id' => $invoice->id, 'notes' => 'Contoh UAT: Payment Pending'],
                        [
                            'transaction_date' => now()->toDateString(),
                            'amount' => 1000000,
                            'payment_method' => 'transfer',
                            'status_id' => $this->statusId('payment', 'pending'),
                        ]);
                }
            });
        } finally {
            if ($previousUser !== null) {
                Auth::setUser($previousUser);
            } else {
                Auth::forgetGuards();
            }
        }
    }

    private function statusId(string $type, string $code): int
    {
        return MsStatus::query()->where('type', $type)->where('code', $code)->firstOrFail()->id;
    }
}
