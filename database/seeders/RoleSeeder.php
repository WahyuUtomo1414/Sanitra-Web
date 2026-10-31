<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /** @var array<string, array<string>> */
    private const ROLE_PERMISSIONS = [
        'Marketing' => [
            'ViewAny:TransSalesQuotationHd',
            'View:TransSalesQuotationHd',
            'Create:TransSalesQuotationHd',
            'Update:TransSalesQuotationHd',
            'Delete:TransSalesQuotationHd',
            'Send:TransSalesQuotationHd',
            'Approve:TransSalesQuotationHd',
            'Revise:TransSalesQuotationHd',
            'Reject:TransSalesQuotationHd',
            'ViewAny:TransSalesOrderHd',
            'View:TransSalesOrderHd',
            'Update:TransSalesOrderHd',
            'ViewAny:MsItemCategory',
            'View:MsItemCategory',
            'ViewAny:MsItem',
            'View:MsItem',
            'ViewAny:MsClient',
            'View:MsClient',
            'Create:MsClient',
            'Update:MsClient',
            'Delete:MsClient',
            'ViewAny:MsProjectType',
            'View:MsProjectType',
            'ViewAny:MsProjectWorkflow',
            'View:MsProjectWorkflow',
            'View:MarketingStatsWidget',
            'View:MarketingConversionChartWidget',
        ],
        'Finance' => [
            'ViewAny:TransSalesQuotationHd',
            'View:TransSalesQuotationHd',
            'ViewAny:TransSalesOrderHd',
            'View:TransSalesOrderHd',
            'Update:TransSalesOrderHd',
            'ViewAny:TransSalesInvoiceHd',
            'View:TransSalesInvoiceHd',
            'Create:TransSalesInvoiceHd',
            'Update:TransSalesInvoiceHd',
            'Void:TransSalesInvoiceHd',
            'ViewAny:TransSalesPayment',
            'View:TransSalesPayment',
            'Create:TransSalesPayment',
            'Confirm:TransSalesPayment',
            'Reject:TransSalesPayment',
            'ViewAny:MsBankAccount',
            'View:MsBankAccount',
            'ViewAny:MsClient',
            'View:FinanceStatsWidget',
            'View:FinanceRevenueChartWidget',
            'View:FinanceInvoiceStatusChartWidget',
        ],
        'Teknis' => [
            'ViewAny:TransSalesOrderHd',
            'View:TransSalesOrderHd',
            'ViewAny:MsProjectType',
            'View:MsProjectType',
            'ViewAny:MsProjectWorkflow',
            'View:MsProjectWorkflow',
            'ViewAny:MsClient',
            'View:MsClient',
            'View:TeknisStatsWidget',
        ],
    ];

    /** @var array<string> */
    private const SUPER_ADMIN_ONLY_PERMISSIONS = [
        'Close:TransSalesOrderHd',
        'Access:ImportMasterData',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::ROLE_PERMISSIONS as $roleName => $permissionNames) {
            foreach ($permissionNames as $permissionName) {
                Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
            }

            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web'])
                ->syncPermissions($permissionNames);
        }

        foreach (self::SUPER_ADMIN_ONLY_PERMISSIONS as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }

        Role::firstOrCreate(['name' => RoleName::SuperAdmin->value, 'guard_name' => 'web'])
            ->syncPermissions(Permission::query()->where('guard_name', 'web')->get());

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
