<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\MsItemCategory;
use Illuminate\Auth\Access\HandlesAuthorization;

class MsItemCategoryPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MsItemCategory');
    }

    public function view(AuthUser $authUser, MsItemCategory $msItemCategory): bool
    {
        return $authUser->can('View:MsItemCategory');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MsItemCategory');
    }

    public function update(AuthUser $authUser, MsItemCategory $msItemCategory): bool
    {
        return $authUser->can('Update:MsItemCategory');
    }

    public function delete(AuthUser $authUser, MsItemCategory $msItemCategory): bool
    {
        return $authUser->can('Delete:MsItemCategory');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:MsItemCategory');
    }

    public function restore(AuthUser $authUser, MsItemCategory $msItemCategory): bool
    {
        return $authUser->can('Restore:MsItemCategory');
    }

    public function forceDelete(AuthUser $authUser, MsItemCategory $msItemCategory): bool
    {
        return $authUser->can('ForceDelete:MsItemCategory');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MsItemCategory');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MsItemCategory');
    }

    public function replicate(AuthUser $authUser, MsItemCategory $msItemCategory): bool
    {
        return $authUser->can('Replicate:MsItemCategory');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MsItemCategory');
    }

}