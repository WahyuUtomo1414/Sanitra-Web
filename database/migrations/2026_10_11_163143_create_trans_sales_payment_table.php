<?php

use App\Traits\TransactionTableColumns;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use TransactionTableColumns;

    public function up(): void
    {
        Schema::create('trans_sales_payment', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->date('transaction_date');
            $table->foreignId('invoice_hd_id')->constrained('trans_sales_invoice_hd');
            $table->decimal('amount', 20, 6);
            $table->string('payment_method', 128);
            $table->text('notes')->nullable();
            $table->string('reference_no', 50)->nullable();
            $table->string('payment_proof', 255)->nullable();
            $this->addAuditColumns($table);

            $table->index(['status_id', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trans_sales_payment');
    }
};
