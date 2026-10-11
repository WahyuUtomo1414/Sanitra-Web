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
        Schema::create('trans_sales_quotation_hd', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->date('transaction_date');
            $table->foreignId('client_id')->constrained('ms_client');
            $table->foreignId('project_type_id')->constrained('ms_project_type');
            $table->foreignId('company_id')->constrained('ms_company');
            $table->string('payment_type', 50);
            $table->text('notes')->nullable();
            $table->string('vat_type', 50);
            $table->decimal('vat_amount', 20, 6)->default(0);
            $table->decimal('discount_total', 20, 6)->default(0);
            $table->decimal('subtotal', 20, 6)->default(0);
            $table->decimal('grand_total', 20, 6)->default(0);
            $this->addAuditColumns($table);

            $table->index(['status_id', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trans_sales_quotation_hd');
    }
};
