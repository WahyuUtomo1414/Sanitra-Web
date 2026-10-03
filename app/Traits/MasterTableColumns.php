<?php

namespace App\Traits;

use Illuminate\Database\Schema\Blueprint;

/**
 * Adds shared audit columns for master tables: active flag, created/updated/deleted actor,
 * timestamps, and soft delete.
 *
 * Call addAuditColumns() after declaring the table's domain columns in the migration callback.
 */
trait MasterTableColumns
{
    public function addAuditColumns(Blueprint $table): void
    {
        $table->boolean('active')->default(true);
        $table->unsignedBigInteger('created_by')->index();
        $table->unsignedBigInteger('updated_by')->nullable()->index();
        $table->unsignedBigInteger('deleted_by')->nullable()->index();
        $table->timestamps();
        $table->softDeletes();
    }
}
