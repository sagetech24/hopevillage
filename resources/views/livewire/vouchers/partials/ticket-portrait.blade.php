@props([
    'item',
    'type' => 'merchant',
])

@php
    $isAdmin = $type === 'admin';
    $cardBg = $isAdmin ? 'bg-green-50' : 'bg-orange-50';
    $headerBg = $isAdmin ? 'bg-green-500' : 'bg-orange-500';
    $headerIconBg = $isAdmin ? 'bg-green-100 text-green-600' : 'bg-orange-100 text-orange-600';
    $btnBorder = $isAdmin ? 'border-green-500 text-green-600' : 'border-orange-500 text-orange-600';
    $merchantLabel = $isAdmin ? 'Admin Voucher' : ($item->merchant_name ?: 'All Sellers');
    $isWithinValidityRange = true;
    $now = now();

    if (!empty($item->valid_from) && $now->lt(\Carbon\Carbon::parse($item->valid_from))) {
        $isWithinValidityRange = false;
    }

    if (!empty($item->valid_until) && $now->gt(\Carbon\Carbon::parse($item->valid_until))) {
        $isWithinValidityRange = false;
    }
@endphp

<div class="{{ $cardBg }} rounded-lg border border-gray-200 overflow-hidden shadow-xs w-full">
    <div class="relative {{ $headerBg }} text-white px-4 py-4 text-center">
        <div class="absolute left-0 right-0 -bottom-1 h-2 bg-white mask-[radial-gradient(circle_at_center,transparent_5px,black_5px)] mask-size-[12px_8px] mask-repeat-x"></div>
        @if(!empty($item->image_url))
            <div class="h-12 w-12 rounded-full overflow-hidden border border-white/40 mx-auto">
                <img src="{{ $item->image_url }}" alt="{{ $item->name }}" class="w-full h-full object-cover">
            </div>
        @else
            <div class="h-12 w-12 rounded-full {{ $headerIconBg }} flex items-center justify-center mx-auto">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M5 7l1.5 11h11L19 7M9 11v4m6-4v4"></path>
                </svg>
            </div>
        @endif
        <p class="text-[0.65rem] mt-2 leading-tight">{{ $merchantLabel }}</p>
    </div>

    <div class="p-4 flex flex-col gap-3">
        <div class="min-w-0 text-center">
            <p class="text-md font-semibold text-gray-900 leading-tight line-clamp-2">{{ $item->name }}</p>

            @if($isAdmin)
                <p class="text-xs text-gray-700 mt-1">Exchange {{ number_format($item->points_cost ?? 0) }} points</p>
            @elseif($item->discount_type === 'percentage')
                <p class="text-xs text-gray-700 mt-1">{{ rtrim(rtrim((string) $item->discount_value, '0'), '.') }}% off</p>
            @elseif($item->discount_type === 'item')
                <p class="text-xs text-gray-700 mt-1">Free Item</p>
            @else
                <p class="text-xs text-gray-700 mt-1">${{ number_format((float) $item->discount_value, 2) }} off</p>
            @endif

            @if(!$isAdmin && !is_null($item->min_purchase))
                <p class="text-gray-600 mt-1 text-[0.7rem]">Min. Spend ${{ number_format((float) $item->min_purchase, 0) }}</p>
            @elseif($isAdmin && $item->description)
                <p class="text-gray-400 mt-1 text-[0.7rem] italic">{{ $item->description }}</p>
            @endif
        </div>

        @if(!empty($item->valid_until))
            <p class="text-xs text-gray-500 text-center">
                <span class="block text-gray-500 font-normal text-[0.55rem] uppercase">Expiration Date</span>
                <span class="block text-gray-700 font-normal text-[0.65rem] tracking-wide">{{ \Carbon\Carbon::parse($item->valid_until)->format('d M Y g:i A') }}</span>
            </p>
        @endif

        <div class="flex items-center justify-center">
            @if($isAdmin)
                <div class="inline-flex flex-wrap justify-center items-center gap-2 w-full">
                    <a
                        href="{{ route('admin.admin-vouchers.profile', $item->voucher_code) }}"
                        title="View Voucher Details"
                        class="flex gap-1 items-center text-xs bg-orange-500 hover:bg-orange-600 px-2 py-1 rounded-full active:-translate-y-0.5 active:bg-orange-600 md:hover:-translate-y-0.5 text-white transition-all duration-200 touch-manipulation"
                    >
                        Details
                    </a>
                    <button
                        type="button"
                        wire:click="edit('{{ $item->voucher_code }}')"
                        title="Edit Voucher"
                        class="flex gap-1 items-center text-xs bg-gray-100 px-2 py-1 rounded-full active:-translate-y-0.5 border border-gray-300 active:bg-sky-100 active:text-sky-600 md:hover:-translate-y-0.5 md:hover:text-sky-600 cursor-pointer text-gray-600 transition-all duration-200 touch-manipulation"
                    >
                        Edit
                    </button>
                    <button
                        type="button"
                        wire:confirm="Are you sure you want to delete this admin voucher?"
                        wire:click="delete('{{ $item->voucher_code }}')"
                        title="Delete Voucher"
                        class="flex gap-1 items-center text-xs bg-gray-100 px-2 py-1 rounded-full active:-translate-y-0.5 border border-gray-300 active:bg-red-100 active:text-red-600 md:hover:-translate-y-0.5 md:hover:text-red-600 cursor-pointer text-gray-600 transition-all duration-200 touch-manipulation"
                    >
                        Delete
                    </button>
                </div>
            @else
                <div class="inline-flex flex-wrap justify-center items-center gap-2 w-full">
                    @if(!$item->is_active && $isWithinValidityRange)
                        <button
                            type="button"
                            wire:click="toggleApproval('{{ $item->voucher_code }}')"
                            wire:confirm="Are you sure you want to approve this voucher?"
                            title="Approve Voucher"
                            class="flex gap-1 items-center text-xs bg-green-500 hover:bg-green-600 px-2 py-1 rounded-full cursor-pointer active:-translate-y-0.5 active:bg-green-600 md:hover:-translate-y-0.5 text-white transition-all duration-300 touch-manipulation"
                        >
                            Click to Approve
                        </button>
                    @endif
                    <a
                        href="{{ route('admin.vouchers.profile', $item->voucher_code) }}"
                        title="View Voucher Details"
                        class="flex gap-1 items-center text-xs bg-gray-100 hover:bg-gray-200 px-2 py-1 border cursor-pointer border-gray-300 rounded-full active:-translate-y-0.5 active:bg-gray-300 md:hover:-translate-y-0.5 text-gray-600 transition-all duration-200 touch-manipulation"
                    >
                        Details
                    </a>
                    <button
                        type="button"
                        wire:click="edit('{{ $item->voucher_code }}')"
                        title="Edit Voucher"
                        class="flex gap-1 items-center text-xs bg-gray-100 px-2 py-1 rounded-full active:-translate-y-0.5 border border-gray-300 active:bg-sky-100 active:text-sky-600 md:hover:-translate-y-0.5 hover:text-sky-600 cursor-pointer text-gray-600 transition-all duration-200 touch-manipulation"
                    >
                        Edit
                    </button>
                    <button
                        type="button"
                        wire:confirm="Are you sure you want to delete this voucher?"
                        wire:click="delete('{{ $item->voucher_code }}')"
                        title="Delete Voucher"
                        class="flex items-center text-xs bg-gray-100 px-2 py-1 rounded-full active:-translate-y-0.5 border border-gray-300 active:bg-red-100 active:text-red-600 md:hover:-translate-y-0.5 md:hover:text-red-600 cursor-pointer text-gray-600 transition-all duration-200 touch-manipulation"
                    >
                        Delete
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>
