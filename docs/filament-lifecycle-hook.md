# Filament Lifecycle Hook & Service Pattern

**Status:** Konsep awal · **Acuan:** [filament-resource.md](filament-resource.md), [database.md](database.md), [prd.md](prd.md) · **Panel:** Filament 4

## 1. Tujuan

Dokumen ini menetapkan pola orkestrasi logika transaksi di Sanitra:

- **Business logic di Service**, bukan Observer.
- **Service di-trigger dari Filament resource lifecycle hooks** (bukan dari model event).
- **Observer dibatasi hanya untuk cross-cutting concerns** (audit, immutability guard).

Pola ini memastikan alur transaksi (create project dari quotation, generate invoice dari termin, kirim email, dll.) dapat dibaca, diuji, dan di-skip dengan mudah dari seeder/test.

## 2. Keputusan Arsitektur: Service > Observer untuk Business Logic

### 2.1 Alasan

| Aspek | Observer | Service |
| --- | --- | --- |
| Explisitas | Fires otomatis di event model — tidak terlihat di call site | Dipanggil eksplisit dari Filament atau controller |
| Orkestrasi berurutan (A→B→C) | Sulit, perlu nested event | Natural dengan satu method |
| `DB::transaction()` wrapping | Perlu dibungkus di level pemanggil | Dibungkus di dalam service |
| Skip di seeder/test | Perlu `withoutEvents()` atau fake observer | Tinggal tidak dipanggil |
| Pass context dari UI | Sulit (model tidak tahu konteks) | Lewat parameter |
| Testable isolated | Perlu mock DB & event | Instansiasi langsung |
| SRS §NFR-04 (DB transaction untuk invoice/term/stage) | Awkward | Textbook |

Observer menang hanya di satu hal: fires tanpa perlu diingat. Untuk business logic kompleks, "tanpa perlu diingat" justru bahaya karena side effect tidak terlihat.

### 2.2 Pembagian tanggung jawab

| Lapisan | Tugas | Contoh |
| --- | --- | --- |
| **Filament Resource** | UI, form schema, table, action button | `TransProjectHdResource`, `TransQuotationHdResource` |
| **Filament Page Hook** | Trigger service dengan data form | `handleRecordCreation()`, `afterSave()`, custom `Action` |
| **Service** | Orkestrasi business logic + `DB::transaction` | `CreateProjectStagesService`, `CreateInvoiceFromTermsService` |
| **Notification** | Email/notif ke user | `TermReadyToBillNotification` |
| **Trait Model** | Cross-cutting (audit kolom) | `AuditedBySoftDelete` |
| **Observer** | Immutability guard, invariant enforcement | `TransProjectMonitoringObserver` (reject update/delete) |

**Rule:** Jika logic punya side effect berurutan atau butuh transaction → service. Jika logic hanya guard invariant model → observer. Jika hanya isi kolom audit → trait.

## 3. Struktur Folder Service

```
app/
└── Services/
    ├── Quotation/
    │   ├── SendQuotationService.php
    │   ├── ApproveQuotationService.php
    │   └── RejectQuotationService.php
    ├── Project/
    │   ├── CreateProjectStagesService.php
    │   ├── UpdateProjectStageService.php
    │   ├── OverrideProjectStageService.php
    │   └── CloseProjectIfPaidService.php
    ├── Invoice/
    │   ├── CreateInvoiceFromTermsService.php
    │   └── VoidInvoiceService.php
    └── Payment/
        └── RecordPaymentService.php
```

Konvensi:

- Satu service = satu use case (verb + noun pattern: `CreateProjectStages`, `ApproveQuotation`, `VoidInvoice`).
- Group per domain (Quotation, Project, Invoice, Payment).
- Entry method = `execute()` atau nama yang spesifik (`approve()`, `void()`).
- Return value = model utama yang dibuat/dimodifikasi, bukan array.
- Dependency injection via constructor (bisa resolve lewat `app(ServiceName::class)` atau type-hint di method).

### 3.1 Naming Helper Method

Nama helper method di dalam service harus langsung bisa dibaca kayak kalimat. Hindari nama generic (`guard...`, `handle...`, `process...`) atau double-negative.

