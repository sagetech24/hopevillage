<?php

namespace Tests\Feature;

use App\Models\ActivityType;
use App\Models\Event;
use App\Models\Location;
use App\Models\MemberActivity;
use App\Models\PointLog;
use App\Models\User;
use App\Services\PointsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AwardEventJoinCreatesActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_award_event_join_creates_timeline_activity_and_links_point_log(): void
    {
        $member = User::factory()->create([
            'user_type' => 'member',
            'qr_code' => 'JOIN-TL-001',
            'total_points' => 0,
        ]);

        $location = Location::query()->create([
            'name' => 'Hope Village Hall',
            'is_active' => true,
            'location_code' => 'LOC-JOIN-TL',
        ]);

        ActivityType::query()->create([
            'name' => PointsService::ACTIVITY_EVENT_JOIN,
            'description' => 'Member join event',
            'is_active' => true,
        ]);

        $admin = User::factory()->create(['user_type' => 'admin']);

        $event = Event::query()->create([
            'location_id' => $location->id,
            'created_by' => $admin->id,
            'title' => 'Music Series III',
            'description' => 'Evening concert',
            'start_date' => now()->addDay(),
            'end_date' => now()->addDay()->addHours(2),
            'venue' => 'Main Hall',
            'max_participants' => 100,
            'status' => 'published',
        ]);

        app(PointsService::class)->awardEventJoin($member, $event);

        $member->refresh();
        $this->assertSame(10, (int) $member->total_points);

        $activity = MemberActivity::query()
            ->where('user_id', $member->id)
            ->where('event_id', $event->id)
            ->whereHas('activityType', fn ($q) => $q->where('name', PointsService::ACTIVITY_EVENT_JOIN))
            ->first();

        $this->assertNotNull($activity);
        $this->assertSame('Member joined event: Music Series III', $activity->description);
        $this->assertSame($event->id, $activity->event_id);
        $this->assertSame($event->event_code, $activity->metadata['event_code'] ?? null);

        $pointLog = PointLog::query()
            ->where('user_id', $member->id)
            ->where('member_activity_id', $activity->id)
            ->first();

        $this->assertNotNull($pointLog);
        $this->assertSame(10, (int) $pointLog->points);
    }

    public function test_award_event_join_is_idempotent_for_same_member_and_event(): void
    {
        $member = User::factory()->create([
            'user_type' => 'member',
            'qr_code' => 'JOIN-TL-002',
            'total_points' => 0,
        ]);

        $location = Location::query()->create([
            'name' => 'Hope Village Hall',
            'is_active' => true,
            'location_code' => 'LOC-JOIN-2',
        ]);

        ActivityType::query()->create([
            'name' => PointsService::ACTIVITY_EVENT_JOIN,
            'description' => 'Member join event',
            'is_active' => true,
        ]);

        $admin = User::factory()->create(['user_type' => 'admin']);

        $event = Event::query()->create([
            'location_id' => $location->id,
            'created_by' => $admin->id,
            'title' => 'Deepavali Carnival',
            'description' => 'Festival',
            'start_date' => now()->addDay(),
            'end_date' => now()->addDay()->addHours(4),
            'venue' => 'Courtyard',
            'max_participants' => 200,
            'status' => 'published',
        ]);

        $service = app(PointsService::class);
        $service->awardEventJoin($member, $event);
        $service->awardEventJoin($member, $event);

        $this->assertSame(1, MemberActivity::query()->where('user_id', $member->id)->count());
        $this->assertSame(1, PointLog::query()->where('user_id', $member->id)->count());
        $this->assertSame(10, (int) $member->fresh()->total_points);
    }
}
