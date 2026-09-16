<?php

namespace Tests\Feature;

use App\Livewire\Locations\Index;
use App\Models\Event;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class LocationsIndexTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ([
            'location.view',
            'location.create',
            'location.edit',
            'location.delete',
            'location.profile',
            'event.view',
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $this->admin = User::factory()->create([
            'user_type' => 'admin',
        ]);
        $this->admin->givePermissionTo([
            'location.view',
            'location.create',
            'location.edit',
            'location.delete',
            'location.profile',
            'event.view',
        ]);

        $this->actingAs($this->admin);
    }

    public function test_locations_page_renders_marketplace_style_controls(): void
    {
        $this->createLocation(['name' => 'Hope Hall']);

        $this->get(route('admin.locations.index'))
            ->assertOk()
            ->assertSee('Locations')
            ->assertSee('Add location')
            ->assertSee('View Events')
            ->assertSee('Search locations…')
            ->assertSee('Hope Hall')
            ->assertSee('Card view')
            ->assertSee('List view');
    }

    public function test_search_filters_locations_by_name(): void
    {
        $this->createLocation(['name' => 'Hope Hall']);
        $this->createLocation(['name' => 'East Community Centre']);

        Livewire::test(Index::class)
            ->set('search', 'East')
            ->assertSee('East Community Centre')
            ->assertDontSee('Hope Hall');
    }

    public function test_status_filter_limits_results(): void
    {
        $this->createLocation(['name' => 'Active Hall', 'is_active' => true]);
        $this->createLocation(['name' => 'Quiet Wing', 'is_active' => false]);

        Livewire::test(Index::class)
            ->set('statusFilter', 'active')
            ->assertSee('Active Hall')
            ->assertDontSee('Quiet Wing');
    }

    public function test_list_view_mode_can_be_toggled(): void
    {
        $this->createLocation(['name' => 'Hope Hall']);

        Livewire::test(Index::class)
            ->assertSet('viewMode', 'card')
            ->call('setViewMode', 'list')
            ->assertSet('viewMode', 'list')
            ->assertSee('Hope Hall')
            ->assertSee('Address')
            ->assertSee('Contact');
    }

    public function test_location_can_be_archived_and_restored(): void
    {
        $location = $this->createLocation(['name' => 'Hope Hall']);

        Livewire::test(Index::class)
            ->call('delete', $location->id)
            ->assertSee('Location archived successfully.');

        $this->assertSoftDeleted('locations', ['id' => $location->id]);

        Livewire::test(Index::class)
            ->set('statusFilter', 'archived')
            ->assertSee('Hope Hall')
            ->call('restore', $location->id)
            ->assertSee('Location restored successfully.');

        $this->assertDatabaseHas('locations', [
            'id' => $location->id,
            'deleted_at' => null,
        ]);
    }

    public function test_empty_filter_state_is_shown_when_nothing_matches(): void
    {
        $this->createLocation(['name' => 'Hope Hall']);

        Livewire::test(Index::class)
            ->set('search', 'does-not-exist')
            ->assertSee('No locations match your filters.');
    }

    public function test_event_count_is_shown_for_each_location(): void
    {
        $location = $this->createLocation(['name' => 'Hope Hall']);

        Event::query()->create([
            'location_id' => $location->id,
            'created_by' => $this->admin->id,
            'title' => 'Community Fair',
            'description' => 'A gathering for members',
            'start_date' => now()->addDay()->setTime(10, 0),
            'end_date' => now()->addDay()->setTime(13, 0),
            'venue' => 'Main Hall',
            'max_participants' => 50,
            'status' => 'published',
        ]);

        $component = Livewire::test(Index::class)
            ->assertSee('Hope Hall')
            ->assertSee('Events');

        $this->assertSame(1, $component->viewData('locations')->first()->events_count);
    }

    protected function createLocation(array $overrides = []): Location
    {
        return Location::query()->create(array_merge([
            'name' => 'Hope Hall',
            'address' => '123 Hope Street',
            'city' => 'Singapore',
            'province' => 'Singapore',
            'postal_code' => '123456',
            'email' => 'hope@example.com',
            'phone' => '91234567',
            'is_active' => true,
        ], $overrides));
    }
}
