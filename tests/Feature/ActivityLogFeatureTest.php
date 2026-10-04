<?php

namespace Tests\Feature;

use App\Filament\Resources\ActivityLogs\ActivityLogResource;
use App\Models\MsStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ActivityLogFeatureTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_master_changes_record_actor_and_field_diff(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $status = MsStatus::create([
            'type' => 'quotation',
            'code' => 'draft',
            'name' => 'Draft',
        ]);
        $status->update(['name' => 'Menunggu Persetujuan']);
        $status->delete();
        $status->restore();

        $activities = Activity::query()->where('subject_type', MsStatus::class)->where('subject_id', $status->id)->orderBy('id')->get();

        $this->assertSame(['created', 'updated', 'deleted', 'restored'], $activities->pluck('event')->all());
        $this->assertSame([$user->id, $user->id, $user->id, $user->id], $activities->pluck('causer_id')->all());
        $this->assertSame('Draft', $activities[1]->attribute_changes->get('old')['name']);
        $this->assertSame('Menunggu Persetujuan', $activities[1]->attribute_changes->get('attributes')['name']);
        $this->assertArrayNotHasKey('updated_by', $activities[1]->attribute_changes->get('attributes'));
    }

    public function test_activity_log_pages_are_read_only(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(ActivityLogResource::getUrl('index'))->assertOk();

        $this->assertFalse(Route::has('filament.dashboard.resources.activity-logs.create'));
        $this->assertFalse(Route::has('filament.dashboard.resources.activity-logs.edit'));
    }

    public function test_activity_log_view_shows_before_and_after_values(): void
    {
        $this->actingAs(User::factory()->create());

        $status = MsStatus::create([
            'type' => 'quotation',
            'code' => 'draft',
            'name' => 'Draft',
        ]);
        $status->update(['name' => 'Disetujui']);
        $activity = Activity::query()->where('subject_type', MsStatus::class)->where('event', 'updated')->firstOrFail();

        $this->get(ActivityLogResource::getUrl('view', ['record' => $activity]))
            ->assertSee('Draft')
            ->assertSee('Disetujui')
            ->assertSee('Nilai Sebelum')
            ->assertSee('Nilai Sesudah');
    }
}
