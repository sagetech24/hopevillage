<div class="pb-16">

    <div class="max-w-xl mx-auto sm:px-6 lg:px-8 shrink-0 flex items-center justify-between px-4 pt-4">
        <a href="{{ route('dashboard') }}">
            <img src="{{ asset('hv-logo.png') }}" alt="hope village Logo" class="w-16">
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="flex items-center hover:text-red-800 text-sm font-semibold bg-orange-500 text-white px-3 py-2 rounded-lg">
                <svg class="size-4" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" version="1.1" fill="#000000">
                    <g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <g style="fill:none;stroke:#ffffff;stroke-width:12px;stroke-linecap:round;stroke-linejoin:round;"> <path d="m 50,10 0,35"></path> <path d="M 26,20 C -3,48 16,90 51,90 79,90 89,67 89,52 89,37 81,26 74,20"></path> </g> </g>
                </svg>
                <span class="ml-1">Logout</span>
            </button>
        </form>
    </div>

    <div class="py-12 lg:px-0 px-4">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6">
                <h3 class="text-2xl font-nunito font-bold text-gray-900">Voucher Redemption</h3>
                <p class="text-gray-600 font-nunito text-sm">View voucher redemption history</p>
            </div>

            <div x-data="{ activeTab: @entangle('activeTab').live }">
                <div class="py-2">
                    <div class="flex items-center overflow-x-auto border-b border-gray-200">
                        <button type="button" @click="activeTab='merchant'" class="w-1/2 pb-3 text-md tracking-wider whitespace-nowrap transition-colors"
                            :class="activeTab === 'merchant' ? 'text-red-600 border-b-2 border-red-600 font-semibold' : 'text-gray-600'">
                            My Vouchers ({{ $merchantCount }})
                        </button>
                        <button type="button" @click="activeTab='admin'" class="w-1/2 pb-3 text-md tracking-wider whitespace-nowrap transition-colors"
                            :class="activeTab === 'admin' ? 'text-red-600 border-b-2 border-red-600 font-semibold' : 'text-gray-600'">
                            Admin Vouchers ({{ $adminCount }})
                        </button>
                    </div>
                </div>

                {{-- Search and Sort --}}
                <div class="mt-4 mb-8 space-y-3">
                    <div class="grid grid-cols-5 gap-2">
                        <div class="relative col-span-3">
                            <input
                                type="text"
                                wire:model.live.debounce.300ms="search"
                                placeholder="Search by member name..."
                                class="w-full pl-8 pr-4 py-3 border border-gray-300 text-gray-700 rounded-full focus:ring-2 focus:ring-orange-500 focus:border-orange-500 text-sm"
                            >
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                            </svg>
                        </div>
                        <div class="flex items-center gap-2 col-span-2">
                            {{-- <label for="sort-select" class="text-sm text-gray-600 whitespace-nowrap">Sort by:</label> --}}
                            <select
                                id="sort-select"
                                wire:model.live="sortOption"
                                class="flex-1 min-w-0 px-3 py-3 border border-gray-300 text-gray-700 rounded-full focus:ring-2 focus:ring-orange-500 focus:border-orange-500 text-sm bg-white"
                            >
                                <option value="">Sort data by</option>
                                <option value="member_name_asc">Member (A-Z)</option>
                                <option value="member_name_desc">Member (Z-A)</option>
                                <option value="voucher_name_asc">Voucher (A-Z)</option>
                                <option value="voucher_name_desc">Voucher (Z-A)</option>
                                <option value="voucher_code_asc">Code (A-Z)</option>
                                <option value="voucher_code_desc">Code (Z-A)</option>
                                <option value="redeemed_at_asc">Date (Oldest first)</option>
                                <option value="redeemed_at_desc">Date (Newest first)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div x-show="activeTab === 'merchant'" x-cloak>
                    <div class="my-4">
                        <h3 class="text-md font-bold text-gray-600">My Voucher Redemptions</h3>
                        <p class="text-gray-600 font-nunito text-sm">Vouchers created by you, redeemed by members</p>
                    </div>
                    @if ($merchantRedemptions->count() > 0)
                        <div class="space-y-3">
                            @foreach($merchantRedemptions as $redemption)
                                <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow">
                                    <div class="flex flex-col gap-2">
                                        <div class="flex justify-between items-start">
                                            <p class="font-semibold text-gray-900">{{ $redemption->member_name }}</p>
                                            <span class="text-xs text-gray-500 font-mono">{{ $redemption->voucher_code }}</span>
                                        </div>
                                        <p class="text-sm text-gray-600">{{ $redemption->voucher_name }}</p>
                                        <p class="text-xs text-gray-500">
                                            Redeemed {{ \Carbon\Carbon::parse($redemption->redeemed_at)->format('M j, Y g:i A') }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="md:mx-0 mx-4 text-center text-gray-300 text-lg py-12 border-dashed border-2 border-gray-300 rounded-lg p-4 bg-gray-200">
                            <p class="text-gray-500 mb-4">No merchant voucher redemptions yet.</p>
                        </div>
                    @endif
                </div>

                <div x-show="activeTab === 'admin'" x-cloak class="relative min-h-[calc(100vh-14rem)]">
                    <div wire:loading wire:target="activeTab" class="absolute inset-0 flex flex-col items-center justify-center text-center text-gray-500">
                        <div class="inline-block animate-spin rounded-full h-10 w-10 border-b-2 border-orange-500"></div>
                        <p class="mt-4 text-sm font-medium">Loading admin voucher redemptions...</p>
                    </div>

                    <div wire:loading.remove wire:target="activeTab">
                        <div class="my-4">
                            <h3 class="text-md font-bold text-gray-600">Admin Voucher Redemptions</h3>
                            <p class="text-gray-600 font-nunito text-sm">Admin vouchers redeemed at your store</p>
                        </div>

                        @if ($adminLoaded && $adminRedemptions->count() > 0)
                            <div class="space-y-3">
                                @foreach($adminRedemptions as $redemption)
                                    <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow">
                                        <div class="flex flex-col gap-2">
                                            <div class="flex justify-between items-start">
                                                <p class="font-semibold text-gray-900">{{ $redemption->member_name }}</p>
                                                <span class="text-xs text-gray-500 font-mono">{{ $redemption->voucher_code }}</span>
                                            </div>
                                            <p class="text-sm text-gray-600">{{ $redemption->voucher_name }}</p>
                                            <p class="text-xs text-gray-500">
                                                Redeemed {{ \Carbon\Carbon::parse($redemption->redeemed_at)->format('M j, Y g:i A') }}
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @if ($adminHasMore)
                                <div class="mt-6 text-center">
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
                                    <p class="mt-2 text-xs text-gray-500">
                                        Showing {{ $adminRedemptions->count() }} of {{ $adminCount }}
                                    </p>
                                </div>
                            @endif
                        @elseif ($adminLoaded)
                            <div class="md:mx-0 mx-4 text-center text-gray-300 text-lg py-12 border-dashed border-2 border-gray-300 rounded-lg p-4 bg-gray-200">
                                <p class="text-gray-500 mb-4">No admin voucher redemptions yet.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
