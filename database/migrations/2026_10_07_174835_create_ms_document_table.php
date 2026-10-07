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
        Schema::create('ms_document', function (Blueprint $table) {
            $table->id();
            $table->string('ref_type', 32);
            $table->unsignedBigInteger('ref_id');
            $table->string('name', 128);
            $table->string('file_path');
            $table->text('notes')->nullable();
            $this->addAuditColumns($table);
            $table->index(['ref_type', 'ref_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ms_document');
    }
};
