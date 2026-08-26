<x-app-layout>
    <x-slot name="header">
        @livewire('member.points-header')
    </x-slot>

    <div class="max-w-md mx-auto">
        <div
            class="max-w-7xl mx-auto"
            x-cloak
            x-data="{
                tab: 'available',
                isLoading: false,
                syncUrl() {
                    const params = new URLSearchParams(window.location.search);
                    if (this.tab === 'available') {
                        params.set('tab', 'available');
                    } else {
                        params.set('tab', 'my-vouchers');
                    }
                    const newUrl = params.toString() ? `${window.location.pathname}?${params.toString()}` : window.location.pathname;
                    window.history.replaceState({}, '', newUrl);
                },
                setTab(next) {
                    this.tab = next;
                    this.syncUrl();
                    // Show loading animation when switching tabs
                    this.isLoading = true;
                    setTimeout(() => {
                        this.isLoading = false;
                    }, 300);
                },
                init() {
                    const tabParam = new URLSearchParams(window.location.search).get('tab');
                    if (tabParam === 'my-vouchers') {
                        this.tab = 'my-vouchers';
                    } else {
                        this.tab = 'available';
                    }
                    this.syncUrl();
                    this.isLoading = true;
                    setTimeout(() => {
                        this.isLoading = false;
                    }, 300);
                },
            }"
            x-init="init()"
        >
            <!-- Content Section -->
            <div class="px-4 sm:px-6 lg:px-8 py-6">
                <!-- Tab Navigation -->
                <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-hide mb-8">
                    <button
                        type="button"
                        @click="setTab('available')"
                        :class="tab === 'available' ? 'bg-orange-500 shadow-md text-white' : 'border border-orange-500 text-orange-500 hover:bg-orange-500 hover:text-white'"
                        class="px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-all duration-200 flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h7zm-7 4a2 2 0 100 4h7a2 2 0 100-4M7 21h10a2 2 0 002-2V9a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        <span>Available</span>
                    </button>
                    <button
                        type="button"
                        @click="setTab('my-vouchers')"
                        :class="tab === 'my-vouchers' ? 'bg-orange-500 shadow-md text-white' : 'border border-orange-500 text-orange-500 hover:bg-orange-500 hover:text-white'"
                        class="px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-all duration-200 flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                        </svg>
                        <span>My Vouchers</span>
                    </button>
                </div>
                
                <!-- Loading Animation -->
                <div 
                    x-show="isLoading"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-out duration-300"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-gray-100/60"
                >
                    <div class="text-center">
                        <span class="loading loading-bars loading-xl text-orange-500"></span>
                        <p class="mt-4 text-gray-600 font-medium animate-pulse">Loading vouchers...</p>
                    </div>
                </div>

                <!-- Available Vouchers -->
                <div x-show="tab === 'available' && !isLoading" x-cloak>
                    <div wire:ignore.self>
                        @livewire(\App\Livewire\Member\VouchersV2\Browse::class, key('member-vouchers-v2-browse-page'))
                    </div>
                </div>

                <!-- My Vouchers -->
                <div x-show="tab === 'my-vouchers' && !isLoading" x-cloak>
                    @livewire(\App\Livewire\Member\VouchersV2\MyVouchers::class, key('member-vouchers-v2-my-vouchers-page'))
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

