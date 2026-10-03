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
        Schema::create('ms_item_category', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 128);
            $table->text('description')->nullable();
            $this->addAuditColumns($table);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ms_item_category');
    }
};
