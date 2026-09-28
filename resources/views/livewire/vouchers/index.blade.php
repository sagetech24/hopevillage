<div>
    @can('voucher.view')
        <x-slot name="header">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
                <div class="flex justify-between items-center">
                    <h2 class="font-semibold md:text-xl text-2xl text-gray-800 leading-tight">
                        {{ __('Vouchers Management') }}
                    </h2>
                    @if($tab === 'merchant')
                        @can('voucher.create')
                            <a href="{{ route('admin.vouchers.create') }}" class="md:block hidden text-orange-500 font-medium hover:text-orange-600 hover:scale-105 transition-all duration-300 py-2 px-4">
                                <span class="flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                    </svg>
                                    Add Voucher
                                </span>
                            </a>
                            <a href="{{ route('admin.vouchers.create') }}" class="md:hidden block bg-orange-500 hover:bg-orange-600 hover:scale-105 text-white p-2 rounded-full transition-all duration-200">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                            </a>
                        @endcan
                    @else
                        <a href="{{ route('admin.admin-vouchers.create') }}" class="md:block hidden text-orange-500 font-medium hover:text-orange-600 hover:scale-105 transition-all duration-300 py-2 px-4">
                            <span class="flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                Add Admin Voucher
                            </span>
                        </a>
                        <a href="{{ route('admin.admin-vouchers.create') }}" class="md:hidden block bg-orange-500 hover:bg-orange-600 hover:scale-105 text-white p-2 rounded-full transition-all duration-200">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                        </a>
                    @endif
                </div>
            </div>
        </x-slot>

        <!-- Tabs -->
        <div class="py-4 md:px-0 px-4">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex gap-6" aria-label="Tabs">
                        <button
                            type="button"
                            wire:click="setTab('merchant')"
                            class="py-4 px-1 border-b-2 font-medium text-sm transition-colors {{ $tab === 'merchant' ? 'border-orange-500 text-orange-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                        >
                            Merchant Vouchers
                        </button>
                        <a
                            href="{{ route('admin.admin-vouchers.index') }}"
                            class="py-4 px-1 border-b-2 font-medium text-sm transition-colors border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300"
                        >
                            Admin Vouchers
                        </a>
                    </nav>
                </div>
            </div>
        </div>

        <div class="pb-12">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
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
                        x-transition:enter="transition ease-out duration-500"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        x-transition:leave="transition ease-out duration-500"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative md:mx-0 mx-4"
                        role="alert"
                    >
                        <span class="block sm:inline">{{ session('message') }}</span>
                    </div>
                @endif

                @php
                    $totalCount = ($statusCounts['pending'] ?? 0)
                        + ($statusCounts['active'] ?? 0)
                        + ($statusCounts['not_yet_valid'] ?? 0)
                        + ($statusCounts['expired'] ?? 0);
                    $displayedGroups = $tab === 'merchant'
                        ? ($groupedVouchers ?? collect())
                        : ($groupedAdminVouchers ?? collect());
                    $displayedCount = collect($displayedGroups)->sum(fn ($group) => $group->count());
                @endphp

                <!-- Summary stats -->
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 md:px-0 px-4 mb-6">
                    <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($totalCount) }}</p>
                    </div>
                    <div class="bg-white rounded-xl border border-green-200 p-4 shadow-sm">
                        <p class="text-xs font-medium text-green-700 uppercase tracking-wide">Active</p>
                        <p class="mt-1 text-2xl font-bold text-green-700">{{ number_format($statusCounts['active'] ?? 0) }}</p>
                    </div>
                    <div class="bg-white rounded-xl border border-amber-200 p-4 shadow-sm">
                        <p class="text-xs font-medium text-amber-700 uppercase tracking-wide">Pending</p>
                        <p class="mt-1 text-2xl font-bold text-amber-700">{{ number_format($statusCounts['pending'] ?? 0) }}</p>
                    </div>
                    <div class="bg-white rounded-xl border border-sky-200 p-4 shadow-sm">
                        <p class="text-xs font-medium text-sky-700 uppercase tracking-wide">Not Yet Valid</p>
                        <p class="mt-1 text-2xl font-bold text-sky-700">{{ number_format($statusCounts['not_yet_valid'] ?? 0) }}</p>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Expired</p>
                        <p class="mt-1 text-2xl font-bold text-gray-600">{{ number_format($statusCounts['expired'] ?? 0) }}</p>
                    </div>
                </div>

                <!-- Search and Filter -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl p-4 md:p-5 md:mx-0 mx-4 mb-6 border border-gray-200">
                    <div class="flex flex-col lg:flex-row lg:items-center gap-3">
                        <div class="relative flex-1">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                            </svg>
                            <input
                                type="text"
                                wire:model.live.debounce.300ms="search"
                                placeholder="Search {{ $tab === 'admin' ? 'admin' : 'merchant' }} vouchers by name, code, or description..."
                                class="w-full pl-10 pr-4 py-2.5 border text-gray-800 border-gray-300 rounded-full focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                            >
                        </div>
                        <div class="flex flex-col sm:flex-row gap-3">
                            <select
                                wire:model.live="statusFilter"
                                class="sm:w-52 px-4 py-2.5 border text-gray-800 border-gray-300 rounded-full focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                            >
                                <option value="all">All Status</option>
                                @if($tab === 'merchant')
                                    <option value="pending">Pending Approval @if(($pendingCount ?? 0) > 0)({{ $pendingCount }})@endif</option>
                                    <option value="active">Active</option>
                                    <option value="not_yet_valid">Not Yet Valid</option>
                                    <option value="expired">Expired</option>
                                @else
                                    <option value="active">Active</option>
                                    <option value="pending">Pending</option>
                                    <option value="not_yet_valid">Not Yet Valid</option>
                                    <option value="expired">Expired</option>
                                @endif
                            </select>
                            @if($tab === 'merchant')
                                <select
                                    wire:model.live="merchantFilter"
                                    class="sm:w-56 px-4 py-2.5 border text-gray-800 border-gray-300 rounded-full focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                >
                                    <option value="">All Merchants</option>
                                    @foreach($merchants as $merchant)
                                        <option value="{{ $merchant->id }}">{{ $merchant->name }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2 items-center border-t border-gray-100 pt-4">
                        <span class="text-sm text-gray-500 font-medium">Sort by:</span>
                        @foreach([
                            'start_date' => 'Start Date',
                            'created_at' => 'Created Date',
                            'name' => 'Name',
                        ] as $field => $label)
                            <button
                                type="button"
                                wire:click="toggleSort('{{ $field }}')"
                                class="px-3 py-1.5 text-sm rounded-full border transition-colors {{ $sortBy === $field ? 'bg-orange-100 border-orange-500 text-orange-700 font-medium' : 'border-gray-300 text-gray-600 hover:bg-gray-50' }}"
                            >
                                {{ $label }}
                                @if($sortBy === $field)
                                    <span class="ml-0.5">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="space-y-10 md:px-0 px-4">
                    @if($tab === 'merchant')
                        @php
                            $statusGroups = [
                                'active' => [
                                    'label' => 'Active',
                                    'description' => 'Approved vouchers currently within their validity period',
                                    'badge' => 'bg-green-100 text-green-800 border-green-200',
                                    'vouchers' => $groupedVouchers['active'] ?? collect(),
                                ],
                                'pending' => [
                                    'label' => 'Pending For Approval',
                                    'description' => 'Awaiting administrator approval and not yet expired',
                                    'badge' => 'bg-amber-100 text-amber-800 border-amber-200',
                                    'vouchers' => $groupedVouchers['pending'] ?? collect(),
                                ],
                                'not_yet_valid' => [
                                    'label' => 'Not Yet Valid',
                                    'description' => 'Approved, but the start date has not been reached',
                                    'badge' => 'bg-sky-100 text-sky-800 border-sky-200',
                                    'vouchers' => $groupedVouchers['not_yet_valid'] ?? collect(),
                                ],
                                'expired' => [
                                    'label' => 'Expired',
                                    'description' => 'Past their validity end date, regardless of approval status',
                                    'badge' => 'bg-gray-100 text-gray-700 border-gray-200',
                                    'vouchers' => $groupedVouchers['expired'] ?? collect(),
                                ],
                            ];
                        @endphp

                        @foreach($statusGroups as $statusKey => $group)
                            @if($group['vouchers']->isNotEmpty())
                                <section class="space-y-4">
                                    <div class="flex flex-wrap items-end justify-between gap-3">
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <h3 class="text-lg font-bold text-gray-900">{{ $group['label'] }}</h3>
                                                <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold {{ $group['badge'] }}">
                                                    {{ $group['vouchers']->count() }}
                                                </span>
                                            </div>
                                            <p class="mt-0.5 text-sm text-gray-500">{{ $group['description'] }}</p>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
                                        @foreach($group['vouchers'] as $voucher)
                                            <x-voucher.merchant-card
                                                :voucher="$voucher"
                                                wire:key="merchant-voucher-{{ $statusKey }}-{{ $voucher->id }}"
                                            />
                                        @endforeach
                                    </div>
                                </section>
                            @endif
                        @endforeach

                        @if($displayedCount === 0)
                            <div class="col-span-full flex flex-col items-center justify-center gap-2 text-center py-16 border-dashed border-2 border-gray-200 rounded-xl bg-white text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-10">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 11.25v8.25a1.5 1.5 0 0 1-1.5 1.5H5.25a1.5 1.5 0 0 1-1.5-1.5v-8.25M12 4.875A2.625 2.625 0 1 0 9.375 7.5H12m0-2.625V7.5m0-2.625A2.625 2.625 0 1 1 14.625 7.5H12m-8.25 3.75h16.5m-16.5 3.75h16.5" />
                                </svg>
                                <p class="text-sm font-medium text-gray-500">No merchant vouchers found</p>
                                <p class="text-xs text-gray-400">Try a different search, status, or merchant filter.</p>
                            </div>
                        @endif
                    @else
                        @php
                            $statusGroups = [
                                'active' => [
                                    'label' => 'Active',
                                    'description' => 'Activated admin vouchers currently within their validity period',
                                    'badge' => 'bg-green-100 text-green-800 border-green-200',
                                    'vouchers' => $groupedAdminVouchers['active'] ?? collect(),
                                ],
                                'pending' => [
                                    'label' => 'Pending',
                                    'description' => 'Not yet activated and not expired',
                                    'badge' => 'bg-amber-100 text-amber-800 border-amber-200',
                                    'vouchers' => $groupedAdminVouchers['pending'] ?? collect(),
                                ],
                                'not_yet_valid' => [
                                    'label' => 'Not Yet Valid',
                                    'description' => 'Activated, but the start date has not been reached',
                                    'badge' => 'bg-sky-100 text-sky-800 border-sky-200',
                                    'vouchers' => $groupedAdminVouchers['not_yet_valid'] ?? collect(),
                                ],
                                'expired' => [
                                    'label' => 'Expired',
                                    'description' => 'Past their validity end date, regardless of activation status',
                                    'badge' => 'bg-gray-100 text-gray-700 border-gray-200',
                                    'vouchers' => $groupedAdminVouchers['expired'] ?? collect(),
                                ],
                            ];
                        @endphp

                        @foreach($statusGroups as $statusKey => $group)
                            @if($group['vouchers']->isNotEmpty())
                                <section class="space-y-4">
                                    <div class="flex flex-wrap items-end justify-between gap-3">
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <h3 class="text-lg font-bold text-gray-900">{{ $group['label'] }}</h3>
                                                <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold {{ $group['badge'] }}">
                                                    {{ $group['vouchers']->count() }}
                                                </span>
                                            </div>
                                            <p class="mt-0.5 text-sm text-gray-500">{{ $group['description'] }}</p>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        @foreach($group['vouchers'] as $adminVoucher)
                                            <x-admin-voucher.card
                                                :voucher="$adminVoucher"
                                                wire:key="admin-voucher-{{ $statusKey }}-{{ $adminVoucher->id }}"
                                            />
                                        @endforeach
                                    </div>
                                </section>
                            @endif
                        @endforeach

                        @if($displayedCount === 0)
                            <div class="col-span-full flex flex-col items-center justify-center gap-2 text-center py-16 border-dashed border-2 border-gray-200 rounded-xl bg-white text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-10">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 11.25v8.25a1.5 1.5 0 0 1-1.5 1.5H5.25a1.5 1.5 0 0 1-1.5-1.5v-8.25M12 4.875A2.625 2.625 0 1 0 9.375 7.5H12m0-2.625V7.5m0-2.625A2.625 2.625 0 1 1 14.625 7.5H12m-8.25 3.75h16.5m-16.5 3.75h16.5" />
                                </svg>
                                <p class="text-sm font-medium text-gray-500">No admin vouchers found</p>
                                <p class="text-xs text-gray-400">Try a different search or status filter.</p>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    @else
        @php abort(403, 'Unauthorized.'); @endphp
    @endcan
</div>
