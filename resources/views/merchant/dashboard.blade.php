<x-app-layout>
    <div class="max-w-xl mx-auto sm:px-6 lg:px-8 shrink-0 flex items-center justify-between px-4 pt-4">
        <a href="{{ route('dashboard') }}">
            <img src="{{ asset('hv-logo.png') }}" alt="hope village Logo" class="w-16">
        </a>
        {{-- add a logout button --}}
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
            {{-- @livewire('merchant.merchant-switcher') --}}
            
            @php
                $currentMerchant = auth()->user()->currentMerchant();
            @endphp
            
            <div class="mb-6">
                <div class="flex gap-4 lg:flex-row flex-col justify-between ">
                    <div class="flex flex-col items-start gap-3">
                        <h1 class="leading-normal">
                            <span class="text-gray-600 text-lg font-normal">Welcome, </span> 
                            <span class="text-gray-600 text-lg font-bold">
                                {{ auth()->user()->name }}
                            </span>
                        </h1>
                        @if($currentMerchant)
                            <div class="border border-orange-400/60 p-4 rounded-lg bg-orange-400/10 w-full lg:w-auto flex flex-col gap-1">
                                <span class="text-gray-600 text-sm font-normal">Store Name:</span>
                                <p class="text-gray-700 flex items-center gap-2 font-normal text-lg">
                                    <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72M6.75 18h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z" />
                                    </svg>
                                    {{ $currentMerchant->name }}
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            @if($currentMerchant)
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6 mt-10">
                    <!-- Active Vouchers Card -->
                    <div class="bg-orange-500/40 p-6 rounded-lg border border-orange-400 shadow-md">
                        <h3 class="text-md font-semibold text-orange-800 mb-2">Active Vouchers</h3>
                        <p class="text-3xl font-bold text-orange-800">{{ $currentMerchant->vouchers()->where('is_active', true)->count() }}</p>
                        <p class="text-xs text-orange-800 mt-2">Live running vouchers</p>
                    </div>

                    <!-- Total Vouchers Card -->
                    <div class="bg-orange-500/40 p-6 rounded-lg border border-orange-400 shadow-md">
                        <h3 class="text-md font-semibold text-orange-800 mb-2">All Vouchers</h3>
                        <p class="text-3xl font-bold text-orange-800">{{ $currentMerchant->vouchers()->count() }}</p>
                        <p class="text-xs text-orange-800 mt-2">All vouchers created</p>
                    </div>

                    <!-- Total Vouchers Card -->
                    <div class="bg-orange-500/40 p-6 rounded-lg border border-orange-400 shadow-md">
                        <h3 class="text-md font-semibold text-orange-800 mb-2">Admin Vouchers</h3>
                        <p class="text-3xl font-bold text-orange-800">{{ $currentMerchant->vouchers()->count() }}</p>
                        <p class="text-xs text-orange-800 mt-2">Vouchers From Admin</p>
                    </div>

                    <!-- Merchant Info Card -->
                    <div class="bg-orange-500/40 p-6 rounded-lg border border-orange-400 shadow-md">
                        <h3 class="text-md font-semibold text-orange-800 mb-2">Merchant Status</h3>
                        <p class="text-xs text-orange-800">
                            <span class="text-lg leading-5 font-semibold {{ $currentMerchant->is_active ? 'text-green-600' : 'text-red-600' }}">
                                {{ $currentMerchant->is_active ? 'Approved' : 'Pending Approval' }}
                            </span>
                        </p>
                    </div>
                </div>
                <div class="flex justify-between items-center mt-10 mb-3">
                    <h2 class="text-lg font-semibold text-orange-800">Recent Vouchers</h2>
                    @if($currentMerchant->is_active)
                        <a href="{{ route('merchant.vouchers.index') }}" class="text-orange-800 hover:text-orange-600 font-semibold text-sm">
                            Manage →
                        </a>
                    @else
                        <span class="bg-gray-400 cursor-not-allowed text-white font-semibold py-2 px-4 rounded-lg" title="Your merchant account is pending approval">
                            Manage →
                        </span>
                    @endif
                </div>

                @php
                    $recentVouchers = $currentMerchant->vouchers()->latest()->take(5)->get();
                @endphp

                @if($recentVouchers->count() > 0)
                    <div class="space-y-3">
                        @foreach($recentVouchers as $voucher)
                            @php
                                $isValid = $voucher->is_active && $voucher->isValid();
                            @endphp
                            <x-merchant.voucher-ticket
                                :voucher="$voucher"
                                type="merchant"
                                :merchant-label="$currentMerchant->name"
                                :status-label="$isValid ? 'Active' : 'Inactive'"
                                :status-class="$isValid ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                            >
                                <x-slot:actions>
                                    @if($currentMerchant->is_active)
                                        <a href="{{ route('merchant.vouchers.edit', $voucher->voucher_code) }}" class="py-1 px-2 rounded-full border border-orange-500 text-orange-600 text-xs font-semibold transition-colors shadow-sm">
                                            Edit
                                        </a>
                                    @else
                                        <span class="py-2 px-3 rounded-full border border-gray-300 text-gray-400 text-xs font-semibold cursor-not-allowed" title="Your merchant account is pending approval">
                                            Locked
                                        </span>
                                    @endif
                                </x-slot:actions>
                            </x-merchant.voucher-ticket>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <p class="text-gray-500 mb-4">No vouchers yet</p>
                        @if($currentMerchant->is_active)
                            <a href="{{ route('merchant.vouchers.create') }}" class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg">
                                Create Your First Voucher
                            </a>
                        @else
                            <div class="inline-block">
                                <span class="bg-gray-400 cursor-not-allowed text-white font-semibold py-2 px-4 rounded-lg" title="Your merchant account is pending approval. You cannot create vouchers until your account is approved.">
                                    Create Your First Voucher
                                </span>
                                <p class="text-sm text-yellow-600 mt-8">Your merchant account is pending approval</p>
                            </div>
                        @endif
                    </div>
                @endif
            @else
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
                    <p class="text-yellow-800">
                        <strong>Notice:</strong> No store is assigned to your account yet. You can still use this dashboard. Contact an administrator to link a merchant store so you can manage vouchers and redemptions.
                    </p>
                </div>
            @endif
        </div>
    </div>

    <br />
    <br />
    <br />
    <br />
</x-app-layout>
