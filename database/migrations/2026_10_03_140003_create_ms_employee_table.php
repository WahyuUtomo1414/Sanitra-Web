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
        Schema::create('ms_employee', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50);
            $table->string('name', 128);
            $table->string('position', 128);
            $table->foreignId('company_id')->constrained('ms_company');
            $table->string('email', 128);
            $table->string('phone', 18);
            $this->addAuditColumns($table);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ms_employee');
    }
};
