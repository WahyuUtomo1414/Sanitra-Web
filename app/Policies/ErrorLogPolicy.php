<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ErrorLog;
use Illuminate\Auth\Access\HandlesAuthorization;

class ErrorLogPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ErrorLog');
    }

    public function view(AuthUser $authUser, ErrorLog $errorLog): bool
    {
        return $authUser->can('View:ErrorLog');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ErrorLog');
    }

    public function update(AuthUser $authUser, ErrorLog $errorLog): bool
    {
        return $authUser->can('Update:ErrorLog');
    }

    public function delete(AuthUser $authUser, ErrorLog $errorLog): bool
    {
        return $authUser->can('Delete:ErrorLog');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ErrorLog');
    }

    public function restore(AuthUser $authUser, ErrorLog $errorLog): bool
    {
        return $authUser->can('Restore:ErrorLog');
    }

    public function forceDelete(AuthUser $authUser, ErrorLog $errorLog): bool
    {
        return $authUser->can('ForceDelete:ErrorLog');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ErrorLog');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ErrorLog');
    }

    public function replicate(AuthUser $authUser, ErrorLog $errorLog): bool
    {
        return $authUser->can('Replicate:ErrorLog');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ErrorLog');
    }

}