| Pola | Pakai kapan | Contoh |
| --- | --- | --- |
| `throwIf<Condition>()` | Guard yang throw exception kalau kondisi terpenuhi | `throwIfStagesExist()`, `throwIfQuotationNotApproved()`, `throwIfInvoiceHasPayments()` |
| `get<Noun>For<Context>()` | Ambil data terfilter | `getActiveWorkflowsForType()`, `getReadyToBillTermsForProject()` |
| `get<Noun>Id()` | Resolve ID dari identifier bisnis | `getStatusId()`, `getFirstTermId()` |
| `copy<Source>To<Target>()` | Replikasi data antar entity | `copyWorkflowsToStages()`, `copyTermsToInvoice()` |
| `calculate<Noun>()` | Hitung nilai derivatif | `calculateProjectProgress()`, `calculateInvoiceTotal()` |
| `lock<Noun>()` / `unlock<Noun>()` | Set flag editable/boolean | `lockStagesCoveredByInvoice()`, `unlockUncoveredStages()` |
| `notify<Audience>About<Event>()` | Trigger notification | `notifyFinanceAboutReadyToBillTerm()`, `notifyTeknisAboutPaidTerm()` |

**Hindari:**

- `guardXxx()` — generic, tidak jelas apa behavior-nya (throw? skip? set flag?). Pakai `throwIfXxx()` kalau memang throw.
- `handleXxx()` — ambigu, tidak describe side effect.
- Double-negative seperti `guardNotCreated` → susah dibaca. Ganti dengan form positif: `throwIfExists`.
- Nama tanpa konteks seperti `fetchData()` atau `processItem()` — selalu sebutkan apa yang di-fetch/process.

Contoh refactor dari nama kurang jelas → nama yang "ngomong":

```
guardStagesNotYetCreated()  →  throwIfStagesExist()
fetchActiveWorkflows()      →  getActiveWorkflowsForType()
resolveStatusId()           →  getStatusId()
handleStageUpdate()         →  updateStageAndLogMonitoring()
processPayment()            →  recordPaymentAndCheckSettlement()
```

## 4. Pattern Dasar Service

Service wajib:

- Wrap seluruh operasi dalam `DB::transaction()`.
- Terima input data (array atau DTO) dan model terkait sebagai parameter.
- Lempar exception bila validasi gagal — jangan return `false` atau null.
- Trigger notification di akhir (setelah transaction commit).

Contoh skeleton — service untuk auto-create tahap project dari workflow master:

```php
<?php

namespace App\Services\Project;

use App\Models\TransProjectHd;
use App\Models\MsProjectWorkflow;
use App\Models\MsStatus;
use Illuminate\Support\Facades\DB;

class CreateProjectStagesService
{
    public function execute(TransProjectHd $project, int $projectTypeId): void
    {
        DB::transaction(function () use ($project, $projectTypeId) {
            $this->throwIfStagesExist($project);

            $workflows = $this->getActiveWorkflowsForType($projectTypeId);
            $pendingStatusId = $this->getStatusId('project_dt', 'pending');

            foreach ($workflows as $workflow) {
                $project->stages()->create([
                    'project_workflow_id' => $workflow->id,
                    'sequence' => $workflow->sequence,
                    'work_name' => $workflow->name,
                    'progress_weight' => $workflow->progress_weight,
                    'status_id' => $pendingStatusId,
                    'editable' => true,
                ]);
            }
        });
    }

    protected function throwIfStagesExist(TransProjectHd $project): void
    {
        if ($project->stages()->exists()) {
            throw new \DomainException('Tahap project sudah ada, tidak boleh dibuat ulang.');
        }
    }

    protected function getActiveWorkflowsForType(int $projectTypeId)
    {
        $workflows = MsProjectWorkflow::where('type_id', $projectTypeId)
            ->where('active', true)
            ->orderBy('sequence')
            ->get();

        if ($workflows->isEmpty()) {
            throw new \DomainException('Workflow untuk tipe project ini belum di-set.');
        }

        return $workflows;
    }

    protected function getStatusId(string $type, string $code): int
    {
        return MsStatus::where('type', $type)->where('code', $code)->value('id')
            ?? throw new \DomainException("Status {$type}:{$code} tidak ditemukan.");
    }
}
```

