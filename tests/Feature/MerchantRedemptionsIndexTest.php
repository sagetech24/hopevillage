<?php

namespace Tests\Feature;

use App\Livewire\Merchant\Redemptions\Index;
use App\Models\AdminVoucher;
use App\Models\Merchant;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MerchantRedemptionsIndexTest extends TestCase
{
    use RefreshDatabase;

    private function actingMerchant(): array
    {
        $user = User::factory()->create([
            'user_type' => 'merchant_user',
        ]);

        $merchant = Merchant::query()->create([
            'name' => 'Kampong Grocer',
            'is_active' => true,
        ]);

        $user->merchants()->attach($merchant->id, ['is_default' => true]);
        $user->update(['current_merchant_id' => $merchant->id]);

        return [$user, $merchant];
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

    public function test_merchant_user_can_view_redemptions_index_v2(): void
    {
        [$user] = $this->actingMerchant();

        $this->actingAs($user)
            ->get(route('merchant.redemptions.index'))
            ->assertOk()
            ->assertSee('Merchant Portal')
            ->assertSee('Redemption History')
            ->assertSee('My Vouchers')
            ->assertSee('Hope Village Vouchers')
            ->assertSee('Members who redeemed vouchers at your store.')
            ->assertDontSee('Voucher Redemption')
            ->assertDontSee('Admin Vouchers')
            ->assertDontSee('View voucher redemption history');
    }

    public function test_store_redemptions_are_labelled_redeemed_with_voucher_value(): void
    {
        [$user, $merchant] = $this->actingMerchant();
        $voucher = $this->createVoucher($merchant, [
            'name' => 'Coffee Voucher',
            'voucher_code' => 'VOU-COFFEE01',
        ]);

        $member = User::factory()->create([
            'user_type' => 'member',
            'name' => 'Aisha Rahman',
            'email' => 'aisha@example.com',
        ]);

        $member->vouchers()->attach($voucher->id, [
            'status' => 'redeemed',
            'claimed_at' => now()->subDay(),
            'redeemed_at' => now()->subMinutes(10),
        ]);

        $this->actingAs($user)
            ->get(route('merchant.redemptions.index'))
            ->assertOk()
            ->assertSee('Aish**** Rahm****')
            ->assertDontSee('Aisha Rahman')
            ->assertDontSee('aisha@example.com')
            ->assertSee('Coffee Voucher')
            ->assertSee('VOU-COFFEE01')
            ->assertSee('20% off')
            ->assertSee('Redeemed')
            ->assertSee('Store voucher')
            ->assertSee('Claimed')
            ->assertDontSee('On Going');
    }

    public function test_claimed_only_vouchers_do_not_appear_in_redemption_history(): void
    {
        [$user, $merchant] = $this->actingMerchant();
        $voucher = $this->createVoucher($merchant);

        $claimedMember = User::factory()->create([
            'user_type' => 'member',
            'name' => 'Claimed Only Member',
        ]);
        $claimedMember->vouchers()->attach($voucher->id, [
            'status' => 'claimed',
            'claimed_at' => now()->subHour(),
        ]);

        $this->actingAs($user)
            ->get(route('merchant.redemptions.index'))
            ->assertOk()
            ->assertDontSee('Claimed Only Member')
            ->assertSee('No store redemptions yet');
    }

    public function test_hope_village_tab_labels_admin_voucher_redemptions(): void
    {
        [$user, $merchant] = $this->actingMerchant();

        $admin = User::factory()->create([
            'user_type' => 'admin',
        ]);

        $adminVoucher = AdminVoucher::query()->create([
            'voucher_code' => 'AVOU-MEAL001',
            'name' => 'Volunteer Reward',
            'points_cost' => 100,
            'is_active' => true,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addMonth(),
            'created_by' => $admin->id,
        ]);
        $adminVoucher->merchants()->attach($merchant->id);

        $member = User::factory()->create([
            'user_type' => 'member',
            'name' => 'Budi Santoso',
        ]);
        $member->adminVouchers()->attach($adminVoucher->id, [
            'status' => 'redeemed',
            'claimed_at' => now()->subDay(),
            'redeemed_at' => now()->subMinutes(20),
            'redeemed_at_merchant_id' => $merchant->id,
        ]);

        $otherMerchant = Merchant::query()->create([
            'name' => 'Other Store',
            'is_active' => true,
        ]);
        $elsewhereMember = User::factory()->create([
            'user_type' => 'member',
            'name' => 'Redeemed Elsewhere',
        ]);
        $elsewhereMember->adminVouchers()->attach($adminVoucher->id, [
            'status' => 'redeemed',
            'claimed_at' => now()->subDay(),
            'redeemed_at' => now()->subMinutes(5),
            'redeemed_at_merchant_id' => $otherMerchant->id,
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('setTab', 'admin')
            ->assertSee('Hope Village Vouchers')
            ->assertSee('B**** Sant****')
            ->assertDontSee('Budi Santoso')
            ->assertSee('Volunteer Reward')
            ->assertSee('AVOU-MEAL001')
            ->assertSee('100 pts')
            ->assertSee('Redeemed')
            ->assertSee('Hope Village')
            ->assertDontSee('Redeemed Elsewhere')
            ->assertDontSee('Admin Vouchers');
    }

    public function test_search_matches_member_and_voucher_details(): void
    {
        [$user, $merchant] = $this->actingMerchant();
        $voucher = $this->createVoucher($merchant, [
            'name' => 'Nasi Lemak Combo',
            'voucher_code' => 'VOU-NASILEMAK',
        ]);

        $matching = User::factory()->create([
            'user_type' => 'member',
            'name' => 'Mei Ling',
        ]);
        $other = User::factory()->create([
            'user_type' => 'member',
            'name' => 'Hidden Member',
        ]);

        $matching->vouchers()->attach($voucher->id, [
            'status' => 'redeemed',
            'claimed_at' => now()->subDay(),
            'redeemed_at' => now()->subMinutes(10),
        ]);
        $other->vouchers()->attach($this->createVoucher($merchant, [
            'name' => 'Other Offer',
            'voucher_code' => 'VOU-OTHER001',
        ])->id, [
            'status' => 'redeemed',
            'claimed_at' => now()->subDay(),
            'redeemed_at' => now()->subMinutes(5),
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->set('search', 'Nasi Lemak')
            ->assertSee('M**** L****')
            ->assertDontSee('Mei Ling')
            ->assertSee('Nasi Lemak Combo')
            ->assertDontSee('Hidden Member');
    }

    public function test_non_merchant_user_cannot_access_redemptions(): void
    {
        $user = User::factory()->create([
            'user_type' => 'member',
        ]);

        $this->actingAs($user)
            ->get(route('merchant.redemptions.index'))
            ->assertForbidden();
    }
}
