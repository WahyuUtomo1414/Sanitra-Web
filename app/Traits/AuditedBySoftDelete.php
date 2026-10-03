<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

/**
 * Auto-populate created_by, updated_by, and deleted_by on model events.
 *
 * Requires:
 * - Columns created_by (NOT NULL), updated_by (nullable), deleted_by (nullable).
 * - Model composes Illuminate\Database\Eloquent\SoftDeletes.
 * - An authenticated user when persisting; call Auth::setUser() in seeders and console commands.
 *
 * @mixin Model
 */
trait AuditedBySoftDelete
{
    protected static function resolveAuditUserId(): int
    {
        $userId = Auth::id();

        if ($userId === null) {
            throw new RuntimeException(
                'AuditedBySoftDelete requires an authenticated user. '
                .'Call Auth::setUser($user) before persisting from seeders or console commands.'
            );
        }

        return $userId;
    }

    public static function bootAuditedBySoftDelete(): void
    {
        static::creating(function (Model $model): void {
            if (empty($model->created_by)) {
                $model->created_by = static::resolveAuditUserId();
            }
        });

        static::updating(function (Model $model): void {
            if (! $model->isDirty('updated_by')) {
                $model->updated_by = static::resolveAuditUserId();
            }
        });

        static::deleting(function (Model $model): void {
            if ($model->isForceDeleting()) {
                return;
            }

            $model->deleted_by = static::resolveAuditUserId();
            $model->saveQuietly();
        });

        static::restoring(function (Model $model): void {
            $model->deleted_by = null;
            $model->saveQuietly();
        });
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getCreatorNameAttribute(): ?string
    {
        return $this->createdBy?->name;
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getUpdaterNameAttribute(): ?string
    {
        return $this->updatedBy?->name;
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function getDeleterNameAttribute(): ?string
    {
        return $this->deletedBy?->name;
    }
}
