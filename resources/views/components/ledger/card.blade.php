@props([
    'entry',
])

@php
    $totalReimbursed = (float) ($entry->reimbursements_sum_amount ?? 0);
    $costPerVoucher = $entry->costPerVoucher();
    $totalDispensed = $entry->computedTotalDispensed();
    $outstanding = $totalDispensed - $totalReimbursed;
    $isOutstanding = $outstanding > 0;
    $merchantName = $entry->merchant?->name ?? 'N/A';
    $merchantCode = $entry->merchant?->merchant_code;
@endphp

<article {{ $attributes->merge([
    'class' => 'group flex flex-col h-full bg-white rounded-xl border overflow-hidden shadow-sm hover:shadow-md transition-all duration-200 '.($isOutstanding
        ? 'border-red-200 hover:border-red-300'
        : 'border-gray-200 hover:border-green-200'),
]) }}>
    <div class="flex items-start justify-between gap-3 p-4 pb-3">
        <div class="min-w-0">
            <p class="text-[10px] font-medium text-gray-500">Merchant Name:</p>
            <p class="text-xl font-semibold text-gray-900 truncate" title="{{ $merchantName }}">{{ $merchantName }}</p>
            @if ($merchantCode)
                <p class="mt-0.5 text-[10px] text-gray-500 font-mono truncate">{{ 'CODE: '.$merchantCode }}</p>
            @endif
        </div>
        <div class="flex flex-col items-end gap-1.5 shrink-0">
            <div class="flex items-center gap-1">
                <x-ledger.status-badge :outstanding="$outstanding" />
            </div>
            <span>
                <span class="text-xs font-medium text-gray-500">Month Covered: </span>
                <span class="text-xs font-bold text-gray-500">
                    {{ $entry->period_month->format('M Y') }}
                </span>
            </span>
        </div>
    </div>

    <div class="px-4">
        <p class="text-[11px] font-medium text-gray-500">Voucher Name:</p>
        <x-ledger.voucher-chip :voucher="$entry->adminVoucher" />
    </div>

    <dl class="grid grid-cols-3 gap-2 px-4 py-4 text-sm">
        <div class="rounded-lg bg-gray-50 border border-gray-100 px-2.5 py-2">
            <dt class="text-[0.65rem] uppercase tracking-wide text-gray-500">Points Per Voucher</dt>
            <dd class="mt-0.5 font-semibold text-gray-900">{{ $entry->adminVoucher?->points_cost }}</dd>
        </div>
        <div class="rounded-lg bg-gray-50 border border-gray-100 px-2.5 py-2">
            <dt class="text-[0.65rem] uppercase tracking-wide text-gray-500">Cost per Point</dt>
            <dd class="mt-0.5 font-semibold text-gray-900">SGD {{ number_format($entry->adminVoucher?->amount_cost, 2) }}</dd>
        </div>
        <div class="rounded-lg bg-gray-50 border border-gray-100 px-2.5 py-2">
            <dt class="text-[0.65rem] uppercase tracking-wide text-gray-500">Cost per voucher</dt>
            <dd class="mt-0.5 font-semibold text-gray-900">SGD {{ number_format($costPerVoucher, 2) }}</dd>
        </div>
        <div class="rounded-lg bg-gray-50 border border-gray-100 px-2.5 py-2">
            <dt class="text-[0.65rem] uppercase tracking-wide text-gray-500">Vouchers Redeemed</dt>
            <dd class="mt-0.5 font-semibold text-gray-900">{{ $entry->total_redemptions }}</dd>
        </div>
        <div class="rounded-lg bg-gray-50 border border-gray-100 px-2.5 py-2">
            <dt class="text-[0.65rem] uppercase tracking-wide text-gray-500">Total Dispensed</dt>
            <dd class="mt-0.5 font-semibold text-gray-900">SGD {{ number_format($totalDispensed, 2) }}</dd>
        </div>
        <div class="rounded-lg bg-gray-50 border border-gray-100 px-2.5 py-2 col-span-1">
            <dt class="text-[0.65rem] uppercase tracking-wide text-gray-500">Total Reimbursed</dt>
            <dd class="mt-0.5 font-semibold text-gray-900">SGD {{ number_format($totalReimbursed, 2) }}</dd>
        </div>
        <div class="rounded-lg border px-2.5 py-2 col-span-3 {{ $isOutstanding ? 'bg-red-50 border-red-100' : 'bg-green-50 border-green-100' }}">
            <dt class="text-[0.65rem] uppercase tracking-wide {{ $isOutstanding ? 'text-red-600' : 'text-green-700' }}">Balance</dt>
            <dd class="mt-0.5 font-semibold {{ $isOutstanding ? 'text-red-600' : 'text-gray-900' }}">SGD {{ number_format($outstanding, 2) }}</dd>
        </div>
    </dl>

    <div class="mt-auto px-4 py-3 border-t {{ $isOutstanding ? 'border-red-100 bg-red-50/50' : 'border-gray-100 bg-gray-50/80' }}">
        <x-ledger.actions :entry="$entry" :outstanding="$outstanding" variant="card" />
    </div>
</article>
