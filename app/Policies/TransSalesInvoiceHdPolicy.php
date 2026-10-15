<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TransSalesInvoiceHd;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class TransSalesInvoiceHdPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:TransSalesInvoiceHd');
    }

    public function view(AuthUser $authUser, TransSalesInvoiceHd $transSalesInvoiceHd): bool
    {
        return $authUser->can('View:TransSalesInvoiceHd');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:TransSalesInvoiceHd');
    }

    public function update(AuthUser $authUser, TransSalesInvoiceHd $transSalesInvoiceHd): bool
    {
        return $authUser->can('Update:TransSalesInvoiceHd');
    }

    public function delete(AuthUser $authUser, TransSalesInvoiceHd $transSalesInvoiceHd): bool
    {
        return $authUser->can('Delete:TransSalesInvoiceHd');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:TransSalesInvoiceHd');
    }

    public function restore(AuthUser $authUser, TransSalesInvoiceHd $transSalesInvoiceHd): bool
    {
        return $authUser->can('Restore:TransSalesInvoiceHd');
    }

    public function forceDelete(AuthUser $authUser, TransSalesInvoiceHd $transSalesInvoiceHd): bool
    {
        return $authUser->can('ForceDelete:TransSalesInvoiceHd');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:TransSalesInvoiceHd');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:TransSalesInvoiceHd');
    }

    public function replicate(AuthUser $authUser, TransSalesInvoiceHd $transSalesInvoiceHd): bool
    {
        return $authUser->can('Replicate:TransSalesInvoiceHd');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:TransSalesInvoiceHd');
    }
}
