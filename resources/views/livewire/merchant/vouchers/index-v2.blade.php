@php
    $statusStyles = function (string $statusLabel, string $statusCategory): array {
        if ($statusLabel === 'Not Yet Valid') {
            return ['bg-sky-50 text-sky-700 border-sky-200', 'bg-sky-500'];
        }

        return match ($statusCategory) {
            'active' => ['bg-emerald-50 text-emerald-700 border-emerald-200', 'bg-emerald-500'],
            'pending_approval' => ['bg-amber-50 text-amber-800 border-amber-200', 'bg-amber-500'],
            'not_yet_valid' => ['bg-sky-50 text-sky-700 border-sky-200', 'bg-sky-500'],
            default => ['bg-slate-100 text-slate-600 border-slate-200', 'bg-slate-400'],
        };
    };

    $merchantValueText = function ($voucher): string {
        if (($voucher->discount_type ?? null) === 'percentage') {
            return rtrim(rtrim((string) ($voucher->discount_value ?? 0), '0'), '.').'% off';
        }

        if (($voucher->discount_type ?? null) === 'item') {
            return 'Free Item';
        }

        return '$'.number_format((float) ($voucher->discount_value ?? 0), 2).' off';
    };

    $merchantIsActive = (bool) ($merchant?->is_active);
@endphp

