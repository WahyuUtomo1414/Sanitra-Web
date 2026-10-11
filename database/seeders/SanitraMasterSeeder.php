<?php

namespace Database\Seeders;

use App\Enums\AutoCodePrefix;
use App\Models\CodeSequence;
use App\Models\MsBankAccount;
use App\Models\MsClient;
use App\Models\MsCompany;
use App\Models\MsEmployee;
use App\Models\MsItem;
use App\Models\MsItemCategory;
use App\Models\MsProjectType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SanitraMasterSeeder extends Seeder
{
    public function run(): void
    {
        $previousUser = Auth::user();

        try {
            DB::transaction(function (): void {
                Auth::setUser(User::query()->firstOrCreate(
                    ['email' => 'master-seeder@sanitra.invalid'],
                    ['name' => 'Seeder Master Sanitra', 'password' => Str::random(64)],
                ));

                $this->synchronizeSequences();

                $this->call([
                    CompanySeeder::class,
                    EmployeeSeeder::class,
                    UserSeeder::class,
                    ClientSeeder::class,
                    ProjectSeeder::class,
                    ItemSeeder::class,
                    BankAccountSeeder::class,
                    StatusSeeder::class,
                ]);
            });
        } finally {
            if ($previousUser !== null) {
                Auth::setUser($previousUser);
            } else {
                Auth::forgetGuards();
            }
        }
    }

    private function synchronizeSequences(): void
    {
        foreach ([
            AutoCodePrefix::MsCompany->value => MsCompany::class,
            AutoCodePrefix::MsEmployee->value => MsEmployee::class,
            AutoCodePrefix::MsClient->value => MsClient::class,
            AutoCodePrefix::MsProjectType->value => MsProjectType::class,
            AutoCodePrefix::MsItemCategory->value => MsItemCategory::class,
            AutoCodePrefix::MsItem->value => MsItem::class,
            AutoCodePrefix::MsBankAccount->value => MsBankAccount::class,
        ] as $prefix => $modelClass) {
            $highestNumber = 0;

            foreach ($modelClass::withTrashed()->where('code', 'like', $prefix.'%')->pluck('code') as $code) {
                if (preg_match('/^'.preg_quote($prefix, '/').'(\d+)$/', $code, $matches)) {
                    $highestNumber = max($highestNumber, (int) $matches[1]);
                }
            }

            if ($highestNumber === 0) {
                continue;
            }

            $sequence = CodeSequence::query()->where('prefix', $prefix)->whereNull('year')
                ->whereNull('month')->lockForUpdate()->first();

            if ($sequence === null) {
                CodeSequence::create(['prefix' => $prefix, 'year' => null, 'month' => null, 'last_number' => $highestNumber]);
            } elseif ($sequence->last_number < $highestNumber) {
                $sequence->update(['last_number' => $highestNumber]);
            }
        }
    }
}
