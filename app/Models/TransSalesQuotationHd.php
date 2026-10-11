<?php

namespace App\Models;

use App\Enums\VatType;
use App\Traits\AuditedBySoftDelete;
use App\Traits\LogsMasterActivity;
use Database\Factories\TransSalesQuotationHdFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransSalesQuotationHd extends Model
{
    /** @use HasFactory<TransSalesQuotationHdFactory> */
    use AuditedBySoftDelete, HasFactory, LogsMasterActivity, SoftDeletes;

    protected $table = 'trans_sales_quotation_hd';

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

    public function items(): HasMany
    {
        return $this->hasMany(TransSalesQuotationDt::class, 'quotation_hd_id');
    }

    public function terms(): HasMany
    {
        return $this->hasMany(TransSalesTerm::class, 'quotation_hd_id');
    }

    public function order(): HasOne
    {
        return $this->hasOne(TransSalesOrderHd::class, 'quotation_hd_id');
    }
}
