@props([
    'voucher',
])

@php
    $imageUrl = $voucher->image_url;
    $initials = strtoupper(substr((string) $voucher->name, 0, 2));
    $merchant = $voucher->merchant;
    $category = $voucher->getDisplayStatusCategory();
    $isMuted = $category === 'expired';
    $showApprove = $voucher->getListStatusGroup() === 'pending';

    if ($voucher->discount_type === 'percentage') {
        $discountLabel = rtrim(rtrim((string) $voucher->discount_value, '0'), '.').'% off';
    } else {
        $discountLabel = '$'.number_format((float) $voucher->discount_value, 2).' off';
    }

    $validFrom = $voucher->valid_from;
    $validUntil = $voucher->valid_until;
@endphp

<article {{ $attributes->merge([
    'class' => 'group flex flex-col h-full bg-white rounded-xl border overflow-hidden shadow-sm hover:shadow-md transition-all duration-200 '.($isMuted
        ? 'border-gray-200 hover:border-gray-300 opacity-95'
        : ($category === 'pending_approval'
            ? 'border-amber-200 hover:border-amber-300'
            : 'border-gray-200 hover:border-orange-200')),
]) }}>
    <div class="flex gap-4 p-4 flex-1">
        <div class="flex flex-col gap-1 items-center shrink-0">
            <div class="size-20 rounded-xl overflow-hidden bg-orange-50 border border-orange-100 flex items-center justify-center">
                @if($imageUrl)
                    <img src="{{ $imageUrl }}" alt="{{ $voucher->name }}" class="size-full object-cover {{ $isMuted ? 'grayscale' : '' }}">
                @else
                    <span class="text-orange-500 text-xl font-bold tracking-wide">{{ $initials }}</span>
                @endif
            </div>
            <span class="text-[0.5rem] font-bold text-gray-500 font-mono">{{ $voucher->voucher_code }}</span>
        </div>

        <div class="min-w-0 flex-1">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <a
                        href="{{ route('admin.vouchers.profile', $voucher->voucher_code) }}"
                        class="block text-base font-semibold text-gray-900 hover:text-orange-600 line-clamp-2 transition-colors"
                        title="{{ $voucher->name }}"
                    >
                        {{ $voucher->name }}
                    </a>
                    @if($merchant)
                        <a
                            href="{{ route('admin.merchants.profile', $merchant->merchant_code) }}"
                            class="mt-0.5 inline-flex items-center gap-1 text-xs text-gray-500 hover:text-orange-600 transition-colors"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-3.5 shrink-0">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72M6.75 18h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z" />
                            </svg>
                            <span class="truncate">{{ $merchant->name }}</span>
                        </a>
                    @else
                        <p class="mt-0.5 text-xs text-gray-400 italic">Unknown merchant</p>
                    @endif
                </div>
                <x-voucher.status-badge :voucher="$voucher" class="shrink-0" />
            </div>

            @if(filled($voucher->description))
                <p class="mt-2 text-sm text-gray-600 line-clamp-2">{{ $voucher->description }}</p>
            @endif

            <ul class="mt-2 space-y-1 text-sm text-gray-600">
                <li class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4 text-gray-400 shrink-0">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" />
                    </svg>
                    <span class="font-medium text-orange-700">{{ $discountLabel }}</span>
                    @if(! is_null($voucher->min_purchase))
                        <span class="text-xs text-gray-500">· Min. ${{ number_format((float) $voucher->min_purchase, 0) }}</span>
                    @endif
                </li>
                <li class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4 text-gray-400 shrink-0">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                    </svg>
                    <span class="text-xs">
                        {{ $validFrom ? $validFrom->format('d M Y') : '—' }}
                        <span class="text-gray-400">→</span>
                        {{ $validUntil ? $validUntil->format('d M Y') : '—' }}
                    </span>
                </li>
            </ul>
        </div>
    </div>

    <div class="mt-auto px-4 py-3 border-t border-gray-100 bg-gray-50/80 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-1.5">
            @if($voucher->usage_limit)
                <span class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-600 bg-white border border-gray-200 rounded-full px-2.5 py-1">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-3.5 text-gray-400">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                    </svg>
                    {{ number_format($voucher->usage_count) }}/{{ number_format($voucher->usage_limit) }} used
                </span>
            @endif
        </div>

        <x-voucher.actions :voucher="$voucher" :show-approve="$showApprove" />
    </div>
</article>
