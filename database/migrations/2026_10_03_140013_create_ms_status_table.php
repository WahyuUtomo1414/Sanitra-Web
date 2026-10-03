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
        Schema::create('ms_status', function (Blueprint $table) {
            $table->id();
            $table->string('type', 128);
            $table->string('code', 128);
            $table->string('name', 128);
            $table->text('description')->nullable();
            $this->addAuditColumns($table);

            $table->unique(['type', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ms_status');
    }
};
