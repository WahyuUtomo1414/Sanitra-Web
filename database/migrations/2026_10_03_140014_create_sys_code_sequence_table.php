<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sys_code_sequence', function (Blueprint $table) {
            $table->id();
            $table->string('prefix', 10);
            $table->unsignedSmallInteger('year')->nullable();
            $table->unsignedTinyInteger('month')->nullable();
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();

            $table->unique(['prefix', 'year', 'month'], 'uniq_prefix_period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sys_code_sequence');
    }
};
