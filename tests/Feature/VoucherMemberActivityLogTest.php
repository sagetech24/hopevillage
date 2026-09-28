<?php

namespace Tests\Feature;

use App\Livewire\AdminVouchers\Profile;
use App\Livewire\Member\VouchersV3\Index;
use App\Models\AdminVoucher;
use App\Models\MemberActivity;
use App\Models\Merchant;
use App\Models\User;
use App\Models\Voucher;
use App\Services\PointsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VoucherMemberActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_merchant_voucher_claim_creates_member_activity(): void
    {
        $member = User::factory()->create(['user_type' => 'member']);
        $voucher = $this->createMerchantVoucher();

        $this->actingAs($member);

        Livewire::test(Index::class)
            ->call('claim', $voucher->id)
            ->assertDispatched('notify', type: 'success');

        $activity = $this->activityFor($member, PointsService::ACTIVITY_VOUCHER_CLAIM);

        $this->assertNotNull($activity);
        $this->assertSame($voucher->id, $activity->metadata['voucher_id']);
        $this->assertSame($voucher->voucher_code, $activity->metadata['voucher_code']);
        $this->assertNotNull($activity->pointLog);
        $this->assertSame($activity->id, $activity->pointLog->member_activity_id);
        $this->assertGreaterThan(0, $activity->pointLog->points);
    }

    public function test_merchant_voucher_redeem_creates_member_activity(): void
    {
        $member = User::factory()->create(['user_type' => 'member']);
        $voucher = $this->createMerchantVoucher();

        $member->vouchers()->attach($voucher->id, [
            'status' => 'claimed',
            'claimed_at' => now(),
        ]);

        $this->actingAs($member);

        Livewire::test(Index::class)
            ->set('tab', 'claimed')
            ->call('adminTestRedeem', $voucher->id, 'merchant')
            ->assertDispatched('notify', type: 'success');

        $activity = $this->activityFor($member, PointsService::ACTIVITY_VOUCHER_REDEEM);

        $this->assertNotNull($activity);
        $this->assertSame($voucher->id, $activity->metadata['voucher_id']);
        $this->assertNotNull($activity->pointLog);
        $this->assertSame($activity->id, $activity->pointLog->member_activity_id);
    }

    public function test_admin_voucher_claim_creates_member_activity(): void
    {
        $member = User::factory()->create([
            'user_type' => 'member',
            'total_points' => 500,
        ]);
        $voucher = $this->createAdminVoucher();

        $this->actingAs($member);

        Livewire::test(Index::class)
            ->call('claimAdminVoucher', $voucher->id)
            ->assertDispatched('notify', type: 'success');

        $activity = $this->activityFor($member, PointsService::ACTIVITY_ADMIN_VOUCHER_CLAIM);

        $this->assertNotNull($activity);
        $this->assertSame($voucher->id, $activity->metadata['admin_voucher_id']);
        $this->assertNotNull($activity->pointLog);
        $this->assertSame(-100, (int) $activity->pointLog->points);
    }

    public function test_admin_voucher_redeem_creates_member_activity(): void
    {
        $member = User::factory()->create(['user_type' => 'member']);
        $voucher = $this->createAdminVoucher();
        $member->claimAdminVoucherAssignment($voucher);

        $this->actingAs($member);

        Livewire::test(Index::class)
            ->set('tab', 'claimed')
            ->call('adminTestRedeem', $voucher->id, 'admin')
            ->assertDispatched('notify', type: 'success');

        $activity = $this->activityFor($member, PointsService::ACTIVITY_ADMIN_VOUCHER_REDEEM);

        $this->assertNotNull($activity);
        $this->assertSame($voucher->id, $activity->metadata['admin_voucher_id']);
        $this->assertNull($activity->pointLog);
    }

    public function test_admin_void_creates_activity_and_marks_claim_void(): void
    {
        $admin = User::factory()->create(['user_type' => 'admin']);
        $member = User::factory()->create([
            'user_type' => 'member',
            'total_points' => 500,
        ]);
        $voucher = $this->createAdminVoucher($admin);

        app(PointsService::class)->deductAdminVoucherClaim($member, $voucher);
        $member->claimAdminVoucherAssignment($voucher);
        $voucher->increment('usage_count');

        $claimActivity = $this->activityFor($member, PointsService::ACTIVITY_ADMIN_VOUCHER_CLAIM);
        $this->assertNotNull($claimActivity);

        $this->actingAs($admin);

        Livewire::test(Profile::class, ['voucher_code' => $voucher->voucher_code])
            ->call('openVoidModal', $member->id)
            ->set('voidReason', 'Issued in error')
            ->call('voidMemberVoucher')
            ->assertDispatched('notify', type: 'success');

        $claimActivity->refresh();
        $this->assertSame('void', $claimActivity->metadata['status'] ?? null);

        $voidActivity = $this->activityFor($member, PointsService::ACTIVITY_ADMIN_VOUCHER_VOID);
        $this->assertNotNull($voidActivity);
        $this->assertSame($voucher->id, $voidActivity->metadata['admin_voucher_id']);
        $this->assertSame('Issued in error', $voidActivity->metadata['void_reason']);
        $this->assertNotNull($voidActivity->pointLog);
        $this->assertSame(100, (int) $voidActivity->pointLog->points);
    }

    private function activityFor(User $member, string $activityName): ?MemberActivity
    {
        return MemberActivity::query()
            ->with('pointLog')
            ->where('user_id', $member->id)
            ->whereHas('activityType', fn ($q) => $q->where('name', $activityName))
            ->latest('id')
            ->first();
    }

    private function createMerchantVoucher(string $code = 'VOU-ACT001'): Voucher
    {
        $merchant = Merchant::query()->create([
            'name' => 'Activity Merchant',
            'email' => 'activity-merchant-'.uniqid().'@example.com',
            'is_active' => true,
        ]);

        return Voucher::query()->create([
            'voucher_code' => $code,
            'name' => 'Activity Merchant Voucher',
            'merchant_id' => $merchant->id,
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'is_active' => true,
            'usage_limit' => 10,
            'usage_count' => 0,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addWeek(),
        ]);
    }

    private function createAdminVoucher(?User $admin = null, string $code = 'AVOU-ACT001'): AdminVoucher
    {
        $admin ??= User::factory()->create(['user_type' => 'admin']);

        return AdminVoucher::query()->create([
            'voucher_code' => $code,
            'name' => 'Activity Admin Voucher',
            'points_cost' => 100,
            'amount_cost' => 10.00,
            'is_active' => true,
            'usage_limit' => 10,
            'usage_count' => 0,
            'created_by' => $admin->id,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addWeek(),
        ]);
    }
}
