<?php

namespace Tests\Feature;

use App\Livewire\Member\VouchersV3\Index;
use App\Models\ActivityType;
use App\Models\AdminVoucher;
use App\Models\Merchant;
use App\Models\User;
use App\Models\Voucher;
use App\Services\PointsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MemberVoucherAdminTestRedeemTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_use_test_redeem_in_local_testing_environments(): void
    {
        $this->seedRedeemActivityType();

        $member = User::factory()->create(['user_type' => 'member']);
        $voucher = $this->createClaimedMerchantVoucher($member);

        $this->actingAs($member);

        Livewire::test(Index::class)
            ->set('tab', 'claimed')
            ->assertSeeHtml('adminTestRedeem')
            ->call('adminTestRedeem', $voucher->id, 'merchant')
            ->assertDispatched('notify', type: 'success');

        $this->assertTrue(
            $member->vouchers()
                ->where('vouchers.id', $voucher->id)
                ->wherePivot('status', 'redeemed')
                ->exists()
        );
    }

    public function test_admin_can_mark_claimed_merchant_voucher_redeemed_outside_production(): void
    {
        $this->seedRedeemActivityType();

        $admin = User::factory()->create(['user_type' => 'admin']);
        $voucher = $this->createClaimedMerchantVoucher($admin);

        $this->actingAs($admin);

        Livewire::test(Index::class)
            ->set('tab', 'claimed')
            ->assertSee('Redeem')
            ->call('adminTestRedeem', $voucher->id, 'merchant')
            ->assertDispatched('notify', type: 'success');

        $this->assertTrue(
            $admin->vouchers()
                ->where('vouchers.id', $voucher->id)
                ->wherePivot('status', 'redeemed')
                ->exists()
        );
    }

    public function test_admin_can_mark_claimed_admin_voucher_redeemed_outside_production(): void
    {
        $admin = User::factory()->create(['user_type' => 'admin']);
        $voucher = $this->createClaimedAdminVoucher($admin);

        $this->actingAs($admin);

        Livewire::test(Index::class)
            ->set('tab', 'claimed')
            ->call('adminTestRedeem', $voucher->id, 'admin')
            ->assertDispatched('notify', type: 'success');

        $this->assertTrue(
            $admin->adminVouchers()
                ->where('admin_vouchers.id', $voucher->id)
                ->wherePivot('status', 'redeemed')
                ->exists()
        );
    }

    public function test_member_cannot_use_test_redeem_in_production(): void
    {
        $this->app['env'] = 'production';

        $member = User::factory()->create(['user_type' => 'member']);
        $voucher = $this->createClaimedMerchantVoucher($member);

        $this->actingAs($member);

        Livewire::test(Index::class)
            ->set('tab', 'claimed')
            ->assertDontSeeHtml('adminTestRedeem')
            ->call('adminTestRedeem', $voucher->id, 'merchant')
            ->assertDispatched('notify', type: 'error');

        $this->assertTrue(
            $member->vouchers()
                ->where('vouchers.id', $voucher->id)
                ->wherePivot('status', 'claimed')
                ->exists()
        );
    }

    public function test_member_cannot_use_test_redeem_in_staging(): void
    {
        $this->app['env'] = 'staging';

        $member = User::factory()->create(['user_type' => 'member']);
        $voucher = $this->createClaimedMerchantVoucher($member);

        $this->actingAs($member);

        Livewire::test(Index::class)
            ->set('tab', 'claimed')
            ->assertDontSeeHtml('adminTestRedeem')
            ->call('adminTestRedeem', $voucher->id, 'merchant')
            ->assertDispatched('notify', type: 'error');

        $this->assertTrue(
            $member->vouchers()
                ->where('vouchers.id', $voucher->id)
                ->wherePivot('status', 'claimed')
                ->exists()
        );
    }

    public function test_admin_cannot_use_test_redeem_in_production(): void
    {
        $this->app['env'] = 'production';

        $admin = User::factory()->create(['user_type' => 'admin']);
        $voucher = $this->createClaimedMerchantVoucher($admin);

        $this->actingAs($admin);

        Livewire::test(Index::class)
            ->set('tab', 'claimed')
            ->assertDontSeeHtml('adminTestRedeem')
            ->call('adminTestRedeem', $voucher->id, 'merchant')
            ->assertDispatched('notify', type: 'error');

        $this->assertTrue(
            $admin->vouchers()
                ->where('vouchers.id', $voucher->id)
                ->wherePivot('status', 'claimed')
                ->exists()
        );
    }

    public function test_member_vouchers_page_is_forbidden_to_admin_in_production(): void
    {
        $this->app['env'] = 'production';

        $admin = User::factory()->create(['user_type' => 'admin']);

        $this->actingAs($admin)
            ->get(route('member.vouchers'))
            ->assertForbidden();
    }

    public function test_admin_can_open_member_vouchers_page_outside_production(): void
    {
        $admin = User::factory()->create(['user_type' => 'admin']);

        $this->actingAs($admin)
            ->get(route('member.vouchers'))
            ->assertOk();
    }

    private function seedRedeemActivityType(): void
    {
        ActivityType::query()->create([
            'name' => PointsService::ACTIVITY_VOUCHER_REDEEM,
            'description' => 'Member redeemed voucher',
            'is_active' => true,
        ]);
    }

    private function createClaimedMerchantVoucher(User $user): Voucher
    {
        $merchant = Merchant::query()->create([
            'name' => 'Test Merchant',
            'email' => 'merchant-test-redeem-'.uniqid().'@example.com',
            'is_active' => true,
        ]);

        $voucher = Voucher::query()->create([
            'voucher_code' => 'VOU-TST'.strtoupper(substr(uniqid(), -6)),
            'name' => 'Test Merchant Voucher',
            'merchant_id' => $merchant->id,
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'is_active' => true,
            'usage_limit' => 10,
            'usage_count' => 1,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addWeek(),
        ]);

        $user->vouchers()->attach($voucher->id, [
            'status' => 'claimed',
            'claimed_at' => now(),
        ]);

        return $voucher;
    }

    private function createClaimedAdminVoucher(User $user): AdminVoucher
    {
        $voucher = AdminVoucher::query()->create([
            'voucher_code' => 'AVOU-TST'.strtoupper(substr(uniqid(), -5)),
            'name' => 'Test Admin Voucher',
            'points_cost' => 50,
            'amount_cost' => 5.00,
            'is_active' => true,
            'usage_limit' => 10,
            'usage_count' => 1,
            'created_by' => $user->id,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addWeek(),
        ]);

        $user->claimAdminVoucherAssignment($voucher);

        return $voucher;
    }
}
