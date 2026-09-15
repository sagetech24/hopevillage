<?php

namespace Tests\Feature;

use App\Livewire\Events\AllEvents;
use App\Models\Event;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AllEventsIndexTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Location $location;

    protected Location $otherLocation;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['event.view', 'event.create', 'event.edit', 'event.delete'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $this->admin = User::factory()->create([
            'user_type' => 'admin',
        ]);
        $this->admin->givePermissionTo([
            'event.view',
            'event.create',
            'event.edit',
            'event.delete',
        ]);

        $this->location = Location::query()->create([
            'name' => 'Hope Hall',
            'is_active' => true,
            'location_code' => 'LOC-773A75BA',
        ]);

        $this->otherLocation = Location::query()->create([
            'name' => 'West Annex',
            'is_active' => true,
            'location_code' => 'LOC-WESTANNX',
        ]);

        $this->actingAs($this->admin);
    }

    public function test_all_events_page_renders_marketplace_style_controls(): void
    {
        $this->createEvent(['title' => 'Community Fair']);

        $this->get(route('admin.events.index'))
            ->assertOk()
            ->assertSee('Events')
            ->assertSee('View Locations')
            ->assertSee('Search events…')
            ->assertSee('Community Fair')
            ->assertSee('Hope Hall')
            ->assertSee('Card view')
            ->assertSee('List view');
    }

    public function test_search_filters_events_by_title(): void
    {
        $this->createEvent(['title' => 'Community Fair']);
        $this->createEvent(['title' => 'Language Workshop']);

        Livewire::test(AllEvents::class)
            ->set('search', 'Workshop')
            ->assertSee('Language Workshop')
            ->assertDontSee('Community Fair');
    }

    public function test_status_filter_limits_results(): void
    {
        $this->createEvent(['title' => 'Draft Picnic', 'status' => 'draft']);
        $this->createEvent(['title' => 'Published Concert', 'status' => 'published']);

        Livewire::test(AllEvents::class)
            ->set('statusFilter', 'published')
            ->assertSee('Published Concert')
            ->assertDontSee('Draft Picnic');
    }

    public function test_location_filter_limits_results(): void
    {
        $this->createEvent(['title' => 'Hall Fair']);
        $this->createEvent(['title' => 'Annex Mixer'], $this->otherLocation);

        Livewire::test(AllEvents::class)
            ->set('locationFilter', (string) $this->otherLocation->id)
            ->assertSee('Annex Mixer')
            ->assertDontSee('Hall Fair');
    }

    public function test_card_view_mode_can_be_toggled(): void
    {
        $this->createEvent(['title' => 'Community Fair']);

        Livewire::test(AllEvents::class)
            ->assertSet('viewMode', 'list')
            ->call('setViewMode', 'card')
            ->assertSet('viewMode', 'card')
            ->assertSee('Community Fair')
            ->assertSee('No image');
    }

    public function test_event_can_be_archived_and_restored(): void
    {
        $event = $this->createEvent(['title' => 'Community Fair']);

        Livewire::test(AllEvents::class)
            ->call('delete', $event->id)
            ->assertSee('Event archived successfully.');

        $this->assertSoftDeleted('events', ['id' => $event->id]);

        Livewire::test(AllEvents::class)
            ->set('statusFilter', 'deleted')
            ->assertSee('Community Fair')
            ->call('restore', $event->id)
            ->assertSee('Event restored successfully.');

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'deleted_at' => null,
        ]);
    }

    public function test_empty_filter_state_is_shown_when_nothing_matches(): void
    {
        $this->createEvent(['title' => 'Community Fair']);

        Livewire::test(AllEvents::class)
            ->set('search', 'does-not-exist')
            ->assertSee('No events match your filters.');
    }

    public function test_empty_state_links_to_locations_instead_of_add_event(): void
    {
        Livewire::test(AllEvents::class)
            ->assertSee('No events yet.')
            ->assertSee('View Locations')
            ->assertDontSee('Add event');
    }

    protected function createEvent(array $overrides = [], ?Location $location = null): Event
    {
        $location ??= $this->location;

        return Event::query()->create(array_merge([
            'location_id' => $location->id,
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
}
