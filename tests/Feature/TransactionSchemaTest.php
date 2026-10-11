<?php

namespace Tests\Feature;

use App\Models\MsBankAccount;
use App\Models\MsClient;
use App\Models\MsStatus;
use App\Models\TransProjectDt;
use App\Models\TransProjectHd;
use App\Models\TransProjectMonitoring;
use App\Models\TransSalesInvoiceDt;
use App\Models\TransSalesInvoiceHd;
use App\Models\TransSalesOrderDt;
use App\Models\TransSalesOrderHd;
use App\Models\TransSalesPayment;
use App\Models\TransSalesQuotationDt;
use App\Models\TransSalesQuotationHd;
use App\Models\TransSalesTerm;
use App\Models\User;
use Database\Seeders\SanitraMasterSeeder;
use Database\Seeders\SanitraTransactionSampleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use LogicException;
use Tests\TestCase;

class TransactionSchemaTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_transaction_tables_and_relationships_support_the_sales_flow(): void
    {
        $this->seed(SanitraMasterSeeder::class);
        $this->actingAs(User::query()->where('email', 'marketing@gmail.com')->firstOrFail());

        $quotation = TransSalesQuotationHd::factory()->create();
        $quotationItem = TransSalesQuotationDt::factory()->create(['quotation_hd_id' => $quotation->id]);
        $term = TransSalesTerm::factory()->create(['quotation_hd_id' => $quotation->id]);
        $order = TransSalesOrderHd::factory()->create(['quotation_hd_id' => $quotation->id]);
        TransSalesOrderDt::factory()->create([
            'order_hd_id' => $order->id,
            'quotation_dt_id' => $quotationItem->id,
        ]);
        $term->update(['order_hd_id' => $order->id]);

        $project = TransProjectHd::factory()->create(['order_hd_id' => $order->id]);
        $stage = TransProjectDt::factory()->create(['project_hd_id' => $project->id]);
        $monitoring = TransProjectMonitoring::factory()->create([
            'project_hd_id' => $project->id,
            'project_dt_id' => $stage->id,
        ]);

        $invoice = TransSalesInvoiceHd::factory()->create([
            'order_hd_id' => $order->id,
            'quotation_hd_id' => $quotation->id,
            'project_hd_id' => null,
        ]);
        TransSalesInvoiceDt::factory()->create([
            'invoice_hd_id' => $invoice->id,
            'sales_term_id' => $term->id,
        ]);
        TransSalesPayment::factory()->create(['invoice_hd_id' => $invoice->id]);

        $this->assertTrue($quotation->order->is($order));
        $this->assertTrue($order->project->is($project));
        $this->assertTrue($order->terms->first()->is($term));
        $this->assertNull($invoice->project);
        $this->assertTrue($invoice->items->first()->salesTerm->is($term));
        $this->assertSame(1, $invoice->payments()->count());
        $this->assertSame(1, $project->monitoring()->count());

        $this->expectException(LogicException::class);
        $monitoring->update(['notes' => 'Log tidak boleh diubah']);
    }

    public function test_seeded_marketing_and_finance_accounts_use_the_requested_password(): void
    {
        $this->seed(SanitraMasterSeeder::class);

        foreach (['marketing@gmail.com', 'finance@gmail.com'] as $email) {
            $user = User::query()->where('email', $email)->firstOrFail();
            $this->assertTrue(Hash::check('12345678', $user->password));
        }
    }

    public function test_transaction_sample_seeder_creates_an_idempotent_dp_flow(): void
    {
        $this->seed(SanitraMasterSeeder::class);
        $this->actingAs(User::query()->where('email', 'super@gmail.com')->firstOrFail());
        MsClient::query()->firstOrFail()->update(['active' => true]);
        MsBankAccount::query()->firstOrFail()->update(['active' => true]);
        $this->seed(SanitraTransactionSampleSeeder::class);
        $this->seed(SanitraTransactionSampleSeeder::class);

        $this->assertSame(3, TransSalesQuotationHd::query()->count());
        $this->assertSame(1, TransSalesOrderHd::query()->count());
        $this->assertSame(1, TransSalesInvoiceHd::query()->whereNull('project_hd_id')->count());
        $this->assertSame(1, TransSalesPayment::query()->count());
        $this->assertSame('pending', TransSalesPayment::query()->firstOrFail()->status->code);
        $this->assertSame('invoiced', TransSalesTerm::query()->whereNotNull('order_hd_id')->firstOrFail()->status->code);
    }

    public function test_blocked_project_stage_requires_a_note(): void
    {
        $this->seed(SanitraMasterSeeder::class);
        $this->actingAs(User::query()->where('email', 'marketing@gmail.com')->firstOrFail());

        $stage = TransProjectDt::factory()->create();
        $blockedStatus = MsStatus::query()->where('type', 'project_dt')->where('code', 'blocked')->firstOrFail();

        $this->assertSame('Belum Dimulai', $stage->status->name);
        $this->assertSame('Terkendala', $blockedStatus->name);

        try {
            $stage->update(['status_id' => $blockedStatus->id, 'notes' => '   ']);
            $this->fail('Tahap terkendala tanpa catatan seharusnya ditolak.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('notes', $exception->errors());
        }

        $this->assertSame('draft', $stage->fresh()->status->code);

        $stage->refresh()->update(['status_id' => $blockedStatus->id, 'notes' => 'Menunggu material dari pemasok.']);

        $this->assertSame('blocked', $stage->fresh()->status->code);
        $this->assertSame('Menunggu material dari pemasok.', $stage->notes);
    }
}
