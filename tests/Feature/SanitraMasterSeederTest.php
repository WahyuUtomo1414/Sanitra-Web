<?php

namespace Tests\Feature;

use App\Enums\AutoCodePrefix;
use App\Models\CodeSequence;
use App\Models\MsBankAccount;
use App\Models\MsClient;
use App\Models\MsCompany;
use App\Models\MsEmployee;
use App\Models\MsItem;
use App\Models\MsItemCategory;
use App\Models\MsItemUnit;
use App\Models\MsProjectType;
use App\Models\MsProjectWorkflow;
use App\Models\MsStatus;
use App\Models\MsUnit;
use App\Services\Shared\GenerateCodeService;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\SanitraMasterSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SanitraMasterSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_seed_populates_every_master_with_marked_placeholder_data(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, MsCompany::count());
        $this->assertSame(2, MsEmployee::count());
        $this->assertSame(7, MsClient::count());
        $this->assertSame(5, MsProjectType::count());
        $this->assertSame(22, MsProjectWorkflow::count());
        $this->assertSame(6, MsItemCategory::count());
        $this->assertSame(7, MsUnit::count());
        $this->assertSame(8, MsItem::count());
        $this->assertSame(8, MsItemUnit::count());
        $this->assertSame(1, MsBankAccount::count());
        $this->assertSame(25, MsStatus::count());
        $this->assertSame('info@sanitra.co.id', MsCompany::firstOrFail()->email);
        $this->assertFalse(MsBankAccount::firstOrFail()->active);
        $this->assertSame(0, MsClient::where('active', true)->count());
        $this->assertSame(8, MsItemUnit::where('is_base', true)->where('conversion', 1)->count());
    }

    public function test_reseeding_keeps_records_and_code_sequence_unchanged(): void
    {
        $this->seed(SanitraMasterSeeder::class);
        $companyId = MsCompany::firstOrFail()->id;
        $before = CodeSequence::query()->orderBy('prefix')->pluck('last_number', 'prefix')->all();

        $this->seed(SanitraMasterSeeder::class);

        $this->assertSame($companyId, MsCompany::firstOrFail()->id);
        $this->assertSame(7, MsClient::count());
        $this->assertSame($before, CodeSequence::query()->orderBy('prefix')->pluck('last_number', 'prefix')->all());
        $this->assertSame('COM0002', app(GenerateCodeService::class)->next(AutoCodePrefix::MsCompany));
    }

    public function test_seed_resumes_after_an_existing_higher_master_code(): void
    {
        $this->seed(SanitraMasterSeeder::class);
        MsCompany::query()->where('code', 'COM0001')->update(['code' => 'COM0042']);

        $this->seed(SanitraMasterSeeder::class);

        $this->assertSame('COM0043', app(GenerateCodeService::class)->next(AutoCodePrefix::MsCompany));
    }
}
