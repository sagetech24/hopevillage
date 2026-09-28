<?php

namespace Tests\Feature;

use App\Models\AdminVoucher;
use App\Models\Merchant;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoucherRedeemabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_usage_limit_blocks_claim_validity_but_not_redeemability_for_merchant_voucher(): void
    {
        $merchant = Merchant::query()->create([
            'name' => 'Test Merchant',
            'email' => 'merchant-redeemability@example.com',
            'is_active' => true,
        ]);

        $voucher = Voucher::query()->create([
            'voucher_code' => 'VOU-LIMIT01',
            'name' => 'Limited Offer',
            'merchant_id' => $merchant->id,
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'is_active' => true,
            'usage_limit' => 10,
            'usage_count' => 10,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addWeek(),
        ]);

        $this->assertFalse($voucher->isValid());
        $this->assertSame('Usage Limit Reached', $voucher->getStatusReason());
        $this->assertTrue($voucher->isRedeemable());
        $this->assertNull($voucher->getRedeemStatusReason());
    }

    public function test_usage_limit_blocks_claim_validity_but_not_redeemability_for_admin_voucher(): void
    {
        $admin = User::factory()->create(['user_type' => 'admin']);

        $voucher = AdminVoucher::query()->create([
            'voucher_code' => 'AVOU-LIMIT01',
            'name' => 'Limited Admin Offer',
            'points_cost' => 50,
            'amount_cost' => 5.00,
            'is_active' => true,
            'usage_limit' => 5,
            'usage_count' => 5,
            'created_by' => $admin->id,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addWeek(),
        ]);

        $this->assertFalse($voucher->isValid());
        $this->assertSame('Usage Limit Reached', $voucher->getStatusReason());
        $this->assertTrue($voucher->isRedeemable());
        $this->assertNull($voucher->getRedeemStatusReason());
    }

    public function test_expired_voucher_is_not_redeemable(): void
    {
        $merchant = Merchant::query()->create([
            'name' => 'Test Merchant Expired',
            'email' => 'merchant-expired@example.com',
            'is_active' => true,
        ]);

        $voucher = Voucher::query()->create([
            'voucher_code' => 'VOU-EXPIRED1',
            'name' => 'Expired Offer',
            'merchant_id' => $merchant->id,
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'is_active' => true,
            'usage_limit' => 10,
            'usage_count' => 1,
            'valid_from' => now()->subWeek(),
            'valid_until' => now()->subDay(),
        ]);

        $this->assertFalse($voucher->isRedeemable());
        $this->assertSame('Expired', $voucher->getRedeemStatusReason());
    }
}
