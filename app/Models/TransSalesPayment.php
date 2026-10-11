<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Traits\AuditedBySoftDelete;
use Database\Factories\TransSalesPaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransSalesPayment extends Model
{
    /** @use HasFactory<TransSalesPaymentFactory> */
    use AuditedBySoftDelete, HasFactory, SoftDeletes;

    protected $table = 'trans_sales_payment';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'amount' => 'decimal:6',
            'payment_method' => PaymentMethod::class,
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(TransSalesInvoiceHd::class, 'invoice_hd_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(MsStatus::class, 'status_id');
    }
}
