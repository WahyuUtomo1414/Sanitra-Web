<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\MsItem;
use Illuminate\Auth\Access\HandlesAuthorization;

class MsItemPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MsItem');
    }

    public function view(AuthUser $authUser, MsItem $msItem): bool
    {
        return $authUser->can('View:MsItem');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MsItem');
    }

    public function update(AuthUser $authUser, MsItem $msItem): bool
    {
        return $authUser->can('Update:MsItem');
    }

    public function delete(AuthUser $authUser, MsItem $msItem): bool
    {
        return $authUser->can('Delete:MsItem');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:MsItem');
    }

    public function restore(AuthUser $authUser, MsItem $msItem): bool
    {
        return $authUser->can('Restore:MsItem');
    }

    public function forceDelete(AuthUser $authUser, MsItem $msItem): bool
    {
        return $authUser->can('ForceDelete:MsItem');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MsItem');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MsItem');
    }

    public function replicate(AuthUser $authUser, MsItem $msItem): bool
    {
        return $authUser->can('Replicate:MsItem');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MsItem');
    }

}