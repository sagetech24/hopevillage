<?php

namespace Tests\Feature;

use App\Livewire\Vouchers\Index;
use App\Models\Merchant;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminMerchantVouchersIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('voucher.view', 'web');
    }

    public function test_pending_filter_shows_unapproved_vouchers_and_hides_other_groups(): void
    {
        $this->actingAsAdmin();
        $merchant = $this->createMerchant();

        $pending = $this->createVoucher($merchant, [
            'name' => 'Awaiting Review',
            'is_active' => false,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addWeek(),
        ]);
        $scheduledUnapproved = $this->createVoucher($merchant, [
            'name' => 'Starts Next Week',
            'is_active' => false,
            'valid_from' => now()->addDay(),
            'valid_until' => now()->addMonth(),
        ]);
        $this->createVoucher($merchant, [
            'name' => 'Live Offer',
            'is_active' => true,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addWeek(),
        ]);
        $this->createVoucher($merchant, [
            'name' => 'Coming Soon',
            'is_active' => true,
            'valid_from' => now()->addDay(),
            'valid_until' => now()->addMonth(),
        ]);
        $this->createVoucher($merchant, [
            'name' => 'Old Promo',
            'is_active' => false,
            'valid_from' => now()->subMonth(),
            'valid_until' => now()->subDay(),
        ]);

        $this->assertSame('pending', $pending->getListStatusGroup());
        $this->assertSame('pending', $scheduledUnapproved->getListStatusGroup());

        Livewire::test(Index::class)
            ->set('statusFilter', 'pending')
            ->assertSee('Awaiting Review')
            ->assertSee('Starts Next Week')
            ->assertSee('Pending For Approval')
            ->assertDontSee('Live Offer')
            ->assertDontSee('Coming Soon')
            ->assertDontSee('Old Promo');
    }

    public function test_unapproved_future_voucher_is_pending_not_expired(): void
    {
        $merchant = $this->createMerchant();
        $voucher = $this->createVoucher($merchant, [
            'name' => 'Starts Next Week',
            'is_active' => false,
            'valid_from' => now()->addDay(),
            'valid_until' => now()->addMonth(),
        ]);

        $this->assertSame('pending', $voucher->getListStatusGroup());
        $this->assertSame('Pending Approval', $voucher->getDisplayStatusLabel());
        $this->assertSame('pending_approval', $voucher->getDisplayStatusCategory());
    }

    public function test_approved_future_voucher_is_not_yet_valid(): void
    {
        $merchant = $this->createMerchant();
        $voucher = $this->createVoucher($merchant, [
            'name' => 'Coming Soon',
            'is_active' => true,
            'valid_from' => now()->addDay(),
            'valid_until' => now()->addMonth(),
        ]);

        $this->assertSame('not_yet_valid', $voucher->getListStatusGroup());
        $this->assertSame('Not Yet Valid', $voucher->getDisplayStatusLabel());
    }

    private function actingAsAdmin(): User
    {
        $admin = User::factory()->create(['user_type' => 'admin']);
        $admin->givePermissionTo('voucher.view');
        $this->actingAs($admin);

        return $admin;
    }

    private function createMerchant(): Merchant
    {
        return Merchant::query()->create([
            'name' => 'Kampong Grocer',
            'is_active' => true,
        ]);
    }

    private function createVoucher(Merchant $merchant, array $attributes = []): Voucher
    {
        return Voucher::query()->create(array_merge([
            'merchant_id' => $merchant->id,
            'name' => 'Lunch 20% Off',
            'description' => 'Weekday lunch discount',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'is_active' => true,
            'usage_count' => 0,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addMonth(),
        ], $attributes));
    }
}
