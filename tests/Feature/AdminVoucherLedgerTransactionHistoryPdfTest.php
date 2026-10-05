<?php

namespace Tests\Feature;

use App\Models\AdminVoucher;
use App\Models\AdminVoucherLedgerEntry;
use App\Models\Merchant;
use App\Models\MerchantAdminVoucherInvoice;
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

    public function test_admin_invoice_transaction_history_matches_the_merchant_pdf(): void
    {
        $admin = User::factory()->create(['user_type' => 'admin']);
        $merchantUser = User::factory()->create(['user_type' => 'merchant_user']);
        $merchant = Merchant::query()->create([
            'name' => 'Kampong Grocer',
            'is_active' => true,
        ]);
        $merchantUser->merchants()->attach($merchant->id, ['is_default' => true]);
        $merchantUser->update(['current_merchant_id' => $merchant->id]);

        $otherMerchant = Merchant::query()->create([
            'name' => 'Other Store',
            'is_active' => true,
        ]);

        $voucher = AdminVoucher::query()->create([
            'voucher_code' => 'AVOU-INVOICE-TX',
            'name' => 'Transit Pass',
            'points_cost' => 10,
            'amount_cost' => 1.50,
            'is_active' => true,
            'valid_from' => now()->subMonth(),
            'valid_until' => now()->subDay(),
            'created_by' => $admin->id,
        ]);
        $voucher->merchants()->attach([$merchant->id, $otherMerchant->id]);

        $member = User::factory()->create([
            'user_type' => 'member',
            'name' => 'Amina Store Member',
            'qr_code' => 'HV-AMINA',
        ]);
        $elsewhere = User::factory()->create([
            'user_type' => 'member',
            'name' => 'Elsewhere Member',
            'qr_code' => 'HV-ELSE',
        ]);

        $member->adminVouchers()->attach($voucher->id, [
            'status' => 'redeemed',
            'claimed_at' => now()->subDay(),
            'redeemed_at' => now()->subHour(),
            'redeemed_at_merchant_id' => $merchant->id,
        ]);
        $elsewhere->adminVouchers()->attach($voucher->id, [
            'status' => 'redeemed',
            'claimed_at' => now()->subDay(),
            'redeemed_at' => now()->subMinutes(10),
            'redeemed_at_merchant_id' => $otherMerchant->id,
        ]);

        $invoice = MerchantAdminVoucherInvoice::query()->create([
            'invoice_number' => 'INV-TX-1',
            'merchant_id' => $merchant->id,
            'admin_voucher_id' => $voucher->id,
            'generated_by' => $merchantUser->id,
            'voucher_name' => $voucher->name,
            'voucher_code' => $voucher->voucher_code,
            'valid_from' => $voucher->valid_from,
            'valid_until' => $voucher->valid_until,
            'redeemed_count' => 1,
            'cost_per_voucher' => 15,
            'amount' => 15,
            'bank_account' => [
                'bank_name' => 'DBS',
                'account_name' => 'Kampong Grocer',
                'account_number' => '123-456',
            ],
            'generated_at' => now(),
        ]);

        $captured = [];
        View::composer('pdf.merchant-admin-voucher-transaction-history', function ($view) use (&$captured) {
            $captured[] = $view->getData();
        });

        $this->actingAs($merchantUser)
            ->get(route('merchant.vouchers.admin-transaction-history-pdf', $voucher->voucher_code))
            ->assertOk()
            ->assertHeader('content-disposition', 'inline; filename=transaction-history-kampong-grocer-transit-pass.pdf');

        $this->actingAs($admin)
            ->get(route('admin.merchant-admin-voucher-invoices.transaction-history-pdf', $invoice))
            ->assertOk()
            ->assertHeader('content-disposition', 'inline; filename=transaction-history-kampong-grocer-transit-pass.pdf');

        $this->assertCount(2, $captured);

        $merchantRows = collect($captured[0]['transactions'])->map(fn ($row) => [
            $row->member_name,
            $row->member_code,
            (float) $row->amount,
        ])->all();
        $adminRows = collect($captured[1]['transactions'])->map(fn ($row) => [
            $row->member_name,
            $row->member_code,
            (float) $row->amount,
        ])->all();

        $this->assertSame($merchantRows, $adminRows);
        $this->assertSame([[MemberNameMask::mask('Amina Store Member'), 'HV-AMINA', 15.0]], $adminRows);
        $this->assertSame($merchant->id, $captured[1]['merchant']->id);
        $this->assertSame($voucher->id, $captured[1]['voucher']->id);
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
