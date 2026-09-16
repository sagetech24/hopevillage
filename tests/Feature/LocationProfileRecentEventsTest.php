<?php

namespace Tests\Feature;

use App\Livewire\Locations\Profile;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Location;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class LocationProfileRecentEventsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Location $location;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-09-16 11:00:00'));

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::findOrCreate('location.profile', 'web');

        $this->admin = User::factory()->create([
            'user_type' => 'admin',
        ]);
        $this->admin->givePermissionTo('location.profile');

        $this->location = Location::query()->create([
            'name' => 'Hope Hall',
            'is_active' => true,
            'location_code' => 'LOC-773A75BA',
        ]);

        $this->actingAs($this->admin);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_recent_events_prioritize_current_and_upcoming_over_newest_created(): void
    {
        $this->createEvent([
            'title' => 'Ancient Event',
            'start_date' => now()->subDays(30)->setTime(10, 0),
            'end_date' => now()->subDays(30)->setTime(13, 0),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->createEvent([
            'title' => 'Old Fair',
            'start_date' => now()->subDays(10)->setTime(10, 0),
            'end_date' => now()->subDays(10)->setTime(13, 0),
            'created_at' => now()->subDays(9),
        ]);
        $this->createEvent([
            'title' => 'Yesterday Workshop',
            'start_date' => now()->subDay()->setTime(10, 0),
            'end_date' => now()->subDay()->setTime(13, 0),
            'created_at' => now()->subDays(2),
        ]);
        $this->createEvent([
            'title' => 'Live Concert',
            'start_date' => now()->setTime(10, 0),
            'end_date' => now()->setTime(15, 0),
            'created_at' => now()->subDays(5),
        ]);
        $this->createEvent([
            'title' => 'Tomorrow Picnic',
            'start_date' => now()->addDay()->setTime(10, 0),
            'end_date' => now()->addDay()->setTime(13, 0),
            'created_at' => now()->subDays(4),
        ]);
        $this->createEvent([
            'title' => 'Later Meetup',
            'start_date' => now()->addDays(10)->setTime(10, 0),
            'end_date' => now()->addDays(10)->setTime(13, 0),
            'created_at' => now()->subDays(3),
        ]);

        Livewire::test(Profile::class, ['location_code' => $this->location->location_code])
            ->assertSeeInOrder([
                'Live Concert',
                'Tomorrow Picnic',
                'Later Meetup',
                'Yesterday Workshop',
                'Old Fair',
            ])
            ->assertDontSee('Ancient Event');
    }

    public function test_recent_events_show_schedule_venue_and_registration_counts(): void
    {
        $event = $this->createEvent([
            'title' => 'Community Fair',
            'description' => '<p>A gathering for members</p>',
            'venue' => 'Main Hall',
            'max_participants' => 50,
            'status' => 'published',
            'start_date' => now()->addDay()->setTime(10, 0),
            'end_date' => now()->addDay()->setTime(13, 0),
        ]);

        $this->createRegistration($event, 'registered');
        $this->createRegistration($event, 'registered');
        $this->createRegistration($event, 'attended');

        Livewire::test(Profile::class, ['location_code' => $this->location->location_code])
            ->assertSeeHtml('flex flex-col sm:flex-row')
            ->assertSeeHtml('flex flex-col sm:flex-row sm:items-start sm:justify-between')
            ->assertSee('Community Fair')
            ->assertSee('Upcoming')
            ->assertSee('Published')
            ->assertSee('Accepting registrations')
            ->assertSee('Main Hall')
            ->assertSee('17 Sep 2026 · 10:00 AM – 1:00 PM')
            ->assertSee('Registrations')
            ->assertSee('/ 50')
            ->assertSee('Attended')
            ->assertDontSee('Still Accepting Registrations');
    }

    public function test_cancelled_and_ended_events_are_not_marked_as_accepting_registrations(): void
    {
        $this->createEvent([
            'title' => 'Cancelled Picnic',
            'status' => 'cancelled',
            'start_date' => now()->addDay()->setTime(10, 0),
            'end_date' => now()->addDay()->setTime(13, 0),
        ]);
        $this->createEvent([
            'title' => 'Finished Class',
            'status' => 'published',
            'start_date' => now()->subDay()->setTime(10, 0),
            'end_date' => now()->subDay()->setTime(13, 0),
        ]);

        Livewire::test(Profile::class, ['location_code' => $this->location->location_code])
            ->assertSee('Cancelled Picnic')
            ->assertSee('Finished Class')
            ->assertSee('Ended')
            ->assertDontSee('Accepting registrations');
    }

    public function test_full_upcoming_event_shows_full_instead_of_accepting(): void
    {
        $event = $this->createEvent([
            'title' => 'Packed Workshop',
            'status' => 'published',
            'max_participants' => 1,
            'start_date' => now()->addDay()->setTime(10, 0),
            'end_date' => now()->addDay()->setTime(13, 0),
        ]);

        $this->createRegistration($event, 'registered');

        Livewire::test(Profile::class, ['location_code' => $this->location->location_code])
            ->assertSee('Packed Workshop')
            ->assertSee('Full')
            ->assertDontSee('Accepting registrations');
    }

    protected function createEvent(array $overrides = []): Event
    {
        return Event::query()->create(array_merge([
            'location_id' => $this->location->id,
            'created_by' => $this->admin->id,
            'title' => 'Community Fair',
            'description' => 'A gathering for members',
            'start_date' => now()->addDay()->setTime(10, 0),
            'end_date' => now()->addDay()->setTime(13, 0),
            'venue' => 'Main Hall',
            'max_participants' => 50,
            'status' => 'published',
        ], $overrides));
    }

    protected function createRegistration(Event $event, string $status): EventRegistration
    {
        return EventRegistration::query()->create([
            'user_id' => User::factory()->create(['user_type' => 'member'])->id,
            'event_id' => $event->id,
            'type' => 'app',
            'status' => $status,
            'registered_at' => now(),
            'attended_at' => $status === 'attended' ? now() : null,
        ]);
    }
}
