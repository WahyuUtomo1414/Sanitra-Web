<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\MsBankAccount;
use Illuminate\Auth\Access\HandlesAuthorization;

class MsBankAccountPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MsBankAccount');
    }

    public function view(AuthUser $authUser, MsBankAccount $msBankAccount): bool
    {
        return $authUser->can('View:MsBankAccount');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MsBankAccount');
    }

    public function update(AuthUser $authUser, MsBankAccount $msBankAccount): bool
    {
        return $authUser->can('Update:MsBankAccount');
    }

    public function delete(AuthUser $authUser, MsBankAccount $msBankAccount): bool
    {
        return $authUser->can('Delete:MsBankAccount');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:MsBankAccount');
    }

    public function restore(AuthUser $authUser, MsBankAccount $msBankAccount): bool
    {
        return $authUser->can('Restore:MsBankAccount');
    }

    public function forceDelete(AuthUser $authUser, MsBankAccount $msBankAccount): bool
    {
        return $authUser->can('ForceDelete:MsBankAccount');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MsBankAccount');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MsBankAccount');
    }

    public function replicate(AuthUser $authUser, MsBankAccount $msBankAccount): bool
    {
        return $authUser->can('Replicate:MsBankAccount');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MsBankAccount');
    }

}