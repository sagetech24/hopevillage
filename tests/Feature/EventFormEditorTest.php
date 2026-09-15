<?php

namespace Tests\Feature;

use App\Livewire\Events\Form;
use App\Models\Event;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class EventFormEditorTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Location $location;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['event.view', 'event.create', 'event.edit', 'event.profile'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $this->admin = User::factory()->create([
            'user_type' => 'admin',
        ]);
        $this->admin->givePermissionTo([
            'event.view',
            'event.create',
            'event.edit',
            'event.profile',
        ]);

        $this->location = Location::query()->create([
            'name' => 'Hope Hall',
            'is_active' => true,
            'location_code' => 'LOC-773A75BA',
        ]);

        $this->actingAs($this->admin);
    }

    public function test_create_form_renders_wysiwyg_editor_for_description(): void
    {
        $this->get(route('admin.locations.events.create', $this->location->location_code))
            ->assertOk()
            ->assertSee('entangle(\'description\')', false)
            ->assertDontSee('id="description"', false);
    }

    public function test_edit_form_loads_existing_description_into_editor(): void
    {
        $event = $this->createEvent([
            'description' => '<p>Welcome to <strong>Hope Village</strong></p>',
        ]);

        Livewire::test(Form::class, [
            'location_code' => $this->location->location_code,
            'id' => $event->id,
        ])
            ->assertSet('description', '<p>Welcome to <strong>Hope Village</strong></p>')
            ->assertSee('entangle(\'description\')', false);
    }

    public function test_saving_html_description_persists_and_renders_on_profile(): void
    {
        $event = $this->createEvent(['description' => 'Plain text']);

        Livewire::test(Form::class, [
            'location_code' => $this->location->location_code,
            'id' => $event->id,
        ])
            ->set('description', '<p>Join us for a <em>community</em> picnic.</p>')
            ->call('save')
            ->assertRedirect(route('admin.locations.events.index', $this->location->location_code));

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'description' => '<p>Join us for a <em>community</em> picnic.</p>',
        ]);

        $this->get(route('admin.events.profile', $event->fresh()->event_code))
            ->assertOk()
            ->assertSee('<em>community</em>', false)
            ->assertDontSee('&lt;em&gt;community&lt;/em&gt;');
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
}
