<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ms_company', function (Blueprint $table) {
            $table->string('website')->nullable()->after('phone');
        });

        DB::table('ms_company')
            ->where('id', 1)
            ->where('legal_name', 'PT Sanitra Sapta Utama')
            ->whereNull('website')
            ->update(['website' => 'https://sanitra.co.id']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ms_company', function (Blueprint $table) {
            $table->dropColumn('website');
        });
    }
};
