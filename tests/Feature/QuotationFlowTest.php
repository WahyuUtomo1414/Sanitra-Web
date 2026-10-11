<?php

namespace Tests\Feature;

use App\Filament\Resources\TransSalesQuotationHds\Pages\CreateTransSalesQuotationHd;
use App\Models\MsClient;
use App\Models\MsItemUnit;
use App\Models\MsProjectType;
use App\Models\MsStatus;
use App\Models\TransSalesOrderHd;
use App\Models\TransSalesQuotationDt;
use App\Models\TransSalesQuotationHd;
use App\Models\TransSalesTerm;
use App\Models\User;
use App\Services\Quotation\ApproveQuotationService;
use App\Services\Quotation\ManageQuotationItemsService;
use App\Services\Quotation\ManageQuotationTermsService;
use App\Services\Quotation\QuotationPdfDataService;
use App\Services\Quotation\RecalculateQuotationTotalsService;
use App\Services\Quotation\RejectQuotationService;
use App\Services\Quotation\ReviseQuotationService;
use App\Services\Quotation\SendQuotationService;
use Database\Seeders\SanitraMasterSeeder;
use DomainException;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class QuotationFlowTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_marketing_can_create_draft_quotation_from_form(): void
    {
        $this->seed(SanitraMasterSeeder::class);
        $marketing = User::query()->where('email', 'marketing@gmail.com')->firstOrFail();
        $marketing->givePermissionTo(
            Permission::findOrCreate('ViewAny:TransSalesQuotationHd', 'web'),
            Permission::findOrCreate('Create:TransSalesQuotationHd', 'web'),
        );
        $this->actingAs($marketing);
        Filament::setCurrentPanel(Filament::getPanel('dashboard'));
        $projectType = MsProjectType::query()->firstOrFail();
        $client = MsClient::query()->firstOrFail();
        $client->update(['active' => true]);

        Livewire::test(CreateTransSalesQuotationHd::class)
            ->set('data.project_type_id', $projectType->id)
            ->assertFormSet(['company_id' => $projectType->company_id])
            ->fillForm([
                'transaction_date' => '2026-10-11',
                'project_type_id' => $projectType->id,
                'client_id' => $client->id,
                'vat_type' => 'none',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $quotation = TransSalesQuotationHd::query()->firstOrFail();
        $this->assertSame('draft', $quotation->status->code);
        $this->assertStringStartsWith('QUO/', $quotation->code);
        $this->assertSame('Termin', $quotation->payment_type);
        $this->assertSame($projectType->company_id, $quotation->company_id);
    }

    public function test_quotation_pages_show_form_and_existing_record(): void
    {
        $this->seed(SanitraMasterSeeder::class);
        $marketing = User::query()->where('email', 'marketing@gmail.com')->firstOrFail();
        $marketing->givePermissionTo(
            Permission::findOrCreate('ViewAny:TransSalesQuotationHd', 'web'),
            Permission::findOrCreate('Create:TransSalesQuotationHd', 'web'),
            Permission::findOrCreate('View:TransSalesQuotationHd', 'web'),
        );
        $this->actingAs($marketing);
        $quotation = TransSalesQuotationHd::factory()->create();

        $this->get('/dashboard/quotations/create')->assertOk();
        $this->get('/dashboard/quotations/'.$quotation->id)->assertSee($quotation->code);
    }

    public function test_client_can_be_created_from_quotation_form(): void
    {
        $this->seed(SanitraMasterSeeder::class);
        $marketing = User::query()->where('email', 'marketing@gmail.com')->firstOrFail();
        $marketing->givePermissionTo(
            Permission::findOrCreate('ViewAny:TransSalesQuotationHd', 'web'),
            Permission::findOrCreate('Create:TransSalesQuotationHd', 'web'),
        );
        $this->actingAs($marketing);
        Filament::setCurrentPanel(Filament::getPanel('dashboard'));

        Livewire::test(CreateTransSalesQuotationHd::class)
            ->callFormComponentAction('client_id', 'createOption', [
                'name' => 'Klien Baru',
                'email' => 'klien-baru@example.com',
                'phone' => '08123456789',
                'address' => 'Jakarta',
                'active' => true,
            ])
            ->assertHasNoFormErrors();

        $client = MsClient::query()->where('email', 'klien-baru@example.com')->firstOrFail();
        $this->assertSame('Klien Baru', $client->name);
    }

    public function test_pdf_template_renders_item_and_term_amounts(): void
    {
        $this->seed(SanitraMasterSeeder::class);
        $this->actingAs(User::query()->where('email', 'marketing@gmail.com')->firstOrFail());
        $quotation = TransSalesQuotationHd::factory()->create();
        TransSalesQuotationDt::factory()->create(['quotation_hd_id' => $quotation->id]);
        TransSalesTerm::factory()->create([
            'quotation_hd_id' => $quotation->id,
            'term_name' => 'DP',
            'percentage' => 100,
            'progress_threshold' => 0,
        ]);

        $html = view('pdf.quotation', app(QuotationPdfDataService::class)->execute($quotation))->render();

        $this->assertStringContainsString($quotation->code, $html);
        $this->assertStringContainsString('DP', $html);
        $this->assertStringContainsString('Rp 1.000.000', $html);
    }

    public function test_item_and_term_services_calculate_values_and_reject_changes_after_send(): void
    {
        $this->seed(SanitraMasterSeeder::class);
        $this->actingAs(User::query()->where('email', 'marketing@gmail.com')->firstOrFail());
        $quotation = TransSalesQuotationHd::factory()->create();
        $itemUnit = MsItemUnit::query()->firstOrFail();

        $item = app(ManageQuotationItemsService::class)->save($quotation, [
            'item_id' => $itemUnit->item_id,
            'unit_id' => $itemUnit->unit_id,
            'qty' => 2,
            'unit_price' => 1000000,
            'discount' => 100000,
        ]);
        $term = app(ManageQuotationTermsService::class)->save($quotation, [
            'term_name' => 'DP',
            'percentage' => 100,
            'progress_threshold' => 0,
        ]);

        $this->assertSame('1900000.000000', $item->total_price);
        $this->assertSame('1900000.000000', $quotation->fresh()->grand_total);
        $this->assertSame('draft', $term->status->code);

        app(SendQuotationService::class)->execute($quotation);

        $this->expectException(ValidationException::class);
        app(ManageQuotationItemsService::class)->delete($quotation, $item);
    }

    public function test_deleted_draft_term_can_reuse_its_sequence(): void
    {
        $this->seed(SanitraMasterSeeder::class);
        $this->actingAs(User::query()->where('email', 'marketing@gmail.com')->firstOrFail());
        $quotation = TransSalesQuotationHd::factory()->create();
        $service = app(ManageQuotationTermsService::class);
        $term = $service->save($quotation, [
            'term_name' => 'DP Lama',
            'percentage' => 100,
            'progress_threshold' => 0,
        ]);
        $service->delete($quotation, $term);

        $replacement = $service->save($quotation, [
            'term_name' => 'DP Baru',
            'percentage' => 100,
            'progress_threshold' => 0,
        ]);

        $this->assertSame($term->id, $replacement->id);
        $this->assertSame('DP Baru', $replacement->term_name);
        $this->assertNull($replacement->deleted_at);
    }

    public function test_vat_included_and_excluded_use_discounted_subtotal_once(): void
    {
        $this->seed(SanitraMasterSeeder::class);
        $this->actingAs(User::query()->where('email', 'marketing@gmail.com')->firstOrFail());
        config()->set('sanitra.vat_rate', '0.11');
        $quotation = TransSalesQuotationHd::factory()->create(['vat_type' => 'include']);
        TransSalesQuotationDt::factory()->create([
            'quotation_hd_id' => $quotation->id,
            'unit_price' => 1000000,
            'discount' => 100000,
            'total_price' => 900000,
        ]);

        app(RecalculateQuotationTotalsService::class)->execute($quotation);

        $this->assertSame('900000.000000', $quotation->fresh()->subtotal);
        $this->assertSame('100000.000000', $quotation->fresh()->discount_total);
        $this->assertSame('89189.189189', $quotation->fresh()->vat_amount);
        $this->assertSame('900000.000000', $quotation->fresh()->grand_total);

        $quotation->refresh()->update(['vat_type' => 'exclude']);
        app(RecalculateQuotationTotalsService::class)->execute($quotation);

        $this->assertSame('99000.000000', $quotation->fresh()->vat_amount);
        $this->assertSame('999000.000000', $quotation->fresh()->grand_total);
    }

    public function test_sending_and_approving_creates_a_waiting_dp_order_with_copied_items_and_terms(): void
    {
        $this->seed(SanitraMasterSeeder::class);
        $this->actingAs(User::query()->where('email', 'marketing@gmail.com')->firstOrFail());
        $quotation = TransSalesQuotationHd::factory()->create();
        $item = TransSalesQuotationDt::factory()->create([
            'quotation_hd_id' => $quotation->id,
            'qty' => 2,
            'unit_price' => 1000000,
            'discount' => 100000,
            'total_price' => 1900000,
        ]);
        TransSalesTerm::factory()->create([
            'quotation_hd_id' => $quotation->id,
            'sequence' => 1,
            'term_name' => 'DP',
            'percentage' => 30,
            'progress_threshold' => 0,
        ]);
        TransSalesTerm::factory()->create([
            'quotation_hd_id' => $quotation->id,
            'sequence' => 2,
            'percentage' => 70,
            'progress_threshold' => 100,
        ]);

        app(SendQuotationService::class)->execute($quotation);
        app(ApproveQuotationService::class)->execute($quotation);

        $order = TransSalesOrderHd::query()->where('quotation_hd_id', $quotation->id)->firstOrFail();
        $this->assertSame('approved', $quotation->fresh()->status->code);
        $this->assertSame('1900000.000000', $quotation->fresh()->subtotal);
        $this->assertSame('100000.000000', $quotation->fresh()->discount_total);
        $this->assertSame('1900000.000000', $quotation->fresh()->grand_total);
        $this->assertSame('waiting_dp', $order->status->code);
        $this->assertSame('unpaid', $order->paymentStatus->code);
        $this->assertSame($item->id, $order->items()->firstOrFail()->quotation_dt_id);
        $this->assertSame('570000.000000', $order->terms()->where('sequence', 1)->firstOrFail()->amount);
        $this->assertSame('ready_to_bill', $order->terms()->where('sequence', 1)->firstOrFail()->status->code);
        $this->assertSame('1330000.000000', $order->terms()->where('sequence', 2)->firstOrFail()->amount);
        $this->assertSame('pending', $order->terms()->where('sequence', 2)->firstOrFail()->status->code);
    }

    public function test_sending_rejects_quotation_without_exactly_one_dp(): void
    {
        $this->seed(SanitraMasterSeeder::class);
        $this->actingAs(User::query()->where('email', 'marketing@gmail.com')->firstOrFail());
        $quotation = TransSalesQuotationHd::factory()->create();
        TransSalesQuotationDt::factory()->create(['quotation_hd_id' => $quotation->id]);
        TransSalesTerm::factory()->create([
            'quotation_hd_id' => $quotation->id,
            'percentage' => 100,
            'progress_threshold' => 30,
        ]);

        try {
            app(SendQuotationService::class)->execute($quotation);
            $this->fail('Quotation tanpa DP seharusnya ditolak.');
        } catch (DomainException $exception) {
            $this->assertSame('Quotation harus memiliki tepat satu termin DP dengan threshold 0%.', $exception->getMessage());
        }

        $this->assertSame('draft', $quotation->fresh()->status->code);
    }

    public function test_revision_and_rejection_only_work_from_sent_and_are_logged(): void
    {
        $this->seed(SanitraMasterSeeder::class);
        $this->actingAs(User::query()->where('email', 'marketing@gmail.com')->firstOrFail());
        $quotation = TransSalesQuotationHd::factory()->create();
        $sentStatus = MsStatus::query()->where('type', 'quotation')->where('code', 'sent')->firstOrFail();
        $quotation->refresh()->update(['status_id' => $sentStatus->id]);

        app(ReviseQuotationService::class)->execute($quotation);

        $this->assertSame('draft', $quotation->fresh()->status->code);
        $this->assertTrue(Activity::query()->where('subject_type', TransSalesQuotationHd::class)
            ->where('subject_id', $quotation->id)->where('description', 'updated')->exists());

        $quotation->refresh()->update(['status_id' => $sentStatus->id]);
        app(RejectQuotationService::class)->execute($quotation);

        $this->assertSame('rejected', $quotation->fresh()->status->code);
        $this->assertSame(0, TransSalesOrderHd::query()->count());
    }
}
