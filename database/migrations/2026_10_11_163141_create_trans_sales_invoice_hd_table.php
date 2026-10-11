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
        Schema::create('trans_sales_invoice_hd', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->date('transaction_date');
            $table->foreignId('order_hd_id')->constrained('trans_sales_order_hd');
            $table->foreignId('project_hd_id')->nullable()->constrained('trans_project_hd');
            $table->foreignId('quotation_hd_id')->constrained('trans_sales_quotation_hd');
            $table->foreignId('client_id')->constrained('ms_client');
            $table->foreignId('company_id')->constrained('ms_company');
            $table->foreignId('bank_account_id')->constrained('ms_bank_account');
            $table->date('due_date')->index();
            $table->decimal('subtotal', 20, 6)->default(0);
            $table->string('vat_type', 50);
            $table->decimal('vat_amount', 20, 6)->default(0);
            $table->decimal('grand_total', 20, 6)->default(0);
            $table->text('notes')->nullable();
            $this->addAuditColumns($table);

            $table->index(['status_id', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trans_sales_invoice_hd');
    }
};
