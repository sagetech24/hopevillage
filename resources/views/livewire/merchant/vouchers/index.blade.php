<div class="pb-16">

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
            <div class="mb-6 flex justify-between items-center">
                <div class="">
                    <h3 class="text-2xl font-nunito font-bold text-gray-900">Manage Vouchers</h3>
                    <p class="text-gray-600 font-nunito text-sm">Manage your vouchers here</p>
                </div>
                @if($merchant?->is_active)
                    <a 
                        title="Add New Voucher"
                        href="{{ route('merchant.vouchers.create') }}" 
                        class="flex items-center gap-2 bg-orange-500 hover:bg-orange-600 text-white font-semibold p-3 rounded-full hover:scale-105 transition-all duration-300"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                    </a>
                @endif
            </div>


            @if($merchant && !$merchant->is_active)
                <div class="mb-4 bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded relative md:mx-0 mx-4" role="alert">
                    <span class="block sm:inline">
                        <strong>Notice:</strong> Your merchant account is pending approval. You cannot create or edit vouchers until your account is approved.
                    </span>
                </div>
            @endif

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
                    class="md:mx-0 mx-4 mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" 
                    role="alert"
                >
                    <span class="block sm:inline">{{ session('message') }}</span>
                </div>
            @endif

            <div x-data="{ activeTab: 'vouchers' }">
                <div class="py-2">
                    <div class="flex items-center overflow-x-auto border-b border-gray-200">
                        <button type="button" @click="activeTab='vouchers'" class="w-1/2 pb-3 text-md tracking-wider whitespace-nowrap transition-colors"
                            :class="activeTab === 'vouchers' ? 'text-red-600 border-b-2 border-red-600 font-semibold' : 'text-gray-600'">
                            My Vouchers ({{ $vouchers->count() }})
                        </button>
                        <button type="button" @click="activeTab='admin-vouchers'" class="w-1/2 pb-3 text-md tracking-wider whitespace-nowrap transition-colors"
                            :class="activeTab === 'admin-vouchers' ? 'text-red-600 border-b-2 border-red-600 font-semibold' : 'text-gray-600'">
                            Admin Vouchers ({{ $adminVouchers->count() }})
                        </button>
                    </div>
                </div>


                <div x-show="activeTab === 'vouchers'" x-cloak>
                    <div class="my-4 md:px-0 px-4">
                        <h3 class="text-xl font-bold text-gray-600">My Vouchers ({{ $vouchers->count() }})</h3>
                        <p class="text-gray-600 font-nunito text-sm">Vouchers created and managed by you</p>
                    </div>
                    @if ($vouchers->count() > 0)
                        <div class="grid grid-cols-1 gap-4 items-stretch">
                            @foreach($vouchers as $voucher)
                                @php
                                    $statusCategory = $voucher->getDisplayStatusCategory();
                                    $wrapperClass = match ($statusCategory) {
                                        'active' => '',
                                        'pending_approval' => 'ring-1 ring-yellow-300/80 rounded-lg',
                                        'not_yet_valid' => 'ring-1 ring-sky-300/80 rounded-lg',
                                        'expired' => 'opacity-90 grayscale rounded-lg',
                                        default => '',
                                    };
                                @endphp
                                <div class="shrink-0 w-full relative {{ $wrapperClass }}">
                                    <livewire:merchant.vouchers.card :voucher-code="$voucher->voucher_code" :key="'voucher-' . $voucher->id" />
                                    @if($statusCategory === 'expired')
                                        <div class="absolute inset-0 bg-black/10 rounded-lg z-10 pointer-events-none" aria-hidden="true"></div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="md:mx-0 mx-4 text-center text-gray-300 text-lg py-12 border-dashed border-2 border-gray-300 rounded-lg p-4 bg-gray-200">
                            <p class="text-gray-500 mb-4">No vouchers found.</p>
                        </div>
                    @endif
                </div>

                <div x-show="activeTab === 'admin-vouchers'" x-cloak>
                    <div class="my-4 md:px-0 px-4">
                        <h3 class="text-xl font-bold text-gray-600">Other Vouchers ({{ $adminVouchers->count() }})</h3>
                        <p class="text-gray-600 font-nunito text-sm">Vouchers created and managed by administrator</p>
                    </div>
                    @if ($adminVouchers->count() > 0)
                        <div class="grid grid-cols-1 gap-4 items-stretch">
                            @foreach($adminVouchers as $adminVoucher)
                                @php
                                    $isExpired = $adminVoucher->valid_until && $adminVoucher->valid_until->isPast();
                                    $isInactive = !$adminVoucher->is_active;
                                    $isDisabled = $isExpired || $isInactive;
                                @endphp
                                <div class="shrink-0 w-full relative {{ $isDisabled ? 'opacity-90 grayscale' : '' }}">
                                    <livewire:merchants.voucher-card
                                        :voucher-code="$adminVoucher->voucher_code"
                                        type="admin"
                                        :open-as-modal="true"
                                        :key="'admin-voucher-' . $adminVoucher->id"
                                    />
                                    @if($isDisabled)
                                        <div class="absolute inset-0 bg-black/10 rounded-lg z-10 pointer-events-none" aria-hidden="true"></div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="md:mx-0 mx-4 text-center text-gray-300 text-lg py-12 border-dashed border-2 border-gray-300 rounded-lg p-4 bg-gray-200">
                            <p class="text-gray-500 mb-4">No vouchers found.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- QR Code Full Screen Modal -->
    <div
        x-data="{ 
            open: false,
            qrCode: '',
            qrImage: '',
            title: '',
            init() {
                this.$watch('open', (value) => {
                    if (value) {
                        document.body.style.overflow = 'hidden';
                    } else {
                        document.body.style.overflow = '';
                    }
                });
                
                window.addEventListener('open-qr-modal', (e) => {
                    this.qrCode = e.detail.qrCode;
                    this.qrImage = e.detail.qrImage;
                    this.title = e.detail.title;
                    this.open = true;
                });
            }
        }"
        x-show="open"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-cloak
        class="fixed inset-0 z-[9999] bg-gray-300/50 flex items-center justify-center p-4"
        @keydown.escape.window="open = false"
        @click.self="open = false"
        style="display: none;"
    >
        <div
            @click.stop
            class="bg-white rounded-lg shadow-2xl max-w-md w-full p-8 relative"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
        >
            <!-- Close button -->
            <button
                @click="open = false"
                class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition"
                aria-label="Close"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <!-- Modal Content -->
            <div class="text-center">
                <h3 class="text-xl font-bold text-gray-900 mb-2" x-text="title"></h3>
                <p class="text-sm text-gray-600 mb-6" x-text="'Code: ' + qrCode"></p>
                
                <div class="flex justify-center mb-6">
                    <img 
                        :src="qrImage" 
                        :alt="'QR Code for ' + qrCode"
                        class="w-80 h-80 border-4 border-gray-200 rounded-lg bg-white p-4 shadow-lg"
                    >
                </div>
                
                <div class="flex items-center justify-center gap-2 text-sm text-gray-600 bg-gray-50 rounded-lg p-3">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                    </svg>
                    <span class="font-mono font-semibold" x-text="qrCode"></span>
                </div>
                
                <button
                    @click="open = false"
                    class="mt-6 px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition font-medium"
                >
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .voucher-scroll-container {
        scrollbar-width: none; /* Firefox */
        -ms-overflow-style: none; /* IE and Edge */
    }
    
    .voucher-scroll-container::-webkit-scrollbar {
        display: none; /* Chrome, Safari, Opera */
    }
</style>
@endpush
