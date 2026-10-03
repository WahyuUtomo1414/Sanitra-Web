<?php

use App\Traits\MasterTableColumns;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use MasterTableColumns;

    public function up(): void
    {
        Schema::create('ms_project_workflow', function (Blueprint $table) {
            $table->id();
            $table->foreignId('type_id')->constrained('ms_project_type');
            $table->string('name', 255);
            $table->unsignedInteger('sequence');
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('progress_weight');
            $this->addAuditColumns($table);

            $table->unique(['type_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ms_project_workflow');
    }
};
