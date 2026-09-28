<?php

namespace Tests\Feature;

use App\Livewire\Merchant\Reimbursements\Index;
use App\Livewire\Merchants\AdminVoucherLedger;
use App\Models\AdminVoucher;
use App\Models\AdminVoucherLedgerEntry;
use App\Models\AdminVoucherReimbursement;
use App\Models\Merchant;
use App\Models\MerchantAdminVoucherInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MerchantReimbursementsIndexTest extends TestCase
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

    public function test_merchant_user_can_view_reimbursement_records_page(): void
    {
        [$user] = $this->actingMerchant();

        $this->actingAs($user)
            ->get(route('merchant.reimbursements.index'))
            ->assertOk()
            ->assertSee('Merchant Portal')
            ->assertSee('Reimbursement Records')
            ->assertSee('Records by voucher')
            ->assertSee(route('merchant.reimbursements.index'), false);
    }

    public function test_merchant_without_store_can_open_reimbursements_without_forbidden(): void
    {
        $user = User::factory()->create([
            'user_type' => 'merchant_user',
        ]);

        $this->actingAs($user)
            ->get(route('merchant.reimbursements.index'))
            ->assertOk()
            ->assertSee('No store assigned yet');
    }

    public function test_non_merchant_user_cannot_access_reimbursements(): void
    {
        $user = User::factory()->create([
            'user_type' => 'member',
        ]);

        $this->actingAs($user)
            ->get(route('merchant.reimbursements.index'))
            ->assertForbidden();
    }

    public function test_reimbursement_records_show_only_this_store(): void
    {
        [$user, $merchant] = $this->actingMerchant();

        $admin = User::factory()->create([
            'user_type' => 'admin',
        ]);

        $adminVoucher = $this->createAdminVoucher($admin, 'Meal Voucher', 'AVOU-REIMB-PAGE');
        $adminVoucher->merchants()->attach($merchant->id);

        $otherMerchant = Merchant::query()->create([
            'name' => 'Other Store',
            'is_active' => true,
        ]);

        $ownEntry = AdminVoucherLedgerEntry::query()->create([
            'merchant_id' => $merchant->id,
            'admin_voucher_id' => $adminVoucher->id,
            'period_month' => now()->startOfMonth()->toDateString(),
            'total_redemptions' => 20,
            'total_amount_dispensed' => 200.00,
        ]);
        $otherEntry = AdminVoucherLedgerEntry::query()->create([
            'merchant_id' => $otherMerchant->id,
            'admin_voucher_id' => $adminVoucher->id,
            'period_month' => now()->startOfMonth()->toDateString(),
            'total_redemptions' => 40,
            'total_amount_dispensed' => 400.00,
        ]);

        AdminVoucherReimbursement::query()->create([
            'admin_voucher_ledger_entry_id' => $ownEntry->id,
            'amount' => 75.50,
            'reimbursed_at' => now()->toDateString(),
            'notes' => 'Store reimbursement',
            'created_by' => $admin->id,
        ]);
        AdminVoucherReimbursement::query()->create([
            'admin_voucher_ledger_entry_id' => $otherEntry->id,
            'amount' => 310.00,
            'reimbursed_at' => now()->toDateString(),
            'notes' => 'Other store reimbursement',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($user)
            ->get(route('merchant.reimbursements.index'))
            ->assertOk()
            ->assertSee('Meal Voucher')
            ->assertSee('Store reimbursement')
            ->assertSee('75.50')
            ->assertSee('Print to PDF')
            ->assertSee(route('merchant.vouchers.admin-reimbursements-pdf', $adminVoucher->voucher_code), false)
            ->assertDontSee('Other store reimbursement')
            ->assertDontSee('310.00');
    }

    public function test_reimbursement_records_exclude_unassigned_admin_vouchers(): void
    {
        [$user, $merchant] = $this->actingMerchant();

        $admin = User::factory()->create([
            'user_type' => 'admin',
        ]);

        $assigned = $this->createAdminVoucher($admin, 'Assigned Meal', 'AVOU-ASSIGNED');
        $unassigned = $this->createAdminVoucher($admin, 'Unassigned Meal', 'AVOU-UNASSIGNED');
        $assigned->merchants()->attach($merchant->id);

        $assignedEntry = AdminVoucherLedgerEntry::query()->create([
            'merchant_id' => $merchant->id,
            'admin_voucher_id' => $assigned->id,
            'period_month' => now()->startOfMonth()->toDateString(),
            'total_redemptions' => 10,
            'total_amount_dispensed' => 100.00,
        ]);
        $unassignedEntry = AdminVoucherLedgerEntry::query()->create([
            'merchant_id' => $merchant->id,
            'admin_voucher_id' => $unassigned->id,
            'period_month' => now()->startOfMonth()->toDateString(),
            'total_redemptions' => 8,
            'total_amount_dispensed' => 80.00,
        ]);

        AdminVoucherReimbursement::query()->create([
            'admin_voucher_ledger_entry_id' => $assignedEntry->id,
            'amount' => 40.00,
            'reimbursed_at' => now()->toDateString(),
            'notes' => 'Assigned store payment',
            'created_by' => $admin->id,
        ]);
        AdminVoucherReimbursement::query()->create([
            'admin_voucher_ledger_entry_id' => $unassignedEntry->id,
            'amount' => 55.00,
            'reimbursed_at' => now()->toDateString(),
            'notes' => 'Unassigned voucher payment',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($user)
            ->get(route('merchant.reimbursements.index'))
            ->assertOk()
            ->assertSee('Assigned Meal')
            ->assertSee('Assigned store payment')
            ->assertDontSee('Unassigned Meal')
            ->assertDontSee('Unassigned voucher payment');
    }

    public function test_search_filters_reimbursement_records_by_notes(): void
    {
        [$user, $merchant] = $this->actingMerchant();

        $admin = User::factory()->create([
            'user_type' => 'admin',
        ]);

        $adminVoucher = $this->createAdminVoucher($admin, 'Meal Voucher', 'AVOU-SEARCH');
        $adminVoucher->merchants()->attach($merchant->id);

        $entry = AdminVoucherLedgerEntry::query()->create([
            'merchant_id' => $merchant->id,
            'admin_voucher_id' => $adminVoucher->id,
            'period_month' => now()->startOfMonth()->toDateString(),
            'total_redemptions' => 10,
            'total_amount_dispensed' => 100.00,
        ]);

        AdminVoucherReimbursement::query()->create([
            'admin_voucher_ledger_entry_id' => $entry->id,
            'amount' => 25.00,
            'reimbursed_at' => now()->toDateString(),
            'notes' => 'September payout',
            'created_by' => $admin->id,
        ]);
        AdminVoucherReimbursement::query()->create([
            'admin_voucher_ledger_entry_id' => $entry->id,
            'amount' => 15.00,
            'reimbursed_at' => now()->subDay()->toDateString(),
            'notes' => 'August adjustment',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->set('search', 'September')
            ->assertSee('September payout')
            ->assertDontSee('August adjustment');
    }

    public function test_outstanding_filter_hides_fully_reimbursed_vouchers(): void
    {
        [$user, $merchant] = $this->actingMerchant();

        $admin = User::factory()->create([
            'user_type' => 'admin',
        ]);

        $openVoucher = $this->createAdminVoucher($admin, 'Open Balance Voucher', 'AVOU-OPEN');
        $paidVoucher = $this->createAdminVoucher($admin, 'Paid In Full Voucher', 'AVOU-PAID');
        $openVoucher->merchants()->attach($merchant->id);
        $paidVoucher->merchants()->attach($merchant->id);

        $openEntry = AdminVoucherLedgerEntry::query()->create([
            'merchant_id' => $merchant->id,
            'admin_voucher_id' => $openVoucher->id,
            'period_month' => now()->startOfMonth()->toDateString(),
            'total_redemptions' => 10,
            'total_amount_dispensed' => 100.00,
        ]);
        $paidEntry = AdminVoucherLedgerEntry::query()->create([
            'merchant_id' => $merchant->id,
            'admin_voucher_id' => $paidVoucher->id,
            'period_month' => now()->startOfMonth()->toDateString(),
            'total_redemptions' => 5,
            'total_amount_dispensed' => 50.00,
        ]);

        AdminVoucherReimbursement::query()->create([
            'admin_voucher_ledger_entry_id' => $openEntry->id,
            'amount' => 20.00,
            'reimbursed_at' => now()->toDateString(),
            'created_by' => $admin->id,
        ]);
        AdminVoucherReimbursement::query()->create([
            'admin_voucher_ledger_entry_id' => $paidEntry->id,
            'amount' => 50.00,
            'reimbursed_at' => now()->toDateString(),
            'created_by' => $admin->id,
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->assertSee('Open Balance Voucher')
            ->assertSee('Paid In Full Voucher')
            ->call('setStatusFilter', 'outstanding')
            ->assertSee('Open Balance Voucher')
            ->assertDontSee('Paid In Full Voucher');
    }

    public function test_invoice_list_shows_finished_or_inactive_assigned_vouchers_only(): void
    {
        [$user, $merchant] = $this->actingMerchant();
        $admin = User::factory()->create(['user_type' => 'admin']);

        $expired = $this->createAdminVoucher($admin, 'August Meal', 'AVOU-AUG');
        $expired->update([
            'is_active' => true,
            'valid_from' => now()->subMonths(2),
            'valid_until' => now()->subDay(),
        ]);
        $expired->merchants()->attach($merchant->id);

        $inactive = $this->createAdminVoucher($admin, 'Paused Meal', 'AVOU-PAUSE');
        $inactive->update([
            'is_active' => false,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addMonth(),
        ]);
        $inactive->merchants()->attach($merchant->id);

        $running = $this->createAdminVoucher($admin, 'Still Running Voucher', 'AVOU-RUN');
        $running->merchants()->attach($merchant->id);

        $otherMerchant = Merchant::query()->create([
            'name' => 'Other Store',
            'is_active' => true,
        ]);
        $otherVoucher = $this->createAdminVoucher($admin, 'Other Store Meal', 'AVOU-OTHER');
        $otherVoucher->update(['valid_until' => now()->subDay()]);
        $otherVoucher->merchants()->attach($otherMerchant->id);

        $this->redeem($expired, $merchant, 1);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->assertSee('August Meal')
            ->assertSee('Paused Meal')
            ->assertSee('No redemptions to invoice')
            ->assertDontSee('Still Running Voucher')
            ->assertDontSee('Other Store Meal');
    }

    public function test_blank_invoice_number_is_generated_and_amount_is_redemptions_times_cost(): void
    {
        [$user, $merchant] = $this->actingMerchant();
        $admin = User::factory()->create(['user_type' => 'admin']);
        $voucher = $this->finishedVoucher($admin, $merchant, 'Billable Meal', 'AVOU-BILL');
        $this->redeem($voucher, $merchant, 2);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openInvoiceModal', $voucher->id)
            ->set('bankName', 'DBS')
            ->set('accountName', 'Kampong Grocer')
            ->set('accountNumber', '123-456')
            ->call('saveInvoice')
            ->assertHasNoErrors()
            ->assertSee('Download invoice');

        $invoice = MerchantAdminVoucherInvoice::query()->first();
        $this->assertNotNull($invoice);
        $this->assertSame('INV-'.now()->year.'-0001', $invoice->invoice_number);
        $this->assertSame(2, $invoice->redeemed_count);
        $this->assertSame('10.00', $invoice->cost_per_voucher);
        $this->assertSame('20.00', $invoice->amount);
        $this->assertSame([
            'bank_name' => 'DBS',
            'account_name' => 'Kampong Grocer',
            'account_number' => '123-456',
        ], $invoice->bank_account);
        $this->assertSame(1, MerchantAdminVoucherInvoice::query()->count());

        Livewire::test(Index::class)
            ->call('openInvoiceModal', $voucher->id)
            ->call('saveInvoice');

        $this->assertSame(1, MerchantAdminVoucherInvoice::query()->count());
    }

    public function test_custom_invoice_number_is_saved_and_duplicates_are_rejected(): void
    {
        [$user, $merchant] = $this->actingMerchant();
        $admin = User::factory()->create(['user_type' => 'admin']);
        $first = $this->finishedVoucher($admin, $merchant, 'First Meal', 'AVOU-FIRST');
        $second = $this->finishedVoucher($admin, $merchant, 'Second Meal', 'AVOU-SECOND');
        $this->redeem($first, $merchant, 1);
        $this->redeem($second, $merchant, 1);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openInvoiceModal', $first->id)
            ->set('invoiceNumber', 'SHOP-99')
            ->set('bankName', 'DBS')
            ->set('accountName', 'Kampong Grocer')
            ->set('accountNumber', '123-456')
            ->call('saveInvoice')
            ->assertHasNoErrors();

        $this->assertSame('SHOP-99', MerchantAdminVoucherInvoice::query()->first()->invoice_number);

        Livewire::test(Index::class)
            ->call('openInvoiceModal', $second->id)
            ->set('invoiceNumber', 'SHOP-99')
            ->set('bankName', 'OCBC')
            ->set('accountName', 'Kampong Grocer')
            ->set('accountNumber', '999')
            ->call('saveInvoice')
            ->assertHasErrors(['invoiceNumber']);

        $this->assertSame(1, MerchantAdminVoucherInvoice::query()->count());
    }

    public function test_bank_account_fields_are_required(): void
    {
        [$user, $merchant] = $this->actingMerchant();
        $admin = User::factory()->create(['user_type' => 'admin']);
        $voucher = $this->finishedVoucher($admin, $merchant, 'Bank Meal', 'AVOU-BANK');
        $this->redeem($voucher, $merchant, 1);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openInvoiceModal', $voucher->id)
            ->call('saveInvoice')
            ->assertHasErrors(['bankName', 'accountName', 'accountNumber']);

        $this->assertSame(0, MerchantAdminVoucherInvoice::query()->count());
    }

    public function test_merchant_can_reuse_bank_details_from_a_previous_invoice(): void
    {
        [$user, $merchant] = $this->actingMerchant();
        $admin = User::factory()->create(['user_type' => 'admin']);
        $first = $this->finishedVoucher($admin, $merchant, 'First Saved Bank Meal', 'AVOU-BANK-1');
        $second = $this->finishedVoucher($admin, $merchant, 'Second Saved Bank Meal', 'AVOU-BANK-2');
        $this->redeem($first, $merchant, 1);
        $this->redeem($second, $merchant, 1);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openInvoiceModal', $first->id)
            ->set('bankName', 'DBS')
            ->set('accountName', 'Kampong Grocer')
            ->set('accountNumber', '123-456')
            ->call('saveInvoice')
            ->assertHasNoErrors();

        Livewire::test(Index::class)
            ->call('openInvoiceModal', $second->id)
            ->assertSee('Saved details')
            ->call('setBankDetailsMode', 'saved')
            ->assertSee('DBS')
            ->assertSee('123-456')
            ->call('saveInvoice')
            ->assertHasErrors(['selectedSavedBank'])
            ->set('selectedSavedBank', 'dbs|kampong grocer|123-456')
            ->call('saveInvoice')
            ->assertHasNoErrors();

        $reused = MerchantAdminVoucherInvoice::query()->where('admin_voucher_id', $second->id)->first();
        $this->assertNotNull($reused);
        $this->assertSame([
            'bank_name' => 'DBS',
            'account_name' => 'Kampong Grocer',
            'account_number' => '123-456',
        ], $reused->bank_account);
    }

    public function test_invoice_pdf_is_limited_to_the_store_and_visible_to_admin(): void
    {
        [$user, $merchant] = $this->actingMerchant();
        $user->update(['name' => 'Mina Merchant']);
        $admin = User::factory()->create([
            'user_type' => 'admin',
            'name' => 'Hope Admin',
        ]);
        $voucher = $this->finishedVoucher($admin, $merchant, 'Pdf Meal', 'AVOU-PDF');
        $this->redeem($voucher, $merchant, 1);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('openInvoiceModal', $voucher->id)
            ->set('invoiceNumber', 'PDF-1')
            ->set('bankName', 'DBS')
            ->set('accountName', 'Kampong Grocer')
            ->set('accountNumber', '123-456')
            ->call('saveInvoice');

        $invoice = MerchantAdminVoucherInvoice::query()->firstOrFail();

        $this->actingAs($user)
            ->get(route('merchant.admin-voucher-invoices.pdf', $invoice))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        [$otherUser] = $this->actingMerchant();
        $this->actingAs($otherUser)
            ->get(route('merchant.admin-voucher-invoices.pdf', $invoice))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('admin.merchant-admin-voucher-invoices.pdf', $invoice))
            ->assertOk();

        Livewire::actingAs($admin)
            ->test(AdminVoucherLedger::class)
            ->assertSee('PDF-1')
            ->assertSee('Kampong Grocer')
            ->assertSee('Mina Merchant')
            ->assertSee('Pdf Meal');
    }

    private function finishedVoucher(User $admin, Merchant $merchant, string $name, string $code): AdminVoucher
    {
        $voucher = $this->createAdminVoucher($admin, $name, $code);
        $voucher->update([
            'is_active' => true,
            'valid_from' => now()->subMonth(),
            'valid_until' => now()->subDay(),
        ]);
        $voucher->merchants()->attach($merchant->id);

        return $voucher;
    }

    private function redeem(AdminVoucher $voucher, Merchant $merchant, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $member = User::factory()->create(['user_type' => 'member']);
            $member->adminVouchers()->attach($voucher->id, [
                'status' => 'redeemed',
                'claimed_at' => now(),
                'redeemed_at' => now(),
                'redeemed_at_merchant_id' => $merchant->id,
            ]);
        }
    }
}
