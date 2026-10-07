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
        Schema::create('ms_client_contact', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('ms_client');
            $table->string('name', 128);
            $table->string('position', 128)->nullable();
            $table->string('email', 128)->nullable();
            $table->string('phone', 18)->nullable();
            $table->boolean('is_primary')->default(false);
            $this->addAuditColumns($table);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ms_client_contact');
    }
};
