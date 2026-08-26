@props([
    'voucher' => null,
    'compact' => false,
])

@php
    $name = $voucher?->name ?? 'N/A';
    $code = $voucher?->voucher_code ?? 'N/A';
@endphp

<div {{ $attributes->merge([
    'class' => 'bg-teal-50 min-h-20 rounded-none border border-gray-200 overflow-hidden shadow-xs '.($compact ? 'max-w-2xl' : 'w-full'),
]) }}>
    <div class="flex">
        <div class="{{ $compact ? 'w-12 min-w-12 py-2' : 'w-14 min-w-14 py-3' }} min-h-20 bg-teal-500 text-white px-1.5 flex flex-col items-center justify-center text-center relative">
            <div class="absolute -left-1 top-0 bottom-0 w-2 bg-white mask-[radial-gradient(circle_at_center,transparent_4px,black_5px)] mask-size-[8px_12px] mask-repeat-y"></div>
            <svg class="{{ $compact ? 'size-4' : 'size-5' }} stroke-teal-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M5 7l1.5 11h11L19 7M9 11v4m6-4v4"></path>
            </svg>
        </div>
        <div class="{{ $compact ? 'px-2.5 py-2' : 'p-3' }} min-w-0 flex-1">
            <p class="text-sm font-semibold text-gray-900 leading-tight line-clamp-1" title="{{ $name }}">{{ $name }}</p>
            <p class="text-[0.65rem] text-gray-600 mt-0.5 font-mono truncate">{{ $code }}</p>
        </div>
    </div>
</div>
