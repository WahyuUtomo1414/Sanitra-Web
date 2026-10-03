<?php

namespace App\Traits;

use Illuminate\Database\Schema\Blueprint;

/**
 * Adds shared audit columns for transaction tables: optional status FK, created/updated/deleted
 * actor, timestamps, and soft delete.
 *
 * Pass withStatus=false for detail tables that do not track their own status.
 */
trait TransactionTableColumns
{
    public function addAuditColumns(Blueprint $table, bool $withStatus = true): void
    {
        if ($withStatus) {
            $table->foreignId('status_id')->constrained('ms_status');
        }

        $table->unsignedBigInteger('created_by')->index();
        $table->unsignedBigInteger('updated_by')->nullable()->index();
        $table->unsignedBigInteger('deleted_by')->nullable()->index();
        $table->timestamps();
        $table->softDeletes();
    }
}
