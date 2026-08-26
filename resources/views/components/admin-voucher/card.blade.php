@props([
    'voucher',
])

@php
    $imageUrl = $voucher->getFirstMediaUrl('image');
    $initials = strtoupper(substr((string) $voucher->name, 0, 2));
    $merchantNames = $voucher->merchants->pluck('name')->filter()->values();
    $merchantPreview = $merchantNames->take(2)->implode(', ');
    $merchantExtra = max(0, $merchantNames->count() - 2);

    $validFrom = $voucher->valid_from;
    $validUntil = $voucher->valid_until;
    $isValidityExpired = $validUntil && now()->gt($validUntil);
    $validDays = ($validFrom && $validUntil) ? (int) round($validFrom->diffInDays($validUntil)) : null;

    if ($isValidityExpired) {
        $validityLabel = 'Expired';
        $validityClass = 'text-red-600';
    } elseif ($validDays === null) {
        $validityLabel = 'Open-ended';
        $validityClass = 'text-gray-700';
    } elseif ($validDays < 1) {
        $validityLabel = 'Same day';
        $validityClass = 'text-amber-600';
    } else {
        $validityLabel = $validDays.' '.($validDays === 1 ? 'day' : 'days');
        $validityClass = 'text-green-700';
    }

    $claimedOnlyCount = (int) ($voucher->claimed_count ?? $voucher->users()->wherePivot('status', 'claimed')->count());
    $redeemedCount = (int) ($voucher->redeemed_count ?? $voucher->users()->wherePivot('status', 'redeemed')->count());
    $claimedCount = $claimedOnlyCount + $redeemedCount;
    $totalReleased = $voucher->usage_limit !== null ? (int) $voucher->usage_limit : null;
    $usageLabel = sprintf(
        'C-%s | R - %s | T = %s',
        number_format($claimedCount),
        number_format($redeemedCount),
        $totalReleased !== null ? number_format($totalReleased) : '∞'
    );

    $pointsCost = max(0, (int) $voucher->points_cost);
    $costPerPoint = max(0, (float) $voucher->amount_cost);
    $costPerVoucher = round($pointsCost * $costPerPoint, 2);

    $statusReason = $voucher->getStatusReason();
    $isMuted = ! $voucher->is_active || $statusReason === 'Expired';
@endphp

<article {{ $attributes->merge([
    'class' => 'group flex flex-col h-full bg-white rounded-xl border overflow-hidden shadow-sm hover:shadow-md transition-all duration-200 '.($isMuted
        ? 'border-gray-200 hover:border-gray-300'
        : 'border-gray-200 hover:border-orange-200'),
]) }}>
    <div class="flex gap-4 p-4 flex-1">
        <div class="flex flex-col gap-1 items-center">
            <div class="size-20 rounded-xl overflow-hidden shrink-0 bg-orange-50 border border-orange-100 flex items-center justify-center">
                @if($imageUrl)
                    <img src="{{ $imageUrl }}" alt="{{ $voucher->name }}" class="size-full object-cover {{ $isMuted ? 'grayscale' : '' }}">
                @else
                    <span class="text-orange-500 text-xl font-bold tracking-wide">{{ $initials }}</span>
                @endif
            </div>
            <span class="text-[0.5rem] font-bold text-gray-500">{{ $voucher->voucher_code }}</span>
        </div>

        <div class="min-w-0 flex-1">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <a
                        href="{{ route('admin.admin-vouchers.profile', $voucher->voucher_code) }}"
                        class="block text-base font-semibold text-gray-900 hover:text-orange-600 truncate transition-colors"
                        title="{{ $voucher->name }}"
                    >
                        {{ $voucher->name }}
                    </a>
                </div>
                <x-admin-voucher.status-badge :voucher="$voucher" class="shrink-0" />
            </div>

            <span class="text-xs font-medium text-gray-600 italic">About:</span>
            <p class="text-sm text-gray-600 line-clamp-2 {{ $voucher->description ? '' : 'text-gray-400 italic' }}">
                {{ $voucher->description ?: 'No description available' }}
            </p>

            <ul class="mt-1 space-y-1.5 text-sm text-gray-600">
                <li class="flex flex-col items-start">
                    <span class="text-xs font-medium text-gray-600 italic">Merchants: </span>
                    <span class="line-clamp-2 text-sm {{ $merchantNames->isNotEmpty() ? '' : 'text-gray-400 italic' }}">
                        @if($merchantNames->isNotEmpty())
                            {{ $merchantPreview }}
                            {{ $merchantExtra > 0 ? ' +'.$merchantExtra.' more' : '' }}
                        @else
                            No merchants assigned
                        @endif
                    </span>
                </li>
            </ul>
        </div>
    </div>

    <div class="mt-auto p-3 border-t border-gray-100 bg-gray-50/80 flex items-start justify-between gap-3">
        <div class="flex flex-wrap items-center gap-1.5 min-w-0">
            <span class="inline-flex items-center gap-1.5 text-xs font-medium text-orange-700 bg-orange-50 border border-orange-700 rounded-full px-2.5 py-1">
                Points Cost:
                {{ number_format($pointsCost) }} pts
            </span>
            <span class="inline-flex items-center gap-1.5 text-xs font-medium text-teal-700 bg-teal-50 border border-teal-600 rounded-full px-2.5 py-1">
                Point Cost:
                SGD {{ number_format($costPerPoint, 2) }}
            </span>
            <span class="inline-flex items-center gap-1.5 text-xs font-medium text-green-700 bg-green-50 border border-green-600 rounded-full px-2.5 py-1">
                Voucher Cost:
                SGD {{ number_format($costPerVoucher, 2) }}
            </span>
            <span
                class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-600 bg-white border border-gray-400 rounded-full px-2.5 py-1"
                title="C = Claimed (claimed + redeemed) · R = Redeemed · T = Total released"
            >
                Usage:
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-3.5 text-gray-700">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                </svg>
                {{ $usageLabel }}
            </span>
        </div>
        <x-admin-voucher.actions :voucher="$voucher" />
    </div>
</article>
