<?php

namespace App\Models;

use App\Traits\AuditedBySoftDelete;
use Database\Factories\TransSalesOrderDtFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransSalesOrderDt extends Model
{
    /** @use HasFactory<TransSalesOrderDtFactory> */
    use AuditedBySoftDelete, HasFactory, SoftDeletes;

    protected $table = 'trans_sales_order_dt';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:6',
            'discount' => 'decimal:6',
            'total_price' => 'decimal:6',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(TransSalesOrderHd::class, 'order_hd_id');
    }

    public function quotationDetail(): BelongsTo
    {
        return $this->belongsTo(TransSalesQuotationDt::class, 'quotation_dt_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(MsItem::class, 'item_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(MsUnit::class, 'unit_id');
    }
}