Catatan:

- **Scope service kecil & fokus**: hanya copy workflow → tahap. Tidak mengurus HD, tidak mengurus quotation, tidak mengurus notification.
- **Reusable**: service ini bisa dipanggil dari Filament create project, dari seeder, atau dari API — tanpa modifikasi.
- **Guard di awal** (sudah ada tahap?) — fail fast.
- **Transaction wrap** — kalau foreach gagal di tengah, semua rollback.
- **Model relation call** (`$project->stages()->create(...)`) lebih bersih daripada `TransProjectDt::create(['project_hd_id' => ...])`.
- **Resolve status via code** (`'pending'`) → service tidak hardcode `status_id`.
- Alur project creation lengkap (HD + stages) di-orkestrasi dari Filament hook, lihat §5.1.

## 5. Triggering dari Filament Resource

Filament 4 menyediakan beberapa lifecycle hook di resource pages dan actions.

### 5.1 Create Page

File: `app/Filament/Resources/TransProjectHd/Pages/CreateTransProjectHd.php`

```php
use App\Services\Project\CreateProjectStagesService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateTransProjectHd extends CreateRecord
{
    protected static string $resource = TransProjectHdResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            // 1. Filament bikin HD standar
            $project = static::getModel()::create($data);

            // 2. Service auto-create tahap dari workflow master berdasarkan tipe project
            app(CreateProjectStagesService::class)
                ->execute($project, $project->project_type_id);

            return $project;
        });
    }
}
```

Hook yang tersedia di `CreateRecord`:

| Hook | Pakai kapan |
| --- | --- |
| `mutateFormDataBeforeCreate(array $data): array` | Normalisasi data form sebelum save (trim, cast tanggal, generate kode). |
| `handleRecordCreation(array $data): Model` | Override penuh — bungkus `Model::create()` dalam `DB::transaction` dan panggil service terkait. |
| `afterCreate(): void` | Side effect ringan setelah record ada (log, cache invalidation, notification). **Jangan** untuk business logic kompleks. |

Catatan untuk contoh di atas:

- `DB::transaction()` wajib — kalau pembuatan tahap gagal (misal workflow belum di-set), HD juga rollback.
- Form sudah pre-fill `client_id`, `project_type_id`, `company_id` dari quotation yang dipilih — lihat §5.5.
- `static::getModel()::create($data)` dipakai supaya tetap generic kalau model berubah; alternatif pakai `TransProjectHd::create($data)` langsung juga OK.

### 5.2 Edit Page

File: `app/Filament/Resources/TransInvoiceHd/Pages/EditTransInvoiceHd.php`

```php
use App\Services\Invoice\UpdateInvoiceService;

class EditTransInvoiceHd extends EditRecord
{
    protected static string $resource = TransInvoiceHdResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(UpdateInvoiceService::class)
            ->execute($record, $data);
    }
}
```

### 5.3 Custom Action (transisi status)

Untuk aksi seperti "Approve Quotation", "Void Invoice", "Record Payment" — pakai `Action` di resource atau page.

File: `app/Filament/Resources/TransQuotationHd/TransQuotationHdResource.php` atau page view.

```php
use App\Services\Quotation\ApproveQuotationService;
use Filament\Actions\Action;

Action::make('approve')
    ->label('Approve')
    ->icon('heroicon-o-check-circle')
    ->color('success')
    ->requiresConfirmation()
    ->modalDescription('Setelah di-approve, quotation tidak bisa diedit dan project otomatis dibuat.')
    ->visible(fn ($record) => $record->status->code === 'sent')
    ->action(function ($record) {
        app(ApproveQuotationService::class)->execute($record);
    })
    ->successNotificationTitle('Quotation di-approve.');
```

Catatan:

- `visible()` pakai status code supaya tombol muncul hanya saat relevan.
- `requiresConfirmation()` + `modalDescription()` wajib untuk aksi yang tidak reversible.
- `->action()` cukup panggil service — exception dari service otomatis di-handle Filament dan ditampilkan sebagai notification error.

### 5.4 Bulk Action

Untuk aksi batch (misal mark multiple terms as Ready to Bill):

