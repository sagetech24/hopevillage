<?php

namespace Tests\Feature;

use App\Models\AdminVoucher;
use App\Models\AdminVoucherLedgerEntry;
use App\Models\AdminVoucherReimbursement;
use App\Models\Merchant;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MerchantDashboardAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_merchant_user_without_assigned_merchant_can_access_dashboard(): void
    {
        $user = User::factory()->create([
            'user_type' => 'merchant_user',
        ]);

        $this->actingAs($user)
            ->get(route('merchant.dashboard'))
            ->assertRedirect(route('merchant.dashboard.v2'));
    }

    public function test_merchant_user_without_assigned_merchant_can_open_vouchers_without_forbidden(): void
    {
        $user = User::factory()->create([
            'user_type' => 'merchant_user',
        ]);

        $response = $this->actingAs($user)
            ->get(route('merchant.vouchers.index'));

        $this->assertNotSame(403, $response->status());
        $this->assertTrue(
            $response->isRedirect(route('merchant.dashboard.v2'))
                || $response->isRedirect(route('merchant.dashboard'))
                || $response->isOk()
        );
    }

    public function test_non_merchant_user_cannot_access_merchant_dashboard(): void
    {
        $user = User::factory()->create([
            'user_type' => 'member',
        ]);

        $this->actingAs($user)
            ->get(route('merchant.dashboard'))
            ->assertForbidden();
    }

    public function test_merchant_user_can_access_dashboard_v2(): void
    {
        $user = User::factory()->create([
            'user_type' => 'merchant_user',
        ]);

        $this->actingAs($user)
            ->get(route('merchant.dashboard.v2'))
            ->assertOk()
            ->assertSee('Merchant Portal')
            ->assertSee('No store assigned yet')
            ->assertSee('Home')
            ->assertSee('Vouchers')
            ->assertSee('Redemptions')
            ->assertSee('Settings')
            ->assertSee('Reimbursements')
            ->assertSee('Invoices')
            ->assertSee('My Profile')
            ->assertSee(route('merchant.reimbursements.index'), false);
    }

    public function test_dashboard_v2_shows_storefront_metrics_listings_and_activity(): void
    {
        $user = User::factory()->create([
            'name' => 'Store Owner',
            'user_type' => 'merchant_user',
        ]);

        $merchant = Merchant::query()->create([
            'name' => 'Kampong Grocer',
            'city' => 'Singapore',
            'is_active' => true,
        ]);

        $user->merchants()->attach($merchant->id, ['is_default' => true]);
        $user->update(['current_merchant_id' => $merchant->id]);

        $voucher = Voucher::query()->create([
            'merchant_id' => $merchant->id,
            'name' => 'Lunch 20% Off',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'is_active' => true,
            'usage_count' => 1,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addMonth(),
        ]);

        $member = User::factory()->create([
            'name' => 'Amina Member',
            'user_type' => 'member',
        ]);

        $member->vouchers()->attach($voucher->id, [
            'status' => 'redeemed',
            'claimed_at' => now()->subHour(),
            'redeemed_at' => now()->subMinutes(10),
        ]);

        $this->actingAs($user)
            ->get(route('merchant.dashboard.v2'))
            ->assertOk()
            ->assertSee('Seller Center')
            ->assertSee('Kampong Grocer')
            ->assertSee('Live offers')
            ->assertSee('Lunch 20% Off')
            ->assertSee('Amin**** Memb****')
            ->assertDontSee('Amina Member')
            ->assertSee('New offer')
            ->assertSee('Scan QR')
            ->assertSee('Home')
            ->assertSee('Vouchers');
    }

    public function test_non_merchant_user_cannot_access_dashboard_v2(): void
    {
        $user = User::factory()->create([
            'user_type' => 'member',
        ]);

        $this->actingAs($user)
            ->get(route('merchant.dashboard.v2'))
            ->assertForbidden();
    }

    public function test_dashboard_v2_reimbursements_kpi_sums_assigned_admin_vouchers_only(): void
    {
        $user = User::factory()->create([
            'user_type' => 'merchant_user',
        ]);

        $merchant = Merchant::query()->create([
            'name' => 'Kampong Grocer',
            'is_active' => true,
        ]);
        $otherMerchant = Merchant::query()->create([
            'name' => 'Other Store',
            'is_active' => true,
        ]);

        $user->merchants()->attach($merchant->id, ['is_default' => true]);
        $user->update(['current_merchant_id' => $merchant->id]);

        $admin = User::factory()->create([
            'user_type' => 'admin',
        ]);

        $voucherA = $this->createAdminVoucher($admin, 'Meal Voucher A', 'AVOU-KPI-A');
        $voucherB = $this->createAdminVoucher($admin, 'Meal Voucher B', 'AVOU-KPI-B');
        $unassigned = $this->createAdminVoucher($admin, 'Unassigned Voucher', 'AVOU-KPI-U');

        $voucherA->merchants()->attach($merchant->id);
        $voucherB->merchants()->attach([$merchant->id, $otherMerchant->id]);

        $entryA = AdminVoucherLedgerEntry::query()->create([
            'merchant_id' => $merchant->id,
            'admin_voucher_id' => $voucherA->id,
            'period_month' => now()->startOfMonth()->toDateString(),
            'total_redemptions' => 50,
            'total_amount_dispensed' => 500.00,
        ]);
        $entryB = AdminVoucherLedgerEntry::query()->create([
            'merchant_id' => $merchant->id,
            'admin_voucher_id' => $voucherB->id,
            'period_month' => now()->startOfMonth()->toDateString(),
            'total_redemptions' => 50,
            'total_amount_dispensed' => 500.00,
        ]);
        $otherEntry = AdminVoucherLedgerEntry::query()->create([
            'merchant_id' => $otherMerchant->id,
            'admin_voucher_id' => $voucherB->id,
            'period_month' => now()->startOfMonth()->toDateString(),
            'total_redemptions' => 80,
            'total_amount_dispensed' => 800.00,
        ]);
        $unassignedEntry = AdminVoucherLedgerEntry::query()->create([
            'merchant_id' => $merchant->id,
            'admin_voucher_id' => $unassigned->id,
            'period_month' => now()->startOfMonth()->toDateString(),
            'total_redemptions' => 90,
            'total_amount_dispensed' => 900.00,
        ]);

        AdminVoucherReimbursement::query()->create([
            'admin_voucher_ledger_entry_id' => $entryA->id,
            'amount' => 150.00,
            'reimbursed_at' => now()->toDateString(),
            'created_by' => $admin->id,
        ]);
        AdminVoucherReimbursement::query()->create([
            'admin_voucher_ledger_entry_id' => $entryB->id,
            'amount' => 100.00,
            'reimbursed_at' => now()->toDateString(),
            'created_by' => $admin->id,
        ]);
        AdminVoucherReimbursement::query()->create([
            'admin_voucher_ledger_entry_id' => $otherEntry->id,
            'amount' => 400.00,
            'reimbursed_at' => now()->toDateString(),
            'created_by' => $admin->id,
        ]);
        AdminVoucherReimbursement::query()->create([
            'admin_voucher_ledger_entry_id' => $unassignedEntry->id,
            'amount' => 275.00,
            'reimbursed_at' => now()->toDateString(),
            'created_by' => $admin->id,
        ]);

        $this->assertSame(
            ['reimbursed' => 250.0, 'receivables' => 1000.0],
            $merchant->adminVoucherReimbursementTotals()
        );

        $this->actingAs($user)
            ->get(route('merchant.dashboard.v2'))
            ->assertOk()
            ->assertSee('Reimbursements')
            ->assertSee('250.00 / 1,000.00')
            ->assertSee('SGD reimbursed / receivables');
    }

    private function createAdminVoucher(User $admin, string $name, string $code): AdminVoucher
    {
        return AdminVoucher::query()->create([
            'voucher_code' => $code,
            'name' => $name,
            'points_cost' => 10,
            'amount_cost' => 1.00,
            'is_active' => true,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addMonth(),
            'created_by' => $admin->id,
        ]);
    }
}
