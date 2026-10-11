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
        Schema::create('trans_sales_term', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_hd_id')->constrained('trans_sales_quotation_hd');
            $table->foreignId('order_hd_id')->nullable()->constrained('trans_sales_order_hd');
            $table->unsignedInteger('sequence');
            $table->string('term_name', 128);
            $table->unsignedTinyInteger('percentage');
            $table->decimal('amount', 20, 6)->default(0);
            $table->unsignedTinyInteger('progress_threshold');
            $table->text('notes')->nullable();
            $this->addAuditColumns($table);

            $table->unique(['quotation_hd_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trans_sales_term');
    }
};
