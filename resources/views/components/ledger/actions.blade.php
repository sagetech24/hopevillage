@props([
    'entry',
    'outstanding' => 0,
    'variant' => 'table', // 'card' | 'table'
])

@php
    $isOutstanding = (float) $outstanding > 0;
    $historyLabel = $isOutstanding ? 'View Reimbursements' : 'Reimbursement History';
    $linkClass = 'inline-flex items-center gap-1 text-xs font-medium text-orange-500 hover:text-orange-600 transition-all hover:scale-105 duration-300 cursor-pointer';
@endphp

@if($variant === 'table')
    <div
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
        class="relative inline-flex items-center justify-end"
        @click.away="open = false"
    >
        <button
            type="button"
            @click="open = !open; $nextTick(() => { if (open) setPosition($el); })"
            class="inline-flex items-center justify-center size-8 rounded-full text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors cursor-pointer"
            title="Actions"
            aria-label="Ledger actions"
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
            class="w-52 z-[9999] origin-top-right rounded-lg bg-white shadow-lg ring-1 ring-black/5 focus:outline-none"
        >
            <div class="py-1" role="menu" aria-orientation="vertical">
                @if ($isOutstanding)
                    <button
                        type="button"
                        wire:click="openReimburseModal({{ $entry->id }})"
                        @click="open = false"
                        class="flex w-full items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 cursor-pointer"
                        role="menuitem"
                    >
                        <svg class="size-4 text-orange-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        <span>Add Reimbursement</span>
                    </button>
                    <div class="border-t border-gray-100 my-1"></div>
                @else
                    <div class="px-4 py-2 text-xs text-gray-500">Fully reimbursed</div>
                    <div class="border-t border-gray-100 my-1"></div>
                @endif
                <button
                    type="button"
                    wire:click="openHistoryModal({{ $entry->id }})"
                    @click="open = false"
                    class="flex whitespace-nowrap w-full items-center justify-start gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 cursor-pointer"
                    role="menuitem"
                >
                    <svg class="size-4 text-orange-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <span>{{ $historyLabel }}</span>
                </button>
                <div class="border-t border-gray-100 my-1"></div>
                <a
                    href="{{ route('admin.admin-voucher-ledger.transaction-history-pdf', $entry->id) }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    @click="open = false"
                    class="flex w-full items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                    role="menuitem"
                >
                    <svg class="size-4 text-orange-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                    </svg>
                    <span>Transaction History</span>
                </a>
            </div>
        </div>
    </div>
@else
    <div {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-x-3 gap-y-2']) }}>
        @if ($isOutstanding)
            <button type="button" wire:click="openReimburseModal({{ $entry->id }})" class="{{ $linkClass }}">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-3.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Add Reimbursement
            </button>
        @endif
        <button type="button" wire:click="openHistoryModal({{ $entry->id }})" class="{{ $linkClass }}">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-3.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            {{ $historyLabel }}
        </button>
        <a
            href="{{ route('admin.admin-voucher-ledger.transaction-history-pdf', $entry->id) }}"
            target="_blank"
            rel="noopener noreferrer"
            class="{{ $linkClass }}"
        >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-3.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
            </svg>
            Transaction History
        </a>
    </div>
@endif
