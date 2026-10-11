<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\MsProjectType;
use Illuminate\Auth\Access\HandlesAuthorization;

class MsProjectTypePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MsProjectType');
    }

    public function view(AuthUser $authUser, MsProjectType $msProjectType): bool
    {
        return $authUser->can('View:MsProjectType');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MsProjectType');
    }

    public function update(AuthUser $authUser, MsProjectType $msProjectType): bool
    {
        return $authUser->can('Update:MsProjectType');
    }

    public function delete(AuthUser $authUser, MsProjectType $msProjectType): bool
    {
        return $authUser->can('Delete:MsProjectType');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:MsProjectType');
    }

    public function restore(AuthUser $authUser, MsProjectType $msProjectType): bool
    {
        return $authUser->can('Restore:MsProjectType');
    }

    public function forceDelete(AuthUser $authUser, MsProjectType $msProjectType): bool
    {
        return $authUser->can('ForceDelete:MsProjectType');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MsProjectType');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MsProjectType');
    }

    public function replicate(AuthUser $authUser, MsProjectType $msProjectType): bool
    {
        return $authUser->can('Replicate:MsProjectType');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MsProjectType');
    }

}