@props([
    'entry',
    'invoice' => null,
])

@php
    $totalReimbursed = (float) ($entry->reimbursements_sum_amount ?? 0);
    $costPerVoucher = $entry->costPerVoucher();
    $totalDispensed = $entry->computedTotalDispensed();
    $outstanding = $totalDispensed - $totalReimbursed;
    $isOutstanding = $outstanding > 0;
    $merchantName = $entry->merchant?->name ?? 'N/A';
    $merchantCode = $entry->merchant?->merchant_code;
    $voucherName = $entry->adminVoucher?->name ?? 'N/A';
    $voucherCode = $entry->adminVoucher?->voucher_code;
@endphp

<tr {{ $attributes->merge([
    'class' => 'hover:bg-orange-50/40 transition-colors '.($isOutstanding ? 'bg-red-50/40' : 'bg-green-50/30'),
]) }}>
    <td class="px-4 py-4 whitespace-nowrap">
        <span class="text-sm font-medium text-gray-900">{{ $entry->period_month->format('M Y') }}</span>
    </td>
    <td class="px-4 py-4">
        <div class="min-w-0 max-w-[10rem]">
            <p class="text-sm font-semibold text-gray-900 truncate" title="{{ $merchantName }}">{{ $merchantName }}</p>
            @if ($merchantCode)
                <p class="mt-0.5 text-[10px] text-gray-500 font-mono truncate">{{ $merchantCode }}</p>
            @endif
            @if ($invoice)
                <p class="mt-0.5 text-[10px] font-semibold text-orange-600 truncate">Invoice {{ $invoice->invoice_number }}</p>
            @endif
        </div>
    </td>
    <td class="px-4 py-4">
        <div class="min-w-0 max-w-[14rem]">
            <p class="text-sm font-semibold text-gray-900 truncate" title="{{ $voucherName }}">{{ $voucherName }}</p>
            @if ($voucherCode)
                <p class="mt-0.5 text-[10px] text-gray-500 font-mono truncate">{{ $voucherCode }}</p>
            @endif
        </div>
    </td>
    <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
        {{ $entry->adminVoucher?->points_cost }}
    </td>
    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-800">
        SGD {{ number_format((float) $entry->adminVoucher?->amount_cost, 2) }}
    </td>
    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-800">
        SGD {{ number_format($costPerVoucher, 2) }}
    </td>
    <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
        {{ $entry->total_redemptions }}
    </td>
    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-800">
        SGD {{ number_format($totalDispensed, 2) }}
    </td>
    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-800">
        SGD {{ number_format($totalReimbursed, 2) }}
    </td>
    <td class="px-4 py-4 whitespace-nowrap">
        <div class="flex flex-col items-start gap-1">
            <span class="text-sm font-semibold {{ $isOutstanding ? 'text-red-600' : 'text-green-600' }}">
                SGD {{ number_format($outstanding, 2) }}
            </span>
            {{-- <x-ledger.status-badge :outstanding="$outstanding" /> --}}
        </div>
    </td>
    <td class="px-4 py-4 text-right">
        <x-ledger.actions :entry="$entry" :outstanding="$outstanding" variant="table" />
    </td>
</tr>
