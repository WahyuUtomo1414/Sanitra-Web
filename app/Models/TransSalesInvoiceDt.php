<?php

namespace App\Models;

use App\Traits\AuditedBySoftDelete;
use Database\Factories\TransSalesInvoiceDtFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransSalesInvoiceDt extends Model
{
    /** @use HasFactory<TransSalesInvoiceDtFactory> */
    use AuditedBySoftDelete, HasFactory, SoftDeletes;

    protected $table = 'trans_sales_invoice_dt';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:6',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(TransSalesInvoiceHd::class, 'invoice_hd_id');
    }

    public function salesTerm(): BelongsTo
    {
        return $this->belongsTo(TransSalesTerm::class, 'sales_term_id');
    }
}
