<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\MsEmployee;
use Illuminate\Auth\Access\HandlesAuthorization;

class MsEmployeePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MsEmployee');
    }

    public function view(AuthUser $authUser, MsEmployee $msEmployee): bool
    {
        return $authUser->can('View:MsEmployee');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MsEmployee');
    }

    public function update(AuthUser $authUser, MsEmployee $msEmployee): bool
    {
        return $authUser->can('Update:MsEmployee');
    }

    public function delete(AuthUser $authUser, MsEmployee $msEmployee): bool
    {
        return $authUser->can('Delete:MsEmployee');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:MsEmployee');
    }

    public function restore(AuthUser $authUser, MsEmployee $msEmployee): bool
    {
        return $authUser->can('Restore:MsEmployee');
    }

    public function forceDelete(AuthUser $authUser, MsEmployee $msEmployee): bool
    {
        return $authUser->can('ForceDelete:MsEmployee');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MsEmployee');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MsEmployee');
    }

    public function replicate(AuthUser $authUser, MsEmployee $msEmployee): bool
    {
        return $authUser->can('Replicate:MsEmployee');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MsEmployee');
    }

}