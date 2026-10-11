<?php

namespace App\Models;

use App\Traits\AuditedBySoftDelete;
use Database\Factories\TransSalesQuotationDtFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransSalesQuotationDt extends Model
{
    /** @use HasFactory<TransSalesQuotationDtFactory> */
    use AuditedBySoftDelete, HasFactory, SoftDeletes;

    protected $table = 'trans_sales_quotation_dt';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:6',
            'discount' => 'decimal:6',
            'total_price' => 'decimal:6',
        ];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(TransSalesQuotationHd::class, 'quotation_hd_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(MsItem::class, 'item_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(MsUnit::class, 'unit_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(TransSalesOrderDt::class, 'quotation_dt_id');
    }
}
