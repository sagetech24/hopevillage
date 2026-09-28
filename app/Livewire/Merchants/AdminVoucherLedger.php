<?php

namespace App\Livewire\Merchants;

use App\Models\AdminVoucherLedgerEntry;
use App\Models\AdminVoucherReimbursement;
use App\Models\MerchantAdminVoucherInvoice;
use App\Services\AdminVoucherLedgerSyncService;
use Livewire\Component;
use Livewire\WithPagination;

class AdminVoucherLedger extends Component
{
    use WithPagination;

    public string $dateFilter = 'all';

    public ?string $monthFrom = null;

    public ?string $monthTo = null;

    public string $merchantSearch = '';

    public bool $outstandingOnly = false;

    public bool $showMessage = false;

    public ?string $syncMessage = null;

    public bool $showReimburseModal = false;

    public bool $showHistoryModal = false;

    public ?int $selectedLedgerEntryId = null;

    public ?int $selectedHistoryEntryId = null;

    public string $reimbAmount = '';

    public string $reimbDate = '';

    public string $reimbNotes = '';

    public string $viewMode = 'card';

    protected $paginationTheme = 'tailwind';

    protected $queryString = [
        'dateFilter' => ['except' => 'all'],
        'monthFrom' => ['except' => ''],
        'monthTo' => ['except' => ''],
        'merchantSearch' => ['except' => ''],
        'outstandingOnly' => ['except' => false],
    ];

    public function mount(): void
    {
        $this->viewMode = session('admin_voucher_ledger_view_mode', 'card');
    }

    public function setViewMode(string $mode): void
    {
        if (! in_array($mode, ['card', 'list'], true)) {
            return;
        }

        $this->viewMode = $mode;
        session(['admin_voucher_ledger_view_mode' => $mode]);
    }

    public function updatingDateFilter(): void
    {
        $this->resetPage();
    }

    public function updatingMerchantSearch(): void
    {
        $this->resetPage();
    }

    public function updatingOutstandingOnly(): void
    {
        $this->resetPage();
    }

    public function syncLedger(): void
    {
        $service = app(AdminVoucherLedgerSyncService::class);
        $count = $service->sync();
        $this->syncMessage = "Ledger synced successfully. {$count} entries updated.";
        $this->showMessage = true;
    }

    public function openReimburseModal(int $entryId): void
    {
        $this->selectedLedgerEntryId = $entryId;
        $this->showReimburseModal = true;
        $this->reimbAmount = '';
        $this->reimbDate = now()->format('Y-m-d');
        $this->reimbNotes = '';
        $this->resetValidation();
    }

    public function closeReimburseModal(): void
    {
        $this->showReimburseModal = false;
        $this->selectedLedgerEntryId = null;
        $this->reimbAmount = '';
        $this->reimbDate = '';
        $this->reimbNotes = '';
        $this->resetValidation();
    }

    public function openHistoryModal(int $entryId): void
    {
        $this->selectedHistoryEntryId = $entryId;
        $this->showHistoryModal = true;
    }

    public function closeHistoryModal(): void
    {
        $this->showHistoryModal = false;
        $this->selectedHistoryEntryId = null;
    }

    public function saveReimbursement(): void
    {
        $this->validate([
            'reimbAmount' => 'required|numeric|min:0.01',
            'reimbDate' => 'required|date',
        ]);

        $entry = AdminVoucherLedgerEntry::with('adminVoucher')
            ->withSum('reimbursements', 'amount')
            ->findOrFail($this->selectedLedgerEntryId);
        $outstanding = $entry->outstanding_balance;
        $amount = (float) $this->reimbAmount;

        if ($amount > $outstanding) {
            $this->addError('reimbAmount', 'Amount cannot exceed outstanding balance ($'.number_format($outstanding, 2).').');

            return;
        }

        AdminVoucherReimbursement::create([
            'admin_voucher_ledger_entry_id' => $this->selectedLedgerEntryId,
            'amount' => $amount,
            'reimbursed_at' => $this->reimbDate,
            'notes' => $this->reimbNotes ?: null,
            'created_by' => auth()->id(),
        ]);

        $this->closeReimburseModal();
        $this->dispatch('hv-toast', type: 'success', message: 'Reimbursement saved successfully.');
    }

