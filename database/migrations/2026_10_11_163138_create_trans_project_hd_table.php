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
        Schema::create('trans_project_hd', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->date('transaction_date');
            $table->foreignId('order_hd_id')->unique()->constrained('trans_sales_order_hd');
            $table->foreignId('client_id')->constrained('ms_client');
            $table->foreignId('company_id')->constrained('ms_company');
            $table->foreignId('project_type_id')->constrained('ms_project_type');
            $table->foreignId('pic_employee_id')->constrained('ms_employee');
            $table->string('project_name', 128);
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedTinyInteger('progress_percentage')->default(0);
            $table->text('notes')->nullable();
            $this->addAuditColumns($table);

            $table->index(['status_id', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trans_project_hd');
    }
};