```php
use Filament\Tables\Actions\BulkAction;

BulkAction::make('markReadyToBill')
    ->label('Tandai Siap Tagih')
    ->action(function ($records) {
        $service = app(MarkTermReadyToBillService::class);

        foreach ($records as $record) {
            $service->execute($record);
        }
    })
    ->deselectRecordsAfterCompletion();
```

Catatan: setiap record di-execute terpisah supaya satu kegagalan tidak rollback yang lain. Kalau butuh atomic bulk (all-or-nothing), bungkus loop dalam `DB::transaction()` di service bulk tersendiri.

### 5.5 Form Reactivity (Prefill dari Related Model)

Untuk kasus seperti "pilih quotation → auto-isi client, project_type, company", pakai **form-level reactivity Filament**, bukan service. Service tetap untuk business logic saat submit; form reactivity cuma untuk kenyamanan input.

Pola: field pemicu pakai `->live()`, lalu `->afterStateUpdated()` nulis ke field lain via util `Set`.

Contoh di `TransProjectHdForm` saat user pilih quotation:

```php
use App\Models\TransQuotationHd;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Select;
use Illuminate\Database\Eloquent\Builder;

Select::make('quotation_hd_id')
    ->label('Quotation')
    ->relationship(
        name: 'quotation',
        titleAttribute: 'code',
        modifyQueryUsing: fn (Builder $query) => $query->whereHas(
            'status',
            fn ($q) => $q->where('type', 'quotation')->where('code', 'approved')
        ),
    )
    ->searchable()
    ->preload()
    ->required()
    ->live()
    ->afterStateUpdated(function (?string $state, Set $set): void {
        if (! $state) {
            return;
        }

        $quotation = TransQuotationHd::find($state);

        if (! $quotation) {
            return;
        }

        $set('client_id', $quotation->client_id);
        $set('project_type_id', $quotation->project_type_id);
        $set('company_id', $quotation->company_id);
    }),

Select::make('client_id')
    ->relationship('client', 'name')
    ->disabled()
    ->dehydrated()
    ->required(),

Select::make('project_type_id')
    ->relationship('projectType', 'name')
    ->disabled()
    ->dehydrated()
    ->required(),

Select::make('company_id')
    ->relationship('company', 'name')
    ->disabled()
    ->dehydrated()
    ->required(),
```

Ketentuan:

- `->live()` membuat field reaktif — tiap perubahan trigger re-render form.
- `->afterStateUpdated()` callback dapat `?string $state` (nilai baru) + `Set $set` util untuk nulis ke field lain.
- `->disabled()` + `->dehydrated()` → field tidak bisa diedit user tapi **tetap disubmit** dengan nilainya. Tanpa `->dehydrated()`, nilai disabled field tidak terkirim ke `handleRecordCreation()`.
- `modifyQueryUsing` membatasi pilihan quotation hanya yang berstatus Approved.
- **Tidak ada business logic di form reactivity ini.** Validasi (misal "satu quotation hanya boleh satu project") tetap di service atau di validation rules.

Pola yang sama bisa diterapkan di resource lain:

- `TransInvoiceHd`: pilih project → auto-fill client, company, quotation.
- `TransPayment`: pilih invoice → auto-fill client (via invoice).
- `MsProjectWorkflow`: pilih tipe project → tampilkan existing weight total biar user tahu sisa 100%.

## 6. Daftar Service yang Diperlukan untuk Sanitra

Berdasarkan [prd.md](prd.md) alur bisnis:

### 6.1 Quotation (`app/Services/Quotation/`)

| Service | Trigger | Fungsi |
| --- | --- | --- |
| `SendQuotationService` | Action "Send" di quotation Draft | Validasi total termin = 100%, ubah status Draft → Sent |
| `ApproveQuotationService` | Action "Approve" di quotation Sent | Ubah status Sent → Approved. **Tidak** auto-create project — project dibuat manual oleh user via form create project dengan pilih quotation Approved (§5.5) |
| `RejectQuotationService` | Action "Reject" di quotation Sent | Ubah status Sent → Rejected (bisa dengan alasan) |

### 6.2 Project (`app/Services/Project/`)

