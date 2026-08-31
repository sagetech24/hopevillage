@props([
    'voucher',
])

@php
    $category = $voucher->getDisplayStatusCategory();

    [$label, $classes, $dot] = match ($category) {
        'active' => ['Active', 'bg-green-50 text-green-700 border-green-200', 'bg-green-500'],
        'pending_approval' => ['Pending Approval', 'bg-amber-50 text-amber-800 border-amber-200', 'bg-amber-500'],
        'expired' => [
            $voucher->getDisplayStatusLabel(),
            'bg-gray-100 text-gray-700 border-gray-300',
            'bg-gray-400',
        ],
        default => ['Expired', 'bg-gray-100 text-gray-700 border-gray-300', 'bg-gray-400'],
    };
@endphp

<span {{ $attributes->merge([
    'class' => 'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium border '.$classes,
]) }}>
    <span class="size-1.5 rounded-full {{ $dot }}"></span>
    {{ $label }}
</span>
