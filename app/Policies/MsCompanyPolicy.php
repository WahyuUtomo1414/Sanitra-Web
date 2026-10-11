<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\MsCompany;
use Illuminate\Auth\Access\HandlesAuthorization;

class MsCompanyPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MsCompany');
    }

    public function view(AuthUser $authUser, MsCompany $msCompany): bool
    {
        return $authUser->can('View:MsCompany');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MsCompany');
    }

    public function update(AuthUser $authUser, MsCompany $msCompany): bool
    {
        return $authUser->can('Update:MsCompany');
    }

    public function delete(AuthUser $authUser, MsCompany $msCompany): bool
    {
        return $authUser->can('Delete:MsCompany');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:MsCompany');
    }

    public function restore(AuthUser $authUser, MsCompany $msCompany): bool
    {
        return $authUser->can('Restore:MsCompany');
    }

    public function forceDelete(AuthUser $authUser, MsCompany $msCompany): bool
    {
        return $authUser->can('ForceDelete:MsCompany');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MsCompany');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MsCompany');
    }

    public function replicate(AuthUser $authUser, MsCompany $msCompany): bool
    {
        return $authUser->can('Replicate:MsCompany');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MsCompany');
    }

}