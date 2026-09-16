<?php

namespace Tests\Feature;

use App\Livewire\Members\Profile;
use App\Models\ActivityType;
use App\Models\Location;
use App\Models\MemberActivity;
use App\Models\PointLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MemberProfileActivitiesTimelineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('member.profile', 'web');
        Permission::findOrCreate('can_void_member_activity', 'web');
    }

    public function test_profile_groups_activities_on_a_timeline(): void
    {
        $this->actingAsAdmin();
        $member = $this->createMember();
        $location = $this->createLocation();
        $type = $this->createActivityType('member_entry_location', 'Location entry');

        $this->createActivity($member, $type, $location, [
            'description' => 'Checked in at the main gate',
            'activity_time' => now()->subHours(2),
            'points' => 10,
        ]);
        $this->createActivity($member, $type, $location, [
            'description' => 'Yesterday evening visit',
            'activity_time' => now()->subDay()->setTime(18, 30),
        ]);

        Livewire::test(Profile::class, ['qr_code' => $member->qr_code])
            ->assertSee('Recent Member Activities')
            ->assertSee('Today')
            ->assertSee('Yesterday')
            ->assertSee('Checked in at the main gate')
            ->assertSee('Yesterday evening visit')
            ->assertSee('Hope Village Hall')
            ->assertSee('+10 pts')
            ->assertSee('Location entry')
            ->assertSee('Showing 2 of 2 activities')
            ->assertSee('All activities loaded')
            ->assertDontSee('Load more')
            ->assertDontSee('No activities yet');
    }

    public function test_profile_load_more_appends_the_next_page_of_activities(): void
    {
        $this->actingAsAdmin();
        $member = $this->createMember();
        $location = $this->createLocation();
        $type = $this->createActivityType();

        $total = Profile::ACTIVITIES_PAGE_SIZE + 3;

        for ($i = 1; $i <= $total; $i++) {
            $this->createActivity($member, $type, $location, [
                'description' => sprintf('Timeline item %02d', $i),
                'activity_time' => now()->subMinutes($i),
            ]);
        }

        Livewire::test(Profile::class, ['qr_code' => $member->qr_code])
            ->assertSee(sprintf('Timeline item %02d', 1))
            ->assertSee(sprintf('Timeline item %02d', Profile::ACTIVITIES_PAGE_SIZE))
            ->assertDontSee(sprintf('Timeline item %02d', Profile::ACTIVITIES_PAGE_SIZE + 1))
            ->assertSee('Load more')
            ->assertSee('Showing '.Profile::ACTIVITIES_PAGE_SIZE.' of '.$total.' activities')
            ->assertSet('activitiesPerPage', Profile::ACTIVITIES_PAGE_SIZE)
            ->call('loadMoreActivities')
            ->assertSet('activitiesPerPage', Profile::ACTIVITIES_PAGE_SIZE * 2)
            ->assertSee(sprintf('Timeline item %02d', Profile::ACTIVITIES_PAGE_SIZE + 1))
            ->assertSee('All activities loaded')
            ->assertDontSee('Load more');
    }

    public function test_profile_shows_voided_status_and_voucher_code(): void
    {
        $this->actingAsAdmin();
        $member = $this->createMember();
        $location = $this->createLocation();
        $type = $this->createActivityType('member_claim_voucher', 'Voucher claim');

        $this->createActivity($member, $type, $location, [
            'description' => 'Claimed meal voucher',
            'activity_time' => now()->subMinutes(5),
            'metadata' => [
                'voucher_code' => 'VOU-MEAL01',
                'status' => 'void',
            ],
            'points' => 5,
        ]);

        Livewire::test(Profile::class, ['qr_code' => $member->qr_code])
            ->assertSee('Claimed meal voucher')
            ->assertSee('VOU-MEAL01')
            ->assertSee('Void')
            ->assertSee('Voucher claim');
    }

    public function test_profile_empty_state_when_member_has_no_activities(): void
    {
        $this->actingAsAdmin();
        $member = $this->createMember();

        Livewire::test(Profile::class, ['qr_code' => $member->qr_code])
            ->assertSee('No activities yet')
            ->assertSee('This member has no recorded activities.')
            ->assertDontSee('Showing');
    }

    protected function actingAsAdmin(): User
    {
        $admin = User::factory()->create(['user_type' => 'admin']);
        $admin->givePermissionTo(['member.profile', 'can_void_member_activity']);
        $this->actingAs($admin);

        return $admin;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createMember(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'user_type' => 'member',
            'qr_code' => 'MEM-TL001',
        ], $attributes));
    }

    private function createLocation(): Location
    {
        return Location::query()->create([
            'name' => 'Hope Village Hall',
            'is_active' => true,
            'location_code' => 'LOC-PROF-TL',
        ]);
    }

    private function createActivityType(string $name = 'entry', string $description = 'Entry'): ActivityType
    {
        return ActivityType::query()->create([
            'name' => $name,
            'description' => $description,
            'is_active' => true,
        ]);
    }

    /**
     * @param  array{description?: string, activity_time?: \Carbon\CarbonInterface, metadata?: array<string, mixed>, points?: int}  $overrides
     */
    private function createActivity(
        User $member,
        ActivityType $type,
        Location $location,
        array $overrides = [],
    ): MemberActivity {
        $activity = MemberActivity::query()->create([
            'user_id' => $member->id,
            'activity_type_id' => $type->id,
            'location_id' => $location->id,
            'activity_time' => $overrides['activity_time'] ?? now(),
            'description' => $overrides['description'] ?? 'Activity',
            'metadata' => $overrides['metadata'] ?? null,
        ]);

        if (array_key_exists('points', $overrides)) {
            PointLog::query()->create([
                'user_id' => $member->id,
                'member_activity_id' => $activity->id,
                'activity_type_id' => $type->id,
                'location_id' => $location->id,
                'points' => $overrides['points'],
                'awarded_at' => $activity->activity_time,
            ]);
        }

        return $activity;
    }
}
