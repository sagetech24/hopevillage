<div>
    @can('merchant.profile')
        <x-slot name="header">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
                <div class="flex justify-between items-center">
                    <div class="flex items-center gap-4">
                        <h2 class="font-semibold md:text-xl text-2xl text-gray-800 leading-tight">
                            {{ $merchant->name }} - Profile
                        </h2>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('admin.merchants.edit', $merchant->merchant_code) }}" class="text-orange-500 font-medium hover:text-orange-700 hover:scale-105 transition-all duration-300">
                            <span class="flex items-center gap-1">
                                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                </svg>
                                Edit Merchant
                            </span>
                        </a>
                    </div>
                </div>
            </div>
        </x-slot>

        <div class="pb-12 pt-6">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
                <div class="mt-4 grid grid-cols-1 lg:grid-cols-3 gap-6 md:mx-0 mx-4">
                    <!-- Left Column - Merchant Details -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Merchant Information Card -->
                        <div class="bg-white overflow-hidden shadow-md sm:rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Merchant Information</h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Name</label>
                                    <p class="text-gray-900">{{ $merchant->name }}</p>
                                </div>
                                
                                @if($merchant->description)
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Description</label>
                                    <p class="text-gray-900">{{ $merchant->description }}</p>
                                </div>
                                @endif

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-sm font-medium text-gray-500">Status</label>
                                        <p>
                                            <span class="px-3 py-1 inline-flex text-md leading-5 font-semibold rounded-full {{ $merchant->is_active ? 'bg-green-100 text-green-800 border border-green-500' : 'bg-red-100 text-red-800 border border-red-500' }}">
                                                {{ $merchant->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </p>
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium text-gray-500">Merchant Code</label>
                                        <p class="text-gray-900">{{ $merchant->merchant_code }}</p>
                                    </div>
                                </div>
                            </div>
                            <br />
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Contact Information</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Contact Name</label>
                                    <p class="text-gray-900 font-semibold">{{ $merchant->contact_name ? $merchant->contact_name : 'No contact name available' }}</p>
                                </div>
            
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Phone</label>
                                    <p class="text-gray-900 font-semibold">{{ $merchant->phone ? $merchant->phone : 'No phone number available' }}</p>
                                </div>
        
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Email</label>
                                    <p class="text-gray-900 font-semibold">{{ $merchant->email ? $merchant->email : 'No email address available' }}</p>
                                </div>

                                <div class="col-span-2">
                                    <label class="text-sm font-medium text-gray-500">Address</label>
                                    <p class="text-gray-900 font-semibold">
                                        {{ $merchant->address ? $merchant->address : 'No address available' }}
                                        @if($merchant->city || $merchant->province || $merchant->postal_code)
                                            {{ $merchant->city ? $merchant->city : '' }}
                                            {{ $merchant->province ? ', ' . $merchant->province : '' }}
                                            {{ $merchant->postal_code ? ' ' . $merchant->postal_code : '' }}
                                        @endif
                                    </p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Website</label>
                                    <p class="text-gray-900 font-semibold">
                                        @if($merchant->website)
                                            <a href="{{ $merchant->website }}" target="_blank" class="text-orange-500 hover:text-orange-700">
                                                {{ $merchant->website }}
                                            </a>
                                        @else
                                            <span class="text-gray-500 italic text-sm">No website available</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- My Vouchers & Vouchers from the Admin added to my merchant -->
                        <div class="bg-white overflow-hidden shadow-md sm:rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Manage Vouchers</h3>
                            <div 
                                x-data="{ 
                                    tab: 'merchant',
                                    setTab(t) { this.tab = t; }
                                }"
                            >
                                <!-- Tabs -->
                                <div class="mb-4 flex items-center gap-2 border-b">
                                    <button
                                        type="button"
                                        @click="setTab('merchant')"
                                        :class="tab === 'merchant' ? 'border-b-2 border-orange-500 text-orange-600 font-semibold' : 'text-gray-600 hover:text-gray-900'"
                                        class="px-4 py-2 text-sm transition"
                                    >
                                        Merchant Vouchers ({{ $merchant->vouchers->count() }})
                                    </button>
                                    <button
                                        type="button"
                                        @click="setTab('admin')"
                                        :class="tab === 'admin' ? 'border-b-2 border-orange-500 text-orange-600 font-semibold' : 'text-gray-600 hover:text-gray-900'"
                                        class="px-4 py-2 text-sm transition"
                                    >
                                        Admin Issued Vouchers ({{ $merchant->adminVouchers->count() }})
                                    </button>
                                </div>

                                <!-- Merchant Vouchers Tab -->
                                <div x-show="tab === 'merchant'" x-cloak>
                                    @if($merchant->vouchers->count() > 0)
                                        <div class="space-y-3 max-h-96 overflow-y-auto">
                                            @foreach($merchant->vouchers as $voucher)
                                                @livewire('merchants.voucher-card', ['voucherCode' => $voucher->voucher_code, 'type' => 'merchant'], key('merchant-voucher-' . $voucher->id))
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="text-center py-8 text-gray-500">
                                            <p>No merchant vouchers found.</p>
                                        </div>
                                    @endif
                                </div>

                                <!-- Admin Vouchers Tab -->
                                <div x-show="tab === 'admin'" x-cloak>
                                    @if($merchant->adminVouchers->count() > 0)
                                        <div class="space-y-3 max-h-96 overflow-y-auto">
                                            @foreach($merchant->adminVouchers as $adminVoucher)
                                                @livewire('merchants.voucher-card', ['voucherCode' => $adminVoucher->voucher_code, 'type' => 'admin'], key('admin-voucher-' . $adminVoucher->id))
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="text-center py-8 text-gray-500">
                                            <p>No admin vouchers found.</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column - Quick Actions & Recent Vouchers -->
                    <div class="space-y-6">
                        {{-- merchant QR Code --}}
                        <div class="bg-white overflow-hidden shadow-md sm:rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Merchant QR Code</h3>
                            <div 
                                x-data="{
                                    qrCodeImage: '{{ $qrCodeImage }}',
                                    merchantCode: '{{ $merchant->merchant_code }}',
                                    async downloadQR() {
                                        try {
                                            const response = await fetch(this.qrCodeImage);
                                            const blob = await response.blob();
                                            const url = window.URL.createObjectURL(blob);
                                            const a = document.createElement('a');
                                            a.href = url;
                                            a.download = `merchant-qr-${this.merchantCode}.png`;
                                            document.body.appendChild(a);
                                            a.click();
                                            window.URL.revokeObjectURL(url);
                                            document.body.removeChild(a);
                                        } catch (error) {
                                            console.error('Download failed:', error);
                                            alert('Failed to download QR code. Please try again.');
                                        }
                                    },
                                    async shareQR() {
                                        try {
                                            if (navigator.share) {
                                                const response = await fetch(this.qrCodeImage);
                                                const blob = await response.blob();
                                                const file = new File([blob], `merchant-qr-${this.merchantCode}.png`, { type: 'image/png' });
                                                await navigator.share({
                                                    title: 'Merchant QR Code: {{ $merchant->name }}',
                                                    text: `Merchant Code: ${this.merchantCode}`,
                                                    files: [file]
                                                });
                                            } else if (navigator.clipboard) {
                                                await navigator.clipboard.writeText(this.merchantCode);
                                                alert('Merchant code copied to clipboard!');
                                            } else {
                                                // Fallback: copy merchant code to clipboard manually
                                                const textArea = document.createElement('textarea');
                                                textArea.value = this.merchantCode;
                                                document.body.appendChild(textArea);
                                                textArea.select();
                                                document.execCommand('copy');
                                                document.body.removeChild(textArea);
                                                alert('Merchant code copied to clipboard!');
                                            }
                                        } catch (error) {
                                            console.error('Share failed:', error);
                                            // Fallback to copy merchant code
                                            try {
                                                await navigator.clipboard.writeText(this.merchantCode);
                                                alert('Merchant code copied to clipboard!');
                                            } catch (e) {
                                                alert('Sharing not available. Merchant Code: ' + this.merchantCode);
                                            }
                                        }
                                    }
                                }"
                            >
                                <div class="flex items-center justify-center mb-4">
                                    <img :src="qrCodeImage" alt="Merchant QR Code" class="w-full max-w-md h-64 object-contain rounded-lg border border-gray-300" id="qr-code-image">
                                </div>
                                <div class="flex gap-3 justify-center">
                                    <button 
                                        @click="downloadQR()"
                                        class="flex items-center text-xs gap-1 px-3 py-1 bg-transparent hover:bg-gray-200 cursor-pointer text-gray-500 border border-gray-500 font-medium rounded-lg transition-colors duration-200"
                                    >
                                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                        </svg>
                                        Download
                                    </button>
                                    <button 
                                        @click="shareQR()"
                                        class="flex items-center text-xs gap-1 px-3 py-1 bg-green-600 hover:bg-green-700 cursor-pointer text-white border border-green-600 font-medium rounded-lg transition-colors duration-200"
                                    >
                                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 1 0 0 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186 9.566-5.314m-9.566 7.5 9.566 5.314m0 0a2.25 2.25 0 1 0 3.935 2.186 2.25 2.25 0 0 0-3.935-2.186Zm0-12.814a2.25 2.25 0 1 0 3.933-2.185 2.25 2.25 0 0 0-3.933 2.185Z" />
                                        </svg>
                                        Share
                                    </button>
                                </div>
                            </div>
                        </div>
                        {{-- end merchant QR Code --}}
                        <!-- Quick Actions Card -->
                        <div class="bg-white overflow-hidden shadow-md sm:rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Quick Actions</h3>
                            
                            <div class="space-y-3">
                                <a href="{{ route('admin.vouchers.create', ['merchant' => $merchant->id]) }}" class="flex items-center justify-center gap-2 w-full bg-indigo-500 hover:bg-indigo-600 hover:-translate-y-0.5 text-white text-center font-semibold py-4 px-4 rounded-lg transition-all duration-200">
                                    <svg class="size-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="#ffffff"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M12 8V16M16 12H8M12 21C16.9706 21 21 16.9706 21 12C21 7.02944 16.9706 3 12 3C7.02944 3 3 7.02944 3 12C3 16.9706 7.02944 21 12 21Z" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg>
                                    Create New Voucher
                                </a>
                                {{-- <a href="{{ route('admin.vouchers.index', ['merchant' => $merchant->id]) }}" class="flex items-center justify-center gap-2 w-full bg-gray-600 hover:bg-gray-700 hover:-translate-y-0.5 text-white text-center font-semibold py-4 px-4 rounded-lg transition-all duration-200">
                                    <svg class="size-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M21 11.25v8.25a1.5 1.5 0 0 1-1.5 1.5H5.25a1.5 1.5 0 0 1-1.5-1.5v-8.25M12 4.875A2.625 2.625 0 1 0 9.375 7.5H12m0-2.625V7.5m0-2.625A2.625 2.625 0 1 1 14.625 7.5H12m-8.25 3.75h16.5m-16.5 3.75h16.5" stroke="#ffffff" stroke-width="2" stroke-linecap="round"></path> </g></svg>
                                    View All Vouchers
                                </a> --}}
                                <button 
                                    wire:click="$dispatch('open-manage-users-modal')"
                                    class="flex items-center justify-center gap-2 w-full bg-orange-400 hover:bg-orange-500 hover:-translate-y-0.5 text-white text-center font-semibold py-4 px-4 rounded-lg transition-all duration-200"
                                >
                                    <svg class="size-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    @if($merchant->users->count() > 0)
                                        Manage Users ({{ $merchant->users->count() }})
                                    @else
                                        Add User
                                    @endif
                                </button>
                                @livewire('merchants.manage-users', ['merchant_code' => $merchant->merchant_code], key('manage-users-' . $merchant->id))
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        @php abort(403, 'Unauthorized.'); @endphp
    @endcan
</div>