| Service | Trigger | Fungsi |
| --- | --- | --- |
| `CreateProjectStagesService` | Dipanggil di `handleRecordCreation` setelah HD dibuat | Copy workflow stages dari `ms_project_workflow` ke `trans_project_dt` berdasarkan `project_type_id` |
| `UpdateProjectStageService` | Edit tahap dari relation manager | Ubah status/tanggal tahap, hitung ulang `progress_percentage` di HD, buat log monitoring |
| `OverrideProjectStageService` | Action "Override" di tahap terkunci | Sama seperti update tapi hanya untuk role berizin, wajib alasan, catat log monitoring tipe override |
| `CloseProjectIfPaidService` | Dipanggil `RecordPaymentService` setelah payment confirmed | Cek progress 100% + semua termin Paid → ubah status project ke Closed |

### 6.3 Invoice (`app/Services/Invoice/`)

| Service | Trigger | Fungsi |
| --- | --- | --- |
| `CreateInvoiceFromTermsService` | Form create invoice (pilih termin Ready to Bill) | Buat invoice header, copy termin ke `trans_invoice_dt`, ubah status termin → Invoiced, lock tahap Completed terkait — **semua dalam satu DB transaction (NFR-04)** |
| `VoidInvoiceService` | Action "Void" di invoice | Ubah status invoice → Void, kembalikan termin ke Ready to Bill, unlock tahap yang tidak tercakup invoice aktif lain |

### 6.4 Payment (`app/Services/Payment/`)

| Service | Trigger | Fungsi |
| --- | --- | --- |
| `RecordPaymentService` | Form create payment di relation manager invoice | Simpan payment, hitung total Confirmed, kalau ≥ grand_total ubah invoice & termin ke Paid, panggil `CloseProjectIfPaidService` |
| `ConfirmPaymentService` | Action "Confirm" di payment pending | Ubah status payment → Confirmed, re-run perhitungan pelunasan |

## 7. Notification & Email

Pakai Laravel Notification. Jangan panggil `Mail::send()` langsung dari service.

Struktur:

```
app/Notifications/
├── QuotationApprovedNotification.php      # ke Teknis + PIC jika termin pertama bukan DP
├── TermReadyToBillNotification.php         # ke Finance saat termin flip ke Ready to Bill
├── FirstTermPaidNotification.php           # ke Teknis + PIC saat termin pertama Paid
└── InvoiceOverdueNotification.php          # ke Finance untuk invoice jatuh tempo (scheduled)
```

### 7.1 Pola trigger notification dari service

Trigger **setelah** `DB::transaction()` commit, bukan di dalam. Alasan: kalau email gagal/queue down, data sudah persisted — gak mau rollback cuma gara-gara email.

```php
public function execute(TransQuotationHd $quotation): TransQuotationHd
{
    $project = DB::transaction(function () use ($quotation) {
        // ... create project, copy workflow, dll.
        return $project;
    });

    // Notification di luar transaction
    $this->notifyTeamIfFirstTermNotDp($quotation, $project);

    return $project;
}

protected function notifyTeamIfFirstTermNotDp(TransQuotationHd $quotation, TransProjectHd $project): void
{
    $firstTerm = $quotation->terms()->orderBy('sequence')->first();

    if ($firstTerm && $firstTerm->progress_threshold > 0) {
        $recipients = $this->resolveTeknisAndPic($project);
        Notification::send($recipients, new QuotationApprovedNotification($quotation, $project));
    }
}
```

### 7.2 Queue atau sync?

SRS tidak eksplisit minta queue. Rekomendasi:

- **Tahap awal**: notification sync (`implements ShouldQueue` dilepas). Lebih sederhana, gak perlu worker.
- **Setelah volume naik**: tambah `implements ShouldQueue` + setup `queue:work`. Tidak butuh code change selain itu.

## 8. Observer — Dipakai Hanya untuk Guard Invariant

Observer boleh dipakai, tapi **jangan** untuk business logic. Hanya untuk:

### 8.1 Immutability guard

`TransProjectMonitoring` adalah log append-only (BR-10). Pakai observer untuk menolak update/delete:

