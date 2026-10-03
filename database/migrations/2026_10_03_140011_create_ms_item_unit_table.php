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
        Schema::create('ms_item_unit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('ms_item');
            $table->foreignId('unit_id')->constrained('ms_unit');
            $table->boolean('is_base');
            $table->unsignedInteger('conversion');
            $this->addAuditColumns($table);

            $table->unique(['item_id', 'unit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ms_item_unit');
    }
};
