<div>
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-4 gap-3">
        <!-- Stat Card 1: Active Vouchers -->
        <div class="relative overflow-visible">
            @if ($pendingMerchantVouchers > 0)
                <a
                    href="{{ route('admin.vouchers.index', ['statusFilter' => 'pending']) }}"
                    class="absolute -top-2 -right-2 z-10 flex h-7 items-center justify-center shadow-md shadow-yellow-500 cursor-pointer rounded-full bg-orange-500 px-2 text-[13px] font-bold leading-none text-white hover:bg-orange-600 animate-bounce transition-colors"
                    title="{{ __(':count merchant voucher(s) pending approval', ['count' => $pendingMerchantVouchers]) }}"
                >
                    {{ $pendingMerchantVouchers > 99 ? '99+' : $pendingMerchantVouchers }} &nbsp;<span class="text-xs">{{ __('for approval') }}</span>
                </a>
            @endif
            <div class="stat bg-white shadow border border-gray-300">
                <div class="stat-figure text-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-6 h-6 lg:w-10 lg:h-10 stroke-current">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
                    </svg>
                </div>
                <div class="stat-title font-semibold">{{ __('Active Vouchers') }}</div>
                <div class="stat-value text-primary font-semibold lg:text-3xl text-2xl">{{ number_format($activeVouchers) }}</div>
                <div class="flex flex-col">
                    <a href="{{ route('admin.admin-vouchers.index') }}" class="stat-desc hover:text-orange-600 text-gray-500 cursor-pointer hover:-translate-y-0.5 transition-all duration-300 hover:underline">
                        {{ number_format($activeAdminVouchers) }} {{ __('Admin Vouchers') }}
                    </a>
                    <a href="{{ route('admin.vouchers.index') }}" class="stat-desc hover:text-orange-600 text-gray-500 cursor-pointer hover:-translate-y-0.5 transition-all duration-300 hover:underline">
                        {{ number_format($activeMerchantVouchers) }} {{ __('Merchant Vouchers') }}
                    </a>
                </div>
            </div>
        </div>

        <!-- Stat Card 2: Members -->
        <div class="stat bg-white shadow border border-gray-300">
            <div class="stat-figure text-secondary lg:pr-0 pr-6">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-6 h-6 lg:w-10 lg:h-10 stroke-current">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
            </div>
            <div class="stat-title font-semibold">{{ __('Members') }}</div>
            <div class="stat-value text-secondary font-semibold lg:text-3xl text-xl">{{ number_format($totalMembers) }}</div>
            <div class="flex flex-col">
                <a href="{{ route('admin.members.index', ['sort' => 'active_30d']) }}" class="stat-desc hover:text-orange-600 text-gray-500 cursor-pointer hover:-translate-y-0.5 transition-all duration-300 hover:underline">
                    {{ number_format($activeMembers30d) }} {{ __('actives (30d)') }}
                </a>
                <a href="{{ route('admin.members.index', ['sort' => 'active_90d']) }}" class="stat-desc hover:text-orange-600 text-gray-500 cursor-pointer hover:-translate-y-0.5 transition-all duration-300 hover:underline">
                    {{ number_format($activeMembers90d) }} {{ __('actives (90d)') }}
                </a>
            </div>
        </div>

        <!-- Stat Card 3: Total Merchants -->
        <div class="relative overflow-visible">
            @if ($pendingMerchantApplications > 0)
                <a
                    href="{{ route('admin.merchants.index', ['statusFilter' => 'pending']) }}"
                    class="absolute -top-2 -right-2 z-10 flex h-8 w-8 hover:-translate-y-0.5 items-center justify-center shadow-md shadow-sky-500 cursor-pointer rounded-full bg-sky-500 px-2 text-[13px] font-bold leading-none text-white hover:bg-sky-600"
                    title="{{ __(':count merchant application(s) pending approval', ['count' => $pendingMerchantApplications]) }}"
                >
                    {{ $pendingMerchantApplications > 99 ? '99+' : $pendingMerchantApplications }}
                </a>
            @endif
            <div class="stat bg-white shadow border border-gray-300">
                <div class="stat-figure text-info">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-6 h-6 lg:w-10 lg:h-10 stroke-current">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
                <div class="stat-title font-semibold">{{ __('Merchants') }}</div>
                <div class="stat-value text-info lg:text-3xl text-xl">{{ number_format($totalMerchants) }}</div>
                <div class="stat-desc line-clamp-2">{{ __('Total Merchants') }}</div>
            </div>
        </div>
        <!-- Points -->
        <div class="stat bg-white shadow border border-gray-300">
            <div class="stat-figure text-warning">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-6 h-6 lg:w-10 lg:h-10 stroke-current">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div class="stat-title font-semibold">{{ __('Total Points') }}</div>
            <div class="stat-value text-warning lg:text-3xl text-xl">{{ number_format($totalPoints) }}</div>
            <div class="stat-desc">{{ __('Awarded') }}</div>
        </div>
    </div>
</div>
