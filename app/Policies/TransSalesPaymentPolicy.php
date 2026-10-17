<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\TransSalesPayment;
use Illuminate\Auth\Access\HandlesAuthorization;

class TransSalesPaymentPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:TransSalesPayment');
    }

    public function view(AuthUser $authUser, TransSalesPayment $transSalesPayment): bool
    {
        return $authUser->can('View:TransSalesPayment');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:TransSalesPayment');
    }

    public function update(AuthUser $authUser, TransSalesPayment $transSalesPayment): bool
    {
        return $authUser->can('Update:TransSalesPayment');
    }

    public function delete(AuthUser $authUser, TransSalesPayment $transSalesPayment): bool
    {
        return $authUser->can('Delete:TransSalesPayment');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:TransSalesPayment');
    }

    public function restore(AuthUser $authUser, TransSalesPayment $transSalesPayment): bool
    {
        return $authUser->can('Restore:TransSalesPayment');
    }

    public function forceDelete(AuthUser $authUser, TransSalesPayment $transSalesPayment): bool
    {
        return $authUser->can('ForceDelete:TransSalesPayment');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:TransSalesPayment');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:TransSalesPayment');
    }

    public function replicate(AuthUser $authUser, TransSalesPayment $transSalesPayment): bool
    {
        return $authUser->can('Replicate:TransSalesPayment');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:TransSalesPayment');
    }

}