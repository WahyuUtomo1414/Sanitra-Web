<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trans_sales_order_hd', function (Blueprint $table): void {
            $table->string('close_reason', 32)->nullable()->after('notes');
            $table->text('close_notes')->nullable()->after('close_reason');
            $table->dropForeign(['payment_status_id']);
            $table->dropColumn('payment_status_id');
        });
    }

    public function down(): void
    {
        Schema::table('trans_sales_order_hd', function (Blueprint $table): void {
            $table->foreignId('payment_status_id')->nullable()->constrained('ms_status');
            $table->dropColumn(['close_reason', 'close_notes']);
        });
    }
};
