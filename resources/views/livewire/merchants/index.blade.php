<div>
    @can('merchant.view')
        <x-slot name="header">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
                <div class="flex justify-between items-center">
                    <h2 class="font-semibold md:text-xl text-2xl text-gray-800 leading-tight">
                        {{ __('Merchants Management') }}
                    </h2>
                    @if ($activeTab === 'merchants')
                        <a href="{{ route('admin.merchants.create') }}" class="md:block hidden text-orange-500 font-medium hover:text-orange-600 hover:scale-105 transition-all duration-300 py-2 px-4">
                            <span class="flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                Add New Merchant
                            </span>
                        </a>
                        <a href="{{ route('admin.merchants.create') }}" class="md:hidden block bg-orange-500 hover:bg-orange-600 hover:scale-105 text-white p-2 rounded-full transition-all duration-200">
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
                            wire:click="$set('activeTab', 'merchants')"
                            class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'merchants' ? 'border-orange-500 text-orange-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                        >
                            Merchants List
                        </button>
                        <button
                            type="button"
                            wire:click="$set('activeTab', 'merchant-ledger')"
                            class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'merchant-ledger' ? 'border-orange-500 text-orange-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                        >
                            Admin Voucher Ledger
                        </button>
                    </nav>
                </div>
            </div>
        </div>

        <div class="py-8">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
                @if ($activeTab === 'merchant-ledger')
                    <livewire:merchants.admin-voucher-ledger />
                @else
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
                        class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" 
                        role="alert"
                    >
                        <span class="block sm:inline">{{ session('message') }}</span>
                    </div>
                @endif

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
                                placeholder="Search by name, code, email, or phone..."
                                class="w-full pl-10 pr-4 py-2.5 border text-gray-800 border-gray-300 rounded-full focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                            >
                        </div>
                        <div class="flex items-center gap-3">
                            <select
                                wire:model.live="statusFilter"
                                class="flex-1 lg:w-52 px-4 py-2.5 border text-gray-800 border-gray-300 rounded-full focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                            >
                                <option value="all">All Status</option>
                                <option value="active">Active</option>
                                <option value="pending">Pending Approval @if($pendingCount > 0)({{ $pendingCount }})@endif</option>
                                <option value="inactive">Inactive</option>
                            </select>
                            <div class="flex items-center gap-3">
                                <div class="inline-flex items-center rounded-xl border border-gray-300 bg-gray-50" role="group" aria-label="View mode">
                                    <button
                                        type="button"
                                        wire:click="setViewMode('card')"
                                        class="inline-flex items-center justify-center gap-1.5 p-3 rounded-xl text-xs font-medium transition-all duration-200 {{ $viewMode === 'card' ? 'bg-orange-600 text-white shadow-sm' : 'text-gray-500 hover:text-gray-800 hover:bg-white' }}"
                                        title="Card view"
                                        aria-pressed="{{ $viewMode === 'card' ? 'true' : 'false' }}"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 8.25 20.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                                        </svg>
                                        {{-- <span class="hidden sm:inline">Cards</span> --}}
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="setViewMode('list')"
                                        class="inline-flex items-center justify-center gap-1.5 p-3 rounded-xl text-xs font-medium transition-all duration-200 {{ $viewMode === 'list' ? 'bg-orange-600 text-white shadow-sm' : 'text-gray-500 hover:text-gray-800 hover:bg-white' }}"
                                        title="Table view"
                                        aria-pressed="{{ $viewMode === 'list' ? 'true' : 'false' }}"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                                        </svg>
                                        {{-- <span class="hidden sm:inline">Table</span> --}}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @if ($viewMode === 'list')
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl md:mx-0 mx-4 border border-gray-200">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Merchant</th>
                                        <th scope="col" class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                        <th scope="col" class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Contact</th>
                                        <th scope="col" class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Vouchers</th>
                                        <th scope="col" class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-100">
                                    @forelse($merchants as $merchant)
                                        <x-merchant.table-row :merchant="$merchant" wire:key="merchant-row-{{ $merchant->id }}" />
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-5 py-16 text-center">
                                                <div class="flex flex-col items-center gap-2 text-gray-400">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-10">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72M6.75 18h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z" />
                                                    </svg>
                                                    <p class="text-sm font-medium text-gray-500">No merchants found</p>
                                                    <p class="text-xs text-gray-400">Try a different search or status filter.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-5 md:px-0 px-4">
                        @forelse($merchants as $merchant)
                            <x-merchant.card :merchant="$merchant" wire:key="merchant-card-{{ $merchant->id }}" />
                        @empty
                            <div class="col-span-full flex flex-col items-center justify-center gap-2 text-center py-16 border-dashed border-2 border-gray-200 rounded-xl bg-white text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-10">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72M6.75 18h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z" />
                                </svg>
                                <p class="text-sm font-medium text-gray-500">No merchants found</p>
                                <p class="text-xs text-gray-400">Try a different search or status filter.</p>
                            </div>
                        @endforelse
                    </div>
                @endif
                <!-- Pagination -->
                @if($merchants->hasPages())
                    <div class="mt-6 md:px-0 px-4">
                        {{ $merchants->links() }}
                    </div>
                @endif
                @endif
            </div>
        </div>
    @else
        @php abort(403, 'Unauthorized.'); @endphp
    @endcan
</div>