    public function getSelectedLedgerEntryProperty(): ?AdminVoucherLedgerEntry
    {
        if (! $this->selectedLedgerEntryId) {
            return null;
        }

        return AdminVoucherLedgerEntry::with(['merchant', 'adminVoucher'])
            ->withSum('reimbursements', 'amount')
            ->find($this->selectedLedgerEntryId);
    }

    public function getHistoryLedgerEntryProperty(): ?AdminVoucherLedgerEntry
    {
        if (! $this->selectedHistoryEntryId) {
            return null;
        }

        return AdminVoucherLedgerEntry::with(['merchant', 'adminVoucher', 'reimbursements.createdBy'])
            ->withSum('reimbursements', 'amount')
            ->find($this->selectedHistoryEntryId);
    }

    public function render()
    {
        $query = AdminVoucherLedgerEntry::query()
            ->with(['merchant', 'adminVoucher'])
            ->withSum('reimbursements', 'amount');

        if ($this->dateFilter === '3months') {
            $query->where('period_month', '>=', now()->subMonths(3)->startOfMonth());
        } elseif ($this->dateFilter === '6months') {
            $query->where('period_month', '>=', now()->subMonths(6)->startOfMonth());
        } elseif ($this->dateFilter === '12months') {
            $query->where('period_month', '>=', now()->subMonths(12)->startOfMonth());
        } elseif ($this->dateFilter === 'custom' && ($this->monthFrom || $this->monthTo)) {
            if ($this->monthFrom) {
                $query->where('period_month', '>=', $this->monthFrom.'-01');
            }
            if ($this->monthTo) {
                $query->where('period_month', '<=', $this->monthTo.'-01');
            }
        }

        if ($this->merchantSearch !== '') {
            $query->whereHas('merchant', function ($q) {
                $q->where('name', 'like', '%'.$this->merchantSearch.'%')
                    ->orWhere('merchant_code', 'like', '%'.$this->merchantSearch.'%');
            });
        }

        if ($this->outstandingOnly) {
            $query->whereRaw(
                '(SELECT COALESCE(SUM(amount), 0) FROM admin_voucher_reimbursements WHERE admin_voucher_reimbursements.admin_voucher_ledger_entry_id = admin_voucher_ledger_entries.id) < (admin_voucher_ledger_entries.total_redemptions * COALESCE((SELECT admin_vouchers.amount_cost * admin_vouchers.points_cost FROM admin_vouchers WHERE admin_vouchers.id = admin_voucher_ledger_entries.admin_voucher_id), 0))'
            );
        }

        $entries = $query->orderByDesc('period_month')
            ->orderBy('admin_voucher_ledger_entries.id')
            ->paginate(15);

        $invoices = MerchantAdminVoucherInvoice::query()
            ->with(['merchant', 'generatedBy'])
            ->when($this->merchantSearch !== '', function ($invoiceQuery) {
                $invoiceQuery->whereHas('merchant', function ($merchantQuery) {
                    $merchantQuery->where('name', 'like', '%'.$this->merchantSearch.'%')
                        ->orWhere('merchant_code', 'like', '%'.$this->merchantSearch.'%');
                });
            })
            ->orderByDesc('generated_at')
            ->limit(25)
            ->get();

        $invoiceLookup = MerchantAdminVoucherInvoice::query()
            ->when($entries->isNotEmpty(), function ($invoiceQuery) use ($entries) {
                $invoiceQuery->whereIn('merchant_id', $entries->pluck('merchant_id')->unique())
                    ->whereIn('admin_voucher_id', $entries->pluck('admin_voucher_id')->unique());
            }, function ($invoiceQuery) {
                $invoiceQuery->whereRaw('0 = 1');
            })
            ->get()
            ->keyBy(fn (MerchantAdminVoucherInvoice $invoice) => MerchantAdminVoucherInvoice::pairKey($invoice->merchant_id, $invoice->admin_voucher_id));

        return view('livewire.merchants.admin-voucher-ledger', [
            'entries' => $entries,
            'selectedLedgerEntry' => $this->selectedLedgerEntry,
            'historyLedgerEntry' => $this->historyLedgerEntry,
            'invoices' => $invoices,
            'invoiceLookup' => $invoiceLookup,
        ]);
    }
}