<div class="min-h-screen bg-slate-50 pb-28">
    <div class="relative overflow-hidden bg-[#3a5870]">
        <div class="pointer-events-none absolute inset-0 opacity-30" aria-hidden="true">
            <div class="absolute -top-16 -right-10 size-56 rounded-full bg-orange-400/40 blur-3xl"></div>
            <div class="absolute -bottom-20 -left-10 size-64 rounded-full bg-sky-400/20 blur-3xl"></div>
        </div>

        <div class="relative max-w-full lg:max-w-5xl w-full mx-auto px-4 sm:px-6 pt-5 pb-16">
            <div class="flex items-center justify-between gap-3">
                <a href="{{ route('merchant.dashboard.v2') }}" class="flex items-center gap-2.5 min-w-0">
                    <img src="{{ asset('hv-logo.png') }}" alt="Hope Village" class="md:w-15 md:h-15 w-11 h-11 object-contain drop-shadow drop-shadow-white/50">
                    <div class="min-w-0">
                        <p class="text-white font-semibold leading-tight md:text-2xl text-sm">Merchant Portal</p>
                        <p class="text-white/70 truncate md:text-lg text-xs">Hope Village Merchants Center</p>
                    </div>
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-full bg-white/10 hover:bg-white/20 text-white text-xs font-semibold px-3 py-2 border border-white/15 transition-colors">
                        <svg class="size-4" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" fill="none">
                            <g style="fill:none;stroke:#ffffff;stroke-width:12px;stroke-linecap:round;stroke-linejoin:round;">
                                <path d="m 50,10 0,35"></path>
                                <path d="M 26,20 C -3,48 16,90 51,90 79,90 89,67 89,52 89,37 81,26 74,20"></path>
                            </g>
                        </svg>
                        Logout
                    </button>
                </form>
            </div>

            <div class="mt-6 flex items-end justify-between gap-3">
                <div class="min-w-0">
                    <a href="{{ route('merchant.dashboard.v2') }}" class="inline-flex items-center gap-1.5 text-orange-200 hover:text-white text-sm font-medium transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                        </svg>
                        Back to Home
                    </a>
                    <h1 class="text-white text-2xl sm:text-3xl font-bold tracking-tight mt-2">Vouchers</h1>
                    <p class="text-white/70 md:text-lg text-sm mt-1">Manage store offers and Hope Village vouchers.</p>
                </div>

                @if($merchantIsActive)
                    <a href="{{ route('merchant.vouchers.create') }}" class="shrink-0 inline-flex items-center gap-1.5 rounded-full bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold px-4 py-2.5 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        New voucher
                    </a>
                @endif
            </div>
        </div>
    </div>

    <div class="max-w-xl md:max-w-2xl lg:max-w-6xl mx-auto px-4 sm:px-6 -mt-10 relative z-10">
        @if (session()->has('message'))
            <div
                x-data="{
                    show: @entangle('showMessage').live,
                    timeoutId: null
                }"
                x-init="
                    $watch('show', value => {
                        if (value && !timeoutId) {
                            timeoutId = setTimeout(() => {
                                show = false;
                                timeoutId = null;
                            }, 3000);
                        } else if (!value && timeoutId) {
                            clearTimeout(timeoutId);
                            timeoutId = null;
                        }
                    });
                    if (show) {
                        timeoutId = setTimeout(() => {
                            show = false;
                            timeoutId = null;
                        }, 3000);
                    }
                "
                x-show="show"
                x-cloak
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-out duration-300"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
                role="alert"
            >
                {{ session('message') }}
            </div>
        @endif

        @if($merchant && ! $merchantIsActive)
            <div class="mb-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                <p class="font-semibold">Store pending approval</p>
                <p class="mt-0.5 text-amber-800/90">You can review vouchers, but creating and editing stay locked until an admin approves your store.</p>
            </div>
        @endif

        <section class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <button type="button" wire:click="setStatusFilter('active')" class="text-left bg-white rounded-2xl border {{ $statusFilter === 'active' ? 'border-orange-300 ring-1 ring-orange-200' : 'border-slate-200' }} p-4 shadow-sm hover:border-orange-200 transition-colors">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Active</p>
                    <span class="size-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-3xl font-bold text-slate-900 tabular-nums">{{ number_format($stats['active']) }}</p>
                <p class="mt-1 text-xs text-slate-500">Live and claimable</p>
            </button>

            <button type="button" wire:click="setStatusFilter('pending_approval')" class="text-left bg-white rounded-2xl border {{ $statusFilter === 'pending_approval' ? 'border-orange-300 ring-1 ring-orange-200' : 'border-slate-200' }} p-4 shadow-sm hover:border-orange-200 transition-colors">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pending Approval</p>
                    <span class="size-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-3xl font-bold text-slate-900 tabular-nums">{{ number_format($stats['pending_approval']) }}</p>
                <p class="mt-1 text-xs text-slate-500">Waiting for admin</p>
            </button>

            <button type="button" wire:click="setStatusFilter('not_yet_valid')" class="text-left bg-white rounded-2xl border {{ $statusFilter === 'not_yet_valid' ? 'border-orange-300 ring-1 ring-orange-200' : 'border-slate-200' }} p-4 shadow-sm hover:border-orange-200 transition-colors">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Not Yet Valid</p>
                    <span class="size-8 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-3xl font-bold text-slate-900 tabular-nums">{{ number_format($stats['not_yet_valid']) }}</p>
                <p class="mt-1 text-xs text-slate-500">Scheduled, not started</p>
            </button>

            <button type="button" wire:click="setStatusFilter('expired')" class="text-left bg-white rounded-2xl border {{ $statusFilter === 'expired' ? 'border-orange-300 ring-1 ring-orange-200' : 'border-slate-200' }} p-4 shadow-sm hover:border-orange-200 transition-colors">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Expired</p>
                    <span class="size-8 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" />
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-3xl font-bold text-slate-900 tabular-nums">{{ number_format($stats['expired']) }}</p>
                <p class="mt-1 text-xs text-slate-500">Past validity date</p>
            </button>
        </section>

        <section class="mt-5 bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-5">
            <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                <label class="relative flex-1">
                    <span class="sr-only">Search vouchers</span>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 size-4 text-slate-400">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                    <input
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search by name, code, or description"
                        class="w-full rounded-xl border-slate-200 bg-slate-50 pl-10 pr-3 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-orange-400 focus:ring-orange-400"
                    >
                </label>

                @if($statusFilter !== 'all')
                    <button type="button" wire:click="setStatusFilter('all')" class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-white hover:border-orange-300 text-slate-700 text-sm font-semibold px-4 py-2.5 transition-colors">
                        Show all
                    </button>
                @endif
            </div>

            <div class="mt-4 flex items-center gap-2 border-b border-slate-200">
                <button
                    type="button"
                    wire:click="setTab('vouchers')"
                    @class([
                        'px-1 pb-3 text-sm border-b-2 transition-colors',
                        'border-orange-500 text-orange-600 font-semibold' => $tab === 'vouchers',
                        'border-transparent text-slate-500 hover:text-slate-800' => $tab !== 'vouchers',
                    ])
                >
                    My Vouchers ({{ $stats['total'] }})
                </button>
                <button
                    type="button"
                    wire:click="setTab('admin-vouchers')"
                    @class([
                        'px-1 pb-3 text-sm border-b-2 transition-colors',
                        'border-orange-500 text-orange-600 font-semibold' => $tab === 'admin-vouchers',
                        'border-transparent text-slate-500 hover:text-slate-800' => $tab !== 'admin-vouchers',
                    ])
                >
                    Hope Village Vouchers ({{ $adminTotal }})
                </button>
            </div>

            @if($tab === 'vouchers')
                <div class="mt-4">
                    <p class="text-sm text-slate-500">Vouchers created and managed by your store.</p>

                    @if($vouchers->count() > 0)
                        <div class="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-3">
                            @foreach($vouchers as $voucher)
                                @php
                                    $statusLabel = $voucher->getDisplayStatusLabel();
                                    $statusCategory = $voucher->getDisplayStatusCategory();
                                    [$statusClass, $statusDot] = $statusStyles($statusLabel, $statusCategory);
                                    $isFullyClaimed = $voucher->usage_limit && $voucher->usage_count >= $voucher->usage_limit;
                                    $claimedCount = (int) ($voucher->claimed_members_count ?? 0);
                                    $redeemedCount = (int) ($voucher->redeemed_members_count ?? 0);
                                    $remainingClaims = $voucher->usage_limit !== null
                                        ? max(0, (int) $voucher->usage_limit - (int) $voucher->usage_count)
                                        : null;
                                @endphp
                                <article
                                    class="relative rounded-2xl border border-slate-200 bg-slate-50/60"
                                    x-data="{ open: false }"
                                    :class="open ? 'z-30' : 'z-0'"
                                    @keydown.escape.window="open = false"
                                    @click.away="open = false"
                                >
                                    <div class="p-3 sm:p-4 flex gap-3">
                                        <div class="size-16 sm:size-20 rounded-xl overflow-hidden shrink-0 bg-orange-500 flex items-center justify-center">
                                            @if($voucher->image_url)
                                                <img src="{{ $voucher->image_url }}" alt="{{ $voucher->name }}" class="size-full object-cover">
                                            @else
                                                <span class="text-white text-lg font-bold">%</span>
                                            @endif
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-start justify-between gap-2">
                                                <h3 class="font-semibold text-slate-900 leading-tight">{{ $voucher->name }}</h3>
                                                <div class="flex items-center gap-1 shrink-0">
                                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[11px] font-semibold border {{ $statusClass }}">
                                                        <span class="size-1.5 rounded-full {{ $statusDot }}"></span>
                                                        {{ $statusLabel }}
                                                    </span>
                                                    <div class="relative">
                                                        <button
                                                            type="button"
                                                            @click="open = !open"
                                                            class="size-8 rounded-full text-slate-500 hover:text-slate-800 hover:bg-white border border-transparent hover:border-slate-200 transition-colors inline-flex items-center justify-center"
                                                            :aria-expanded="open.toString()"
                                                            aria-haspopup="menu"
                                                            aria-label="Voucher actions"
                                                        >
                                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-5">
                                                                <circle cx="12" cy="5" r="1.75" />
                                                                <circle cx="12" cy="12" r="1.75" />
                                                                <circle cx="12" cy="19" r="1.75" />
                                                            </svg>
                                                        </button>
                                                        <div
                                                            x-show="open"
                                                            x-cloak
                                                            x-transition:enter="transition ease-out duration-150"
                                                            x-transition:enter-start="opacity-0 translate-y-1"
                                                            x-transition:enter-end="opacity-100 translate-y-0"
                                                            x-transition:leave="transition ease-in duration-100"
                                                            x-transition:leave-start="opacity-100 translate-y-0"
                                                            x-transition:leave-end="opacity-0 translate-y-1"
                                                            class="absolute right-0 top-full mt-2 z-50 w-44 rounded-xl border border-slate-200 bg-white shadow-lg shadow-slate-900/10 py-1"
                                                            role="menu"
                                                            style="display: none;"
                                                        >
                                                            <span class="pointer-events-none absolute -top-1.5 right-3 size-3 rotate-45 rounded-[2px] bg-white border-l border-t border-slate-200"></span>
                                                            <a
                                                                href="{{ route('merchant.vouchers.profile', $voucher->voucher_code) }}"
                                                                class="flex items-center gap-2 px-3 py-2 text-sm text-slate-700 hover:bg-orange-50 hover:text-orange-700"
                                                                role="menuitem"
                                                            >
                                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-4">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                                </svg>
                                                                View
                                                            </a>
                                                            <button
                                                                type="button"
                                                                wire:click="openQr(@js($voucher->voucher_code))"
                                                                class="flex w-full items-center gap-2 px-3 py-2 text-sm text-slate-700 hover:bg-orange-50 hover:text-orange-700"
                                                                role="menuitem"
                                                            >
                                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-4">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 3.75 9.375v-4.5ZM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 13.5 9.375v-4.5Z" />
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.25 15.75h4.5M17.25 12.75v6" />
                                                                </svg>
                                                                View QR
                                                            </button>
                                                            @if($merchantIsActive)
                                                                <a
                                                                    href="{{ route('merchant.vouchers.edit', $voucher->voucher_code) }}"
                                                                    class="flex items-center gap-2 px-3 py-2 text-sm text-slate-700 hover:bg-orange-50 hover:text-orange-700"
                                                                    role="menuitem"
                                                                >
                                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-4">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                                                                    </svg>
                                                                    Edit
                                                                </a>
                                                                <button
                                                                    type="button"
                                                                    wire:click="confirmDelete(@js($voucher->voucher_code), @js($voucher->name))"
                                                                    class="flex w-full items-center gap-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50"
                                                                    role="menuitem"
                                                                >
                                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-4">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                                    </svg>
                                                                    Delete
                                                                </button>
                                                            @else
                                                                <div class="flex items-center gap-2 px-3 py-2 text-sm text-slate-400 cursor-not-allowed" role="menuitem" aria-disabled="true">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-4">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                                                    </svg>
                                                                    Edit locked
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <p class="text-sm text-slate-700 mt-0.5">{{ $merchantValueText($voucher) }}</p>
                                            <div class="mt-1.5 flex justify-between items-center gap-x-3 gap-y-1 text-xs text-slate-500">
                                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                                    <span>{{ number_format($claimedCount) }} claimed</span>
                                                    <span>{{ number_format($redeemedCount) }} redeemed</span>
                                                </div>
                                                @if($voucher->valid_until)
                                                    <span>
                                                        <em class="text-slate-500 font-medium">Valid until</em><br />
                                                        {{ $voucher->valid_until->format('d M Y') }}
                                                    </span>
                                                @else
                                                    <span>No end date</span>
                                                @endif
                                            </div>
                                            <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                                @if($isFullyClaimed)
                                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold border bg-orange-50 text-orange-700 border-orange-200">
                                                        Fully claimed
                                                    </span>
                                                {{-- @elseif($remainingClaims !== null)
                                                    <span class="text-[11px] text-slate-500">{{ number_format($remainingClaims) }} claims left</span> --}}
                                                {{-- @else
                                                    <span class="text-[11px] text-slate-500">Unlimited claims</span> --}}
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @else
                        <div class="mt-4 rounded-2xl border border-dashed border-slate-300 px-6 py-12 text-center">
                            <div class="mx-auto size-12 rounded-2xl bg-orange-50 text-orange-500 flex items-center justify-center mb-3">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z" />
                                </svg>
                            </div>
                            <p class="font-semibold text-slate-800">{{ $search || $statusFilter !== 'all' ? 'No matching vouchers' : 'No vouchers yet' }}</p>
                            <p class="text-sm text-slate-500 mt-1">
                                @if($search || $statusFilter !== 'all')
                                    Try another search or clear the status filter.
                                @else
                                    Create your first offer for members to claim.
                                @endif
                            </p>
                            @if($merchantIsActive && ! $search && $statusFilter === 'all')
                                <a href="{{ route('merchant.vouchers.create') }}" class="inline-flex mt-4 items-center justify-center rounded-full bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold px-4 py-2.5 transition-colors">
                                    Create first voucher
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            @else
                <div class="mt-4">
                    <p class="text-sm text-slate-500">Hope Village vouchers that members can redeem at your store. You can view details, but these are managed by administrators.</p>

                    @if($adminVouchers->count() > 0)
                        <div class="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-3">
                            @foreach($adminVouchers as $adminVoucher)
                                @php
                                    $statusLabel = $adminVoucher->getDisplayStatusLabel();
                                    $statusCategory = $adminVoucher->getDisplayStatusCategory();
                                    [$statusClass, $statusDot] = $statusStyles($statusLabel, $statusCategory);
                                    $isFullyClaimed = $adminVoucher->usage_limit && $adminVoucher->usage_count >= $adminVoucher->usage_limit;
                                    $claimedCount = (int) ($adminVoucher->claimed_members_count ?? 0);
                                    $redeemedCount = (int) ($adminVoucher->redeemed_members_count ?? 0);
                                    $redeemedHereCount = (int) ($adminVoucher->redeemed_at_store_count ?? 0);
                                    $pointsCost = (int) ($adminVoucher->points_cost ?? 0);
                                    $adminValueText = $pointsCost > 0
                                        ? number_format($pointsCost).' pts'
                                        : 'Hope Village reward';
                                @endphp
                                <article
                                    class="relative rounded-2xl border border-slate-200 bg-white"
                                    x-data="{ open: false }"
                                    :class="open ? 'z-30' : 'z-0'"
                                    @keydown.escape.window="open = false"
                                    @click.away="open = false"
                                >
                                    <div class="p-3 sm:p-4 flex gap-3">
                                        <div class="size-16 sm:size-20 rounded-xl overflow-hidden shrink-0 bg-teal-600 flex items-center justify-center">
                                            @if($adminVoucher->image_url)
                                                <img src="{{ $adminVoucher->image_url }}" alt="{{ $adminVoucher->name }}" class="size-full object-cover">
                                            @else
                                                <span class="text-white text-lg font-bold">HV</span>
                                            @endif
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-start justify-between gap-2">
                                                <h3 class="font-semibold text-slate-900 leading-tight">{{ $adminVoucher->name }}</h3>
                                                <div class="flex items-center gap-1 shrink-0">
                                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[11px] font-semibold border {{ $statusClass }}">
                                                        <span class="size-1.5 rounded-full {{ $statusDot }}"></span>
                                                        {{ $statusLabel }}
                                                    </span>
                                                    <div class="relative">
                                                        <button
                                                            type="button"
                                                            @click="open = !open"
                                                            class="size-8 rounded-full text-slate-500 hover:text-slate-800 hover:bg-slate-50 border border-transparent hover:border-slate-200 transition-colors inline-flex items-center justify-center"
                                                            :aria-expanded="open.toString()"
                                                            aria-haspopup="menu"
                                                            aria-label="Voucher actions"
                                                        >
                                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-5">
                                                                <circle cx="12" cy="5" r="1.75" />
                                                                <circle cx="12" cy="12" r="1.75" />
                                                                <circle cx="12" cy="19" r="1.75" />
                                                            </svg>
                                                        </button>
                                                        <div
                                                            x-show="open"
                                                            x-cloak
                                                            x-transition:enter="transition ease-out duration-150"
                                                            x-transition:enter-start="opacity-0 translate-y-1"
                                                            x-transition:enter-end="opacity-100 translate-y-0"
                                                            x-transition:leave="transition ease-in duration-100"
                                                            x-transition:leave-start="opacity-100 translate-y-0"
                                                            x-transition:leave-end="opacity-0 translate-y-1"
                                                            class="absolute right-0 top-full mt-2 z-50 w-60 rounded-xl border border-slate-200 bg-white shadow-lg shadow-slate-900/10 py-1"
                                                            role="menu"
                                                            style="display: none;"
                                                        >
                                                            <span class="pointer-events-none absolute -top-1.5 right-3 size-3 rotate-45 rounded-[2px] bg-white border-l border-t border-slate-200"></span>
                                                            <button
                                                                type="button"
                                                                wire:click="openAdminVoucher(@js($adminVoucher->voucher_code))"
                                                                @click="open = false"
                                                                class="flex w-full items-center gap-2 px-3 py-2 text-sm text-slate-700 hover:bg-orange-50 hover:text-orange-700"
                                                                role="menuitem"
                                                            >
                                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-4 shrink-0">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                                </svg>
                                                                View details
                                                            </button>
                                                            <button
                                                                type="button"
                                                                wire:click="openAdminReimbursements(@js($adminVoucher->voucher_code))"
                                                                @click="open = false"
                                                                class="flex w-full items-center gap-2 px-3 py-2 text-sm text-slate-700 hover:bg-orange-50 hover:text-orange-700"
                                                                role="menuitem"
                                                            >
                                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-4 shrink-0">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                                                                </svg>
                                                                View Reimbursements
                                                            </button>
                                                            <button
                                                                type="button"
                                                                wire:click="openAdminTransactions(@js($adminVoucher->voucher_code))"
                                                                @click="open = false"
                                                                class="flex w-full items-center gap-2 px-3 py-2 text-sm text-slate-700 hover:bg-orange-50 hover:text-orange-700"
                                                                role="menuitem"
                                                            >
                                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-4 shrink-0">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                                                </svg>
                                                                View Transaction History
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            {{-- <p class="text-sm text-slate-700 mt-0.5">{{ $adminValueText }}</p> --}}
                                            <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
                                                <span>{{ number_format($claimedCount) }} Claimed</span>
                                                {{-- <span>{{ number_format($redeemedCount) }} redeemed</span> --}}
                                                <span>{{ number_format($redeemedHereCount) }} Redeemed</span>
                                            </div>
                                            <div class="mt-1.5 flex justify-between items-center gap-1.5">
                                                @if($isFullyClaimed)
                                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold border bg-orange-50 text-orange-700 border-orange-200">
                                                        Fully claimed
                                                    </span>
                                                @else
                                                    <span class="text-[11px] text-slate-500">
                                                    </span>
                                                @endif
                                                @if($adminVoucher->valid_until)
                                                    <span class="text-[11px] text-slate-500"> 
                                                        <em class="text-slate-500 font-medium">Valid until</em><br />
                                                        {{ $adminVoucher->valid_until->format('d M Y') }}
                                                    </span>
                                                @else
                                                    <span class="text-[11px] text-slate-500">No end date</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @else
                        <div class="mt-4 rounded-2xl border border-dashed border-slate-300 px-6 py-12 text-center">
                            <p class="font-semibold text-slate-800">{{ $search ? 'No matching Hope Village vouchers' : 'No Hope Village vouchers' }}</p>
                            <p class="text-sm text-slate-500 mt-1">
                                @if($search)
                                    Try another search to find a Hope Village voucher.
                                @else
                                    When Hope Village assigns a voucher to your store, it appears here.
                                @endif
                            </p>
                        </div>
                    @endif
                </div>
            @endif
        </section>
    </div>

    @if($qrVoucherCode && $qrCodeImage)
        <div
            class="fixed inset-0 z-[9999] bg-black/60 flex items-center justify-center p-4"
            wire:click="closeQr"
            wire:keydown.escape.window="closeQr"
            role="dialog"
            aria-modal="true"
            aria-labelledby="merchant-voucher-qr-title"
        >
            <div
                wire:click.stop
                class="bg-white rounded-2xl border border-slate-200 shadow-xl w-full max-w-md p-4 sm:p-5 relative"
                x-data="{
                    qrCodeImage: @js($qrCodeImage),
                    voucherCode: @js($qrVoucherCode),
                    copied: false,
                    async downloadQR() {
                        try {
                            const response = await fetch(this.qrCodeImage);
                            const blob = await response.blob();
                            const url = window.URL.createObjectURL(blob);
                            const a = document.createElement('a');
                            a.href = url;
                            a.download = `voucher-qr-${this.voucherCode}.png`;
                            document.body.appendChild(a);
                            a.click();
                            window.URL.revokeObjectURL(url);
                            document.body.removeChild(a);
                        } catch (error) {
                            alert('Failed to download QR code. Please try again.');
                        }
                    },
                    async copyCode() {
                        try {
                            await navigator.clipboard.writeText(this.voucherCode);
                            this.copied = true;
                            setTimeout(() => this.copied = false, 2000);
                        } catch (e) {
                            alert(this.voucherCode);
                        }
                    }
                }"
            >
                <button
                    type="button"
                    wire:click="closeQr"
                    class="absolute top-4 right-4 text-slate-400 hover:text-slate-700 transition-colors"
                    aria-label="Close"
                >
                    <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <h3 id="merchant-voucher-qr-title" class="text-sm font-semibold text-slate-800 pr-8">Voucher QR code</h3>
                <p class="mt-1 text-xs text-slate-500">{{ $qrVoucherName }} — members can scan this to claim the offer.</p>

                <div class="mt-4 flex items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <img src="{{ $qrCodeImage }}" alt="Voucher QR Code" class="w-full max-w-[220px] aspect-square object-contain bg-white rounded-xl p-2">
                </div>

                <div class="flex items-center justify-center gap-2 mt-1">
                    <span class="font-mono text-sm">{{ $qrVoucherCode }}</span>
                    <span class="text-slate-400 text-sm italic" x-text="copied ? 'Copied' : ''"></span>
                </div>

                <div class="mt-3 grid grid-cols-2 gap-2">
                    <button
                        type="button"
                        @click="downloadQR()"
                        class="inline-flex items-center justify-center gap-1.5 rounded-full border border-slate-300 hover:border-orange-400 hover:text-orange-600 text-slate-700 text-sm font-semibold px-4 py-2.5 transition-colors"
                    >
                        Download QR Code
                    </button>
                    <button
                        type="button"
                        @click="copyCode()"
                        class="inline-flex items-center justify-center gap-1.5 rounded-full bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold px-4 py-2.5 transition-colors"
                    >
                        Copy Voucher Code
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if($selectedAdminVoucher)
        @php
            $adminStatusLabel = $selectedAdminVoucher->getDisplayStatusLabel();
            $adminStatusCategory = $selectedAdminVoucher->getDisplayStatusCategory();
            [$adminStatusClass, $adminStatusDot] = $statusStyles($adminStatusLabel, $adminStatusCategory);
            $adminFullyClaimed = $selectedAdminVoucher->usage_limit && $selectedAdminVoucher->usage_count >= $selectedAdminVoucher->usage_limit;
            $adminClaimedCount = (int) ($selectedAdminVoucher->claimed_members_count ?? 0);
            $adminRedeemedCount = (int) ($selectedAdminVoucher->redeemed_members_count ?? 0);
            $adminRedeemedHereCount = (int) ($selectedAdminVoucher->redeemed_at_store_count ?? 0);
            $adminPointsCost = (int) ($selectedAdminVoucher->points_cost ?? 0);
            $adminCostPerPoint = (float) ($selectedAdminVoucher->amount_cost ?? 0);
            $adminMerchantNames = $selectedAdminVoucher->merchants->pluck('name')->filter()->values();
        @endphp
        <div
            class="fixed inset-0 z-[9999] bg-black/60 flex items-center justify-center p-4"
            wire:click="closeAdminVoucher"
            wire:keydown.escape.window="closeAdminVoucher"
            role="dialog"
            aria-modal="true"
            aria-labelledby="admin-voucher-detail-title"
        >
            <div wire:click.stop class="bg-white rounded-2xl border border-slate-200 shadow-xl w-full max-w-lg p-4 sm:p-5 relative max-h-[85vh] overflow-y-auto">
                <button
                    type="button"
                    wire:click="closeAdminVoucher"
                    class="absolute top-4 right-4 text-slate-400 hover:text-slate-700 transition-colors"
                    aria-label="Close"
                >
                    <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <div class="pr-8">
                    <p class="text-xs font-semibold uppercase tracking-wide text-teal-600">Hope Village voucher</p>
                    <h3 id="admin-voucher-detail-title" class="mt-1 text-lg font-bold text-slate-900">{{ $selectedAdminVoucher->name }}</h3>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold border {{ $adminStatusClass }}">
                            <span class="size-1.5 rounded-full {{ $adminStatusDot }}"></span>
                            {{ $adminStatusLabel }}
                        </span>
                        @if($adminFullyClaimed)
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold border bg-orange-50 text-orange-700 border-orange-200">
                                Fully claimed
                            </span>
                        @endif
                    </div>
                </div>

                @if($selectedAdminVoucher->description)
                    <p class="mt-4 text-sm text-slate-600 whitespace-pre-line">{{ $selectedAdminVoucher->description }}</p>
                @endif

                <dl class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Voucher Code</dt>
                        <dd class="mt-1 text-sm font-mono text-slate-900">{{ $selectedAdminVoucher->voucher_code }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Points Cost</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-900">{{ number_format($adminPointsCost) }} pts</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Validity</dt>
                        <dd class="mt-1 text-sm text-slate-900">
                            {{ $selectedAdminVoucher->valid_from ? $selectedAdminVoucher->valid_from->format('d M Y') : 'N/A' }}
                            -
                            {{ $selectedAdminVoucher->valid_until ? $selectedAdminVoucher->valid_until->format('d M Y') : 'N/A' }}
                        </dd>
                    </div>
                    @if($adminCostPerPoint > 0)
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-slate-500">Cost Per Point</dt>
                            <dd class="mt-1 text-sm text-slate-900">SGD {{ number_format($adminCostPerPoint, 2) }}</dd>
                        </div>
                    @endif
                </dl>

                <div class="mt-5 grid grid-cols-2 gap-2">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-center">
                        <p class="text-lg font-bold text-slate-900 tabular-nums">{{ number_format($adminClaimedCount) }}</p>
                        <p class="text-[11px] text-slate-500">Total Claimed</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-center">
                        <p class="text-lg font-bold text-slate-900 tabular-nums">{{ number_format($adminRedeemedHereCount) }}</p>
                        <p class="text-[11px] text-slate-500">Total Redeemed</p>
                    </div>
                </div>
                <button
                    type="button"
                    wire:click="closeAdminVoucher"
                    class="mt-5 w-full inline-flex items-center justify-center rounded-2xl bg-slate-800 hover:bg-slate-900 text-white font-semibold py-3 px-4 transition-colors"
                >
                    Close
                </button>
            </div>
        </div>
    @endif

    @if($selectedAdminReimbursements)
        @php
            $reimbVoucher = $selectedAdminReimbursements['voucher'];
            $reimbursements = $selectedAdminReimbursements['reimbursements'];
            $reimbDispensed = $selectedAdminReimbursements['totalDispensed'];
            $reimbTotal = $selectedAdminReimbursements['totalReimbursed'];
            $reimbOutstanding = $selectedAdminReimbursements['outstanding'];
        @endphp
        <div
            class="fixed inset-0 z-[9999] bg-black/60 flex items-center justify-center p-4"
            wire:click="closeAdminReimbursements"
            wire:keydown.escape.window="closeAdminReimbursements"
            role="dialog"
            aria-modal="true"
            aria-labelledby="admin-voucher-reimbursements-title"
        >
            <div wire:click.stop class="bg-white rounded-2xl border border-slate-200 shadow-xl w-full max-w-lg p-4 sm:p-5 relative max-h-[85vh] overflow-y-auto">
                <button
                    type="button"
                    wire:click="closeAdminReimbursements"
                    class="absolute top-4 right-4 text-slate-400 hover:text-slate-700 transition-colors"
                    aria-label="Close"
                >
                    <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <div class="pr-8">
                    <p class="text-xs font-semibold uppercase tracking-wide text-teal-600">Hope Village voucher</p>
                    <p class="mt-1 text-lg font-bold text-slate-900">{{ $reimbVoucher->name }} — payments from Hope Village to your store</p>
                </div>

                <div class="mt-5 grid grid-cols-3 gap-2">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-center">
                        <p class="text-sm font-bold text-slate-900 tabular-nums">SGD {{ number_format($reimbDispensed, 2) }}</p>
                        <p class="text-[11px] text-slate-500">Total Redeemed</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-center">
                        <p class="text-sm font-bold text-slate-900 tabular-nums">SGD {{ number_format($reimbTotal, 2) }}</p>
                        <p class="text-[11px] text-slate-500">Reimbursed</p>
                    </div>
                    <div class="rounded-xl border px-3 py-3 text-center {{ $reimbOutstanding > 0 ? 'border-red-200 bg-red-50' : 'border-emerald-200 bg-emerald-50' }}">
                        <p class="text-sm font-bold tabular-nums {{ $reimbOutstanding > 0 ? 'text-red-700' : 'text-emerald-700' }}">SGD {{ number_format($reimbOutstanding, 2) }}</p>
                        <p class="text-[11px] {{ $reimbOutstanding > 0 ? 'text-red-600' : 'text-emerald-600' }}">Outstanding</p>
                    </div>
                </div>

                <div class="mt-5 overflow-hidden rounded-xl border border-slate-200">
                    @if($reimbursements->isNotEmpty())
                        <ul class="max-h-80 overflow-y-auto divide-y divide-slate-100">
                            @foreach($reimbursements as $reimbursement)
                                <li class="px-4 py-3.5">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="font-semibold text-slate-900">SGD {{ number_format((float) $reimbursement->amount, 2) }}</p>
                                            <p class="text-xs text-slate-500 mt-0.5">
                                                {{ $reimbursement->ledgerEntry?->period_month?->format('M Y') ?? '—' }}
                                                @if($reimbursement->notes)
                                                    · {{ $reimbursement->notes }}
                                                @endif
                                            </p>
                                        </div>
                                        <p class="text-xs text-slate-500 shrink-0">
                                            {{ $reimbursement->reimbursed_at?->format('d M Y') ?? '—' }}
                                        </p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="px-6 py-12 text-center">
                            <p class="font-semibold text-slate-800">No reimbursements yet</p>
                            <p class="text-sm text-slate-500 mt-1">Payments Hope Village records for this voucher at your store will appear here.</p>
                        </div>
                    @endif
                </div>

                <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <a
                        href="{{ route('merchant.vouchers.admin-reimbursements-pdf', $reimbVoucher->voucher_code) }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-zinc-500 hover:bg-zinc-700 text-white font-semibold py-3 px-4 transition-colors"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a24.95 24.95 0 0 1 12.56 0m-12.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V6.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v.858m10.5 0V9.75m-10.5-2.517V9.75" />
                        </svg>
                        Print to PDF
                    </a>
                    <button
                        type="button"
                        wire:click="closeAdminReimbursements"
                        class="inline-flex items-center justify-center rounded-2xl bg-slate-800 hover:bg-slate-900 text-white font-semibold py-3 px-4 transition-colors"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if($selectedAdminTransactions)
        @php
            $txVoucher = $selectedAdminTransactions['voucher'];
            $transactions = $selectedAdminTransactions['transactions'];
            $txTotal = $selectedAdminTransactions['totalAmount'];
        @endphp
        <div
            class="fixed inset-0 z-[9999] bg-black/60 flex items-center justify-center p-4"
            wire:click="closeAdminTransactions"
            wire:keydown.escape.window="closeAdminTransactions"
            role="dialog"
            aria-modal="true"
            aria-labelledby="admin-voucher-transactions-title"
        >
            <div wire:click.stop class="bg-white rounded-2xl border border-slate-200 shadow-xl w-full max-w-lg p-4 sm:p-5 relative max-h-[85vh] overflow-y-auto">
                <button
                    type="button"
                    wire:click="closeAdminTransactions"
                    class="absolute top-4 right-4 text-slate-400 hover:text-slate-700 transition-colors"
                    aria-label="Close"
                >
                    <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <div class="pr-8">
                    <p class="text-xs font-semibold uppercase tracking-wide text-teal-600">Hope Village voucher</p>
                    <h3 id="admin-voucher-transactions-title" class="mt-1 text-lg font-bold text-slate-900">View Transaction History</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ $txVoucher->name }} — redemptions at your store</p>
                </div>

                <div class="mt-5 grid grid-cols-2 gap-2">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-center">
                        <p class="text-lg font-bold text-slate-900 tabular-nums">{{ number_format($transactions->count()) }}</p>
                        <p class="text-[11px] text-slate-500">Redeemed here</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-center">
                        <p class="text-lg font-bold text-slate-900 tabular-nums">SGD {{ number_format($txTotal, 2) }}</p>
                        <p class="text-[11px] text-slate-500">Total amount</p>
                    </div>
                </div>

                <div class="mt-5 overflow-hidden rounded-xl border border-slate-200">
                    @if($transactions->isNotEmpty())
                        <ul class="max-h-80 overflow-y-auto divide-y divide-slate-100">
                            @foreach($transactions as $transaction)
                                <li class="px-4 py-3.5">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="font-semibold text-slate-900 truncate">{{ $transaction['name'] }}</p>
                                            @if($transaction['qr_code'])
                                                <p class="text-[11px] text-slate-400 font-mono mt-0.5">{{ $transaction['qr_code'] }}</p>
                                            @endif
                                        </div>
                                        <div class="text-right shrink-0">
                                            <p class="text-sm font-semibold text-slate-900">SGD {{ number_format((float) $transaction['amount'], 2) }}</p>
                                            <p class="text-xs text-slate-500 mt-0.5">
                                                {{ $transaction['redeemed_at'] ? \Carbon\Carbon::parse($transaction['redeemed_at'])->format('d M Y g:i A') : '—' }}
                                            </p>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="px-6 py-12 text-center">
                            <p class="font-semibold text-slate-800">No transactions yet</p>
                            <p class="text-sm text-slate-500 mt-1">When members redeem this voucher at your store, those redemptions show up here.</p>
                        </div>
                    @endif
                </div>

                <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <a
                        href="{{ route('merchant.vouchers.admin-transaction-history-pdf', $txVoucher->voucher_code) }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-zinc-500 hover:bg-zinc-700 text-white font-semibold py-3 px-4 transition-colors"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a24.95 24.95 0 0 1 12.56 0m-12.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V6.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v.858m10.5 0V9.75m-10.5-2.517V9.75" />
                        </svg>
                        Transaction History
                    </a>
                    <button
                        type="button"
                        wire:click="closeAdminTransactions"
                        class="inline-flex items-center justify-center rounded-2xl bg-slate-800 hover:bg-slate-900 text-white font-semibold py-3 px-4 transition-colors"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if($deletingVoucherCode)
        <div
            class="fixed inset-0 z-[9999] bg-black/60 flex items-center justify-center p-4"
            wire:click="cancelDelete"
            wire:keydown.escape.window="cancelDelete"
            role="dialog"
            aria-modal="true"
            aria-labelledby="delete-voucher-title"
        >
            <div wire:click.stop class="bg-white rounded-2xl border border-slate-200 shadow-xl w-full max-w-md p-4 sm:p-5">
                <h3 id="delete-voucher-title" class="text-lg font-bold text-slate-900">Delete voucher?</h3>
                <p class="mt-2 text-sm text-slate-600">
                    <span class="font-semibold text-slate-800">{{ $deletingVoucherName }}</span> will be archived and hidden from members. This can be restored later by an administrator.
                </p>
                <div class="mt-5 grid grid-cols-2 gap-2">
                    <button
                        type="button"
                        wire:click="cancelDelete"
                        class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white hover:border-slate-300 text-slate-800 font-semibold py-3 px-4 transition-colors"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        wire:click="deleteConfirmed"
                        class="inline-flex items-center justify-center rounded-2xl bg-red-600 hover:bg-red-700 text-white font-semibold py-3 px-4 transition-colors"
                    >
                        Delete voucher
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
