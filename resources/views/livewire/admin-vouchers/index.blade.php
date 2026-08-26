<div>
    <x-slot name="header">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-between items-center">
                <h2 class="font-semibold md:text-xl text-2xl text-gray-800 leading-tight">
                    {{ __('Admin Vouchers Management') }}
                </h2>
                <a href="{{ route('admin.admin-vouchers.create') }}" class="md:block hidden text-orange-500 font-medium hover:text-orange-700 hover:scale-105 transition-all duration-300">
                    <span class="flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Create New Voucher
                    </span>
                </a>
                <a href="{{ route('admin.admin-vouchers.create') }}" class="md:hidden block bg-orange-500 hover:bg-orange-600 hover:scale-105 text-white p-2 rounded-full transition-all duration-200">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
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
                            placeholder="Search admin vouchers..." 
                            class="w-full pl-10 pr-4 py-2.5 border text-gray-800 border-gray-300 rounded-full focus:ring-0 focus:outline-none focus:ring-orange-500 focus:border-orange-500"
                        >
                    </div>
                    <div class="lg:w-48">
                        <select 
                            wire:model.live="statusFilter" 
                            class="w-full px-4 py-2.5 border text-gray-800 border-gray-300 rounded-full focus:ring-0 focus:outline-none focus:ring-orange-500 focus:border-orange-500"
                        >
                            <option value="all">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="full">Full</option>
                            <option value="expired">Expired</option>
                        </select>
                    </div>
                </div>
            </div>

            <p class="text-sm text-gray-600 mb-2">
                Note: For voucher usage section, <strong>C</strong> = Total Claimed, <strong>R</strong> = Total Redeemed, <strong>T</strong> = Total Quantity.
            </p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:px-0 px-4">
                @forelse($adminVouchers as $adminVoucher)
                    <x-admin-voucher.card
                        :voucher="$adminVoucher"
                        wire:key="admin-voucher-card-{{ $adminVoucher->id }}"
                    />
                @empty
                    <div class="col-span-full text-center text-gray-300 text-lg py-12 border-dashed border-2 border-gray-200 rounded-lg p-4 bg-white">
                        No admin vouchers found.
                    </div>
                @endforelse
            </div>

            <!-- Pagination -->
            <div class="mt-6 md:px-0 px-4">
                {{ $adminVouchers->links() }}
            </div>
        </div>
    </div>
</div>
