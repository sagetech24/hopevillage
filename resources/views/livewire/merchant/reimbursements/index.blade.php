<div class="min-h-screen bg-slate-50 pb-28">
    <div class="relative overflow-hidden bg-[#3a5870]">
        <div class="pointer-events-none absolute inset-0 opacity-30" aria-hidden="true">
            <div class="absolute -top-16 -right-10 size-56 rounded-full bg-orange-400/40 blur-3xl"></div>
            <div class="absolute -bottom-20 -left-10 size-64 rounded-full bg-sky-400/20 blur-3xl"></div>
        </div>

        <div class="relative max-w-full lg:max-w-5xl w-full mx-auto px-4 sm:px-6 pt-5 pb-16">
            <div class="flex items-center justify-between gap-3">
                <a href="{{ route('merchant.dashboard.v2') }}" class="flex items-center gap-2.5 min-w-0">
                    <img src="{{ asset('hv-logo.png') }}" alt="Hope Village" class="md:w-15 md:h-15 w-11 h-11 object-contain drop-shadow drop-shadow-white/50">
                    <div class="min-w-0">
                        <p class="text-white font-semibold leading-tight md:text-2xl text-sm">Merchant Portal</p>
                        <p class="text-white/70 truncate md:text-lg text-xs">Hope Village Merchants Center</p>
                    </div>
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-full bg-white/10 hover:bg-white/20 text-white text-xs font-semibold px-3 py-2 border border-white/15 transition-colors">
                        <svg class="size-4" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" fill="none">
                            <g style="fill:none;stroke:#ffffff;stroke-width:12px;stroke-linecap:round;stroke-linejoin:round;">
                                <path d="m 50,10 0,35"></path>
                                <path d="M 26,20 C -3,48 16,90 51,90 79,90 89,67 89,52 89,37 81,26 74,20"></path>
                            </g>
                        </svg>
                        Logout
                    </button>
                </form>
            </div>

            <div class="mt-6">
                <a href="{{ route('merchant.dashboard.v2') }}" class="inline-flex items-center gap-1.5 text-orange-200 hover:text-white text-sm font-medium transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                    </svg>
                    Back to Home
                </a>
                <h1 class="text-white text-2xl sm:text-3xl font-bold tracking-tight mt-2">Reimbursement Records</h1>
                <p class="text-white/70 md:text-lg text-sm mt-1">Payments Hope Village records for Hope Village vouchers at your store.</p>
            </div>
        </div>
    </div>

    <div class="max-w-xl md:max-w-2xl lg:max-w-6xl mx-auto px-4 sm:px-6 -mt-10 relative z-10">
        @if($merchant)
            <section class="grid grid-cols-2 lg:grid-cols-3 gap-3">
                <button
                    type="button"
                    wire:click="setStatusFilter('all')"
                    class="text-left bg-white rounded-2xl border {{ $statusFilter === 'all' ? 'border-orange-300 ring-1 ring-orange-200' : 'border-slate-200' }} p-4 shadow-sm hover:border-orange-200 transition-colors"
                >
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Reimbursed</p>
                        <span class="size-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                            </svg>
                        </span>
                    </div>
                    <p class="mt-3 text-2xl sm:text-3xl font-bold text-slate-900 tabular-nums">SGD {{ number_format((float) $totals['reimbursed'], 2) }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ number_format($stats['all']) }} voucher {{ \Illuminate\Support\Str::plural('record', $stats['all']) }}</p>
                </button>

                <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Receivables</p>
                        <span class="size-8 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                            </svg>
                        </span>
                    </div>
                    <p class="mt-3 text-2xl sm:text-3xl font-bold text-slate-900 tabular-nums">SGD {{ number_format((float) $totals['receivables'], 2) }}</p>
                    <p class="mt-1 text-xs text-slate-500">Total dispensed at this store</p>
                </div>

                <button
                    type="button"
                    wire:click="setStatusFilter('outstanding')"
                    class="text-left bg-white rounded-2xl border {{ $statusFilter === 'outstanding' ? 'border-orange-300 ring-1 ring-orange-200' : 'border-slate-200' }} p-4 shadow-sm hover:border-orange-200 transition-colors col-span-2 lg:col-span-1"
                >
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Outstanding</p>
                        <span class="size-8 rounded-xl {{ $totals['outstanding'] > 0 ? 'bg-red-50 text-red-600' : 'bg-emerald-50 text-emerald-600' }} flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </span>
                    </div>
                    <p class="mt-3 text-2xl sm:text-3xl font-bold tabular-nums {{ $totals['outstanding'] > 0 ? 'text-red-700' : 'text-slate-900' }}">SGD {{ number_format((float) $totals['outstanding'], 2) }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ number_format($stats['outstanding']) }} still awaiting payment</p>
                </button>
            </section>

            <section class="mt-5">
                <div class="relative">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search voucher name, code, or notes..."
                        class="w-full pl-10 pr-4 py-3 bg-white border border-slate-200 text-slate-700 rounded-2xl shadow-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500 text-sm"
                    >
                    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 size-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                </div>

                <div class="mt-3 flex items-center gap-2 overflow-x-auto">
                    <button
                        type="button"
                        wire:click="setStatusFilter('all')"
                        class="shrink-0 rounded-full px-3 py-1.5 text-xs font-semibold border transition-colors {{ $statusFilter === 'all' ? 'bg-orange-500 text-white border-orange-500' : 'bg-white text-slate-600 border-slate-200 hover:border-orange-200' }}"
                    >
                        All
                    </button>
                    <button
                        type="button"
                        wire:click="setStatusFilter('outstanding')"
                        class="shrink-0 rounded-full px-3 py-1.5 text-xs font-semibold border transition-colors {{ $statusFilter === 'outstanding' ? 'bg-orange-500 text-white border-orange-500' : 'bg-white text-slate-600 border-slate-200 hover:border-orange-200' }}"
                    >
                        Outstanding
                    </button>
                    <button
                        type="button"
                        wire:click="setStatusFilter('reimbursed')"
                        class="shrink-0 rounded-full px-3 py-1.5 text-xs font-semibold border transition-colors {{ $statusFilter === 'reimbursed' ? 'bg-orange-500 text-white border-orange-500' : 'bg-white text-slate-600 border-slate-200 hover:border-orange-200' }}"
                    >
                        Fully reimbursed
                    </button>
                </div>
            </section>

            <section class="mt-6">
                <div class="flex items-center justify-between gap-3 mb-3">
                    <h3 class="text-sm font-semibold text-slate-800">Invoices you can generate</h3>
                    <p class="text-xs text-slate-500">{{ $billable->count() }} finished {{ \Illuminate\Support\Str::plural('voucher', $billable->count()) }}</p>
                </div>

                @if($billable->isNotEmpty())
                    <div class="space-y-4">
                        @foreach($billable as $row)
                            @php
                                $voucher = $row['voucher'];
                                $invoice = $row['invoice'];
                                $validFrom = $voucher->valid_from?->format('d M Y');
                                $validUntil = $voucher->valid_until?->format('d M Y');
                            @endphp
                            <article class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden" wire:key="billable-{{ $voucher->id }}">
                                <div class="p-4 sm:p-5">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-orange-600">Bill Hope Village</p>
                                            <h3 class="mt-1 text-lg font-bold text-slate-900 leading-tight">{{ $voucher->name }}</h3>
                                            <p class="text-[11px] text-slate-400 font-mono mt-0.5">{{ $voucher->voucher_code }}</p>
                                        </div>
                                        @if($invoice)
                                            <span class="shrink-0 inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold border bg-emerald-50 text-emerald-700 border-emerald-200">
                                                {{ $invoice->invoice_number }}
                                            </span>
                                        @elseif(! $voucher->is_active || $voucher->trashed())
                                            <span class="shrink-0 inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold border bg-slate-50 text-slate-600 border-slate-200">
                                                Inactive
                                            </span>
                                        @else
                                            <span class="shrink-0 inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold border bg-amber-50 text-amber-700 border-amber-200">
                                                Finished
                                            </span>
                                        @endif
                                    </div>

                                    <p class="mt-2 text-xs text-slate-500">
                                        Validity:
                                        @if($validFrom && $validUntil)
                                            {{ $validFrom }} – {{ $validUntil }}
                                        @else
                                            {{ $validFrom ?? $validUntil ?? '—' }}
                                        @endif
                                    </p>

                                    <div class="mt-4 grid grid-cols-3 gap-2">
                                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-center">
                                            <p class="text-sm font-bold text-slate-900 tabular-nums">{{ number_format($row['redeemed_count']) }}</p>
                                            <p class="text-[11px] text-slate-500">Redeemed</p>
                                        </div>
                                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-center">
                                            <p class="text-sm font-bold text-slate-900 tabular-nums">SGD {{ number_format($row['cost_per_voucher'], 2) }}</p>
                                            <p class="text-[11px] text-slate-500">Per voucher</p>
                                        </div>
                                        <div class="rounded-xl border border-orange-200 bg-orange-50 px-3 py-3 text-center">
                                            <p class="text-sm font-bold text-orange-700 tabular-nums">SGD {{ number_format($row['amount'], 2) }}</p>
                                            <p class="text-[11px] text-orange-600">Amount to bill</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="border-t border-slate-100 px-4 sm:px-5 py-3 bg-slate-50/80">
                                    @if($invoice)
                                        <a
                                            href="{{ route('merchant.admin-voucher-invoices.pdf', $invoice) }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="inline-flex items-center gap-1.5 text-sm font-semibold text-orange-600 hover:text-orange-700"
                                        >
                                            Download invoice
                                        </a>
                                    @elseif($row['redeemed_count'] > 0)
                                        <button
                                            type="button"
                                            wire:click="openInvoiceModal({{ $voucher->id }})"
                                            class="inline-flex items-center gap-1.5 text-sm font-semibold text-orange-600 hover:text-orange-700"
                                        >
                                            Generate Invoice
                                        </button>
                                    @else
                                        <p class="text-sm text-slate-500">No redemptions to invoice</p>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="bg-white rounded-2xl border border-dashed border-slate-300 px-6 py-8 text-center">
                        <p class="font-semibold text-slate-800">No finished vouchers to invoice</p>
                        <p class="text-sm text-slate-500 mt-1">Inactive or expired Hope Village vouchers assigned to this store appear here.</p>
                    </div>
                @endif
            </section>

            <section class="mt-6">
                <div class="flex items-center justify-between gap-3 mb-3">
                    <h3 class="text-sm font-semibold text-slate-800">Records by voucher</h3>
                    <p class="text-xs text-slate-500">{{ $groups->count() }} shown</p>
                </div>

                @if($groups->isNotEmpty())
                    <div class="space-y-4">
                        @foreach($groups as $group)
                            @php
                                $voucher = $group['voucher'];
                                $isOutstanding = $group['outstanding'] > 0;
                            @endphp
                            <article class="bg-white rounded-2xl border {{ $isOutstanding ? 'border-red-100' : 'border-slate-200' }} shadow-sm overflow-hidden">
                                <div class="p-4 sm:p-5">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-teal-600">Hope Village voucher</p>
                                            <h3 class="mt-1 text-lg font-bold text-slate-900 leading-tight">{{ $voucher->name }}</h3>
                                            <p class="text-[11px] text-slate-400 font-mono mt-0.5">{{ $voucher->voucher_code }}</p>
                                        </div>
                                        <span class="shrink-0 inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold border {{ $isOutstanding ? 'bg-red-50 text-red-700 border-red-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200' }}">
                                            {{ $isOutstanding ? 'Outstanding' : 'Fully reimbursed' }}
                                        </span>
                                    </div>

                                    <div class="mt-4 grid grid-cols-3 gap-2">
                                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-center">
                                            <p class="text-sm font-bold text-slate-900 tabular-nums">SGD {{ number_format((float) $group['receivables'], 2) }}</p>
                                            <p class="text-[11px] text-slate-500">Dispensed</p>
                                        </div>
                                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-center">
                                            <p class="text-sm font-bold text-slate-900 tabular-nums">SGD {{ number_format((float) $group['reimbursed'], 2) }}</p>
                                            <p class="text-[11px] text-slate-500">Reimbursed</p>
                                        </div>
                                        <div class="rounded-xl border px-3 py-3 text-center {{ $isOutstanding ? 'border-red-200 bg-red-50' : 'border-emerald-200 bg-emerald-50' }}">
                                            <p class="text-sm font-bold tabular-nums {{ $isOutstanding ? 'text-red-700' : 'text-emerald-700' }}">SGD {{ number_format((float) $group['outstanding'], 2) }}</p>
                                            <p class="text-[11px] {{ $isOutstanding ? 'text-red-600' : 'text-emerald-600' }}">Outstanding</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="border-t border-slate-100">
                                    @if($group['reimbursements']->isNotEmpty())
                                        <ul class="divide-y divide-slate-100">
                                            @foreach($group['reimbursements'] as $reimbursement)
                                                <li class="px-4 sm:px-5 py-3.5">
                                                    <div class="flex items-start justify-between gap-3">
                                                        <div class="min-w-0">
                                                            <p class="font-semibold text-slate-900">SGD {{ number_format((float) $reimbursement->amount, 2) }}</p>
                                                            <p class="text-xs text-slate-500 mt-0.5">
                                                                {{ $reimbursement->ledgerEntry?->period_month?->format('M Y') ?? '—' }}
                                                                @if($reimbursement->notes)
                                                                    · {{ $reimbursement->notes }}
                                                                @endif
                                                            </p>
                                                        </div>
                                                        <p class="text-xs text-slate-500 shrink-0">
                                                            {{ $reimbursement->reimbursed_at?->format('d M Y') ?? '—' }}
                                                        </p>
                                                    </div>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <div class="px-4 sm:px-5 py-8 text-center">
                                            <p class="font-semibold text-slate-800">No payments recorded yet</p>
                                            <p class="text-sm text-slate-500 mt-1">Hope Village reimbursements for this voucher will appear here.</p>
                                        </div>
                                    @endif
                                </div>

                                <div class="border-t border-slate-100 px-4 sm:px-5 py-3 bg-slate-50/80">
                                    <a
                                        href="{{ route('merchant.vouchers.admin-reimbursements-pdf', $voucher->voucher_code) }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex items-center gap-1.5 text-sm font-semibold text-orange-600 hover:text-orange-700"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a24.95 24.95 0 0 1 12.56 0m-12.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V6.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v.858m10.5 0V9.75m-10.5-2.517V9.75" />
                                        </svg>
                                        Print to PDF
                                    </a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="bg-white rounded-2xl border border-dashed border-slate-300 px-6 py-12 text-center">
                        <div class="mx-auto size-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                            </svg>
                        </div>
                        <p class="font-semibold text-slate-800">{{ $search || $statusFilter !== 'all' ? 'No matching reimbursement records' : 'No reimbursement records yet' }}</p>
                        <p class="text-sm text-slate-500 mt-1">
                            @if($search || $statusFilter !== 'all')
                                Try another search or clear the filters to see all records.
                            @else
                                When Hope Village records a payment for vouchers redeemed at your store, it appears here.
                            @endif
                        </p>
                    </div>
                @endif
            </section>
        @else
            <section class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 text-center">
                <div class="mx-auto size-14 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-7">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72" />
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-slate-900">No store assigned yet</h2>
                <p class="mt-2 text-sm text-slate-600 max-w-md mx-auto">
                    Ask an administrator to link a merchant account so you can review reimbursement records.
                </p>
            </section>
        @endif
    </div>

    @if($showInvoiceModal && $selectedBillable)
        <div class="fixed inset-0 z-[9999] overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="invoice-modal-title">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="fixed inset-0 bg-gray-500/60" wire:click="closeInvoiceModal" aria-hidden="true"></div>
                <div class="relative z-10 w-full max-w-lg rounded-2xl bg-white p-5 sm:p-6 shadow-xl">
                    <h3 id="invoice-modal-title" class="text-lg font-bold text-slate-900">Generate Invoice</h3>
                    <p class="mt-1 text-sm text-slate-600">{{ $selectedBillable['voucher']->name }}</p>
                    <p class="mt-1 text-sm font-semibold text-orange-700">Amount to bill: SGD {{ number_format($selectedBillable['amount'], 2) }}</p>

                    <form wire:submit="saveInvoice" class="mt-5 space-y-4">
                        <div>
                            <label for="invoiceNumber" class="block text-sm font-medium text-slate-700">Invoice number</label>
                            <input
                                id="invoiceNumber"
                                type="text"
                                wire:model="invoiceNumber"
                                maxlength="50"
                                placeholder="Leave blank to auto-generate"
                                class="mt-1 block w-full rounded-full border-slate-300 text-slate-800 shadow-sm focus:border-orange-500 focus:ring-orange-500"
                            >
                            <p class="mt-1 text-xs text-slate-500">Leave this blank and Hope Village will assign a number such as INV-{{ now()->year }}-0001.</p>
                            @error('invoiceNumber')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <p class="block text-sm font-medium text-slate-700">Bank details</p>
                            <div class="mt-2 inline-flex rounded-full border border-slate-200 bg-slate-50 p-1" role="tablist" aria-label="Bank details">
                                <button
                                    type="button"
                                    wire:click="setBankDetailsMode('new')"
                                    role="tab"
                                    aria-selected="{{ $bankDetailsMode === 'new' ? 'true' : 'false' }}"
                                    class="rounded-full px-3 py-1.5 text-xs font-semibold transition-colors {{ $bankDetailsMode === 'new' ? 'bg-orange-500 text-white' : 'text-slate-600 hover:text-slate-900' }}"
                                >
                                    New details
                                </button>
                                <button
                                    type="button"
                                    wire:click="setBankDetailsMode('saved')"
                                    role="tab"
                                    aria-selected="{{ $bankDetailsMode === 'saved' ? 'true' : 'false' }}"
                                    class="rounded-full px-3 py-1.5 text-xs font-semibold transition-colors {{ $bankDetailsMode === 'saved' ? 'bg-orange-500 text-white' : 'text-slate-600 hover:text-slate-900' }}"
                                >
                                    Saved details
                                </button>
                            </div>
                        </div>

                        @if($bankDetailsMode === 'saved')
                            <div role="tabpanel">
                                @if($savedBanks->isEmpty())
                                    <p class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-sm text-slate-500">No saved bank details yet. Use New details to enter an account.</p>
                                @else
                                    <div class="space-y-2">
                                        @foreach($savedBanks as $bank)
                                            <label class="flex items-start gap-3 rounded-xl border px-3 py-3 cursor-pointer {{ $selectedSavedBank === $bank['key'] ? 'border-orange-300 bg-orange-50' : 'border-slate-200 bg-white' }}">
                                                <input
                                                    type="radio"
                                                    wire:model.live="selectedSavedBank"
                                                    value="{{ $bank['key'] }}"
                                                    class="mt-1 text-orange-500 border-slate-300 focus:ring-orange-500"
                                                >
                                                <span class="min-w-0">
                                                    <span class="block text-sm font-semibold text-slate-900">{{ $bank['bank_name'] }}</span>
                                                    <span class="block text-xs text-slate-500">{{ $bank['account_name'] }} · {{ $bank['account_number'] }}</span>
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                @endif
                                @error('selectedSavedBank')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        @else
                            <div role="tabpanel" class="space-y-4">
                                <div>
                                    <label for="bankName" class="block text-sm font-medium text-slate-700">Bank name</label>
                                    <input id="bankName" type="text" wire:model="bankName" class="mt-1 block w-full rounded-full border-slate-300 text-slate-800 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                    @error('bankName')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="accountName" class="block text-sm font-medium text-slate-700">Account name</label>
                                    <input id="accountName" type="text" wire:model="accountName" class="mt-1 block w-full rounded-full border-slate-300 text-slate-800 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                    @error('accountName')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="accountNumber" class="block text-sm font-medium text-slate-700">Account number</label>
                                    <input id="accountNumber" type="text" wire:model="accountNumber" class="mt-1 block w-full rounded-full border-slate-300 text-slate-800 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                    @error('accountNumber')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        @endif
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" wire:click="closeInvoiceModal" class="px-4 py-2 rounded-full border border-slate-300 text-sm font-medium text-slate-700 bg-white hover:bg-slate-50">
                                Cancel
                            </button>
                            <button type="submit" class="px-4 py-2 rounded-full text-sm font-semibold text-white bg-orange-500 hover:bg-orange-600">
                                Generate Invoice
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @script
    <script>
        $wire.on('invoice-ready', (payload) => {
            const url = payload?.url ?? payload?.[0]?.url;
            if (url) {
                window.open(url, '_blank', 'noopener');
            }
        });
    </script>
    @endscript
</div>
