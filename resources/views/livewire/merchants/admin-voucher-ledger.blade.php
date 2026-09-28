<div>
    @if ($showMessage && $syncMessage)
        <div
            x-data="{ show: @entangle('showMessage').live }"
            x-show="show"
            x-transition
            class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-xl relative md:mx-0 mx-4"
        >
            {{ $syncMessage }}
        </div>
    @endif

    <!-- Filters and Sync -->
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl p-4 md:p-5 md:mx-0 mx-4 mb-6 border border-gray-200">
        <div class="grid grid-cols-1 lg:grid-cols-10 gap-2">
            <div class="col-span-1 lg:col-span-5">
                <div class="">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date Range</label>
                    <select
                        wire:model.live="dateFilter"
                        class="w-full px-4 py-2.5 text-gray-800 border border-gray-300 rounded-full focus:ring-0 focus:outline-none focus:border-orange-500"
                    >
                        <option value="all">All time</option>
                        <option value="3months">Last 3 months</option>
                        <option value="6months">Last 6 months</option>
                        <option value="12months">Last 12 months</option>
                        <option value="custom">Custom range</option>
                    </select>
                </div>

                @if ($dateFilter === 'custom')  
                    <div class="grid grid-cols-2 lg:grid-cols-2 gap-2 mt-2">
                        <div class="">
                            <label class="block text-sm font-medium text-gray-700 mb-1">From month</label>
                            <div class="relative">
                                <input
                                    type="month"
                                    id="monthFrom"
                                    wire:model.live="monthFrom"
                                    class="w-full px-4 py-2.5 text-gray-800 border border-gray-300 rounded-full focus:ring-0 focus:outline-none focus:border-orange-500"
                                >
                                <button
                                    type="button"
                                    onclick="document.getElementById('monthFrom').showPicker?.() || document.getElementById('monthFrom').click()"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none"
                                    aria-label="Open calendar"
                                >
                                </button>
                            </div>
                        </div>
                        <div class="">
                            <label class="block text-sm font-medium text-gray-700 mb-1">To month</label>
                            <div class="relative">
                                <input
                                    type="month"
                                    id="monthTo"
                                    wire:model.live="monthTo"
                                    class="w-full px-4 py-2.5 text-gray-800 border border-gray-300 rounded-full focus:ring-0 focus:outline-none focus:border-orange-500"
                                >
                                <button
                                    type="button"
                                    onclick="document.getElementById('monthTo').showPicker?.() || document.getElementById('monthTo').click()"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none"
                                    aria-label="Open calendar"
                                >
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
            <div class="col-span-1 lg:col-span-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Search merchant</label>
                <div class="relative">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="merchantSearch"
                        placeholder="Merchant name or code..."
                        class="w-full pl-10 pr-4 py-2.5 text-gray-800 border border-gray-300 rounded-full focus:ring-0 focus:outline-none focus:border-orange-500"
                    >
                </div>
            </div>
            <div class="col-span-1 lg:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">&nbsp;</label>
                <div class="flex gap-1 justify-end items-end">
                    <button
                        type="button"
                        wire:click="syncLedger"
                        class="lg:w-auto shrink-0 px-5 py-2.5 text-sm cursor-pointer font-medium text-white bg-orange-500 rounded-full hover:bg-orange-600 focus:ring-2 focus:ring-orange-500 focus:ring-offset-2"
                    >
                        Sync Ledger
                    </button>
                    <div class="inline-flex items-center self-end sm:self-auto rounded-xl border border-gray-300 bg-gray-50" role="group" aria-label="View mode">
                        <button
                            type="button"
                            wire:click="setViewMode('card')"
                            class="inline-flex items-center justify-center p-3 rounded-xl text-xs font-medium transition-all duration-200 {{ $viewMode === 'card' ? 'bg-orange-600 text-white shadow-sm' : 'text-gray-500 hover:text-gray-800 hover:bg-white' }}"
                            title="Card view"
                            aria-pressed="{{ $viewMode === 'card' ? 'true' : 'false' }}"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 8.25 20.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                            </svg>
                            <span class="sr-only">Card view</span>
                        </button>
                        <button
                            type="button"
                            wire:click="setViewMode('list')"
                            class="inline-flex items-center justify-center p-3 rounded-xl text-xs font-medium transition-all duration-200 {{ $viewMode === 'list' ? 'bg-orange-600 text-white shadow-sm' : 'text-gray-500 hover:text-gray-800 hover:bg-white' }}"
                            title="Table view"
                            aria-pressed="{{ $viewMode === 'list' ? 'true' : 'false' }}"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                            </svg>
                            <span class="sr-only">Table view</span>
                        </button>
                    </div>
                </div>
                <label class="mt-4 flex justify-end items-end  gap-2 cursor-pointer select-none">
                    <input
                        type="checkbox"
                        wire:model.live="outstandingOnly"
                        class="rounded border-gray-300 text-orange-500 focus:ring-orange-500"
                    >
                    <span class="text-xs text-gray-700">With outstanding balance only</span>
                </label>
            </div>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl md:mx-0 mx-4 mb-6 border border-gray-200">
        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between gap-3">
            <h3 class="text-sm font-semibold text-gray-800">Merchant invoices</h3>
            <p class="text-xs text-gray-500">{{ $invoices->count() }} shown</p>
        </div>
        @if ($invoices->isEmpty())
            <p class="px-4 py-6 text-sm text-gray-500">No merchant invoices submitted yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Invoice</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Merchant</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Voucher</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Validity</th>
                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase">Amount</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Generated by</th>
                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase">PDF</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach ($invoices as $invoice)
                            <tr>
                                <td class="px-4 py-3 text-sm font-semibold text-gray-900 whitespace-nowrap">{{ $invoice->invoice_number }}</td>
                                <td class="px-4 py-3 text-sm text-gray-800">{{ $invoice->merchant?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-800">
                                    <p>{{ $invoice->voucher_name }}</p>
                                    <p class="text-[10px] text-gray-500 font-mono">{{ $invoice->voucher_code }}</p>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 whitespace-nowrap">{{ $invoice->validityLabel() }}</td>
                                <td class="px-4 py-3 text-sm text-right font-semibold text-gray-900 whitespace-nowrap">SGD {{ number_format((float) $invoice->amount, 2) }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $invoice->generatedBy?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a
                                        href="{{ route('admin.merchant-admin-voucher-invoices.pdf', $invoice) }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="text-sm font-semibold text-orange-600 hover:text-orange-700"
                                    >
                                        PDF
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    @if ($viewMode === 'list')
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl md:mx-0 mx-4 border border-gray-200">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Merchant</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Voucher</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Points Per Voucher</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Cost per Point</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Cost per voucher</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Vouchers Redeemed</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Dispensed</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Reimbursed</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Balance</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse ($entries as $entry)
                            <x-ledger.table-row
                                :entry="$entry"
                                :invoice="$invoiceLookup[App\Models\MerchantAdminVoucherInvoice::pairKey($entry->merchant_id, $entry->admin_voucher_id)] ?? null"
                                wire:key="ledger-row-{{ $entry->id }}"
                            />
                        @empty
                            <tr>
                                <td colspan="11" class="px-5 py-16 text-center">
                                    <div class="flex flex-col items-center gap-2 text-gray-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-10">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                        </svg>
                                        <p class="text-sm font-medium text-gray-500">No ledger entries found</p>
                                        <p class="text-xs text-gray-400">Click “Sync Ledger” to populate from redemptions.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-5 md:px-0 px-4">
            @forelse ($entries as $entry)
                <x-ledger.card
                    :entry="$entry"
                    :invoice="$invoiceLookup[App\Models\MerchantAdminVoucherInvoice::pairKey($entry->merchant_id, $entry->admin_voucher_id)] ?? null"
                    wire:key="ledger-card-{{ $entry->id }}"
                />
            @empty
                <div class="col-span-full flex flex-col items-center justify-center gap-2 text-center py-16 border-dashed border-2 border-gray-200 rounded-xl bg-white text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-10">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                    </svg>
                    <p class="text-sm font-medium text-gray-500">No ledger entries found</p>
                    <p class="text-xs text-gray-400">Click “Sync Ledger” to populate from redemptions.</p>
                </div>
            @endforelse
        </div>
    @endif

    @if ($entries->hasPages())
        <div class="mt-6 md:px-0 px-4">
            {{ $entries->links() }}
        </div>
    @endif

    <!-- Add Reimbursement Modal (teleported to body to avoid overflow/stacking issues) -->
    @if ($showReimburseModal && $selectedLedgerEntry)
        @teleport('body')
        <div
            class="fixed inset-0 z-[9999] overflow-y-auto"
            aria-labelledby="modal-title"
            role="dialog"
            aria-modal="true"
        >
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="fixed inset-0 bg-gray-500/60 transition-opacity" wire:click="closeReimburseModal" aria-hidden="true"></div>
                <div class="relative z-10 mx-auto w-full max-w-lg transform overflow-hidden rounded-lg bg-white px-4 pt-5 pb-4 text-left shadow-xl transition-all sm:p-6">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4" id="modal-title">
                        Add Reimbursement
                    </h3>
                    <p class="mb-4 flex items-center justify-start gap-1">
                        <span class="text-sm text-gray-500">Merchant Name:</span>
                        <svg class="size-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72M6.75 18h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z" />
                        </svg>
                        <span class="text-md text-gray-500">
                            {{ $selectedLedgerEntry->merchant?->name }}
                        </span>
                    </p>
                    <p class="flex items-center justify-start gap-1 text-gray-500 mb-4">
                        <span class="text-sm text-gray-500">Voucher Name:</span>
                        <span class="text-md text-gray-500">
                            {{ $selectedLedgerEntry->adminVoucher?->name }}
                        </span>
                    </p>
                    <p class="text-sm text-gray-600 mb-4">
                        Outstanding Payable: <strong>${{ number_format($selectedLedgerEntry->outstanding_balance, 2) }}</strong>
                    </p>
                    <form wire:submit="saveReimbursement">
                        <div class="space-y-4">
                            <div>
                                <label for="reimbAmount" class="block text-sm font-medium text-gray-700">Amount</label>
                                <input
                                    type="number"
                                    id="reimbAmount"
                                    placeholder="Enter amount"
                                    wire:model="reimbAmount"
                                    step="0.01"
                                    min="0"
                                    required
                                    class="mt-1 block w-full rounded-full text-gray-700 border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500"
                                >
                                @error('reimbAmount')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="reimbDate" class="block text-sm font-medium text-gray-700">Date</label>
                                <div class="relative mt-1">
                                    <input
                                        type="date"
                                        id="reimbDate"
                                        wire:model="reimbDate"
                                        required
                                        placeholder="Enter date"
                                        class="block w-full rounded-full text-gray-700 border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 pr-9"
                                    >
                                    <button
                                        type="button"
                                        onclick="(function(){var el=document.getElementById('reimbDate');try{if(el.showPicker)el.showPicker();else el.click();}catch(e){el.focus();el.click();}})()"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none"
                                        aria-label="Open calendar"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                        </svg>
                                    </button>
                                </div>
                                @error('reimbDate')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="reimbNotes" class="block text-sm font-medium text-gray-700">Notes (optional)</label>
                                <textarea
                                    id="reimbNotes"
                                    wire:model="reimbNotes"
                                    rows="2"
                                    class="mt-1 block w-full rounded-lg text-gray-700 border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500"
                                ></textarea>
                            </div>
                        </div>
                        <div class="mt-6 flex justify-end gap-3">
                            <button
                                type="button"
                                wire:click="closeReimburseModal"
                                class="px-4 py-2 border border-gray-300 rounded-full text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                class="px-4 py-2 border border-transparent rounded-full text-sm font-medium text-white bg-orange-500 hover:bg-orange-600"
                            >
                                Save Reimbursement
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endteleport
    @endif

    <!-- Reimbursement History Modal -->
    @if ($showHistoryModal && $historyLedgerEntry)
        @teleport('body')
        <div
            class="fixed inset-0 z-[9999] overflow-y-auto"
            aria-labelledby="history-modal-title"
            role="dialog"
            aria-modal="true"
        >
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="fixed inset-0 bg-gray-500/60 transition-opacity" wire:click="closeHistoryModal" aria-hidden="true"></div>
                <div class="relative z-10 mx-auto w-full max-w-2xl transform overflow-hidden rounded-lg bg-white px-4 pt-5 pb-4 text-left shadow-xl transition-all sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium leading-6 text-gray-900" id="history-modal-title">
                            Reimbursement History
                        </h3>
                        <button
                            type="button"
                            wire:click="closeHistoryModal"
                            class="rounded-md text-gray-400 hover:text-gray-600 focus:outline-none"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <p class="text-sm text-gray-500 mb-4">
                        {{ $historyLedgerEntry->merchant?->name }} — {{ $historyLedgerEntry->adminVoucher?->name }} ({{ $historyLedgerEntry->period_month->format('M Y') }})
                    </p>
                    <div class="overflow-hidden border border-gray-200 rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                                    <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500 uppercase">Amount</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Added by</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Notes</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 uppercase">&nbsp;</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($historyLedgerEntry->reimbursements->sortByDesc('reimbursed_at') as $reimbursement)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">
                                            {{ $reimbursement->reimbursed_at->format('M d, Y') }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-right font-medium text-gray-900 whitespace-nowrap">
                                            ${{ number_format((float) $reimbursement->amount, 2) }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600">
                                            {{ $reimbursement->createdBy?->name ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600 max-w-xs truncate">
                                            {{ $reimbursement->notes ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <button
                                                type="button"
                                                class="flex gap-1 items-center text-orange-600 hover:text-orange-800 text-xs font-medium cursor-pointer hover:underline hover:scale-105 transition-all duration-300"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m.75 12 3 3m0 0 3-3m-3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                                </svg>
                                                <span class="text-sm">PDF</span>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">
                                            No reimbursements recorded yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6 min-w-1/2 overflow-hidden border border-orange-100 rounded-lg">
                        <table class="min-w-full divide-y divide-orange-100">
                            <tbody class="bg-white divide-y divide-orange-100">
                                <tr class="bg-orange-50">
                                    <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">
                                        Total Dispensed:
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">
                                        <strong>${{ number_format($historyLedgerEntry->computedTotalDispensed(), 2) }}</strong>
                                    </td>
                                </tr>
                                <tr class="bg-orange-50">
                                    <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">
                                        Total Reimbursed:
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">
                                        <strong>${{ number_format((float) ($historyLedgerEntry->reimbursements_sum_amount ?? 0), 2) }}</strong>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4 flex justify-end">
                        <button
                            type="button"
                            wire:click="closeHistoryModal"
                            class="px-4 py-2 border border-gray-300 rounded-full text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endteleport
    @endif
</div>
