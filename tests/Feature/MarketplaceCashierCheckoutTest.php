<?php

namespace Tests\Feature;

use App\Livewire\Marketplace\Cashier;
use App\Livewire\Marketplace\Index;
use App\Models\Location;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceItem;
use App\Models\MarketplaceOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MarketplaceCashierCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected MarketplaceCategory $category;

    protected Location $location;

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

        $this->location = Location::query()->create([
            'name' => 'Main Hall',
            'is_active' => true,
            'location_code' => 'LOC-TEST01',
        ]);

        $this->actingAs($this->admin);
    }

    public function test_checkout_items_survive_a_page_reload(): void
    {
        $item = $this->createItem();

        Livewire::test(Cashier::class)
            ->call('addToBasket', $item->id)
            ->call('beginCheckoutSelected')
            ->assertSet('awaitingMemberPayment', true);

        $reloaded = Livewire::test(Cashier::class);

        $this->assertTrue($reloaded->get('awaitingMemberPayment'));
        $this->assertCount(1, $reloaded->get('pendingLines'));
        $this->assertSame($item->id, (int) $reloaded->get('pendingLines')[0]['marketplace_item_id']);
        $this->assertSame(10, $reloaded->get('pendingPointsTotal'));
        $this->assertNull($reloaded->get('resolvedMemberId'));
        $this->assertSame('', $reloaded->get('memberQrInput'));
    }

    public function test_scanning_a_member_qr_charges_and_keeps_the_same_checkout_items(): void
    {
        $item = $this->createItem(['stock' => 20]);
        $memberA = $this->createMember('MEM-AAA', 50);
        $memberB = $this->createMember('MEM-BBB', 50);

        $component = Livewire::test(Cashier::class)
            ->call('addToBasket', $item->id)
            ->call('beginCheckoutSelected')
            ->call('onQrCodeScanned', 'MEM-AAA');

        $component
            ->assertSet('awaitingMemberPayment', true)
            ->assertSet('resolvedMemberId', null)
            ->assertSet('memberQrInput', '');

        $this->assertNotNull($component->get('lastSaleMessage'));
        $this->assertSame(40, $memberA->fresh()->total_points);
        $this->assertSame(1, MarketplaceOrder::query()->where('user_id', $memberA->id)->count());

        $component->assertDispatched('cashier-charge-result');

        $component->call('onQrCodeScanned', 'MEM-BBB');

        $this->assertTrue($component->get('awaitingMemberPayment'));
        $this->assertCount(1, $component->get('pendingLines'));
        $this->assertSame(40, $memberB->fresh()->total_points);
        $this->assertSame(2, MarketplaceOrder::query()->count());
    }

    public function test_the_same_qr_code_is_ignored_during_the_cooldown(): void
    {
        $item = $this->createItem(['stock' => 20]);
        $member = $this->createMember('MEM-AAA', 50);

        $component = Livewire::test(Cashier::class)
            ->call('addToBasket', $item->id)
            ->call('beginCheckoutSelected')
            ->call('onQrCodeScanned', 'MEM-AAA')
            ->call('onQrCodeScanned', 'MEM-AAA');

        $this->assertSame(1, MarketplaceOrder::query()->where('user_id', $member->id)->count());
        $this->assertSame(40, $member->fresh()->total_points);
        $this->assertTrue($component->get('awaitingMemberPayment'));
    }

    public function test_confirm_payment_keeps_checkout_items_for_the_next_member(): void
    {
        $item = $this->createItem();
        $member = $this->createMember('MEM-AAA', 50);

        Livewire::test(Cashier::class)
            ->call('addToBasket', $item->id)
            ->call('beginCheckoutSelected')
            ->set('memberQrInput', 'MEM-AAA')
            ->call('lookupMember')
            ->call('confirmPayment')
            ->assertSet('awaitingMemberPayment', true)
            ->assertSet('resolvedMemberId', null);

        $this->assertSame(1, MarketplaceOrder::query()->where('user_id', $member->id)->count());
        $this->assertSame(40, $member->fresh()->total_points);
    }

    public function test_back_to_basket_restores_checkout_items(): void
    {
        $item = $this->createItem();

        $component = Livewire::test(Cashier::class)
            ->call('addToBasket', $item->id)
            ->call('beginCheckoutSelected')
            ->call('cancelPayment');

        $this->assertFalse($component->get('awaitingMemberPayment'));
        $this->assertSame([], $component->get('pendingLines'));
        $this->assertCount(1, $component->get('basket'));
        $this->assertSame($item->id, (int) $component->get('basket')[0]['marketplace_item_id']);
    }

    public function test_unknown_qr_does_not_leave_checkout(): void
    {
        $item = $this->createItem();

        Livewire::test(Cashier::class)
            ->call('addToBasket', $item->id)
            ->call('beginCheckoutSelected')
            ->call('onQrCodeScanned', 'UNKNOWN')
            ->assertSet('awaitingMemberPayment', true)
            ->assertSet('resolvedMemberId', null)
            ->assertDispatched('cashier-charge-result');

        $this->assertSame(0, MarketplaceOrder::query()->count());
    }

    public function test_insufficient_points_dispatches_a_fail_charge_result_instead_of_a_toast(): void
    {
        $item = $this->createItem();
        $this->createMember('MEM-AAA', 5);

        Livewire::test(Cashier::class)
            ->call('addToBasket', $item->id)
            ->call('beginCheckoutSelected')
            ->call('onQrCodeScanned', 'MEM-AAA')
            ->assertDispatched('cashier-charge-result')
            ->assertSet('awaitingMemberPayment', true);

        $this->assertSame(0, MarketplaceOrder::query()->count());
    }

    public function test_cashier_special_permission_allows_cashier_without_marketplace_module_access(): void
    {
        Permission::findOrCreate('can_access_marketplace_cashier', 'web');

        $cashier = User::factory()->create(['user_type' => 'admin']);
        $cashier->givePermissionTo('can_access_marketplace_cashier');

        $this->actingAs($cashier);

        $this->assertTrue($cashier->canAccessMarketplaceCashier());
        $this->assertFalse($cashier->canAccessAdminMarketplace());

        $this->get(route('admin.marketplace.cashier'))->assertOk();
        Livewire::test(Cashier::class)->assertOk();
    }

    public function test_cashier_special_permission_does_not_grant_marketplace_admin_access(): void
    {
        Permission::findOrCreate('can_access_marketplace_cashier', 'web');

        $cashier = User::factory()->create(['user_type' => 'admin']);
        $cashier->givePermissionTo('can_access_marketplace_cashier');

        $this->actingAs($cashier);

        $this->get(route('admin.marketplace.index'))->assertForbidden();
        Livewire::test(Index::class)->assertForbidden();
    }

    public function test_user_without_cashier_permission_cannot_access_cashier(): void
    {
        $user = User::factory()->create(['user_type' => 'admin']);

        $this->actingAs($user);

        $this->get(route('admin.marketplace.cashier'))->assertForbidden();
        Livewire::test(Cashier::class)->assertForbidden();
    }

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
            'daily_limit_quantity' => null,
            'is_active' => true,
            'created_by' => $this->admin->id,
        ], $overrides));
    }

    protected function createMember(string $qrCode, int $points): User
    {
        return User::factory()->create([
            'user_type' => 'member',
            'qr_code' => $qrCode,
            'total_points' => $points,
        ]);
    }
}
