<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ms_company', function (Blueprint $table) {
            $table->text('address')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('logo')->nullable();
            $table->unique('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ms_company', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn(['address', 'postal_code', 'logo']);
        });
    }
};
