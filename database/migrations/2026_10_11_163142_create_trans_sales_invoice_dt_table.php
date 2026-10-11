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
        Schema::create('trans_sales_invoice_dt', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_hd_id')->constrained('trans_sales_invoice_hd');
            $table->foreignId('sales_term_id')->constrained('trans_sales_term');
            $table->unsignedInteger('sequence');
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('percentage');
            $table->decimal('amount', 20, 6);
            $this->addAuditColumns($table, withStatus: false);

            $table->unique(['invoice_hd_id', 'sales_term_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trans_sales_invoice_dt');
    }
};
