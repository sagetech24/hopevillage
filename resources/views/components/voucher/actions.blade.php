@props([
    'voucher',
    'showApprove' => false,
])

<div
    {{ $attributes->merge(['class' => 'relative inline-flex items-center justify-end shrink-0']) }}
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
    @if($showApprove)
        <button
            type="button"
            wire:click="toggleApproval('{{ $voucher->voucher_code }}')"
            wire:confirm="Are you sure you want to approve this voucher?"
            class="mr-2 inline-flex items-center gap-1.5 rounded-full bg-green-500 px-3 py-1.5 text-xs font-semibold text-white hover:bg-green-600 transition-colors"
            title="Approve voucher"
        >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-3.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            Approve
        </button>
    @endif

    <button
        type="button"
        @click="open = !open; $nextTick(() => { if (open) setPosition($el); })"
        class="inline-flex items-center justify-center size-10 rounded-full bg-gray-100 text-gray-400 hover:text-gray-700 hover:bg-gray-200/70 hover:scale-105 transition-all duration-200 cursor-pointer"
        title="Actions"
        aria-label="Voucher actions"
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
            <a
                href="{{ route('admin.vouchers.profile', $voucher->voucher_code) }}"
                class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                role="menuitem"
                @click="open = false"
            >
                <svg class="size-4 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>
                View
            </a>
            <button
                type="button"
                wire:click="edit('{{ $voucher->voucher_code }}')"
                @click="open = false"
                class="flex w-full items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 cursor-pointer"
                role="menuitem"
            >
                <svg class="size-4 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                </svg>
                Edit
            </button>
            <div class="border-t border-gray-100 my-1"></div>
            <button
                type="button"
                wire:confirm="Are you sure you want to delete this voucher?"
                wire:click="delete('{{ $voucher->voucher_code }}')"
                @click="open = false"
                class="flex w-full items-center gap-3 px-4 py-2 text-sm text-red-600 hover:bg-red-50 cursor-pointer"
                role="menuitem"
            >
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                </svg>
                Delete
            </button>
        </div>
    </div>
</div>
