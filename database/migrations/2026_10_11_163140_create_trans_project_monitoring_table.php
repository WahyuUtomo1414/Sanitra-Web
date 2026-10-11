<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trans_project_monitoring', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_hd_id')->constrained('trans_project_hd');
            $table->foreignId('project_dt_id')->constrained('trans_project_dt');
            $table->foreignId('status_before_id')->constrained('ms_status');
            $table->foreignId('status_after_id')->constrained('ms_status');
            $table->string('log_type', 50);
            $table->unsignedTinyInteger('progress_before');
            $table->unsignedTinyInteger('progress_after');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['project_hd_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trans_project_monitoring');
    }
};
