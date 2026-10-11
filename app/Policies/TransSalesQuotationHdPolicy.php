<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\TransSalesQuotationHd;
use Illuminate\Auth\Access\HandlesAuthorization;

class TransSalesQuotationHdPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:TransSalesQuotationHd');
    }

    public function view(AuthUser $authUser, TransSalesQuotationHd $transSalesQuotationHd): bool
    {
        return $authUser->can('View:TransSalesQuotationHd');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:TransSalesQuotationHd');
    }

    public function update(AuthUser $authUser, TransSalesQuotationHd $transSalesQuotationHd): bool
    {
        return $authUser->can('Update:TransSalesQuotationHd');
    }

    public function delete(AuthUser $authUser, TransSalesQuotationHd $transSalesQuotationHd): bool
    {
        return $authUser->can('Delete:TransSalesQuotationHd');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:TransSalesQuotationHd');
    }

    public function restore(AuthUser $authUser, TransSalesQuotationHd $transSalesQuotationHd): bool
    {
        return $authUser->can('Restore:TransSalesQuotationHd');
    }

    public function forceDelete(AuthUser $authUser, TransSalesQuotationHd $transSalesQuotationHd): bool
    {
        return $authUser->can('ForceDelete:TransSalesQuotationHd');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:TransSalesQuotationHd');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:TransSalesQuotationHd');
    }

    public function replicate(AuthUser $authUser, TransSalesQuotationHd $transSalesQuotationHd): bool
    {
        return $authUser->can('Replicate:TransSalesQuotationHd');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:TransSalesQuotationHd');
    }

}