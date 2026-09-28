<?php

namespace Tests\Feature;

use App\Livewire\Members\Index;
use App\Models\ActivityType;
use App\Models\Location;
use App\Models\MemberActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MembersIndexSortTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('member.view', 'web');
    }

    public function test_points_column_toggles_between_highest_and_lowest(): void
    {
        $this->actingAsAdmin();

        $low = User::factory()->create([
            'user_type' => 'member',
            'name' => 'Low Points',
            'total_points' => 10,
        ]);
        $high = User::factory()->create([
            'user_type' => 'member',
            'name' => 'High Points',
            'total_points' => 90,
        ]);

        $component = Livewire::test(Index::class)
            ->call('sortByPoints')
            ->assertSet('pointsSort', 'highest');

        $ordered = $component->viewData('members')->pluck('id')->all();
        $this->assertSame([$high->id, $low->id], array_slice($ordered, 0, 2));

        $component->call('sortByPoints')->assertSet('pointsSort', 'lowest');

        $ordered = $component->viewData('members')->pluck('id')->all();
        $this->assertSame([$low->id, $high->id], array_slice($ordered, 0, 2));
    }

    public function test_date_column_sort_clears_points_sort(): void
    {
        $this->actingAsAdmin();

        Livewire::test(Index::class)
            ->call('sortByPoints')
            ->assertSet('pointsSort', 'highest')
            ->call('sortByDate')
            ->assertSet('pointsSort', 'default')
            ->assertSet('dateSort', 'asc');
    }

    public function test_filters_hydrate_from_url_query_parameters(): void
    {
        $this->actingAsAdmin();

        Livewire::withQueryParams([
            'keyword' => 'Alice',
            'user_type' => 'merchant_user',
            'work_type' => 'Migrant worker',
            'sort' => 'highest',
            'date_sort' => 'asc',
        ])
            ->test(Index::class)
            ->assertSet('search', 'Alice')
            ->assertSet('userTypeFilter', 'merchant_user')
            ->assertSet('typeOfWorkFilter', 'Migrant worker')
            ->assertSet('pointsSort', 'highest')
            ->assertSet('dateSort', 'asc');
    }

    public function test_invalid_url_filter_values_fall_back_to_defaults(): void
    {
        $this->actingAsAdmin();

        Livewire::withQueryParams([
            'user_type' => 'not-a-type',
            'work_type' => 'unknown',
            'sort' => 'not-a-sort',
            'date_sort' => 'sideways',
        ])
            ->test(Index::class)
            ->assertSet('userTypeFilter', 'member')
            ->assertSet('typeOfWorkFilter', 'all')
            ->assertSet('pointsSort', 'default')
            ->assertSet('dateSort', 'desc');
    }

    public function test_url_query_parameters_filter_the_members_list(): void
    {
        $this->actingAsAdmin();

        User::factory()->create([
            'user_type' => 'member',
            'name' => 'Alice Member',
            'type_of_work' => 'Migrant worker',
            'total_points' => 10,
        ]);
        User::factory()->create([
            'user_type' => 'merchant_user',
            'name' => 'Bob Merchant',
            'type_of_work' => 'Migrant domestic worker',
            'total_points' => 50,
        ]);

        $this->get(route('admin.members.index', [
            'keyword' => 'Bob',
            'user_type' => 'merchant_user',
            'work_type' => 'Migrant domestic worker',
            'sort' => 'highest',
        ]))
            ->assertOk()
            ->assertSee('Bob Merchant')
            ->assertDontSee('Alice Member');
    }

    public function test_clear_filters_button_is_hidden_when_filters_are_default(): void
    {
        $this->actingAsAdmin();

        Livewire::test(Index::class)
            ->assertDontSee('wire:click="clearFilters"', false);
    }

    public function test_clear_filters_resets_url_backed_values(): void
    {
        $this->actingAsAdmin();

        Livewire::withQueryParams([
            'keyword' => 'Alice',
            'user_type' => 'merchant_user',
            'work_type' => 'Migrant worker',
            'sort' => 'highest',
            'date_sort' => 'asc',
        ])
            ->test(Index::class)
            ->assertSee('Clear')
            ->call('clearFilters')
            ->assertSet('search', '')
            ->assertSet('userTypeFilter', 'member')
            ->assertSet('typeOfWorkFilter', 'all')
            ->assertSet('pointsSort', 'default')
            ->assertSet('dateSort', 'desc')
            ->assertDontSee('wire:click="clearFilters"', false);
    }

    public function test_active_30_day_sort_ranks_members_by_recent_activity_count(): void
    {
        $this->actingAsAdmin();

        $inactive = User::factory()->create([
            'user_type' => 'member',
            'name' => 'Inactive Member',
            'total_points' => 100,
        ]);
        $olderActive = User::factory()->create([
            'user_type' => 'member',
            'name' => 'Older Active',
            'total_points' => 50,
        ]);
        $mostActive = User::factory()->create([
            'user_type' => 'member',
            'name' => 'Most Active',
            'total_points' => 10,
        ]);
        $lessActive = User::factory()->create([
            'user_type' => 'member',
            'name' => 'Less Active',
            'total_points' => 20,
        ]);

        $this->createMemberActivity($olderActive, now()->subDays(45));
        $this->createMemberActivity($mostActive, now()->subDays(2));
        $this->createMemberActivity($mostActive, now()->subDays(3));
        $this->createMemberActivity($mostActive, now()->subDays(4));
        $this->createMemberActivity($lessActive, now()->subDays(10));

        $component = Livewire::test(Index::class)
            ->set('pointsSort', 'active_30d');

        $ordered = $component->viewData('members')->pluck('id')->all();

        $this->assertSame([$mostActive->id, $lessActive->id], $ordered);
        $this->assertNotContains($inactive->id, $ordered);
        $this->assertNotContains($olderActive->id, $ordered);
        $component->assertSee('Activities (30d)')->assertSee('3');
    }

    public function test_active_90_day_sort_includes_members_active_beyond_30_days(): void
    {
        $this->actingAsAdmin();

        $olderActive = User::factory()->create([
            'user_type' => 'member',
            'name' => 'Older Active',
        ]);
        $recentActive = User::factory()->create([
            'user_type' => 'member',
            'name' => 'Recent Active',
        ]);
        $tooOld = User::factory()->create([
            'user_type' => 'member',
            'name' => 'Too Old',
        ]);

        $this->createMemberActivity($olderActive, now()->subDays(45), count: 2);
        $this->createMemberActivity($recentActive, now()->subDays(5));
        $this->createMemberActivity($tooOld, now()->subDays(120));

        $ordered = Livewire::test(Index::class)
            ->set('pointsSort', 'active_90d')
            ->viewData('members')
            ->pluck('id')
            ->all();

        $this->assertSame([$olderActive->id, $recentActive->id], $ordered);
        $this->assertNotContains($tooOld->id, $ordered);
    }

    public function test_active_members_csv_export_includes_only_the_filtered_list_and_activity_count(): void
    {
        $this->actingAsAdmin();

        $active = User::factory()->create([
            'user_type' => 'member',
            'name' => 'Export Active',
            'email' => 'export-active@example.com',
        ]);
        $inactive = User::factory()->create([
            'user_type' => 'member',
            'name' => 'Export Inactive',
            'email' => 'export-inactive@example.com',
        ]);

        $this->createMemberActivity($active, now()->subDays(2), count: 2);

        $response = Livewire::test(Index::class)
            ->set('pointsSort', 'active_30d')
            ->call('exportToCsv');

        $response->assertFileDownloaded();
        $csv = base64_decode($response->effects['download']['content'] ?? '');

        $this->assertStringContainsString('Activities (30d)', $csv);
        $this->assertStringContainsString('Export Active', $csv);
        $this->assertStringContainsString('export-active@example.com', $csv);
        $this->assertStringContainsString(',2', $csv);
        $this->assertStringNotContainsString('Export Inactive', $csv);
    }

    private ?ActivityType $activityType = null;

    private ?Location $location = null;

    protected function actingAsAdmin(): User
    {
        $admin = User::factory()->create(['user_type' => 'admin']);
        $admin->givePermissionTo('member.view');
        $this->actingAs($admin);

        return $admin;
    }

    private function createMemberActivity(User $member, $activityTime, int $count = 1): void
    {
        $this->activityType ??= ActivityType::query()->create([
            'name' => 'entry',
            'is_active' => true,
        ]);

        $this->location ??= Location::query()->create([
            'name' => 'Test Location',
            'is_active' => true,
            'location_code' => 'LOC-TEST01',
        ]);

        for ($i = 0; $i < $count; $i++) {
            MemberActivity::query()->create([
                'user_id' => $member->id,
                'activity_type_id' => $this->activityType->id,
                'location_id' => $this->location->id,
                'activity_time' => $activityTime->copy()->subMinutes($i),
            ]);
        }
    }
}
