@props([
    'merchant',
])

<div {{ $attributes->merge(['class' => 'flex items-center gap-2']) }}>
    <button
        type="button"
        wire:click="approve('{{ $merchant->merchant_code }}')"
        wire:confirm="Are you sure you want to approve this merchant application?"
        title="Approve Merchant"
        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-medium bg-green-600 text-white hover:bg-green-700 transition-colors cursor-pointer"
    >
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-3.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
        </svg>
        Approve
    </button>
    <button
        type="button"
        wire:click="reject('{{ $merchant->merchant_code }}')"
        wire:confirm="Are you sure you want to reject this merchant application?"
        title="Reject Merchant"
        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-medium bg-white text-red-600 border border-red-200 hover:bg-red-50 transition-colors cursor-pointer"
    >
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-3.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
        </svg>
        Reject
    </button>
</div>
