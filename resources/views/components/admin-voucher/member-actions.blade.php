@props([
    'memberId',
])

<div
    {{ $attributes->merge(['class' => 'relative inline-flex items-center justify-end']) }}
    x-data="{
        open: false,
        position: { top: 0, right: 0 },
        setPosition($el) {
            const rect = $el.getBoundingClientRect();
            this.position = {
                top: rect.bottom + 4,
                right: window.innerWidth - rect.right
            };
        }
    }"
    @click.away="open = false"
>
    <button
        type="button"
        @click="open = !open; $nextTick(() => { if (open) setPosition($el); })"
        class="inline-flex items-center justify-center size-8 rounded-full text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-all duration-200 cursor-pointer"
        title="Actions"
        aria-label="Member voucher actions"
        aria-haspopup="menu"
        :aria-expanded="open.toString()"
    >
        <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 12.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 18.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Z" />
        </svg>
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        :style="`position: fixed; top: ${position.top}px; right: ${position.right}px;`"
        class="w-44 z-[9999] origin-top-right rounded-lg bg-white shadow-lg ring-1 ring-black/5 focus:outline-none"
        role="menu"
    >
        <div class="py-1">
            <button
                type="button"
                wire:click="openVoidModal({{ $memberId }})"
                @click="open = false"
                class="flex w-full items-center gap-3 px-4 py-2 text-sm text-red-600 hover:bg-red-50 cursor-pointer"
                role="menuitem"
            >
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" />
                </svg>
                Void Voucher
            </button>
        </div>
    </div>
</div>