```php
// app/Observers/TransProjectMonitoringObserver.php
class TransProjectMonitoringObserver
{
    public function updating(TransProjectMonitoring $model): bool
    {
        throw new \DomainException('Log monitoring tidak boleh diubah. Buat log baru untuk koreksi.');
    }

    public function deleting(TransProjectMonitoring $model): bool
    {
        throw new \DomainException('Log monitoring tidak boleh dihapus.');
    }
}
```

Register di `AppServiceProvider::boot()`:

```php
TransProjectMonitoring::observe(TransProjectMonitoringObserver::class);
```

### 8.2 Trait event (sudah ada)

Trait `AuditedBySoftDelete` sudah pakai pola model event di dalam trait untuk audit kolom. Pola ini valid karena:

- Cross-cutting, berlaku untuk semua model yang pakai trait.
- Logic sangat sederhana (set 1-3 kolom).
- Tidak punya side effect di luar model itu sendiri.

**Jangan tambah logic business ke `AuditedBySoftDelete`.**

## 9. Testing Service

Service harus bisa di-test sebagai unit, lepas dari Filament.

```php
// tests/Unit/Services/Project/CreateProjectStagesServiceTest.php
use App\Models\TransProjectHd;
use App\Models\MsProjectWorkflow;
use App\Models\MsProjectType;
use App\Services\Project\CreateProjectStagesService;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class CreateProjectStagesServiceTest extends TestCase
{
    public function test_copies_active_workflow_stages_to_project(): void
    {
        Auth::setUser(User::factory()->create()); // trait AuditedBySoftDelete butuh ini

        $type = MsProjectType::factory()->create();
        MsProjectWorkflow::factory()->count(3)->sequence(fn ($seq) => [
            'type_id' => $type->id,
            'sequence' => $seq->index + 1,
            'progress_weight' => 30 + $seq->index * 5,
            'active' => true,
        ])->create();

        $project = TransProjectHd::factory()->create(['project_type_id' => $type->id]);

        app(CreateProjectStagesService::class)->execute($project, $type->id);

        $this->assertSame(3, $project->stages()->count());
        $this->assertSame(1, $project->stages()->orderBy('sequence')->first()->sequence);
    }

    public function test_throws_when_workflow_not_set_for_type(): void
    {
        Auth::setUser(User::factory()->create());

        $type = MsProjectType::factory()->create(); // no workflow
        $project = TransProjectHd::factory()->create(['project_type_id' => $type->id]);

        $this->expectException(\DomainException::class);

        app(CreateProjectStagesService::class)->execute($project, $type->id);
    }

    public function test_throws_when_stages_already_exist(): void
    {
        Auth::setUser(User::factory()->create());

        $type = MsProjectType::factory()->create();
        MsProjectWorkflow::factory()->create(['type_id' => $type->id]);

        $project = TransProjectHd::factory()->create(['project_type_id' => $type->id]);
        $project->stages()->create([
            'project_workflow_id' => 1,
            'sequence' => 1,
            'work_name' => 'Existing',
            'progress_weight' => 100,
            'status_id' => 1,
            'editable' => true,
        ]);

        $this->expectException(\DomainException::class);

        app(CreateProjectStagesService::class)->execute($project, $type->id);
    }
}
```

Catatan:

- `Auth::setUser()` wajib di test karena trait `AuditedBySoftDelete` throw tanpa auth user.
- Factory state (`approved()`, `draft()`) mempermudah setup — buat state di factory sejak awal.
- Test fokus ke contract service (input → output + side effect), bukan ke internal implementation.

## 10. Catatan Pengembangan

- **Jangan mix pattern.** Satu use case = satu service. Jangan sebagian di observer, sebagian di hook.
- **Service tidak tahu Filament.** Service harus bisa dipanggil dari API, console command, atau test tanpa modifikasi.
- **Jangan panggil service dari observer.** Kalau butuh orchestration, panggil service dari atas (hook/controller), bukan dari observer.
- **Exception sebagai control flow.** Service lempar `DomainException` untuk business error. Filament akan menampilkan sebagai notification error otomatis.
- **Hindari `DB::beginTransaction()` manual.** Pakai closure `DB::transaction()` supaya rollback otomatis saat exception.
- **Notification di luar transaction.** Email/notif harus setelah commit, bukan di dalam closure.
- **Guard sebelum data access.** Validasi status dan kewenangan di awal method, sebelum touch DB.
