<?php

namespace Tests\Feature;

use App\Livewire\AdminVouchers\Profile;
use App\Models\AdminVoucher;
use App\Models\Merchant;
use App\Models\PointLog;
use App\Models\User;
use App\Services\PointsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminVoucherVoidTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $member;

    protected AdminVoucher $voucher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'user_type' => 'admin',
        ]);

        $this->member = User::factory()->create([
            'user_type' => 'member',
            'total_points' => 500,
        ]);

        $this->voucher = AdminVoucher::query()->create([
            'voucher_code' => 'AVOU-VOID001',
            'name' => 'Void Test Voucher',
            'description' => 'For void tests',
            'points_cost' => 100,
            'amount_cost' => 10.00,
            'is_active' => true,
            'usage_limit' => 5,
            'usage_count' => 0,
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin);
    }

    public function test_void_claimed_voucher_refunds_points_and_writes_audit_log(): void
    {
        app(PointsService::class)->deductAdminVoucherClaim($this->member, $this->voucher);
        $this->member->refresh();
        $this->assertSame(400, $this->member->total_points);

        $this->member->claimAdminVoucherAssignment($this->voucher);
        $this->voucher->increment('usage_count');

        Livewire::test(Profile::class, ['voucher_code' => $this->voucher->voucher_code])
            ->call('openVoidModal', $this->member->id)
            ->assertSet('showVoidModal', true)
            ->assertSet('voidRefundPreview', 100)
            ->set('voidReason', 'Issued in error')
            ->call('voidMemberVoucher')
            ->assertDispatched('notify', type: 'success');

        $this->member->refresh();
        $this->voucher->refresh();

        $this->assertSame(500, $this->member->total_points);
        $this->assertSame(0, $this->voucher->usage_count);
        $this->assertTrue(
            $this->member->adminVouchers()
                ->where('admin_vouchers.id', $this->voucher->id)
                ->wherePivot('status', 'voided')
                ->exists()
        );

        $pivot = $this->member->adminVouchers()
            ->where('admin_vouchers.id', $this->voucher->id)
            ->first()
            ->pivot;

        $this->assertSame(100, (int) $pivot->points_refunded);
        $this->assertSame($this->admin->id, (int) $pivot->voided_by);
        $this->assertSame('Issued in error', $pivot->void_reason);
        $this->assertNotNull($pivot->voided_at);

        $log = PointLog::query()
            ->where('user_id', $this->member->id)
            ->where('points', 100)
            ->whereHas('activityType', fn ($q) => $q->where('name', PointsService::ACTIVITY_ADMIN_VOUCHER_VOID))
            ->first();

        $this->assertNotNull($log);
        $this->assertStringContainsString($this->voucher->voucher_code, $log->description);
        $this->assertStringContainsString($this->admin->name, $log->description);
        $this->assertStringContainsString('Issued in error', $log->description);
    }

    public function test_void_awarded_voucher_does_not_refund_points(): void
    {
        Livewire::test(Profile::class, ['voucher_code' => $this->voucher->voucher_code])
            ->call('openAwardModal')
            ->set('selectedMemberIds', [$this->member->id])
            ->call('awardToMembers')
            ->assertDispatched('notify', type: 'success');

        $this->member->refresh();
        $this->assertSame(500, $this->member->total_points);
        $this->assertSame(1, $this->voucher->fresh()->usage_count);

        Livewire::test(Profile::class, ['voucher_code' => $this->voucher->voucher_code])
            ->call('openVoidModal', $this->member->id)
            ->assertSet('voidRefundPreview', 0)
            ->set('voidReason', 'Awarded by mistake')
            ->call('voidMemberVoucher')
            ->assertDispatched('notify', type: 'success');

        $this->member->refresh();
        $this->voucher->refresh();

        $this->assertSame(500, $this->member->total_points);
        $this->assertSame(0, $this->voucher->usage_count);

        $log = PointLog::query()
            ->where('user_id', $this->member->id)
            ->where('points', 0)
            ->whereHas('activityType', fn ($q) => $q->where('name', PointsService::ACTIVITY_ADMIN_VOUCHER_VOID))
            ->first();

        $this->assertNotNull($log);
        $this->assertStringContainsString('Awarded by mistake', $log->description);
    }

    public function test_void_redeemed_voucher_refunds_claim_points(): void
    {
        $merchant = Merchant::query()->create([
            'name' => 'Test Merchant',
            'email' => 'merchant-void@example.com',
            'is_active' => true,
        ]);

        app(PointsService::class)->deductAdminVoucherClaim($this->member, $this->voucher);
        $this->member->claimAdminVoucherAssignment($this->voucher);
        $this->voucher->increment('usage_count');

        $this->member->adminVouchers()->updateExistingPivot($this->voucher->id, [
            'status' => 'redeemed',
            'redeemed_at' => now(),
            'redeemed_at_merchant_id' => $merchant->id,
        ]);

        Livewire::test(Profile::class, ['voucher_code' => $this->voucher->voucher_code])
            ->set('engagementTab', 'redeemed')
            ->call('openVoidModal', $this->member->id)
            ->assertSet('voidPreviousStatus', 'redeemed')
            ->assertSet('voidRefundPreview', 100)
            ->call('voidMemberVoucher')
            ->assertDispatched('notify', type: 'success');

        $this->member->refresh();
        $this->assertSame(500, $this->member->total_points);
        $this->assertTrue(
            $this->member->adminVouchers()
                ->where('admin_vouchers.id', $this->voucher->id)
                ->wherePivot('status', 'voided')
                ->exists()
        );
        $this->assertSame(0, $this->voucher->fresh()->usage_count);
    }

    public function test_void_rejects_already_voided_assignment(): void
    {
        $this->member->claimAdminVoucherAssignment($this->voucher);
        $this->voucher->increment('usage_count');
        $this->member->adminVouchers()->updateExistingPivot($this->voucher->id, [
            'status' => 'voided',
            'voided_at' => now(),
            'voided_by' => $this->admin->id,
        ]);

        Livewire::test(Profile::class, ['voucher_code' => $this->voucher->voucher_code])
            ->call('openVoidModal', $this->member->id)
            ->assertSet('showVoidModal', false)
            ->assertDispatched('notify', type: 'error');
    }
}
