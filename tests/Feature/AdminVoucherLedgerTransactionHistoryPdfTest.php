<?php

namespace Tests\Feature;

use App\Models\AdminVoucher;
use App\Models\AdminVoucherLedgerEntry;
use App\Models\Merchant;
use App\Models\User;
use App\Support\MemberNameMask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class AdminVoucherLedgerTransactionHistoryPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_transaction_history_pdf_masks_member_names(): void
    {
        $admin = User::factory()->create(['user_type' => 'admin']);
        $merchant = Merchant::query()->create([
            'name' => 'Kampong Grocer',
            'is_active' => true,
        ]);
        $voucher = AdminVoucher::query()->create([
            'voucher_code' => 'AVOU-LEDGER-PDF',
            'name' => 'Meal Voucher',
            'points_cost' => 10,
            'amount_cost' => 1.50,
            'is_active' => true,
            'valid_from' => now()->subMonth(),
            'valid_until' => now()->addMonth(),
            'created_by' => $admin->id,
        ]);
        $entry = AdminVoucherLedgerEntry::query()->create([
            'merchant_id' => $merchant->id,
            'admin_voucher_id' => $voucher->id,
            'period_month' => now()->startOfMonth()->toDateString(),
            'total_redemptions' => 1,
            'total_amount_dispensed' => 15.00,
        ]);

        $member = User::factory()->create([
            'user_type' => 'member',
            'name' => 'Marnelle Apat',
            'qr_code' => 'HV-MARNELLE',
        ]);
        $outsidePeriod = User::factory()->create([
            'user_type' => 'member',
            'name' => 'Outside Period',
            'qr_code' => 'HV-OUTSIDE',
        ]);

        $member->adminVouchers()->attach($voucher->id, [
            'status' => 'redeemed',
            'claimed_at' => now(),
            'redeemed_at' => now(),
            'redeemed_at_merchant_id' => $merchant->id,
        ]);
        $outsidePeriod->adminVouchers()->attach($voucher->id, [
            'status' => 'redeemed',
            'claimed_at' => now()->subMonth(),
            'redeemed_at' => now()->subMonth()->startOfMonth(),
            'redeemed_at_merchant_id' => $merchant->id,
        ]);

        $captured = null;
        View::composer('pdf.admin-voucher-ledger-transaction-history', function ($view) use (&$captured) {
            $captured = $view->getData();
        });

        $this->actingAs($admin)
            ->get(route('admin.admin-voucher-ledger.transaction-history-pdf', $entry))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->assertNotNull($captured);
        $names = collect($captured['transactions'])->pluck('member_name');
        $codes = collect($captured['transactions'])->pluck('member_code');
        $maskedName = MemberNameMask::mask('Marnelle Apat');

        $this->assertTrue($names->contains($maskedName));
        $this->assertFalse($names->contains('Marnelle Apat'));
        $this->assertFalse($names->contains('Outside Period'));
        $this->assertTrue($codes->contains('HV-MARNELLE'));

        $html = view('pdf.admin-voucher-ledger-transaction-history', $captured)->render();
        $this->assertStringContainsString($maskedName, $html);
        $this->assertStringNotContainsString('Marnelle Apat', $html);
    }

    public function test_non_admin_cannot_generate_ledger_transaction_history_pdf(): void
    {
        $admin = User::factory()->create(['user_type' => 'admin']);
        $member = User::factory()->create(['user_type' => 'member']);
        $merchant = Merchant::query()->create([
            'name' => 'Kampong Grocer',
            'is_active' => true,
        ]);
        $voucher = AdminVoucher::query()->create([
            'voucher_code' => 'AVOU-LEDGER-DENY',
            'name' => 'Meal Voucher',
            'points_cost' => 10,
            'amount_cost' => 1.00,
            'is_active' => true,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addMonth(),
            'created_by' => $admin->id,
        ]);
        $entry = AdminVoucherLedgerEntry::query()->create([
            'merchant_id' => $merchant->id,
            'admin_voucher_id' => $voucher->id,
            'period_month' => now()->startOfMonth()->toDateString(),
            'total_redemptions' => 0,
            'total_amount_dispensed' => 0,
        ]);

        $this->actingAs($member)
            ->get(route('admin.admin-voucher-ledger.transaction-history-pdf', $entry))
            ->assertForbidden();
    }
}
