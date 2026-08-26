@props([
    'voucher',
])

@php
    $statusReason = $voucher->getStatusReason();
    $isFull = $voucher->usage_limit && $voucher->usage_count >= $voucher->usage_limit;
    $isExpired = $statusReason === 'Expired';
    $isUpcoming = $statusReason === 'Not Yet Valid';
    $isInactive = ! $voucher->is_active;
    $isActive = $voucher->is_active && $voucher->isValid();

    if ($isInactive) {
        $label = 'Inactive';
        $classes = 'bg-red-50 text-red-700 border-red-200';
        $dot = 'bg-red-500';
    } elseif ($isExpired) {
        $label = 'Expired';
        $classes = 'bg-gray-100 text-gray-700 border-gray-600';
        $dot = 'bg-gray-400';
    } elseif ($isFull || $statusReason === 'Usage Limit Reached') {
        $label = 'Full';
        $classes = 'bg-orange-50 text-orange-700 border-orange-600';
        $dot = 'bg-orange-500';
    } elseif ($isUpcoming) {
        $label = 'Upcoming';
        $classes = 'bg-sky-50 text-sky-700 border-sky-200';
        $dot = 'bg-sky-500';
    } elseif ($isActive) {
        $label = 'Active';
        $classes = 'bg-green-50 text-green-700 border-green-600';
        $dot = 'bg-green-500';
    } else {
        $label = $statusReason ?? 'Inactive';
        $classes = 'bg-amber-50 text-amber-800 border-amber-200';
        $dot = 'bg-amber-500';
    }
@endphp

<span {{ $attributes->merge([
    'class' => 'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium border '.$classes,
]) }}>
    <span class="size-1.5 rounded-full {{ $dot }}"></span>
    {{ $label }}
</span>
