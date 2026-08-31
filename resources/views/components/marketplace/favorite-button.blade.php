@props([
    'itemId',
    'label' => __('Favorite'),
    'activeLabel' => null,
])

@php
    $inactiveLabel = $label;
    $favoritedLabel = $activeLabel ?? __('Remove from Favorites');
@endphp

<button
    type="button"
    @click.stop="toggle({{ $itemId }})"
    :aria-pressed="isFavorite({{ $itemId }})"
    :title="isFavorite({{ $itemId }}) ? @js($favoritedLabel) : @js($inactiveLabel)"
    {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-lg transition-all duration-200 focus:outline-none focus:ring-0 focus:ring-offset-0 cursor-pointer focus:ring-orange-500 focus:ring-offset-1']) }}
>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        class="size-5 shrink-0 transition-colors"
        :class="isFavorite({{ $itemId }}) ? 'fill-amber-400 stroke-amber-500' : 'fill-none stroke-gray-400 hover:stroke-amber-400'"
        stroke-width="1.5"
    >
        <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
    </svg>
    <span
        class="text-sm font-medium whitespace-nowrap transition-colors"
        :class="isFavorite({{ $itemId }}) ? 'text-amber-600' : 'text-gray-600'"
        x-text="isFavorite({{ $itemId }}) ? @js($favoritedLabel) : @js($inactiveLabel)"
    ></span>
</button>
