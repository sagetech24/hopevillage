<x-app-layout>
    @php
        $user = auth()->user();
        $currentMerchant = $user->currentMerchant();
        $hour = now()->hour;
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
        $hasMultipleStores = $user->merchants()->count() > 1;

        $activeVouchersCount = 0;
        $totalVouchersCount = 0;
        $adminVouchersCount = 0;
        $todayRedemptions = 0;
        $storeLocation = null;
        $recentVouchers = collect();
        $recentRedemptions = collect();

        if ($currentMerchant) {
            $currentMerchant->loadMissing('media');
            $activeVouchersCount = $currentMerchant->vouchers()->valid()->count();
            $totalVouchersCount = $currentMerchant->vouchers()->count();
            $adminVouchersCount = $currentMerchant->adminVouchers()->count();

            $merchantRedemptionsBase = \Illuminate\Support\Facades\DB::table('user_voucher')
                ->join('vouchers', 'vouchers.id', '=', 'user_voucher.voucher_id')
                ->whereNull('vouchers.deleted_at')
                ->where('vouchers.merchant_id', $currentMerchant->id)
                ->where('user_voucher.status', 'redeemed');

            $adminRedemptionsBase = \Illuminate\Support\Facades\DB::table('user_admin_voucher')
                ->where('redeemed_at_merchant_id', $currentMerchant->id)
                ->where('status', 'redeemed');

            $todayStart = now()->startOfDay();
            $todayRedemptions = (clone $merchantRedemptionsBase)->where('user_voucher.redeemed_at', '>=', $todayStart)->count()
                + (clone $adminRedemptionsBase)->where('redeemed_at', '>=', $todayStart)->count();

            $storeLocation = trim(implode(', ', array_filter([
                $currentMerchant->address,
                $currentMerchant->city,
                $currentMerchant->province,
            ])));

            $recentVouchers = $currentMerchant->vouchers()->with('media')->latest()->take(5)->get();

            $merchantRecent = \Illuminate\Support\Facades\DB::table('user_voucher')
                ->join('vouchers', 'vouchers.id', '=', 'user_voucher.voucher_id')
                ->join('users', 'users.id', '=', 'user_voucher.user_id')
                ->whereNull('vouchers.deleted_at')
                ->whereNull('users.deleted_at')
                ->where('vouchers.merchant_id', $currentMerchant->id)
                ->where('user_voucher.status', 'redeemed')
                ->select(
                    'users.name as member_name',
                    'vouchers.name as voucher_name',
                    'vouchers.voucher_code',
                    'user_voucher.redeemed_at',
                    \Illuminate\Support\Facades\DB::raw("'store' as source")
                )
                ->orderByDesc('user_voucher.redeemed_at')
                ->limit(5)
                ->get();

            $adminRecent = \Illuminate\Support\Facades\DB::table('user_admin_voucher')
                ->join('admin_vouchers', 'admin_vouchers.id', '=', 'user_admin_voucher.admin_voucher_id')
                ->join('users', 'users.id', '=', 'user_admin_voucher.user_id')
                ->whereNull('admin_vouchers.deleted_at')
                ->whereNull('users.deleted_at')
                ->where('user_admin_voucher.redeemed_at_merchant_id', $currentMerchant->id)
                ->where('user_admin_voucher.status', 'redeemed')
                ->select(
                    'users.name as member_name',
                    'admin_vouchers.name as voucher_name',
                    'admin_vouchers.voucher_code',
                    'user_admin_voucher.redeemed_at',
                    \Illuminate\Support\Facades\DB::raw("'admin' as source")
                )
                ->orderByDesc('user_admin_voucher.redeemed_at')
                ->limit(5)
                ->get();

            $recentRedemptions = $merchantRecent
                ->concat($adminRecent)
                ->sortByDesc('redeemed_at')
                ->take(5)
                ->values();
        }
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
                    <p class="text-orange-200 md:text-xl text-sm font-medium">{{ $greeting }}</p>
                    <h1 class="text-white text-2xl sm:text-3xl font-bold tracking-tight mt-0.5">{{ $user->name }}</h1>
                    <p class="text-white/70 md:text-lg text-sm mt-1">Manage your account, vouchers, and member redemptions.</p>
                </div>
            </div>
        </div>

        <div class="max-w-xl md:max-w-2xl lg:max-w-6xl mx-auto px-4 sm:px-6 -mt-10 relative z-10">
            @if (session('merchant-switched'))
                <div
                    x-data="{ show: true }"
                    x-init="setTimeout(() => show = false, 2000)"
                    x-show="show"
                    x-transition:leave="transition ease-out duration-500"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
                    role="alert"
                >
                    {{ session('merchant-switched') }}
                </div>
            @endif

            @if($currentMerchant)
                <section class="bg-white rounded-2xl border border-slate-200/80 shadow-lg shadow-slate-900/5 p-4 sm:p-5">
                    <div class="flex items-start gap-4">
                        <div class="size-16 sm:size-20 rounded-2xl overflow-hidden shrink-0 bg-orange-50 border border-orange-100 flex items-center justify-center">
                            @if($currentMerchant->logo_url)
                                <img src="{{ $currentMerchant->logo_url }}" alt="{{ $currentMerchant->name }}" class="size-full object-cover">
                            @else
                                <span class="text-orange-500 text-xl font-bold tracking-wide">{{ strtoupper(substr($currentMerchant->name, 0, 2)) }}</span>
                            @endif
                        </div>

                        <div class="min-w-0">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h2 class="text-lg sm:text-xl font-bold text-slate-900">{{ $currentMerchant->name }}</h2>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold border {{ $currentMerchant->is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-800 border-amber-200' }}">
                                        <span class="size-1.5 rounded-full {{ $currentMerchant->is_active ? 'bg-emerald-500' : 'bg-amber-500 animate-pulse' }}"></span>
                                        {{ $currentMerchant->is_active ? 'Live' : 'Pending' }}
                                    </span>
                                </div>
                            </div>
                            @if($hasMultipleStores)
                                <div class="w-full">
                                    @livewire('merchant.merchant-switcher-dropdown')
                                </div>
                            @endif
                        </div>
                    </div>

                </section>

                @if(! $currentMerchant->is_active)
                    <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                        <p class="font-semibold">Store pending approval</p>
                        <p class="mt-0.5 text-amber-800/90">You can review this dashboard, but new offers stay locked until an admin approves your store.</p>
                    </div>
                @endif

                <section class="mt-5 grid grid-cols-2 lg:grid-cols-4 gap-3">
                    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Active Vouchers</p>
                            <span class="size-8 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z" />
                                </svg>
                            </span>
                        </div>
                        <p class="mt-3 text-3xl font-bold text-slate-900 tabular-nums">{{ number_format($activeVouchersCount) }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ number_format($totalVouchersCount) }} total vouchers</p>
                    </div>

                    <a href="{{ route('merchant.reimbursements.index') }}" class="block group" aria-label="View reimbursement records">
                        <x-merchant.reimbursements-kpi :merchant="$currentMerchant" class="transition-all duration-200 group-hover:-translate-y-0.5 group-hover:shadow-md group-hover:border-orange-200" />
                    </a>

                    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Today</p>
                            <span class="size-8 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                            </span>
                        </div>
                        <p class="mt-3 text-3xl font-bold text-slate-900 tabular-nums">{{ number_format($todayRedemptions) }}</p>
                        <p class="mt-1 text-xs text-slate-500">Redeemed since midnight</p>
                    </div>

                    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Admin Vouchers</p>
                            <span class="size-8 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                                </svg>
                            </span>
                        </div>
                        <p class="mt-3 text-3xl font-bold text-slate-900 tabular-nums">{{ number_format($adminVouchersCount) }}</p>
                        <p class="mt-1 text-xs text-slate-500">Hope Village Vouchers</p>
                    </div>
                </section>

                <section class="mt-6">
                    <h3 class="text-sm font-semibold text-slate-800">Quick actions</h3>
                    <div class="mt-3 grid grid-cols-2 sm:grid-cols-4 gap-3">
                        @if($currentMerchant->is_active)
                            <a href="{{ route('merchant.vouchers.create') }}" class="group bg-orange-500 hover:bg-orange-600 text-white rounded-2xl p-4 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
                                <span class="size-10 rounded-xl bg-white/15 flex items-center justify-center mb-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                    </svg>
                                </span>
                                <p class="font-semibold">New Voucher</p>
                                <p class="text-xs text-orange-50/80 mt-0.5">Create New Voucher (Subject to Approval)</p>
                            </a>
                        @else
                            <div class="bg-slate-100 text-slate-400 rounded-2xl p-4 border border-slate-200 cursor-not-allowed">
                                <span class="size-10 rounded-xl bg-white flex items-center justify-center mb-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                    </svg>
                                </span>
                                <p class="font-semibold">New Voucher</p>
                                <p class="text-xs mt-0.5">Locked until merchant account is activated</p>
                            </div>
                        @endif

                        <button type="button" onclick="window.dispatchEvent(new CustomEvent('openQrScanner'))" class="text-left group bg-white hover:border-orange-300 rounded-2xl p-4 border border-slate-200 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
                            <span class="size-10 rounded-xl bg-slate-800 text-white flex items-center justify-center mb-3">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 3.75 9.375v-4.5ZM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 13.5 9.375v-4.5Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.25 15.75h4.5M17.25 12.75v6" />
                                </svg>
                            </span>
                            <p class="font-semibold text-slate-900">Scan QR</p>
                            <p class="text-xs text-slate-500 mt-0.5">Redeem a Voucher</p>
                        </button>

                        <a href="{{ route('merchant.vouchers.index') }}" class="group bg-white hover:border-orange-300 rounded-2xl p-4 border border-slate-200 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
                            <span class="size-10 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center mb-3">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72" />
                                </svg>
                            </span>
                            <p class="font-semibold text-slate-900">Vouchers</p>
                            <p class="text-xs text-slate-500 mt-0.5">Manage vouchers</p>
                        </a>

                        <a href="{{ route('merchant.reimbursements.index') }}" class="group bg-white hover:border-orange-300 rounded-2xl p-4 border border-slate-200 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
                            <span class="size-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-3">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                            </span>
                            <p class="font-semibold text-slate-900">My Invoices</p>
                            <p class="text-xs text-slate-500 mt-0.5">View and download invoices</p>
                        </a>
                    </div>
                </section>

                <div class="mt-6 grid grid-cols-1 lg:grid-cols-5 gap-6">
                    <section class="lg:col-span-3">
                        <div class="flex items-center justify-between gap-3 mb-3">
                            <h3 class="text-sm font-semibold text-slate-800">My Vouchers ({{ $recentVouchers->count() }})</h3>
                            @if($currentMerchant->is_active)
                                <a href="{{ route('merchant.vouchers.index') }}" class="text-sm font-semibold text-orange-600 hover:text-orange-700">
                                    View all
                                </a>
                            @else
                                <span class="text-sm font-semibold text-slate-400">View all</span>
                            @endif
                        </div>

                        @if($recentVouchers->count() > 0)
                            <div class="space-y-3">
                                @foreach($recentVouchers as $voucher)
                                    @php
                                        $statusLabel = $voucher->getDisplayStatusLabel();
                                        $statusCategory = $voucher->getDisplayStatusCategory();
                                        $statusClass = match ($statusCategory) {
                                            'active' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                            'pending_approval' => 'bg-amber-50 text-amber-800 border-amber-200',
                                            'not_yet_valid' => 'bg-sky-50 text-sky-700 border-sky-200',
                                            default => 'bg-slate-100 text-slate-600 border-slate-200',
                                        };
                                        if (($voucher->discount_type ?? null) === 'percentage') {
                                            $valueText = rtrim(rtrim((string) ($voucher->discount_value ?? 0), '0'), '.').'% off';
                                        } elseif (($voucher->discount_type ?? null) === 'item') {
                                            $valueText = 'Free Item';
                                        } else {
                                            $valueText = '$'.number_format((float) ($voucher->discount_value ?? 0), 2).' off';
                                        }
                                    @endphp
                                    <a
                                        href="{{ $currentMerchant->is_active ? route('merchant.vouchers.profile', $voucher->voucher_code) : '#' }}"
                                        @class([
                                            'flex gap-3 bg-white rounded-2xl border border-slate-200 p-3 shadow-sm transition-all duration-200',
                                            'hover:-translate-y-0.5 hover:shadow-md hover:border-orange-200' => $currentMerchant->is_active,
                                            'opacity-80 pointer-events-none' => ! $currentMerchant->is_active,
                                        ])
                                    >
                                        <div class="size-16 rounded-xl overflow-hidden shrink-0 bg-orange-500 flex items-center justify-center">
                                            @if($voucher->image_url)
                                                <img src="{{ $voucher->image_url }}" alt="" class="size-full object-cover">
                                            @else
                                                <span class="text-white text-lg font-bold">%</span>
                                            @endif
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-start justify-between gap-2">
                                                <p class="font-semibold text-slate-900 truncate">{{ $voucher->name }}</p>
                                                <span class="shrink-0 rounded-full border px-2 py-0.5 text-[11px] font-semibold {{ $statusClass }}">
                                                    {{ $statusLabel }}
                                                </span>
                                            </div>
                                            <p class="text-sm text-slate-600 mt-0.5">{{ $valueText }}</p>
                                            <div class="mt-1.5 flex items-center gap-3 text-xs text-slate-500">
                                                <span>{{ number_format((int) $voucher->usage_count) }} redeemed</span>
                                                @if($voucher->valid_until)
                                                    <span>Until {{ $voucher->valid_until->format('d M Y') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <div class="bg-white rounded-2xl border border-dashed border-slate-300 px-6 py-12 text-center">
                                <div class="mx-auto size-12 rounded-2xl bg-orange-50 text-orange-500 flex items-center justify-center mb-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z" />
                                    </svg>
                                </div>
                                <p class="font-semibold text-slate-800">No listings yet</p>
                                <p class="text-sm text-slate-500 mt-1">Create your first offer to appear in the marketplace.</p>
                                @if($currentMerchant->is_active)
                                    <a href="{{ route('merchant.vouchers.create') }}" class="inline-flex mt-4 items-center justify-center rounded-full bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold px-4 py-2.5 transition-colors">
                                        Create first offer
                                    </a>
                                @endif
                            </div>
                        @endif
                    </section>

                    <section class="lg:col-span-2">
                        <div class="flex items-center justify-between gap-3 mb-3">
                            <h3 class="text-sm font-semibold text-slate-800">Recent Activity</h3>
                            <a href="{{ route('merchant.redemptions.index') }}" class="text-sm font-semibold text-orange-600 hover:text-orange-700">
                                View All
                            </a>
                        </div>

                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                            @if($recentRedemptions->count() > 0)
                                <ul class="divide-y divide-slate-100">
                                    @foreach($recentRedemptions as $redemption)
                                        <li class="px-4 py-3.5">
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="min-w-0">
                                                    <p class="font-semibold text-slate-900 truncate">{{ $redemption->member_name }}</p>
                                                    <p class="text-sm text-slate-600 truncate mt-0.5">{{ $redemption->voucher_name }}</p>
                                                    <p class="text-[11px] text-slate-400 font-mono mt-1">{{ $redemption->voucher_code }}</p>
                                                </div>
                                                <div class="text-right shrink-0">
                                                    {{-- <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide {{ $redemption->source === 'admin' ? 'bg-teal-50 text-teal-700' : 'bg-orange-50 text-orange-700' }}">
                                                        {{ $redemption->source === 'admin' ? 'Admin' : 'Store' }}
                                                    </span> --}}
                                                    <p class="text-xs text-slate-500 mt-1.5">{{ \Carbon\Carbon::parse($redemption->redeemed_at)->diffForHumans() }}</p>
                                                </div>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <div class="px-6 py-12 text-center">
                                    <p class="font-semibold text-slate-800">No redemptions yet</p>
                                    <p class="text-sm text-slate-500 mt-1">When members redeem at your store, activity shows up here.</p>
                                    <button type="button" onclick="window.dispatchEvent(new CustomEvent('openQrScanner'))" class="inline-flex mt-4 items-center justify-center rounded-full border border-slate-300 hover:border-orange-400 hover:text-orange-600 text-slate-700 text-sm font-semibold px-4 py-2.5 transition-colors">
                                        Scan a member QR
                                    </button>
                                </div>
                            @endif
                        </div>
                    </section>
                </div>
            @else
                <section class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 text-center">
                    <div class="mx-auto size-14 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-7">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72" />
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-slate-900">No store assigned yet</h2>
                    <p class="mt-2 text-sm text-slate-600 max-w-md mx-auto">
                        You can still use Merchant Portal. Ask an administrator to link a merchant account so you can manage vouchers and redemptions.
                    </p>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
