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
        Schema::create('trans_project_dt', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_hd_id')->constrained('trans_project_hd');
            $table->foreignId('project_workflow_id')->constrained('ms_project_workflow');
            $table->unsignedInteger('sequence');
            $table->string('work_name', 128);
            $table->unsignedTinyInteger('progress_weight');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('editable')->default(true);
            $this->addAuditColumns($table);

            $table->unique(['project_hd_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trans_project_dt');
    }
};
