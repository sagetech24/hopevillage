<?php

namespace App\Livewire\Merchant\Vouchers;

use App\Models\AdminVoucher;
use App\Models\AdminVoucherLedgerEntry;
use App\Models\AdminVoucherReimbursement;
use App\Models\Merchant;
use App\Models\Voucher;
use App\Services\QrCodeService;
use App\Support\MemberNameMask;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public $search = '';

    public $statusFilter = 'all';

    public $tab = 'vouchers';

    public $showMessage = false;

    public $qrVoucherCode = null;

    public $qrVoucherName = null;

    public $qrCodeImage = null;

    public $adminDetailCode = null;

    public $adminReimbursementsCode = null;

    public $adminTransactionsCode = null;

    public $deletingVoucherCode = null;

    public $deletingVoucherName = null;

    protected $paginationTheme = 'tailwind';

    protected $listeners = ['voucher-deleted' => '$refresh'];

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'all'],
        'tab' => ['except' => 'vouchers'],
    ];

    public function mount()
    {
        $this->showMessage = session()->has('message');

        if (! auth()->user()->currentMerchant()) {
            $this->redirect(route('merchant.dashboard'));
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['vouchers', 'admin-vouchers'], true) ? $tab : 'vouchers';
        $this->resetPage();
    }

    public function setStatusFilter(string $status): void
    {
        $allowed = ['all', 'active', 'pending_approval', 'not_yet_valid', 'expired'];

        if (! in_array($status, $allowed, true)) {
            $this->statusFilter = 'all';
        } elseif ($status !== 'all' && $this->statusFilter === $status) {
            $this->statusFilter = 'all';
        } else {
            $this->statusFilter = $status;
        }

        $this->tab = 'vouchers';
        $this->resetPage();
    }

    public function edit($voucher_code)
    {
        return redirect()->route('merchant.vouchers.edit', $voucher_code);
    }

    public function openQr(string $voucherCode): void
    {
        $merchant = auth()->user()->currentMerchant();
        if (! $merchant) {
            $this->redirect(route('merchant.dashboard'));

            return;
        }

        $voucher = Voucher::query()
            ->where('voucher_code', $voucherCode)
            ->where('merchant_id', $merchant->id)
            ->firstOrFail();

        $this->qrVoucherCode = $voucher->voucher_code;
        $this->qrVoucherName = $voucher->name;
        $this->qrCodeImage = app(QrCodeService::class)->generateQrCodeImage($voucher->voucher_code, 400);
    }

    public function closeQr(): void
    {
        $this->qrVoucherCode = null;
        $this->qrVoucherName = null;
        $this->qrCodeImage = null;
    }

    public function openAdminVoucher(string $voucherCode): void
    {
        $this->tab = 'admin-vouchers';
        $this->closeAdminModals();
        $this->adminDetailCode = $voucherCode;
    }

    public function closeAdminVoucher(): void
    {
        $this->adminDetailCode = null;
    }

    public function openAdminReimbursements(string $voucherCode): void
    {
        $this->tab = 'admin-vouchers';
        $this->closeAdminModals();
        $this->adminReimbursementsCode = $voucherCode;
    }

    public function closeAdminReimbursements(): void
    {
        $this->adminReimbursementsCode = null;
    }

    public function openAdminTransactions(string $voucherCode): void
    {
        $this->tab = 'admin-vouchers';
        $this->closeAdminModals();
        $this->adminTransactionsCode = $voucherCode;
    }

    public function closeAdminTransactions(): void
    {
        $this->adminTransactionsCode = null;
    }

    private function closeAdminModals(): void
    {
        $this->adminDetailCode = null;
        $this->adminReimbursementsCode = null;
        $this->adminTransactionsCode = null;
    }

    public function confirmDelete(string $voucherCode, string $voucherName): void
    {
        $this->deletingVoucherCode = $voucherCode;
        $this->deletingVoucherName = $voucherName;
    }

    public function cancelDelete(): void
    {
        $this->deletingVoucherCode = null;
        $this->deletingVoucherName = null;
    }

    public function deleteConfirmed(): void
    {
        if (! $this->deletingVoucherCode) {
            return;
        }

        $this->delete($this->deletingVoucherCode);
        $this->cancelDelete();
    }

    public function delete($voucher_code)
    {
        $merchant = auth()->user()->currentMerchant();
        if (! $merchant) {
            return redirect()->route('merchant.dashboard');
        }

        if (! $merchant->is_active) {
            abort(403, 'Your merchant account is pending approval. You cannot delete vouchers until your account is approved.');
        }

        $voucher = Voucher::where('voucher_code', $voucher_code)
            ->where('merchant_id', $merchant->id)
            ->firstOrFail();

        $voucher->delete();

        session()->flash('message', 'Voucher archived successfully.');
        $this->showMessage = true;
        $this->dispatch('voucher-deleted');
    }

    public function render()
    {
        $merchant = auth()->user()->currentMerchant();

        $emptyStats = [
            'total' => 0,
            'active' => 0,
            'pending_approval' => 0,
            'not_yet_valid' => 0,
            'expired' => 0,
        ];

        if (! $merchant) {
            return view('livewire.merchant.vouchers.index-v2', [
                'vouchers' => collect(),
                'adminVouchers' => collect(),
                'merchant' => null,
                'stats' => $emptyStats,
                'adminTotal' => 0,
                'selectedAdminVoucher' => null,
                'selectedAdminReimbursements' => null,
                'selectedAdminTransactions' => null,
            ])->layout('layouts.app');
        }

        $query = Voucher::query()
            ->where('merchant_id', $merchant->id)
            ->with('media')
            ->withCount([
                'users as claimed_members_count' => fn ($q) => $q->where('user_voucher.status', 'claimed'),
                'users as redeemed_members_count' => fn ($q) => $q->where('user_voucher.status', 'redeemed'),
            ]);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('voucher_code', 'like', '%'.$this->search.'%')
                    ->orWhere('description', 'like', '%'.$this->search.'%');
            });
        }

        $allMerchantVouchers = $query->get()
            ->sortBy(function (Voucher $voucher) {
                return [
                    $voucher->getDisplayStatusSortOrder(),
                    $voucher->valid_until ? -$voucher->valid_until->timestamp : PHP_INT_MIN,
                    -$voucher->id,
                ];
            })
            ->values();

        $stats = [
            'total' => $allMerchantVouchers->count(),
            'active' => $allMerchantVouchers->filter(fn (Voucher $voucher) => $voucher->getDisplayStatusLabel() === 'Active')->count(),
            'pending_approval' => $allMerchantVouchers->filter(fn (Voucher $voucher) => $voucher->getDisplayStatusLabel() === 'Pending Approval')->count(),
            'not_yet_valid' => $allMerchantVouchers->filter(fn (Voucher $voucher) => $voucher->getDisplayStatusLabel() === 'Not Yet Valid')->count(),
            'expired' => $allMerchantVouchers->filter(fn (Voucher $voucher) => $voucher->getDisplayStatusLabel() === 'Expired')->count(),
        ];

        $vouchers = $this->applyStatusFilter($allMerchantVouchers);

        $adminVouchersQuery = AdminVoucher::query()
            ->whereHas('merchants', function ($q) use ($merchant) {
                $q->where('merchants.id', $merchant->id);
            })
            ->with(['media', 'merchants'])
            ->withCount([
                'users as claimed_members_count' => fn ($q) => $q->where('user_admin_voucher.status', 'claimed'),
                'users as redeemed_members_count' => fn ($q) => $q->where('user_admin_voucher.status', 'redeemed'),
                'users as redeemed_at_store_count' => fn ($q) => $q
                    ->where('user_admin_voucher.status', 'redeemed')
                    ->where('user_admin_voucher.redeemed_at_merchant_id', $merchant->id),
            ]);

        if ($this->search) {
            $adminVouchersQuery->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('voucher_code', 'like', '%'.$this->search.'%')
                    ->orWhere('description', 'like', '%'.$this->search.'%');
            });
        }

        $adminVouchers = $adminVouchersQuery->get()
            ->sortBy(function (AdminVoucher $voucher) {
                return [
                    $voucher->getDisplayStatusSortOrder(),
                    $voucher->valid_until ? -$voucher->valid_until->timestamp : PHP_INT_MIN,
                    -$voucher->id,
                ];
            })
            ->values();

        $selectedAdminVoucher = $this->resolveMerchantAdminVoucher(
            $this->adminDetailCode,
            $adminVouchers,
            $merchant
        );
        $reimbursementVoucher = $this->resolveMerchantAdminVoucher(
            $this->adminReimbursementsCode,
            $adminVouchers,
            $merchant
        );
        $transactionVoucher = $this->resolveMerchantAdminVoucher(
            $this->adminTransactionsCode,
            $adminVouchers,
            $merchant
        );

        return view('livewire.merchant.vouchers.index-v2', [
            'vouchers' => $vouchers,
            'adminVouchers' => $adminVouchers,
            'merchant' => $merchant,
            'stats' => $stats,
            'adminTotal' => $adminVouchers->count(),
            'selectedAdminVoucher' => $selectedAdminVoucher,
            'selectedAdminReimbursements' => $reimbursementVoucher
                ? $this->adminReimbursementPayload($reimbursementVoucher, $merchant)
                : null,
            'selectedAdminTransactions' => $transactionVoucher
                ? $this->adminTransactionPayload($transactionVoucher, $merchant)
                : null,
        ])->layout('layouts.app');
    }

    private function resolveMerchantAdminVoucher(?string $voucherCode, Collection $adminVouchers, Merchant $merchant): ?AdminVoucher
    {
        if (! $voucherCode) {
            return null;
        }

        $voucher = $adminVouchers->firstWhere('voucher_code', $voucherCode);
        if ($voucher) {
            return $voucher;
        }

        return AdminVoucher::query()
            ->where('voucher_code', $voucherCode)
            ->whereHas('merchants', function ($q) use ($merchant) {
                $q->where('merchants.id', $merchant->id);
            })
            ->with(['media', 'merchants'])
            ->withCount([
                'users as claimed_members_count' => fn ($q) => $q->where('user_admin_voucher.status', 'claimed'),
                'users as redeemed_members_count' => fn ($q) => $q->where('user_admin_voucher.status', 'redeemed'),
                'users as redeemed_at_store_count' => fn ($q) => $q
                    ->where('user_admin_voucher.status', 'redeemed')
                    ->where('user_admin_voucher.redeemed_at_merchant_id', $merchant->id),
            ])
            ->first();
    }

    private function adminReimbursementPayload(AdminVoucher $voucher, Merchant $merchant): array
    {
        $entries = AdminVoucherLedgerEntry::query()
            ->where('merchant_id', $merchant->id)
            ->where('admin_voucher_id', $voucher->id)
            ->with('adminVoucher')
            ->withSum('reimbursements', 'amount')
            ->orderByDesc('period_month')
            ->get();

        $reimbursements = AdminVoucherReimbursement::query()
            ->whereHas('ledgerEntry', function ($q) use ($merchant, $voucher) {
                $q->where('merchant_id', $merchant->id)
                    ->where('admin_voucher_id', $voucher->id);
            })
            ->with(['ledgerEntry', 'createdBy'])
            ->orderByDesc('reimbursed_at')
            ->orderByDesc('id')
            ->get();

        $totalDispensed = round((float) $entries->sum(fn (AdminVoucherLedgerEntry $entry) => $entry->computedTotalDispensed()), 2);
        $totalReimbursed = round((float) $reimbursements->sum('amount'), 2);

        return [
            'voucher' => $voucher,
            'reimbursements' => $reimbursements,
            'totalDispensed' => $totalDispensed,
            'totalReimbursed' => $totalReimbursed,
            'outstanding' => round($totalDispensed - $totalReimbursed, 2),
        ];
    }

    private function adminTransactionPayload(AdminVoucher $voucher, Merchant $merchant): array
    {
        $costPerVoucher = round(
            max(0, (float) ($voucher->amount_cost ?? 0)) * max(0, (float) ($voucher->points_cost ?? 0)),
            2
        );

        $transactions = $voucher->users()
            ->wherePivot('status', 'redeemed')
            ->wherePivot('redeemed_at_merchant_id', $merchant->id)
            ->orderByPivot('redeemed_at', 'desc')
            ->get()
            ->map(function ($user) use ($costPerVoucher) {
                return [
                    'name' => MemberNameMask::mask($user->name),
                    'qr_code' => $user->qr_code,
                    'redeemed_at' => $user->pivot->redeemed_at,
                    'amount' => $costPerVoucher,
                ];
            });

        return [
            'voucher' => $voucher,
            'transactions' => $transactions,
            'costPerVoucher' => $costPerVoucher,
            'totalAmount' => round($costPerVoucher * $transactions->count(), 2),
        ];
    }

    private function applyStatusFilter(Collection $vouchers): Collection
    {
        if ($this->statusFilter === 'all') {
            return $vouchers->values();
        }

        $label = match ($this->statusFilter) {
            'active' => 'Active',
            'pending_approval' => 'Pending Approval',
            'not_yet_valid' => 'Not Yet Valid',
            'expired' => 'Expired',
            default => null,
        };

        if ($label === null) {
            return $vouchers->values();
        }

        return $vouchers
            ->filter(fn ($voucher) => $voucher->getDisplayStatusLabel() === $label)
            ->values();
    }
}
