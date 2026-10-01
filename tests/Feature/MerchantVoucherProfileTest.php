<?php

namespace Tests\Feature;

use App\Models\Merchant;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MerchantVoucherProfileTest extends TestCase
{
    use RefreshDatabase;

    private function actingMerchantWithVoucher(array $voucherAttributes = [], bool $merchantActive = true): array
    {
        $user = User::factory()->create([
            'user_type' => 'merchant_user',
        ]);

        $merchant = Merchant::query()->create([
            'name' => 'Kampong Grocer',
            'is_active' => $merchantActive,
        ]);

        $user->merchants()->attach($merchant->id, ['is_default' => true]);
        $user->update(['current_merchant_id' => $merchant->id]);

        $voucher = Voucher::query()->create(array_merge([
            'merchant_id' => $merchant->id,
            'name' => 'Lunch 20% Off',
            'description' => 'Weekday lunch discount',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'is_active' => true,
            'usage_count' => 0,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addMonth(),
        ], $voucherAttributes));

        return [$user, $merchant, $voucher];
    }

    public function test_merchant_user_can_view_voucher_profile_v2(): void
    {
        [$user, , $voucher] = $this->actingMerchantWithVoucher();

        $this->actingAs($user)
            ->get(route('merchant.vouchers.profile', $voucher->voucher_code))
            ->assertOk()
            ->assertSee('Merchant Portal')
            ->assertSee('Lunch 20% Off')
            ->assertSee($voucher->voucher_code)
            ->assertSee('20% off')
            ->assertSee('Voucher Information')
            ->assertSee('Member Activity')
            ->assertSee('View QR')
            ->assertSee('Voucher QR code')
            ->assertDontSee('On Going');
    }

    public function test_active_voucher_is_labelled_active_not_on_going(): void
    {
        [$user, , $voucher] = $this->actingMerchantWithVoucher([
            'is_active' => true,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addWeek(),
        ]);

        $this->assertSame('Active', $voucher->getDisplayStatusLabel());

        $this->actingAs($user)
            ->get(route('merchant.vouchers.profile', $voucher->voucher_code))
            ->assertOk()
            ->assertSee('Active')
            ->assertDontSee('On Going')
            ->assertDontSee('Pending Approval');
    }

    public function test_pending_voucher_is_labelled_pending_approval_not_on_going(): void
    {
        [$user, , $voucher] = $this->actingMerchantWithVoucher([
            'is_active' => false,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addWeek(),
        ]);

        $this->assertSame('Pending Approval', $voucher->getDisplayStatusLabel());

        $this->actingAs($user)
            ->get(route('merchant.vouchers.profile', $voucher->voucher_code))
            ->assertOk()
            ->assertSee('Pending Approval')
            ->assertSee('Waiting for Hope Village admin approval')
            ->assertDontSee('On Going')
            ->assertDontSee('>Active<', false);
    }

    public function test_unapproved_scheduled_voucher_is_labelled_pending_approval(): void
    {
        [$user, , $voucher] = $this->actingMerchantWithVoucher([
            'is_active' => false,
            'valid_from' => now()->addDay(),
            'valid_until' => now()->addMonth(),
        ]);

        $this->assertSame('Pending Approval', $voucher->getDisplayStatusLabel());
        $this->assertSame('pending', $voucher->getListStatusGroup());

        $this->actingAs($user)
            ->get(route('merchant.vouchers.profile', $voucher->voucher_code))
            ->assertOk()
            ->assertSee('Pending Approval')
            ->assertDontSee('Not Yet Valid')
            ->assertDontSee('Expired');
    }

    public function test_expired_voucher_is_labelled_expired(): void
    {
        [$user, , $voucher] = $this->actingMerchantWithVoucher([
            'is_active' => true,
            'valid_from' => now()->subMonth(),
            'valid_until' => now()->subDay(),
        ]);

        $this->assertSame('Expired', $voucher->getDisplayStatusLabel());

        $this->actingAs($user)
            ->get(route('merchant.vouchers.profile', $voucher->voucher_code))
            ->assertOk()
            ->assertSee('Expired')
            ->assertSee('This offer is past its validity date.')
            ->assertDontSee('On Going');
    }

    public function test_scheduled_voucher_is_labelled_not_yet_valid(): void
    {
        [$user, , $voucher] = $this->actingMerchantWithVoucher([
            'is_active' => true,
            'valid_from' => now()->addDay(),
            'valid_until' => now()->addMonth(),
        ]);

        $this->assertSame('Not Yet Valid', $voucher->getDisplayStatusLabel());

        $this->actingAs($user)
            ->get(route('merchant.vouchers.profile', $voucher->voucher_code))
            ->assertOk()
            ->assertSee('Not Yet Valid')
            ->assertSee('This offer is scheduled and is not available yet.')
            ->assertDontSee('On Going');
    }

    public function test_fully_claimed_voucher_keeps_display_status_and_shows_fully_claimed(): void
    {
        [$user, , $voucher] = $this->actingMerchantWithVoucher([
            'is_active' => true,
            'usage_limit' => 5,
            'usage_count' => 5,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addWeek(),
        ]);

        $this->assertSame('Active', $voucher->getDisplayStatusLabel());

        $this->actingAs($user)
            ->get(route('merchant.vouchers.profile', $voucher->voucher_code))
            ->assertOk()
            ->assertSee('Active')
            ->assertSee('Fully claimed')
            ->assertSee('This offer is approved, but all available claims have been used.')
            ->assertDontSee('On Going');
    }

    public function test_profile_lists_claimed_and_redeemed_members(): void
    {
        [$user, , $voucher] = $this->actingMerchantWithVoucher();

        $claimedMember = User::factory()->create([
            'name' => 'Claimed Member',
            'user_type' => 'member',
            'qr_code' => 'HV-CLAIMED01',
        ]);
        $redeemedMember = User::factory()->create([
            'name' => 'Redeemed Member',
            'user_type' => 'member',
            'qr_code' => 'HV-REDEEMED01',
        ]);

        $claimedMember->vouchers()->attach($voucher->id, [
            'status' => 'claimed',
            'claimed_at' => now()->subHour(),
        ]);
        $redeemedMember->vouchers()->attach($voucher->id, [
            'status' => 'redeemed',
            'claimed_at' => now()->subDay(),
            'redeemed_at' => now()->subMinutes(10),
        ]);

        $this->actingAs($user)
            ->get(route('merchant.vouchers.profile', $voucher->voucher_code))
            ->assertOk()
            ->assertSee('Clai**** Memb****')
            ->assertSee('Rede**** Memb****')
            ->assertDontSee('Claimed Member')
            ->assertDontSee('Redeemed Member')
            ->assertSee('HV-CLAIMED01')
            ->assertSee('HV-REDEEMED01')
            ->assertSee('Claimed (1)')
            ->assertSee('Redeemed (1)');
    }

    public function test_pending_store_locks_edit_on_voucher_profile(): void
    {
        [$user, , $voucher] = $this->actingMerchantWithVoucher(merchantActive: false);

        $this->actingAs($user)
            ->get(route('merchant.vouchers.profile', $voucher->voucher_code))
            ->assertOk()
            ->assertSee('Store pending approval')
            ->assertSee('Edit locked')
            ->assertDontSee('Edit voucher');
    }

    public function test_non_merchant_user_cannot_access_voucher_profile(): void
    {
        $user = User::factory()->create([
            'user_type' => 'member',
        ]);

        $this->actingAs($user)
            ->get(route('merchant.vouchers.profile', 'VOU-A90824EB'))
            ->assertForbidden();
    }
}
