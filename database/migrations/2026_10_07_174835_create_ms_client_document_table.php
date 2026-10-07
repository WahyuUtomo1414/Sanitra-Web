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
        Schema::create('ms_client_document', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('ms_client');
            $table->string('name', 128);
            $table->string('file_path');
            $this->addAuditColumns($table);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ms_client_document');
    }
};
