<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\MsUnit;
use Illuminate\Auth\Access\HandlesAuthorization;

class MsUnitPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MsUnit');
    }

    public function view(AuthUser $authUser, MsUnit $msUnit): bool
    {
        return $authUser->can('View:MsUnit');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MsUnit');
    }

    public function update(AuthUser $authUser, MsUnit $msUnit): bool
    {
        return $authUser->can('Update:MsUnit');
    }

    public function delete(AuthUser $authUser, MsUnit $msUnit): bool
    {
        return $authUser->can('Delete:MsUnit');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:MsUnit');
    }

    public function restore(AuthUser $authUser, MsUnit $msUnit): bool
    {
        return $authUser->can('Restore:MsUnit');
    }

    public function forceDelete(AuthUser $authUser, MsUnit $msUnit): bool
    {
        return $authUser->can('ForceDelete:MsUnit');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MsUnit');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MsUnit');
    }

    public function replicate(AuthUser $authUser, MsUnit $msUnit): bool
    {
        return $authUser->can('Replicate:MsUnit');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MsUnit');
    }

}