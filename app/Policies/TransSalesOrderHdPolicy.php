<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\TransSalesOrderHd;
use Illuminate\Auth\Access\HandlesAuthorization;

class TransSalesOrderHdPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:TransSalesOrderHd');
    }

    public function view(AuthUser $authUser, TransSalesOrderHd $transSalesOrderHd): bool
    {
        return $authUser->can('View:TransSalesOrderHd');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:TransSalesOrderHd');
    }

    public function update(AuthUser $authUser, TransSalesOrderHd $transSalesOrderHd): bool
    {
        return $authUser->can('Update:TransSalesOrderHd');
    }

    public function delete(AuthUser $authUser, TransSalesOrderHd $transSalesOrderHd): bool
    {
        return $authUser->can('Delete:TransSalesOrderHd');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:TransSalesOrderHd');
    }

    public function restore(AuthUser $authUser, TransSalesOrderHd $transSalesOrderHd): bool
    {
        return $authUser->can('Restore:TransSalesOrderHd');
    }

    public function forceDelete(AuthUser $authUser, TransSalesOrderHd $transSalesOrderHd): bool
    {
        return $authUser->can('ForceDelete:TransSalesOrderHd');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:TransSalesOrderHd');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:TransSalesOrderHd');
    }

    public function replicate(AuthUser $authUser, TransSalesOrderHd $transSalesOrderHd): bool
    {
        return $authUser->can('Replicate:TransSalesOrderHd');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:TransSalesOrderHd');
    }

}