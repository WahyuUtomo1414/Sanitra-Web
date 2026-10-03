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
        Schema::create('ms_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_category_id')->constrained('ms_item_category');
            $table->string('code', 50);
            $table->string('name', 128);
            $table->string('image', 255)->nullable();
            $table->text('description')->nullable();
            $this->addAuditColumns($table);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ms_item');
    }
};
