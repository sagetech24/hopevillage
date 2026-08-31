@props([
    'merchant',
])

@php
    $isActive = (bool) $merchant->is_active;
@endphp

<span {{ $attributes->merge([
    'class' => 'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium border '.($isActive
        ? 'bg-green-50 text-green-700 border-green-200'
        : 'bg-amber-50 text-amber-800 border-amber-200'),
]) }}>
    <span class="size-1.5 rounded-full {{ $isActive ? 'bg-green-500' : 'bg-amber-500' }}"></span>
    {{ $isActive ? 'Active' : 'Pending Approval' }}
</span>
