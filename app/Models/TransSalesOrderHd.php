<?php

namespace App\Models;

use App\Enums\VatType;
use App\Traits\AuditedBySoftDelete;
use Database\Factories\TransSalesOrderHdFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransSalesOrderHd extends Model
{
    /** @use HasFactory<TransSalesOrderHdFactory> */
    use AuditedBySoftDelete, HasFactory, SoftDeletes;

    protected $table = 'trans_sales_order_hd';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'vat_type' => VatType::class,
            'vat_amount' => 'decimal:6',
            'discount_total' => 'decimal:6',
            'subtotal' => 'decimal:6',
            'grand_total' => 'decimal:6',
        ];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(TransSalesQuotationHd::class, 'quotation_hd_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(MsClient::class, 'client_id');
    }

    public function projectType(): BelongsTo
    {
        return $this->belongsTo(MsProjectType::class, 'project_type_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(MsCompany::class, 'company_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(MsStatus::class, 'status_id');
    }

    public function paymentStatus(): BelongsTo
    {
        return $this->belongsTo(MsStatus::class, 'payment_status_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransSalesOrderDt::class, 'order_hd_id');
    }

    public function terms(): HasMany
    {
        return $this->hasMany(TransSalesTerm::class, 'order_hd_id');
    }

    public function project(): HasOne
    {
        return $this->hasOne(TransProjectHd::class, 'order_hd_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(TransSalesInvoiceHd::class, 'order_hd_id');
    }
}
