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
        Schema::create('trans_sales_quotation_dt', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_hd_id')->constrained('trans_sales_quotation_hd');
            $table->foreignId('item_id')->constrained('ms_item');
            $table->foreignId('unit_id')->constrained('ms_unit');
            $table->text('notes')->nullable();
            $table->unsignedInteger('qty');
            $table->decimal('unit_price', 20, 6);
            $table->decimal('discount', 20, 6)->default(0);
            $table->decimal('total_price', 20, 6);
            $this->addAuditColumns($table, withStatus: false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trans_sales_quotation_dt');
    }
};
