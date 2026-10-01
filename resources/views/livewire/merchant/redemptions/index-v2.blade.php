@php
    $storeValueText = function ($redemption): string {
        if (($redemption->discount_type ?? null) === 'percentage') {
            return rtrim(rtrim((string) ($redemption->discount_value ?? 0), '0'), '.').'% off';
        }

        if (($redemption->discount_type ?? null) === 'item') {
            return 'Free Item';
        }

        return '$'.number_format((float) ($redemption->discount_value ?? 0), 2).' off';
    };

    $adminValueText = function ($redemption): string {
        $pointsCost = (int) ($redemption->points_cost ?? 0);
        if ($pointsCost > 0) {
            return number_format($pointsCost).' pts';
        }

        if ($redemption->amount_cost !== null && $redemption->amount_cost !== '') {
            return 'SGD '.number_format((float) $redemption->amount_cost, 2);
        }

        return 'Hope Village reward';
    };

    $hasSearch = trim($search) !== '';
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

            <div class="mt-6">
                <a href="{{ route('merchant.dashboard.v2') }}" class="inline-flex items-center gap-1.5 text-orange-200 hover:text-white text-sm font-medium transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                    </svg>
                    Back to Home
                </a>
                <h1 class="text-white text-2xl sm:text-3xl font-bold tracking-tight mt-2">Redemption History</h1>
                <p class="text-white/70 md:text-lg text-sm mt-1">Members who redeemed vouchers at your store.</p>
            </div>
        </div>
    </div>

    <div class="max-w-xl md:max-w-2xl lg:max-w-6xl mx-auto px-4 sm:px-6 -mt-10 relative z-10">
        <section class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Today</p>
                    <span class="size-8 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-3xl font-bold text-slate-900 tabular-nums">{{ number_format($stats['today']) }}</p>
                <p class="mt-1 text-xs text-slate-500">Redeemed today</p>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">This Week</p>
                    <span class="size-8 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-3xl font-bold text-slate-900 tabular-nums">{{ number_format($stats['this_week']) }}</p>
                <p class="mt-1 text-xs text-slate-500">Redeemed this week</p>
            </div>

            <button
                type="button"
                wire:click="setTab('merchant')"
                class="text-left bg-white rounded-2xl border {{ $activeTab === 'merchant' ? 'border-orange-300 ring-1 ring-orange-200' : 'border-slate-200' }} p-4 shadow-sm hover:border-orange-200 transition-colors"
            >
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">My Vouchers</p>
                    <span class="size-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-3xl font-bold text-slate-900 tabular-nums">{{ number_format($stats['merchant_total']) }}</p>
                <p class="mt-1 text-xs text-slate-500">Store offers redeemed</p>
            </button>

            <button
                type="button"
                wire:click="setTab('admin')"
                class="text-left bg-white rounded-2xl border {{ $activeTab === 'admin' ? 'border-orange-300 ring-1 ring-orange-200' : 'border-slate-200' }} p-4 shadow-sm hover:border-orange-200 transition-colors"
            >
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Hope Village</p>
                    <span class="size-8 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-3xl font-bold text-slate-900 tabular-nums">{{ number_format($stats['admin_total']) }}</p>
                <p class="mt-1 text-xs text-slate-500">Redeemed at this store</p>
            </button>
        </section>

        <section class="mt-5 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-4 sm:p-5">
                <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                    <label class="relative flex-1">
                        <span class="sr-only">Search redemptions</span>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 size-4 text-slate-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                        <input
                            type="search"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Search by member, voucher, or code"
                            class="w-full rounded-xl border-slate-200 bg-slate-50 pl-10 pr-3 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-orange-400 focus:ring-orange-400"
                        >
                    </label>
                    <select
                        id="sort-select"
                        wire:model.live="sortOption"
                        class="w-full sm:w-52 rounded-xl border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-800 focus:border-orange-400 focus:ring-orange-400"
                    >
                        <option value="redeemed_at_desc">Newest first</option>
                        <option value="redeemed_at_asc">Oldest first</option>
                        <option value="member_name_asc">Member (A-Z)</option>
                        <option value="member_name_desc">Member (Z-A)</option>
                        <option value="voucher_name_asc">Voucher (A-Z)</option>
                        <option value="voucher_name_desc">Voucher (Z-A)</option>
                        <option value="voucher_code_asc">Code (A-Z)</option>
                        <option value="voucher_code_desc">Code (Z-A)</option>
                    </select>
                </div>

                <div class="mt-4 flex items-center gap-2 border-b border-slate-200">
                    <button
                        type="button"
                        wire:click="setTab('merchant')"
                        @class([
                            'px-1 pb-3 text-sm border-b-2 transition-colors',
                            'border-orange-500 text-orange-600 font-semibold' => $activeTab === 'merchant',
                            'border-transparent text-slate-500 hover:text-slate-800' => $activeTab !== 'merchant',
                        ])
                    >
                        My Vouchers ({{ $merchantCount }})
                    </button>
                    <button
                        type="button"
                        wire:click="setTab('admin')"
                        @class([
                            'px-1 pb-3 text-sm border-b-2 transition-colors',
                            'border-orange-500 text-orange-600 font-semibold' => $activeTab === 'admin',
                            'border-transparent text-slate-500 hover:text-slate-800' => $activeTab !== 'admin',
                        ])
                    >
                        Hope Village Vouchers ({{ $adminCount }})
                    </button>
                </div>
            </div>

            @if($activeTab === 'merchant')
                <div>
                    <div class="px-4 sm:px-5 pb-3">
                        <p class="text-sm text-slate-500">Store offers created by you, then redeemed by members.</p>
                    </div>

                    @if ($merchantRedemptions->count() > 0)
                        <ul class="divide-y divide-slate-100">
                            @foreach($merchantRedemptions as $redemption)
                                <li class="px-4 sm:px-5 py-3.5">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="font-semibold text-slate-900 truncate">{{ $redemption->member_name }}</p>
                                            @if($redemption->member_qr_code)
                                                <p class="text-[11px] text-slate-400 font-mono mt-1">{{ $redemption->member_qr_code }}</p>
                                            @endif
                                            <div class="mt-2 min-w-0">
                                                <a
                                                    href="{{ route('merchant.vouchers.profile', $redemption->voucher_code) }}"
                                                    class="text-sm font-medium text-slate-800 hover:text-orange-600 truncate block"
                                                >
                                                    {{ $redemption->voucher_name }}
                                                </a>
                                                <p class="mt-0.5 text-[11px] text-slate-500">
                                                    <span class="font-mono">{{ $redemption->voucher_code }}</span>
                                                    <span class="mx-1">·</span>
                                                    <span>{{ $storeValueText($redemption) }}</span>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide bg-emerald-50 text-emerald-700">
                                                Redeemed
                                            </span>
                                            <p class="text-[10px] font-semibold uppercase tracking-wide text-orange-600 mt-1.5">Store voucher</p>
                                            <p class="text-xs text-slate-500 mt-1.5">
                                                {{ $redemption->redeemed_at ? \Carbon\Carbon::parse($redemption->redeemed_at)->format('d M Y g:i A') : 'N/A' }}
                                            </p>
                                            @if($redemption->claimed_at)
                                                <p class="text-[11px] text-slate-400 mt-1">
                                                    Claimed {{ \Carbon\Carbon::parse($redemption->claimed_at)->format('d M Y') }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="px-6 py-12 text-center">
                            <div class="mx-auto size-12 rounded-2xl bg-orange-50 text-orange-500 flex items-center justify-center mb-3">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                            </div>
                            <p class="font-semibold text-slate-800">{{ $hasSearch ? 'No matching redemptions' : 'No store redemptions yet' }}</p>
                            <p class="text-sm text-slate-500 mt-1">
                                @if($hasSearch)
                                    Try another member name, voucher, or code.
                                @else
                                    When members redeem your store vouchers, they show up here.
                                @endif
                            </p>
                        </div>
                    @endif
                </div>
            @else
                <div class="relative min-h-[16rem]">
                    <div wire:loading.flex wire:target="setTab,activeTab" class="absolute inset-0 z-10 flex-col items-center justify-center text-center text-slate-500 bg-white/80">
                        <div class="inline-block animate-spin rounded-full h-10 w-10 border-b-2 border-orange-500"></div>
                        <p class="mt-4 text-sm font-medium">Loading Hope Village voucher redemptions...</p>
                    </div>

                    <div wire:loading.remove wire:target="setTab,activeTab">
                        <div class="px-4 sm:px-5 pb-3">
                            <p class="text-sm text-slate-500">Hope Village vouchers redeemed at your store.</p>
                        </div>

                        @if ($adminLoaded && $adminRedemptions->count() > 0)
                            @php
                                $adminRedemptionsByDate = $adminRedemptions->groupBy(function ($redemption) {
                                    return $redemption->redeemed_at
                                        ? \Carbon\Carbon::parse($redemption->redeemed_at)->toDateString()
                                        : 'unknown';
                                });
                            @endphp

                            <div class="px-4 sm:px-5 pb-2">
                                @foreach($adminRedemptionsByDate as $dateKey => $dayRedemptions)
                                    @php
                                        $dayDate = $dateKey === 'unknown' ? null : \Carbon\Carbon::parse($dateKey);
                                        $dayHeading = match (true) {
                                            $dayDate === null => 'Unknown date',
                                            $dayDate->isToday() => 'Today',
                                            $dayDate->isYesterday() => 'Yesterday',
                                            default => $dayDate->format('l'),
                                        };
                                    @endphp
                                    <section class="mb-6 last:mb-1">
                                        <div class="flex items-baseline justify-between gap-3 mb-3">
                                            <h4 class="text-sm font-bold text-slate-900 tracking-tight">{{ $dayHeading }}</h4>
                                            @if($dayDate)
                                                <p class="text-[11px] font-medium text-slate-400 uppercase tracking-wide">{{ $dayDate->format('d M Y') }}</p>
                                            @endif
                                        </div>

                                        <ol class="relative ms-3 border-s-2 border-teal-100">
                                            @foreach($dayRedemptions as $redemption)
                                                @php
                                                    $redeemedAt = $redemption->redeemed_at ? \Carbon\Carbon::parse($redemption->redeemed_at) : null;
                                                @endphp
                                                <li class="relative ms-6 pb-5 last:pb-1">
                                                    <span class="absolute -start-[31px] top-3 flex size-7 items-center justify-center rounded-full bg-emerald-500 text-white ring-4 ring-emerald-50">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-3.5" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                        </svg>
                                                    </span>

                                                    <article class="bg-slate-50/80 rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                                                        <div class="p-3.5 sm:p-4 flex items-start gap-3">
                                                            <time
                                                                @if($redeemedAt)
                                                                    datetime="{{ $redeemedAt->toIso8601String() }}"
                                                                    title="{{ $redeemedAt->format('d M Y g:i A') }}"
                                                                @endif
                                                                class="shrink-0 min-w-[3.25rem] flex flex-col items-center justify-center rounded-xl bg-emerald-50 text-emerald-800 px-2 py-3"
                                                            >
                                                                <span class="text-sm font-bold leading-none">{{ $redeemedAt?->format('g:i') ?? '--' }}</span>
                                                                <span class="text-[11px] font-semibold leading-none mt-1">{{ $redeemedAt?->format('A') ?? '' }}</span>
                                                            </time>

                                                            <div class="min-w-0 flex-1">
                                                                <div class="flex flex-wrap items-center gap-1.5">
                                                                    <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] border border-emerald-700/60 font-semibold uppercase tracking-wide bg-emerald-50 text-emerald-700">
                                                                        Redeemed
                                                                    </span>
                                                                    <span class="inline-flex rounded-full px-2 py-0.5 border border-teal-700/60 text-[10px] font-semibold uppercase tracking-wide bg-teal-50 text-teal-700">
                                                                        Hope Village
                                                                    </span>
                                                                </div>

                                                                <p class="mt-2 font-semibold text-slate-900 text-sm sm:text-lg leading-tight">{{ $redemption->member_name }}</p>
                                                                @if($redemption->member_qr_code)
                                                                    <p class="text-[11px] text-slate-400 font-mono mt-1">{{ $redemption->member_qr_code }}</p>
                                                                @endif

                                                                <div class="mt-2 min-w-0">
                                                                    <p class="text-sm sm:text-base font-medium text-slate-800">{{ $redemption->voucher_name }}</p>
                                                                    <p class="mt-0.5 text-[11px] text-slate-500">
                                                                        <span class="font-mono">{{ $redemption->voucher_code }}</span>
                                                                        <span class="mx-1">·</span>
                                                                        <span>{{ $adminValueText($redemption) }}</span>
                                                                    </p>
                                                                </div>

                                                                @if($redemption->claimed_at)
                                                                    <p class="text-[11px] text-slate-400 mt-2">
                                                                        Claimed {{ \Carbon\Carbon::parse($redemption->claimed_at)->format('d M Y') }}
                                                                    </p>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </article>
                                                </li>
                                            @endforeach
                                        </ol>
                                    </section>
                                @endforeach
                            </div>

                            @if ($adminHasMore)
                                <div class="px-4 sm:px-5 py-5 text-center border-t border-slate-100">
                                    <button
                                        type="button"
                                        wire:click="loadMoreAdminRedemptions"
                                        wire:loading.attr="disabled"
                                        wire:target="loadMoreAdminRedemptions"
                                        class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-semibold text-orange-600 bg-white border border-orange-300 rounded-full hover:bg-orange-50 disabled:opacity-50 disabled:cursor-not-allowed transition"
                                    >
                                        <span wire:loading.remove wire:target="loadMoreAdminRedemptions">Load more</span>
                                        <span wire:loading wire:target="loadMoreAdminRedemptions" class="inline-flex items-center gap-2">
                                            <span class="inline-block animate-spin rounded-full h-4 w-4 border-b-2 border-orange-500"></span>
                                            Loading...
                                        </span>
                                    </button>
                                    <p class="mt-2 text-xs text-slate-500">
                                        Showing {{ $adminRedemptions->count() }} of {{ $adminCount }}
                                    </p>
                                </div>
                            @endif
                        @elseif ($adminLoaded)
                            <div class="px-6 py-12 text-center">
                                <div class="mx-auto size-12 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center mb-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                                    </svg>
                                </div>
                                <p class="font-semibold text-slate-800">{{ $hasSearch ? 'No matching redemptions' : 'No Hope Village redemptions yet' }}</p>
                                <p class="text-sm text-slate-500 mt-1">
                                    @if($hasSearch)
                                        Try another member name, voucher, or code.
                                    @else
                                        When members redeem Hope Village vouchers at your store, they show up here.
                                    @endif
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </section>
    </div>
</div>
