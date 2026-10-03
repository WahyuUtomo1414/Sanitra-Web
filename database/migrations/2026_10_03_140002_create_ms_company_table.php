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
        Schema::create('ms_company', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50);
            $table->string('name', 128);
            $table->string('legal_name', 128);
            $table->string('email', 128);
            $table->string('phone', 18);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable()->index();
            $table->unsignedBigInteger('pic_employee_id')->nullable()->index();
            $this->addAuditColumns($table);

            $table->foreign('parent_id')->references('id')->on('ms_company');
            // FK pic_employee_id ditunda — ditambah di migration setelah ms_employee dibuat.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ms_company');
    }
};
