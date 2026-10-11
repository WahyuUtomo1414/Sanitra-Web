<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\MsStatus;
use Illuminate\Auth\Access\HandlesAuthorization;

class MsStatusPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MsStatus');
    }

    public function view(AuthUser $authUser, MsStatus $msStatus): bool
    {
        return $authUser->can('View:MsStatus');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MsStatus');
    }

    public function update(AuthUser $authUser, MsStatus $msStatus): bool
    {
        return $authUser->can('Update:MsStatus');
    }

    public function delete(AuthUser $authUser, MsStatus $msStatus): bool
    {
        return $authUser->can('Delete:MsStatus');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:MsStatus');
    }

    public function restore(AuthUser $authUser, MsStatus $msStatus): bool
    {
        return $authUser->can('Restore:MsStatus');
    }

    public function forceDelete(AuthUser $authUser, MsStatus $msStatus): bool
    {
        return $authUser->can('ForceDelete:MsStatus');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MsStatus');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MsStatus');
    }

    public function replicate(AuthUser $authUser, MsStatus $msStatus): bool
    {
        return $authUser->can('Replicate:MsStatus');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MsStatus');
    }

}