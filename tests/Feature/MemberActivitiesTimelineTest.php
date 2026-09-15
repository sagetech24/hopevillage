<?php

namespace Tests\Feature;

use App\Livewire\Member\Activities;
use App\Models\ActivityType;
use App\Models\Location;
use App\Models\MemberActivity;
use App\Models\PointLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MemberActivitiesTimelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_sees_own_activities_grouped_on_the_timeline(): void
    {
        $member = $this->createMember();
        $other = $this->createMember('other-member@example.com');
        $location = $this->createLocation();
        $type = $this->createActivityType('member_entry_location', 'Location entry');

        $this->createActivity($member, $type, $location, [
            'description' => 'Checked in at the main gate',
            'activity_time' => now()->subHours(2),
            'points' => 10,
        ]);
        $this->createActivity($other, $type, $location, [
            'description' => 'Should stay private',
            'activity_time' => now()->subHour(),
        ]);

        Livewire::actingAs($member)
            ->test(Activities::class)
            ->assertSee('Today')
            ->assertSee('Checked in at the main gate')
            ->assertSee('Hope Village Hall')
            ->assertSee('+10 pts')
            ->assertSee('Location entry')
            ->assertDontSee('Should stay private');
    }

    public function test_load_more_appends_the_next_page_of_activities(): void
    {
        $member = $this->createMember();
        $location = $this->createLocation();
        $type = $this->createActivityType();

        $total = Activities::PAGE_SIZE + 3;

        for ($i = 1; $i <= $total; $i++) {
            $this->createActivity($member, $type, $location, [
                'description' => sprintf('Timeline item %02d', $i),
                'activity_time' => now()->subMinutes($i),
            ]);
        }

        Livewire::actingAs($member)
            ->test(Activities::class)
            ->assertSee(sprintf('Timeline item %02d', 1))
            ->assertSee(sprintf('Timeline item %02d', Activities::PAGE_SIZE))
            ->assertDontSee(sprintf('Timeline item %02d', Activities::PAGE_SIZE + 1))
            ->assertSee('Load more')
            ->assertSee('Showing '.Activities::PAGE_SIZE.' of '.$total.' activities')
            ->call('loadMore')
            ->assertSee(sprintf('Timeline item %02d', Activities::PAGE_SIZE + 1))
            ->assertSee("You're all caught up")
            ->assertDontSee('Load more');
    }

    public function test_search_and_date_filter_reset_loaded_page_size(): void
    {
        $member = $this->createMember();
        $location = $this->createLocation();
        $type = $this->createActivityType();

        $this->createActivity($member, $type, $location, [
            'description' => 'Today check-in',
            'activity_time' => now()->subHour(),
        ]);
        $this->createActivity($member, $type, $location, [
            'description' => 'Last month visit',
            'activity_time' => now()->subDays(20),
        ]);

        Livewire::actingAs($member)
            ->test(Activities::class)
            ->assertSee('Today check-in')
            ->assertSee('Last month visit')
            ->call('setDateFilter', 'today')
            ->assertSet('perPage', Activities::PAGE_SIZE)
            ->assertSee('Today check-in')
            ->assertDontSee('Last month visit')
            ->set('search', 'month visit')
            ->call('setDateFilter', 'month')
            ->assertSee('Last month visit')
            ->assertDontSee('Today check-in');
    }

    public function test_voided_activities_and_voucher_codes_are_shown(): void
    {
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

        Livewire::actingAs($member)
            ->test(Activities::class)
            ->assertSee('Claimed meal voucher')
            ->assertSee('VOU-MEAL01')
            ->assertSee('Void');
    }

    private function createMember(string $email = 'member-activities@example.com'): User
    {
        return User::factory()->create([
            'user_type' => 'member',
            'email' => $email,
        ]);
    }

    private function createLocation(): Location
    {
        return Location::query()->create([
            'name' => 'Hope Village Hall',
            'is_active' => true,
            'location_code' => 'LOC-ACTTEST',
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
