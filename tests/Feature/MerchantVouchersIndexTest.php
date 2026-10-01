<?php

namespace Tests\Feature;

use App\Livewire\Merchant\Vouchers\Index;
use App\Models\AdminVoucher;
use App\Models\AdminVoucherLedgerEntry;
use App\Models\AdminVoucherReimbursement;
use App\Models\Merchant;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MerchantVouchersIndexTest extends TestCase
{
    use RefreshDatabase;

    private function actingMerchant(bool $merchantActive = true): array
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

    public function test_merchant_user_can_view_vouchers_index_v2(): void
    {
        [$user, $merchant] = $this->actingMerchant();
        $this->createVoucher($merchant);

        $this->actingAs($user)
            ->get(route('merchant.vouchers.index'))
            ->assertOk()
            ->assertSee('Merchant Portal')
            ->assertSee('Vouchers')
            ->assertSee('My Vouchers')
            ->assertSee('Hope Village Vouchers')
            ->assertSee('Lunch 20% Off')
            ->assertSee('20% off')
            ->assertSee('Active')
            ->assertSee('Voucher actions')
            ->assertSee('View QR')
            ->assertDontSee('On Going')
            ->assertDontSee('Manage your vouchers here');
    }

    public function test_index_labels_active_pending_expired_and_not_yet_valid(): void
    {
        [$user, $merchant] = $this->actingMerchant();

        $this->createVoucher($merchant, [
            'name' => 'Live Offer',
            'is_active' => true,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addWeek(),
        ]);
        $this->createVoucher($merchant, [
            'name' => 'Awaiting Review',
            'is_active' => false,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addWeek(),
        ]);
        $this->createVoucher($merchant, [
            'name' => 'Old Promo',
            'is_active' => true,
            'valid_from' => now()->subMonth(),
            'valid_until' => now()->subDay(),
        ]);
        $this->createVoucher($merchant, [
            'name' => 'Coming Soon',
            'is_active' => true,
            'valid_from' => now()->addDay(),
            'valid_until' => now()->addMonth(),
        ]);

        $this->actingAs($user)
            ->get(route('merchant.vouchers.index'))
            ->assertOk()
            ->assertSee('Live Offer')
            ->assertSee('Awaiting Review')
            ->assertSee('Old Promo')
            ->assertSee('Coming Soon')
            ->assertSee('Active')
            ->assertSee('Pending Approval')
            ->assertSee('Expired')
            ->assertSee('Not Yet Valid')
            ->assertDontSee('On Going')
            ->assertDontSee('>Full<', false);
    }

    public function test_index_labels_claimed_and_redeemed_separately(): void
    {
        [$user, $merchant] = $this->actingMerchant();
        $voucher = $this->createVoucher($merchant, [
            'name' => 'Coffee Voucher',
        ]);

        $claimedMember = User::factory()->create(['user_type' => 'member']);
        $redeemedMember = User::factory()->create(['user_type' => 'member']);

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
            ->get(route('merchant.vouchers.index'))
            ->assertOk()
            ->assertSee('Coffee Voucher')
            ->assertSee('1 claimed')
            ->assertSee('1 redeemed')
            ->assertDontSee('On Going');
    }

    public function test_fully_claimed_voucher_keeps_active_status_and_shows_fully_claimed(): void
    {
        [$user, $merchant] = $this->actingMerchant();
        $this->createVoucher($merchant, [
            'name' => 'Limited Lunch',
            'is_active' => true,
            'usage_limit' => 5,
            'usage_count' => 5,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addWeek(),
        ]);

        $this->actingAs($user)
            ->get(route('merchant.vouchers.index'))
            ->assertOk()
            ->assertSee('Limited Lunch')
            ->assertSee('Active')
            ->assertSee('Fully claimed')
            ->assertDontSee('On Going');
    }

    public function test_pending_store_locks_edit_on_vouchers_index(): void
    {
        [$user, $merchant] = $this->actingMerchant(merchantActive: false);
        $this->createVoucher($merchant);

        $this->actingAs($user)
            ->get(route('merchant.vouchers.index'))
            ->assertOk()
            ->assertSee('Store pending approval')
            ->assertSee('Edit locked')
            ->assertDontSee('New voucher')
            ->assertDontSee('>Edit<', false);
    }

    public function test_status_filter_shows_only_pending_approval_vouchers(): void
    {
        [$user, $merchant] = $this->actingMerchant();
        $this->createVoucher($merchant, ['name' => 'Live Offer']);
        $this->createVoucher($merchant, [
            'name' => 'Awaiting Review',
            'is_active' => false,
        ]);
        $this->createVoucher($merchant, [
            'name' => 'Starts Next Week',
            'is_active' => false,
            'valid_from' => now()->addDay(),
            'valid_until' => now()->addMonth(),
        ]);
        $this->createVoucher($merchant, [
            'name' => 'Coming Soon',
            'is_active' => true,
            'valid_from' => now()->addDay(),
            'valid_until' => now()->addMonth(),
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('setStatusFilter', 'pending_approval')
            ->assertSee('Awaiting Review')
            ->assertSee('Starts Next Week')
            ->assertDontSee('Live Offer')
            ->assertDontSee('Coming Soon')
            ->assertSee('Pending Approval')
            ->assertDontSee('On Going');
    }

    public function test_admin_voucher_details_label_claimed_redeemed_and_redeemed_here(): void
    {
        [$user, $merchant] = $this->actingMerchant();

        $admin = User::factory()->create([
            'user_type' => 'admin',
        ]);

        $adminVoucher = AdminVoucher::query()->create([
            'voucher_code' => 'AVOU-TEST001',
            'name' => 'Volunteer Reward',
            'points_cost' => 100,
            'is_active' => true,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addMonth(),
            'created_by' => $admin->id,
        ]);
        $adminVoucher->merchants()->attach($merchant->id);

        $claimedMember = User::factory()->create(['user_type' => 'member']);
        $redeemedHereMember = User::factory()->create(['user_type' => 'member']);
        $redeemedElsewhereMember = User::factory()->create(['user_type' => 'member']);

        $otherMerchant = Merchant::query()->create([
            'name' => 'Other Store',
            'is_active' => true,
        ]);

        $claimedMember->adminVouchers()->attach($adminVoucher->id, [
            'status' => 'claimed',
            'claimed_at' => now()->subHour(),
        ]);
        $redeemedHereMember->adminVouchers()->attach($adminVoucher->id, [
            'status' => 'redeemed',
            'claimed_at' => now()->subDay(),
            'redeemed_at' => now()->subMinutes(20),
            'redeemed_at_merchant_id' => $merchant->id,
        ]);
        $redeemedElsewhereMember->adminVouchers()->attach($adminVoucher->id, [
            'status' => 'redeemed',
            'claimed_at' => now()->subDay(),
            'redeemed_at' => now()->subMinutes(5),
            'redeemed_at_merchant_id' => $otherMerchant->id,
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('setTab', 'admin-vouchers')
            ->assertSee('Volunteer Reward')
            ->assertSee('Hope Village Vouchers')
            ->assertSee('1 claimed')
            ->assertSee('2 redeemed')
            ->assertSee('1 redeemed here')
            ->assertSee('Active')
            ->assertSee('View details')
            ->assertSee('View Reimbursements')
            ->assertSee('View Transaction History')
            ->assertDontSee('On Going')
            ->call('openAdminVoucher', $adminVoucher->voucher_code)
            ->assertSee('Waiting to redeem')
            ->assertSee('All stores')
            ->assertSee('At this store')
            ->assertSee('100 pts');
    }

    public function test_admin_voucher_reimbursements_show_only_this_store(): void
    {
        [$user, $merchant] = $this->actingMerchant();

        $admin = User::factory()->create([
            'user_type' => 'admin',
        ]);

        $adminVoucher = AdminVoucher::query()->create([
            'voucher_code' => 'AVOU-REIMB001',
            'name' => 'Meal Voucher',
            'points_cost' => 50,
            'amount_cost' => 0.20,
            'is_active' => true,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addMonth(),
            'created_by' => $admin->id,
        ]);
        $adminVoucher->merchants()->attach($merchant->id);

        $otherMerchant = Merchant::query()->create([
            'name' => 'Other Store',
            'is_active' => true,
        ]);

        $ownEntry = AdminVoucherLedgerEntry::query()->create([
            'merchant_id' => $merchant->id,
            'admin_voucher_id' => $adminVoucher->id,
            'period_month' => now()->startOfMonth()->toDateString(),
            'total_redemptions' => 2,
            'total_amount_dispensed' => 20.00,
        ]);
        $otherEntry = AdminVoucherLedgerEntry::query()->create([
            'merchant_id' => $otherMerchant->id,
            'admin_voucher_id' => $adminVoucher->id,
            'period_month' => now()->startOfMonth()->toDateString(),
            'total_redemptions' => 4,
            'total_amount_dispensed' => 40.00,
        ]);

        AdminVoucherReimbursement::query()->create([
            'admin_voucher_ledger_entry_id' => $ownEntry->id,
            'amount' => 8.50,
            'reimbursed_at' => now()->toDateString(),
            'notes' => 'Store reimbursement',
            'created_by' => $admin->id,
        ]);
        AdminVoucherReimbursement::query()->create([
            'admin_voucher_ledger_entry_id' => $otherEntry->id,
            'amount' => 33.00,
            'reimbursed_at' => now()->toDateString(),
            'notes' => 'Other store reimbursement',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('setTab', 'admin-vouchers')
            ->call('openAdminReimbursements', $adminVoucher->voucher_code)
            ->assertSee('View Reimbursements')
            ->assertSee('Meal Voucher')
            ->assertSee('Store reimbursement')
            ->assertSee('8.50')
            ->assertSee('Print to PDF')
            ->assertSee(route('merchant.vouchers.admin-reimbursements-pdf', $adminVoucher->voucher_code), false)
            ->assertDontSee('Other store reimbursement')
            ->assertDontSee('33.00');
    }

    public function test_merchant_can_print_admin_voucher_reimbursements_pdf_for_this_store(): void
    {
        [$user, $merchant] = $this->actingMerchant();

        $admin = User::factory()->create([
            'user_type' => 'admin',
        ]);

        $adminVoucher = AdminVoucher::query()->create([
            'voucher_code' => 'AVOU-REIMBPDF',
            'name' => 'Meal Voucher',
            'points_cost' => 50,
            'amount_cost' => 0.20,
            'is_active' => true,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addMonth(),
            'created_by' => $admin->id,
        ]);
        $adminVoucher->merchants()->attach($merchant->id);

        $otherMerchant = Merchant::query()->create([
            'name' => 'Other Store',
            'is_active' => true,
        ]);

        $ownEntry = AdminVoucherLedgerEntry::query()->create([
            'merchant_id' => $merchant->id,
            'admin_voucher_id' => $adminVoucher->id,
            'period_month' => now()->startOfMonth()->toDateString(),
            'total_redemptions' => 2,
            'total_amount_dispensed' => 20.00,
        ]);
        $otherEntry = AdminVoucherLedgerEntry::query()->create([
            'merchant_id' => $otherMerchant->id,
            'admin_voucher_id' => $adminVoucher->id,
            'period_month' => now()->startOfMonth()->toDateString(),
            'total_redemptions' => 4,
            'total_amount_dispensed' => 40.00,
        ]);

        AdminVoucherReimbursement::query()->create([
            'admin_voucher_ledger_entry_id' => $ownEntry->id,
            'amount' => 8.50,
            'reimbursed_at' => now()->toDateString(),
            'notes' => 'Store reimbursement',
            'created_by' => $admin->id,
        ]);
        AdminVoucherReimbursement::query()->create([
            'admin_voucher_ledger_entry_id' => $otherEntry->id,
            'amount' => 33.00,
            'reimbursed_at' => now()->toDateString(),
            'notes' => 'Other store reimbursement',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($user);

        $response = $this->get(route('merchant.vouchers.admin-reimbursements-pdf', $adminVoucher->voucher_code));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString(
            'reimbursement-history-kampong-grocer-meal-voucher.pdf',
            (string) $response->headers->get('content-disposition')
        );

        $html = view('pdf.merchant-admin-voucher-reimbursements', [
            'merchant' => $merchant,
            'voucher' => $adminVoucher,
            'reimbursements' => AdminVoucherReimbursement::query()
                ->whereHas('ledgerEntry', function ($q) use ($merchant, $adminVoucher) {
                    $q->where('merchant_id', $merchant->id)
                        ->where('admin_voucher_id', $adminVoucher->id);
                })
                ->with('ledgerEntry')
                ->orderBy('reimbursed_at')
                ->orderBy('id')
                ->get()
                ->values()
                ->map(function ($reimbursement, int $index) {
                    $reimbursement->row_number = $index + 1;

                    return $reimbursement;
                }),
            'totalDispensed' => 20.00,
            'totalReimbursed' => 8.50,
            'outstanding' => 11.50,
            'logoSrc' => null,
        ])->render();

        $this->assertStringContainsString('Store reimbursement', $html);
        $this->assertStringContainsString('$8.50', $html);
        $this->assertStringNotContainsString('Other store reimbursement', $html);
        $this->assertStringNotContainsString('$33.00', $html);
    }

    public function test_merchant_cannot_print_reimbursements_pdf_for_unassigned_voucher(): void
    {
        [$user] = $this->actingMerchant();

        $admin = User::factory()->create([
            'user_type' => 'admin',
        ]);
        $otherMerchant = Merchant::query()->create([
            'name' => 'Other Store',
            'is_active' => true,
        ]);
        $adminVoucher = AdminVoucher::query()->create([
            'voucher_code' => 'AVOU-OTHERPDF',
            'name' => 'Other Store Voucher',
            'points_cost' => 10,
            'amount_cost' => 1.00,
            'is_active' => true,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addMonth(),
            'created_by' => $admin->id,
        ]);
        $adminVoucher->merchants()->attach($otherMerchant->id);

        $this->actingAs($user)
            ->get(route('merchant.vouchers.admin-reimbursements-pdf', $adminVoucher->voucher_code))
            ->assertNotFound();
    }

    public function test_admin_voucher_transactions_show_only_this_store_redemptions(): void
    {
        [$user, $merchant] = $this->actingMerchant();

        $admin = User::factory()->create([
            'user_type' => 'admin',
        ]);

        $adminVoucher = AdminVoucher::query()->create([
            'voucher_code' => 'AVOU-TX001',
            'name' => 'Transit Pass',
            'points_cost' => 10,
            'amount_cost' => 1.50,
            'is_active' => true,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addMonth(),
            'created_by' => $admin->id,
        ]);
        $adminVoucher->merchants()->attach($merchant->id);

        $otherMerchant = Merchant::query()->create([
            'name' => 'Other Store',
            'is_active' => true,
        ]);

        $redeemedHereMember = User::factory()->create([
            'user_type' => 'member',
            'name' => 'Amina Store Member',
        ]);
        $redeemedElsewhereMember = User::factory()->create([
            'user_type' => 'member',
            'name' => 'Elsewhere Member',
        ]);

        $redeemedHereMember->adminVouchers()->attach($adminVoucher->id, [
            'status' => 'redeemed',
            'claimed_at' => now()->subDay(),
            'redeemed_at' => now()->subMinutes(20),
            'redeemed_at_merchant_id' => $merchant->id,
        ]);
        $redeemedElsewhereMember->adminVouchers()->attach($adminVoucher->id, [
            'status' => 'redeemed',
            'claimed_at' => now()->subDay(),
            'redeemed_at' => now()->subMinutes(5),
            'redeemed_at_merchant_id' => $otherMerchant->id,
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('setTab', 'admin-vouchers')
            ->call('openAdminTransactions', $adminVoucher->voucher_code)
            ->assertSee('View Transaction History')
            ->assertSee('Transit Pass')
            ->assertSee('Amin**** Stor**** Memb****')
            ->assertDontSee('Amina Store Member')
            ->assertSee('15.00')
            ->assertSee('Transaction History')
            ->assertSee(route('merchant.vouchers.admin-transaction-history-pdf', $adminVoucher->voucher_code), false)
            ->assertDontSee('Elsewhere Member');
    }

    public function test_merchant_can_print_admin_voucher_transaction_history_pdf_for_this_store(): void
    {
        [$user, $merchant] = $this->actingMerchant();

        $admin = User::factory()->create([
            'user_type' => 'admin',
        ]);

        $adminVoucher = AdminVoucher::query()->create([
            'voucher_code' => 'AVOU-TXPDF',
            'name' => 'Transit Pass',
            'points_cost' => 10,
            'amount_cost' => 1.50,
            'is_active' => true,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addMonth(),
            'created_by' => $admin->id,
        ]);
        $adminVoucher->merchants()->attach($merchant->id);

        $otherMerchant = Merchant::query()->create([
            'name' => 'Other Store',
            'is_active' => true,
        ]);

        $redeemedHereMember = User::factory()->create([
            'user_type' => 'member',
            'name' => 'Amina Store Member',
            'qr_code' => 'HV-AMINA',
        ]);
        $redeemedElsewhereMember = User::factory()->create([
            'user_type' => 'member',
            'name' => 'Elsewhere Member',
            'qr_code' => 'HV-ELSE',
        ]);

        $redeemedHereMember->adminVouchers()->attach($adminVoucher->id, [
            'status' => 'redeemed',
            'claimed_at' => now()->subDay(),
            'redeemed_at' => now()->subMinutes(20),
            'redeemed_at_merchant_id' => $merchant->id,
        ]);
        $redeemedElsewhereMember->adminVouchers()->attach($adminVoucher->id, [
            'status' => 'redeemed',
            'claimed_at' => now()->subDay(),
            'redeemed_at' => now()->subMinutes(5),
            'redeemed_at_merchant_id' => $otherMerchant->id,
        ]);

        $this->actingAs($user);

        $response = $this->get(route('merchant.vouchers.admin-transaction-history-pdf', $adminVoucher->voucher_code));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString(
            'transaction-history-kampong-grocer-transit-pass.pdf',
            (string) $response->headers->get('content-disposition')
        );

        $html = view('pdf.merchant-admin-voucher-transaction-history', [
            'merchant' => $merchant,
            'voucher' => $adminVoucher,
            'transactions' => collect([
                (object) [
                    'row_number' => 1,
                    'member_name' => 'Amin**** Stor**** Memb****',
                    'member_code' => 'HV-AMINA',
                    'redeemed_at' => now()->subMinutes(20),
                    'amount' => 15.00,
                ],
            ]),
            'costPerVoucher' => 15.00,
            'totalAmount' => 15.00,
            'logoSrc' => null,
        ])->render();

        $this->assertStringContainsString('Amin**** Stor**** Memb****', $html);
        $this->assertStringNotContainsString('Amina Store Member', $html);
        $this->assertStringContainsString('HV-AMINA', $html);
        $this->assertStringContainsString('$15.00', $html);
        $this->assertStringNotContainsString('Elsewhere Member', $html);
    }

    public function test_merchant_cannot_print_transaction_history_pdf_for_unassigned_voucher(): void
    {
        [$user] = $this->actingMerchant();

        $admin = User::factory()->create([
            'user_type' => 'admin',
        ]);
        $otherMerchant = Merchant::query()->create([
            'name' => 'Other Store',
            'is_active' => true,
        ]);
        $adminVoucher = AdminVoucher::query()->create([
            'voucher_code' => 'AVOU-OTHERTX',
            'name' => 'Other Store Voucher',
            'points_cost' => 10,
            'amount_cost' => 1.00,
            'is_active' => true,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addMonth(),
            'created_by' => $admin->id,
        ]);
        $adminVoucher->merchants()->attach($otherMerchant->id);

        $this->actingAs($user)
            ->get(route('merchant.vouchers.admin-transaction-history-pdf', $adminVoucher->voucher_code))
            ->assertNotFound();
    }

    public function test_non_merchant_user_cannot_access_vouchers_index(): void
    {
        $user = User::factory()->create([
            'user_type' => 'member',
        ]);

        $this->actingAs($user)
            ->get(route('merchant.vouchers.index'))
            ->assertForbidden();
    }

    public function test_non_merchant_user_cannot_print_admin_voucher_reimbursements_pdf(): void
    {
        $user = User::factory()->create([
            'user_type' => 'member',
        ]);

        $this->actingAs($user)
            ->get(route('merchant.vouchers.admin-reimbursements-pdf', 'AVOU-REIMBPDF'))
            ->assertForbidden();
    }

    public function test_non_merchant_user_cannot_print_admin_voucher_transaction_history_pdf(): void
    {
        $user = User::factory()->create([
            'user_type' => 'member',
        ]);

        $this->actingAs($user)
            ->get(route('merchant.vouchers.admin-transaction-history-pdf', 'AVOU-TXPDF'))
            ->assertForbidden();
    }
}
