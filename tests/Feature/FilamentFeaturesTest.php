<?php

namespace Tests\Feature;

use App\Filament\Resources\MsStatuses\Pages\ListMsStatuses;
use App\Filament\Widgets\MonthlyQuotationsChart;
use App\Filament\Widgets\OwnerStats;
use App\Filament\Widgets\ProjectStatusChart;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentFeaturesTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_dashboard_shows_master_counts_and_labels_dummy_transaction_data(): void
    {
        $this->actingAs(User::factory()->create());
        Filament::setCurrentPanel(Filament::getPanel('dashboard'));

        Livewire::test(OwnerStats::class)
            ->assertSee('Total Perusahaan')
            ->assertSee('Total Karyawan')
            ->assertSee('Total Klien')
            ->assertSee('Total Item')
            ->assertSee('Data contoh');

        Livewire::test(MonthlyQuotationsChart::class)->assertSee('Quotation per Bulan (Data Contoh)');
        Livewire::test(ProjectStatusChart::class)->assertSee('Status Project (Data Contoh)');
    }

    public function test_master_tables_show_excel_export_action(): void
    {
        $this->actingAs(User::factory()->create());

        foreach (['companies', 'employees', 'clients', 'items', 'item-categories', 'units', 'bank-accounts', 'statuses', 'project-types', 'project-workflows'] as $resource) {
            $this->get("/dashboard/{$resource}")->assertSee('Unduh Excel');
        }
    }

    public function test_excel_action_exports_master_table(): void
    {
        $this->actingAs(User::factory()->create());
        Filament::setCurrentPanel(Filament::getPanel('dashboard'));

        Livewire::test(ListMsStatuses::class)
            ->callAction('export')
            ->assertHasNoErrors()
            ->assertFileDownloaded();
    }
}
