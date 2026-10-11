<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\MsProjectWorkflow;
use Illuminate\Auth\Access\HandlesAuthorization;

class MsProjectWorkflowPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MsProjectWorkflow');
    }

    public function view(AuthUser $authUser, MsProjectWorkflow $msProjectWorkflow): bool
    {
        return $authUser->can('View:MsProjectWorkflow');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MsProjectWorkflow');
    }

    public function update(AuthUser $authUser, MsProjectWorkflow $msProjectWorkflow): bool
    {
        return $authUser->can('Update:MsProjectWorkflow');
    }

    public function delete(AuthUser $authUser, MsProjectWorkflow $msProjectWorkflow): bool
    {
        return $authUser->can('Delete:MsProjectWorkflow');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:MsProjectWorkflow');
    }

    public function restore(AuthUser $authUser, MsProjectWorkflow $msProjectWorkflow): bool
    {
        return $authUser->can('Restore:MsProjectWorkflow');
    }

    public function forceDelete(AuthUser $authUser, MsProjectWorkflow $msProjectWorkflow): bool
    {
        return $authUser->can('ForceDelete:MsProjectWorkflow');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MsProjectWorkflow');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MsProjectWorkflow');
    }

    public function replicate(AuthUser $authUser, MsProjectWorkflow $msProjectWorkflow): bool
    {
        return $authUser->can('Replicate:MsProjectWorkflow');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MsProjectWorkflow');
    }

}