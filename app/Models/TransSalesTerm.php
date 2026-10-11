<?php

namespace App\Models;

use App\Traits\AuditedBySoftDelete;
use Database\Factories\TransSalesTermFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransSalesTerm extends Model
{
    /** @use HasFactory<TransSalesTermFactory> */
    use AuditedBySoftDelete, HasFactory, SoftDeletes;

    protected $table = 'trans_sales_term';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:6',
        ];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(TransSalesQuotationHd::class, 'quotation_hd_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(TransSalesOrderHd::class, 'order_hd_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(MsStatus::class, 'status_id');
    }

    public function invoiceDetails(): HasMany
    {
        return $this->hasMany(TransSalesInvoiceDt::class, 'sales_term_id');
    }
}
