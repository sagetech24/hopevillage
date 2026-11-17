<?php

namespace Tests\Feature;

use App\Livewire\Members\SetActivityVoidButton;
use App\Models\ActivityType;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Location;
use App\Models\MemberActivity;
use App\Models\PointLog;
use App\Models\User;
use App\Services\PointsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MemberActivityVoidUndoTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_void_and_undo_event_join_activity(): void
    {
        $admin = User::factory()->create(['user_type' => 'admin']);
        $member = User::factory()->create([
            'user_type' => 'member',
            'qr_code' => 'VOID-UNDO-1',
            'total_points' => 0,
        ]);

        $location = Location::query()->create([
            'name' => 'Hope Village Hall',
            'is_active' => true,
            'location_code' => 'LOC-VU1',
        ]);

        ActivityType::query()->create([
            'name' => PointsService::ACTIVITY_EVENT_JOIN,
            'description' => 'Member join event',
            'is_active' => true,
        ]);

        $event = Event::query()->create([
            'location_id' => $location->id,
            'created_by' => $admin->id,
            'title' => 'Music Series Undo Test',
            'description' => 'Test event',
            'start_date' => now()->addDay(),
            'end_date' => now()->addDay()->addHours(2),
            'venue' => 'Main Hall',
            'max_participants' => 50,
            'status' => 'published',
        ]);

        app(PointsService::class)->awardEventJoin($member, $event);

        EventRegistration::query()->create([
            'user_id' => $member->id,
            'event_id' => $event->id,
            'type' => 'app',
            'status' => 'registered',
            'registered_at' => now(),
        ]);

        $activity = MemberActivity::query()
            ->where('user_id', $member->id)
            ->where('event_id', $event->id)
            ->firstOrFail();

        $this->assertSame(10, (int) $member->fresh()->total_points);

        $this->actingAs($admin);

        Livewire::test(SetActivityVoidButton::class, ['memberActivity' => $activity])
            ->call('setAsVoid')
            ->assertSet('showMessage', true)
            ->assertSee('Undo void');

        $activity->refresh();
        $this->assertSame('void', $activity->metadata['status'] ?? null);
        $this->assertSame(0, (int) $member->fresh()->total_points);
        $this->assertFalse(
            EventRegistration::query()
                ->where('user_id', $member->id)
                ->where('event_id', $event->id)
                ->exists()
        );

        Livewire::test(SetActivityVoidButton::class, ['memberActivity' => $activity->fresh()])
            ->call('undoVoid')
            ->assertSet('showMessage', true)
            ->assertSee('Click to void this activity');

        $activity->refresh();
        $this->assertArrayNotHasKey('status', $activity->metadata ?? []);
        $this->assertSame(10, (int) $member->fresh()->total_points);
        $this->assertTrue(
            EventRegistration::query()
                ->where('user_id', $member->id)
                ->where('event_id', $event->id)
                ->where('status', 'registered')
                ->exists()
        );

        $restoreLog = PointLog::query()
            ->where('member_activity_id', $activity->id)
            ->where('description', 'like', '%void undone%')
            ->first();
        $this->assertNotNull($restoreLog);
        $this->assertSame(10, (int) $restoreLog->points);
    }

    public function test_undo_restores_points_for_legacy_void_without_void_context(): void
    {
        $admin = User::factory()->create(['user_type' => 'admin']);
        $member = User::factory()->create([
            'user_type' => 'member',
            'qr_code' => 'VOID-UNDO-2',
            'total_points' => 0,
        ]);

        $location = Location::query()->create([
            'name' => 'Hope Village Hall',
            'is_active' => true,
            'location_code' => 'LOC-VU2',
        ]);

        $type = ActivityType::query()->create([
            'name' => 'member_registration',
            'description' => 'Member Registration',
            'is_active' => true,
        ]);

        $activity = MemberActivity::query()->create([
            'user_id' => $member->id,
            'activity_type_id' => $type->id,
            'location_id' => $location->id,
            'activity_time' => now(),
            'description' => 'Member account created.',
            'metadata' => ['status' => 'void'],
        ]);

        PointLog::query()->create([
            'user_id' => $member->id,
            'member_activity_id' => $activity->id,
            'activity_type_id' => $type->id,
            'location_id' => $location->id,
            'points' => 10,
            'description' => 'Member account created',
            'awarded_at' => now()->subMinute(),
        ]);

        PointLog::query()->create([
            'user_id' => $member->id,
            'member_activity_id' => $activity->id,
            'activity_type_id' => $type->id,
            'location_id' => $location->id,
            'points' => -10,
            'description' => 'Points reversed - activity voided (Member Activity #'.$activity->id.') - with trx code: LEGACY01',
            'awarded_at' => now(),
        ]);

        $this->actingAs($admin);

        Livewire::test(SetActivityVoidButton::class, ['memberActivity' => $activity])
            ->call('undoVoid')
            ->assertSet('showMessage', true);

        $this->assertSame(10, (int) $member->fresh()->total_points);
        $this->assertArrayNotHasKey('status', $activity->fresh()->metadata ?? []);
    }
}
