<?php

namespace App\Livewire\Merchant\Reimbursements;

use App\Models\AdminVoucherLedgerEntry;
use App\Models\Merchant;
use App\Models\MerchantAdminVoucherInvoice;
use App\Services\MerchantAdminVoucherInvoiceService;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Index extends Component
{
    public string $search = '';

    public string $statusFilter = 'all';

    public bool $showInvoiceModal = false;

    public ?int $selectedInvoiceVoucherId = null;

    public string $invoiceNumber = '';

    public string $bankDetailsMode = 'new';

    public string $selectedSavedBank = '';

    public string $bankName = '';

    public string $accountName = '';

    public string $accountNumber = '';

    public string $editingSavedBankKey = '';

    public string $editBankName = '';

    public string $editAccountName = '';

    public string $editAccountNumber = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'all'],
    ];

    public function setStatusFilter(string $status): void
    {
        $allowed = ['all', 'outstanding', 'reimbursed'];

        if (! in_array($status, $allowed, true)) {
            $this->statusFilter = 'all';
        } elseif ($status !== 'all' && $this->statusFilter === $status) {
            $this->statusFilter = 'all';
        } else {
            $this->statusFilter = $status;
        }
    }

    public function render()
    {
        $merchant = auth()->user()?->currentMerchant();
        $totals = ['reimbursed' => 0.0, 'receivables' => 0.0, 'outstanding' => 0.0];
        $groups = collect();
        $stats = [
            'all' => 0,
            'outstanding' => 0,
            'reimbursed' => 0,
        ];

        $billable = collect();

        if ($merchant) {
            $totals = $merchant->adminVoucherReimbursementTotals();
            $totals['outstanding'] = round((float) $totals['receivables'] - (float) $totals['reimbursed'], 2);

            $billable = app(MerchantAdminVoucherInvoiceService::class)->billableVouchers($merchant);
            $groups = $this->voucherGroups($merchant);
            $stats = [
                'all' => $groups->count(),
                'outstanding' => $groups->filter(fn (array $group) => $group['outstanding'] > 0)->count(),
                'reimbursed' => $groups->filter(fn (array $group) => $group['outstanding'] <= 0 && $group['receivables'] > 0)->count(),
            ];
            $groups = $this->applyFilters($groups);
        }

        return view('livewire.merchant.reimbursements.index', [
            'merchant' => $merchant,
            'totals' => $totals,
            'groups' => $groups,
            'stats' => $stats,
            'billable' => $billable,
            'selectedBillable' => $billable->first(
                fn (array $row) => $row['voucher']->id === $this->selectedInvoiceVoucherId
            ),
            'savedBanks' => $merchant ? $this->savedBankAccounts($merchant) : collect(),
        ])->layout('layouts.app');
    }

    public function setBankDetailsMode(string $mode): void
    {
        if (! in_array($mode, ['new', 'saved'], true)) {
            return;
        }

        $this->bankDetailsMode = $mode;
        $this->resetSavedBankEdit();
        $this->resetValidation();
    }

    public function openInvoiceModal(int $adminVoucherId): void
    {
        $merchant = auth()->user()?->currentMerchant();
        if (! $merchant) {
            return;
        }

        $row = app(MerchantAdminVoucherInvoiceService::class)
            ->billableVouchers($merchant)
            ->first(fn (array $item) => $item['voucher']->id === $adminVoucherId);

        if ($row === null || $row['redeemed_count'] < 1 || $row['invoice'] !== null) {
            return;
        }

        $this->selectedInvoiceVoucherId = $adminVoucherId;
        $this->invoiceNumber = '';
        $this->bankDetailsMode = 'new';
        $this->selectedSavedBank = '';
        $this->bankName = '';
        $this->accountName = '';
        $this->accountNumber = '';
        $this->resetSavedBankEdit();
        $this->showInvoiceModal = true;
        $this->resetValidation();
    }

    public function closeInvoiceModal(): void
    {
        $this->showInvoiceModal = false;
        $this->selectedInvoiceVoucherId = null;
        $this->invoiceNumber = '';
        $this->bankDetailsMode = 'new';
        $this->selectedSavedBank = '';
        $this->bankName = '';
        $this->accountName = '';
        $this->accountNumber = '';
        $this->resetSavedBankEdit();
        $this->resetValidation();
    }

    public function editSavedBank(string $key): void
    {
        $merchant = auth()->user()?->currentMerchant();
        if (! $merchant) {
            return;
        }

        $saved = $this->savedBankAccounts($merchant)->firstWhere('key', $key);
        if ($saved === null) {
            return;
        }

        $this->editingSavedBankKey = $key;
        $this->selectedSavedBank = $key;
        $this->editBankName = $saved['bank_name'];
        $this->editAccountName = $saved['account_name'];
        $this->editAccountNumber = $saved['account_number'];
        $this->resetValidation();
    }

    public function cancelSavedBankEdit(): void
    {
        $this->resetSavedBankEdit();
        $this->resetValidation();
    }

    public function updateSavedBank(): void
    {
        $merchant = auth()->user()?->currentMerchant();
        if (! $merchant || $this->editingSavedBankKey === '') {
            return;
        }

        $saved = $this->savedBankAccounts($merchant)->firstWhere('key', $this->editingSavedBankKey);
        if ($saved === null) {
            $this->cancelSavedBankEdit();

            return;
        }

        $this->editBankName = trim($this->editBankName);
        $this->editAccountName = trim($this->editAccountName);
        $this->editAccountNumber = trim($this->editAccountNumber);

        $this->validate([
            'editBankName' => ['required', 'string', 'max:120'],
            'editAccountName' => ['required', 'string', 'max:120'],
            'editAccountNumber' => ['required', 'string', 'max:50'],
        ], [], [
            'editBankName' => 'bank name',
            'editAccountName' => 'account name',
            'editAccountNumber' => 'account number',
        ]);

        $updated = [
            'bank_name' => $this->editBankName,
            'account_name' => $this->editAccountName,
            'account_number' => $this->editAccountNumber,
        ];

        MerchantAdminVoucherInvoice::query()
            ->where('merchant_id', $merchant->id)
            ->get()
            ->each(function (MerchantAdminVoucherInvoice $invoice) use ($saved, $updated) {
                $bank = $this->normalizedBank($invoice->bank_account ?? []);
                if ($bank === null || $this->bankAccountKey($bank) !== $saved['key']) {
                    return;
                }

                $invoice->update(['bank_account' => $updated]);
            });

        $this->selectedSavedBank = $this->bankAccountKey($updated);
        $this->resetSavedBankEdit();
        $this->resetValidation();
    }

    public function saveInvoice(MerchantAdminVoucherInvoiceService $invoices): void
    {
        $merchant = auth()->user()?->currentMerchant();
        $user = auth()->user();

        if (! $merchant || ! $user || ! $this->selectedInvoiceVoucherId) {
            return;
        }

        $this->invoiceNumber = trim($this->invoiceNumber);

        if ($this->bankDetailsMode === 'saved') {
            $saved = $this->savedBankAccounts($merchant)->firstWhere('key', $this->selectedSavedBank);
            if ($saved === null) {
                $this->addError('selectedSavedBank', 'Choose a saved bank account.');

                return;
            }

            $this->bankName = $saved['bank_name'];
            $this->accountName = $saved['account_name'];
            $this->accountNumber = $saved['account_number'];
        }

        $this->bankName = trim($this->bankName);
        $this->accountName = trim($this->accountName);
        $this->accountNumber = trim($this->accountNumber);

        $this->validate([
            'invoiceNumber' => [
                'nullable',
                'string',
                'max:50',
                Rule::when(
                    filled($this->invoiceNumber),
                    Rule::unique('merchant_admin_voucher_invoices', 'invoice_number')
                ),
            ],
            'bankName' => ['required', 'string', 'max:120'],
            'accountName' => ['required', 'string', 'max:120'],
            'accountNumber' => ['required', 'string', 'max:50'],
        ]);

        $invoice = $invoices->create(
            $merchant,
            $user,
            $this->selectedInvoiceVoucherId,
            $this->invoiceNumber,
            [
                'bank_name' => $this->bankName,
                'account_name' => $this->accountName,
                'account_number' => $this->accountNumber,
            ],
        );

        $url = route('merchant.admin-voucher-invoices.pdf', $invoice);
        $this->closeInvoiceModal();
        $this->dispatch('invoice-ready', url: $url);
    }

    /**
     * @return Collection<int, array{key: string, bank_name: string, account_name: string, account_number: string}>
     */
    private function savedBankAccounts(Merchant $merchant): Collection
    {
        return MerchantAdminVoucherInvoice::query()
            ->where('merchant_id', $merchant->id)
            ->orderByDesc('generated_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (MerchantAdminVoucherInvoice $invoice) => $this->normalizedBank($invoice->bank_account ?? []))
            ->filter()
            ->unique(fn (array $bank) => $this->bankAccountKey($bank))
            ->map(fn (array $bank) => $bank + ['key' => $this->bankAccountKey($bank)])
            ->values();
    }

    /**
     * @param  array<string, mixed>  $bank
     * @return array{bank_name: string, account_name: string, account_number: string}|null
     */
    private function normalizedBank(array $bank): ?array
    {
        $normalized = [
            'bank_name' => trim((string) ($bank['bank_name'] ?? '')),
            'account_name' => trim((string) ($bank['account_name'] ?? '')),
            'account_number' => trim((string) ($bank['account_number'] ?? '')),
        ];

        if ($normalized['bank_name'] === '' || $normalized['account_name'] === '' || $normalized['account_number'] === '') {
            return null;
        }

        return $normalized;
    }

    private function resetSavedBankEdit(): void
    {
        $this->editingSavedBankKey = '';
        $this->editBankName = '';
        $this->editAccountName = '';
        $this->editAccountNumber = '';
    }

    /**
     * @param  array{bank_name: string, account_name: string, account_number: string}  $bank
     */
    private function bankAccountKey(array $bank): string
    {
        return mb_strtolower($bank['bank_name'].'|'.$bank['account_name'].'|'.$bank['account_number']);
    }

    private function voucherGroups(Merchant $merchant): Collection
    {
        $assignedVoucherIds = $merchant->adminVouchers()->select('admin_vouchers.id');

        $entries = AdminVoucherLedgerEntry::query()
            ->where('merchant_id', $merchant->id)
            ->whereIn('admin_voucher_id', $assignedVoucherIds)
            ->with(['adminVoucher', 'reimbursements' => function ($query) {
                $query->orderByDesc('reimbursed_at')->orderByDesc('id');
            }])
            ->withSum('reimbursements', 'amount')
            ->orderByDesc('period_month')
            ->get();

        return $entries
            ->groupBy('admin_voucher_id')
            ->map(function (Collection $voucherEntries) {
                $voucher = $voucherEntries->first()?->adminVoucher;
                $reimbursed = round((float) $voucherEntries->sum(fn (AdminVoucherLedgerEntry $entry) => (float) ($entry->reimbursements_sum_amount ?? 0)), 2);
                $receivables = round((float) $voucherEntries->sum(fn (AdminVoucherLedgerEntry $entry) => $entry->computedTotalDispensed()), 2);
                $reimbursements = $voucherEntries
                    ->flatMap(function (AdminVoucherLedgerEntry $entry) {
                        return $entry->reimbursements->each(
                            fn ($reimbursement) => $reimbursement->setRelation('ledgerEntry', $entry)
                        );
                    })
                    ->sortByDesc(fn ($reimbursement) => sprintf(
                        '%s-%020d',
                        $reimbursement->reimbursed_at?->format('Y-m-d') ?? '0000-00-00',
                        (int) $reimbursement->id
                    ))
                    ->values();

                return [
                    'voucher' => $voucher,
                    'reimbursed' => $reimbursed,
                    'receivables' => $receivables,
                    'outstanding' => round($receivables - $reimbursed, 2),
                    'reimbursements' => $reimbursements,
                ];
            })
            ->filter(fn (array $group) => $group['voucher'] !== null)
            ->sortByDesc(fn (array $group) => $group['reimbursements']->first()?->reimbursed_at?->timestamp ?? 0)
            ->values();
    }

    private function applyFilters(Collection $groups): Collection
    {
        if ($this->statusFilter === 'outstanding') {
            $groups = $groups->filter(fn (array $group) => $group['outstanding'] > 0);
        } elseif ($this->statusFilter === 'reimbursed') {
            $groups = $groups->filter(fn (array $group) => $group['outstanding'] <= 0 && $group['receivables'] > 0);
        }

        $search = trim($this->search);
        if ($search === '') {
            return $groups->values();
        }

        $needle = mb_strtolower($search);

        return $groups
            ->map(function (array $group) use ($needle) {
                $voucher = $group['voucher'];
                $voucherMatches = str_contains(mb_strtolower((string) ($voucher->name ?? '')), $needle)
                    || str_contains(mb_strtolower((string) ($voucher->voucher_code ?? '')), $needle);

                $matchingRecords = $group['reimbursements']->filter(function ($reimbursement) use ($needle) {
                    return str_contains(mb_strtolower((string) ($reimbursement->notes ?? '')), $needle);
                })->values();

                if (! $voucherMatches && $matchingRecords->isEmpty()) {
                    return null;
                }

                if (! $voucherMatches) {
                    $group['reimbursements'] = $matchingRecords;
                }

                return $group;
            })
            ->filter()
            ->values();
    }
}
