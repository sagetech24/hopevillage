<?php

namespace Tests\Feature;

use App\Livewire\Members\Profile;
use App\Models\AdminVoucher;
use App\Models\Merchant;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MemberProfileStatsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('member.profile', 'web');
    }

    public function test_used_vouchers_counts_merchant_and_admin_redemptions(): void
    {
        $admin = $this->actingAsAdmin();
        $member = $this->createMember(['total_points' => 40]);

        $merchant = Merchant::query()->create([
            'name' => 'Test Merchant',
            'email' => 'merchant-profile-stats@example.com',
            'is_active' => true,
        ]);

        $redeemedMerchant = Voucher::query()->create([
            'voucher_code' => 'VOU-USED01',
            'name' => 'Redeemed Merchant Voucher',
            'merchant_id' => $merchant->id,
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'is_active' => true,
        ]);

        $claimedMerchant = Voucher::query()->create([
            'voucher_code' => 'VOU-CLAIM01',
            'name' => 'Claimed Merchant Voucher',
            'merchant_id' => $merchant->id,
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'is_active' => true,
        ]);

        $member->vouchers()->attach($redeemedMerchant->id, [
            'status' => 'redeemed',
            'claimed_at' => now()->subDay(),
            'redeemed_at' => now(),
        ]);
        $member->vouchers()->attach($claimedMerchant->id, [
            'status' => 'claimed',
            'claimed_at' => now(),
        ]);

        $redeemedAdmin = AdminVoucher::query()->create([
            'voucher_code' => 'AVOU-USED01',
            'name' => 'Redeemed Admin Voucher',
            'points_cost' => 50,
            'amount_cost' => 5.00,
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        $claimedAdmin = AdminVoucher::query()->create([
            'voucher_code' => 'AVOU-CLAIM01',
            'name' => 'Claimed Admin Voucher',
            'points_cost' => 50,
            'amount_cost' => 5.00,
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        $member->claimAdminVoucherAssignment($redeemedAdmin, [
            'status' => 'redeemed',
            'claimed_at' => now()->subDay(),
            'redeemed_at' => now(),
        ]);
        $member->claimAdminVoucherAssignment($claimedAdmin);

        $component = Livewire::test(Profile::class, ['qr_code' => $member->qr_code]);

        $this->assertSame(2, $component->instance()->usedVouchersCount);
        $component
            ->assertSee('Used Vouchers')
            ->assertDontSee('Coming soon');
    }

    public function test_profile_shows_member_rank_among_members(): void
    {
        $this->actingAsAdmin();

        User::factory()->create([
            'user_type' => 'member',
            'total_points' => 80,
            'created_at' => now()->subDays(5),
            'qr_code' => 'MEM-HIGH01',
        ]);

        $member = $this->createMember([
            'total_points' => 50,
            'created_at' => now()->subDays(2),
            'qr_code' => 'MEM-MID001',
        ]);

        User::factory()->create([
            'user_type' => 'member',
            'total_points' => 10,
            'created_at' => now()->subDay(),
            'qr_code' => 'MEM-LOW001',
        ]);

        Livewire::test(Profile::class, ['qr_code' => $member->qr_code])
            ->assertSee('Ranking')
            ->assertSee('2nd')
            ->assertSee('out of 3')
            ->assertDontSee('Coming soon');
    }

    public function test_profile_rank_has_no_suffix_outside_top_three(): void
    {
        $this->actingAsAdmin();

        foreach ([90, 80, 70] as $index => $points) {
            User::factory()->create([
                'user_type' => 'member',
                'total_points' => $points,
                'created_at' => now()->subDays(4 - $index),
                'qr_code' => 'MEM-TOP0'.($index + 1),
            ]);
        }

        $member = $this->createMember([
            'total_points' => 10,
            'created_at' => now()->subDay(),
            'qr_code' => 'MEM-FOUR04',
        ]);

        $component = Livewire::test(Profile::class, ['qr_code' => $member->qr_code]);

        $this->assertSame('4', $component->instance()->rankOrdinal);
        $component
            ->assertSee('4')
            ->assertDontSee('4th')
            ->assertDontSee('4st');
    }

    protected function actingAsAdmin(): User
    {
        $admin = User::factory()->create(['user_type' => 'admin']);
        $admin->givePermissionTo('member.profile');
        $this->actingAs($admin);

        return $admin;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function createMember(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'user_type' => 'member',
            'qr_code' => 'MEM-PROF01',
        ], $attributes));
    }
}
