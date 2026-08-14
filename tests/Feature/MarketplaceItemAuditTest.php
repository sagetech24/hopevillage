<?php

namespace Tests\Feature;

use App\Livewire\Marketplace\History;
use App\Livewire\Marketplace\Index;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceItem;
use App\Models\MarketplaceItemAudit;
use App\Models\User;
use App\Services\MarketplaceItemAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MarketplaceItemAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected MarketplaceCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['marketplace.view', 'marketplace.create', 'marketplace.edit', 'marketplace.delete'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $this->admin = User::factory()->create([
            'user_type' => 'admin',
        ]);
        $this->admin->givePermissionTo([
            'marketplace.view',
            'marketplace.create',
            'marketplace.edit',
            'marketplace.delete',
        ]);

        $this->category = MarketplaceCategory::query()->create([
            'name' => 'Snacks',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin);
    }

    public function test_creating_an_item_writes_a_created_audit_with_initial_values(): void
    {
        $item = $this->createItem([
            'points_cost' => 10,
            'amount_cost' => 0.50,
            'stock' => 100,
            'description' => 'White rice',
        ]);

        $audit = MarketplaceItemAudit::query()
            ->where('marketplace_item_id', $item->id)
            ->where('event', MarketplaceItemAudit::EVENT_CREATED)
            ->first();

        $this->assertNotNull($audit);
        $this->assertSame($this->admin->id, $audit->user_id);
        $this->assertNull($audit->changes['points_cost']['old']);
        $this->assertSame(10, $audit->changes['points_cost']['new']);
        $this->assertSame('0.50', $audit->changes['amount_cost']['new']);
        $this->assertSame(100, $audit->changes['stock']['new']);
        $this->assertSame('White rice', $audit->changes['description']['new']);
        $this->assertSame($this->category->id, $audit->changes['marketplace_category_id']['new']);
    }

    public function test_updating_points_cost_amount_stock_and_description_writes_old_and_new_values(): void
    {
        $item = $this->createItem([
            'points_cost' => 10,
            'amount_cost' => 0.50,
            'stock' => 100,
            'description' => 'White rice',
        ]);

        $item->update([
            'points_cost' => 25,
            'amount_cost' => 1.25,
            'stock' => 80,
            'description' => 'Brown rice',
        ]);

        $audit = MarketplaceItemAudit::query()
            ->where('marketplace_item_id', $item->id)
            ->where('event', MarketplaceItemAudit::EVENT_UPDATED)
            ->latest('id')
            ->first();

        $this->assertNotNull($audit);
        $this->assertSame($this->admin->id, $audit->user_id);
        $this->assertSame(10, $audit->changes['points_cost']['old']);
        $this->assertSame(25, $audit->changes['points_cost']['new']);
        $this->assertSame('0.50', $audit->changes['amount_cost']['old']);
        $this->assertSame('1.25', $audit->changes['amount_cost']['new']);
        $this->assertSame(100, $audit->changes['stock']['old']);
        $this->assertSame(80, $audit->changes['stock']['new']);
        $this->assertSame('White rice', $audit->changes['description']['old']);
        $this->assertSame('Brown rice', $audit->changes['description']['new']);
    }

    public function test_toggle_active_archive_and_restore_are_logged(): void
    {
        $item = $this->createItem();

        Livewire::test(Index::class)
            ->call('toggleActive', $item->id);

        $item->refresh();
        $this->assertFalse($item->is_active);

        $toggleAudit = MarketplaceItemAudit::query()
            ->where('marketplace_item_id', $item->id)
            ->where('event', MarketplaceItemAudit::EVENT_UPDATED)
            ->latest('id')
            ->first();

        $this->assertNotNull($toggleAudit);
        $this->assertTrue($toggleAudit->changes['is_active']['old']);
        $this->assertFalse($toggleAudit->changes['is_active']['new']);

        Livewire::test(Index::class)
            ->call('delete', $item->id);

        $this->assertSoftDeleted('marketplace_items', ['id' => $item->id]);
        $this->assertTrue(
            MarketplaceItemAudit::query()
                ->where('marketplace_item_id', $item->id)
                ->where('event', MarketplaceItemAudit::EVENT_ARCHIVED)
                ->exists()
        );

        Livewire::test(Index::class)
            ->call('restore', $item->id);

        $this->assertDatabaseHas('marketplace_items', [
            'id' => $item->id,
            'deleted_at' => null,
        ]);
        $this->assertTrue(
            MarketplaceItemAudit::query()
                ->where('marketplace_item_id', $item->id)
                ->where('event', MarketplaceItemAudit::EVENT_RESTORED)
                ->exists()
        );
    }

    public function test_history_page_is_forbidden_without_marketplace_access(): void
    {
        $item = $this->createItem();

        $adminWithoutAccess = User::factory()->create([
            'user_type' => 'admin',
        ]);

        $this->actingAs($adminWithoutAccess)
            ->get(route('admin.marketplace.history', $item->id))
            ->assertForbidden();
    }

    public function test_history_page_lists_audit_rows_for_admins_with_access(): void
    {
        $item = $this->createItem([
            'name' => 'Audit Trail Rice',
            'points_cost' => 10,
        ]);

        $item->update(['points_cost' => 15]);

        $this->get(route('admin.marketplace.history', $item->id))
            ->assertOk()
            ->assertSee('Audit Trail Rice')
            ->assertSee('Created')
            ->assertSee('Updated')
            ->assertSee('Points per item');

        Livewire::test(History::class, ['id' => $item->id])
            ->assertOk()
            ->assertSee('Audit Trail Rice')
            ->assertSee('10')
            ->assertSee('15');
    }

    public function test_location_changes_are_logged(): void
    {
        $item = $this->createItem();

        app(MarketplaceItemAuditService::class)->logLocationsChanged($item, [], [3, 1]);

        $audit = MarketplaceItemAudit::query()
            ->where('marketplace_item_id', $item->id)
            ->where('event', MarketplaceItemAudit::EVENT_LOCATIONS_CHANGED)
            ->first();

        $this->assertNotNull($audit);
        $this->assertSame([], $audit->changes['locations']['old']);
        $this->assertSame([1, 3], $audit->changes['locations']['new']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function createItem(array $overrides = []): MarketplaceItem
    {
        return MarketplaceItem::query()->create(array_merge([
            'name' => 'Rice',
            'description' => 'White rice',
            'marketplace_category_id' => $this->category->id,
            'points_cost' => 10,
            'amount_cost' => 0.50,
            'per_item_quantity' => 1,
            'stock' => 100,
            'daily_limit_quantity' => 2,
            'is_active' => true,
            'created_by' => $this->admin->id,
        ], $overrides));
    }
}
