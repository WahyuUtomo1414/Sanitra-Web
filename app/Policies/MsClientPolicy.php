<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\MsClient;
use Illuminate\Auth\Access\HandlesAuthorization;

class MsClientPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MsClient');
    }

    public function view(AuthUser $authUser, MsClient $msClient): bool
    {
        return $authUser->can('View:MsClient');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MsClient');
    }

    public function update(AuthUser $authUser, MsClient $msClient): bool
    {
        return $authUser->can('Update:MsClient');
    }

    public function delete(AuthUser $authUser, MsClient $msClient): bool
    {
        return $authUser->can('Delete:MsClient');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:MsClient');
    }

    public function restore(AuthUser $authUser, MsClient $msClient): bool
    {
        return $authUser->can('Restore:MsClient');
    }

    public function forceDelete(AuthUser $authUser, MsClient $msClient): bool
    {
        return $authUser->can('ForceDelete:MsClient');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MsClient');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MsClient');
    }

    public function replicate(AuthUser $authUser, MsClient $msClient): bool
    {
        return $authUser->can('Replicate:MsClient');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MsClient');
    }

}