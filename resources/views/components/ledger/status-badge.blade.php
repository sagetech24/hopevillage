@props([
    'outstanding' => 0,
])

@php
    $isOutstanding = (float) $outstanding > 0;
@endphp

<span {{ $attributes->merge([
    'class' => 'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium border '.($isOutstanding
        ? 'bg-red-50 text-red-700 border-red-200'
        : 'bg-green-50 text-green-700 border-green-500'),
]) }}>
    <span class="size-1.5 rounded-full {{ $isOutstanding ? 'bg-red-500' : 'bg-green-500' }}"></span>
    {{ $isOutstanding ? 'With Outstanding Balance' : 'Fully Reimbursed' }}
</span>
