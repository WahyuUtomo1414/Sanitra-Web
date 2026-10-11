<?php

namespace App\Models;

use App\Enums\VatType;
use App\Traits\AuditedBySoftDelete;
use Database\Factories\TransSalesInvoiceHdFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransSalesInvoiceHd extends Model
{
    /** @use HasFactory<TransSalesInvoiceHdFactory> */
    use AuditedBySoftDelete, HasFactory, SoftDeletes;

    protected $table = 'trans_sales_invoice_hd';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'due_date' => 'date',
            'vat_type' => VatType::class,
            'subtotal' => 'decimal:6',
            'vat_amount' => 'decimal:6',
            'grand_total' => 'decimal:6',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(TransSalesOrderHd::class, 'order_hd_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(TransProjectHd::class, 'project_hd_id');
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(TransSalesQuotationHd::class, 'quotation_hd_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(MsClient::class, 'client_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(MsCompany::class, 'company_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(MsBankAccount::class, 'bank_account_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(MsStatus::class, 'status_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransSalesInvoiceDt::class, 'invoice_hd_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(TransSalesPayment::class, 'invoice_hd_id');
    }
}
