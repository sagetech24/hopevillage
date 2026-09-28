<div class="pb-16">
    <div class="shrink-0 flex items-center justify-between px-4 pt-4">
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
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <a href="{{ route('merchant.vouchers.index') }}" class="text-gray-600 hover:text-gray-900 mb-4 inline-block">
                ← Back to Vouchers
            </a>
            
            <div class="mt-4 grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- Left Column - Voucher Details -->
                <div class="lg:col-span-3 space-y-6">
                    <!-- Voucher Information Card -->
                    <div class="bg-white overflow-hidden shadow-md sm:rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4 border-b pb-2">
                            <h3 class="text-lg font-semibold text-gray-800">Voucher Information</h3>
                            @php
                                $statusReason = $voucher->getStatusReason();
                                $isExpired = $statusReason === 'Expired';
                                $isFull = $voucher->usage_limit && $voucher->usage_count >= $voucher->usage_limit;
                                
                                if ($isExpired) {
                                    $statusLabel = 'Expired';
                                    $statusClass = 'bg-red-500 text-white';
                                } elseif ($isFull) {
                                    $statusLabel = 'Full';
                                    $statusClass = 'bg-orange-500 text-white';
                                } else {
                                    $statusLabel = 'On Going';
                                    $statusClass = 'bg-green-500 text-white';
                                }
                            @endphp
                            <span class="px-3 uppercase py-1 inline-flex text-lg tracking-widest font-thin rounded-sm {{ $statusClass }}">
                                {{ $statusLabel }}
                            </span>
                        </div>
                        
                        <div class="space-y-4">
                            @if($voucher->image_url)
                                <div class="mb-4">
                                    <img src="{{ $voucher->image_url }}" alt="{{ $voucher->name }}" class="w-full max-w-md h-64 object-cover rounded-lg border border-gray-300">
                                </div>
                            @endif

                            <div>
                                <label class="text-sm font-medium text-gray-500">Name</label>
                                <p class="text-gray-900 font-semibold text-lg">{{ $voucher->name }}</p>
                            </div>
                            
                            @if($voucher->description)
                            <div>
                                <label class="text-sm font-medium text-gray-500">Description</label>
                                <p class="text-gray-900">{{ $voucher->description }}</p>
                            </div>
                            @endif

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Voucher Code</label>
                                    <p class="text-gray-900 font-mono">{{ $voucher->voucher_code }}</p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Status</label>
                                    <p>
                                        @php
                                            $isValid = $voucher->is_active && $voucher->isValid();
                                            $statusReason = $voucher->getStatusReason();
                                            if (!$voucher->is_active) {
                                                $statusText = 'Pending Approval';
                                                $statusClass = 'bg-yellow-100 text-yellow-800 border border-yellow-500';
                                                $statusTitle = 'Voucher is pending administrator approval';
                                            } elseif ($isValid) {
                                                $statusText = 'Active';
                                                $statusClass = 'bg-green-100 text-green-800 border border-green-500';
                                                $statusTitle = 'Voucher is active and valid';
                                            } else {
                                                $statusText = $statusReason ?: 'Inactive';
                                                $statusClass = 'bg-red-100 text-red-800 border border-red-500';
                                                $statusTitle = $statusReason ? 'Reason: ' . $statusReason : '';
                                            }
                                        @endphp
                                        <span class="px-3 py-1 inline-flex text-md leading-5 font-semibold rounded-full {{ $statusClass }}" title="{{ $statusTitle }}">
                                            {{ $statusText }}
                                        </span>
                                        @if($statusReason && !$isValid && $voucher->is_active)
                                            <p class="text-xs text-red-600 mt-1">{{ $statusReason }}</p>
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Discount Type</label>
                                    <p class="text-gray-900 font-semibold text-lg capitalize">
                                        @if($voucher->discount_type === 'percentage')
                                            {{ number_format($voucher->discount_value, 2) }}% Off
                                        @elseif($voucher->discount_type === 'fixed')
                                            ${{ number_format($voucher->discount_value, 2) }} Off
                                        @elseif($voucher->discount_type === 'item')
                                            Free Item
                                        @else
                                            {{ $voucher->discount_type }}
                                        @endif
                                    </p>
                                </div>
                                @if($voucher->min_purchase)
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Minimum Purchase</label>
                                    <p class="text-gray-900 font-semibold text-lg flex items-center gap-1">
                                        <svg class="w-5 h-5 text-teal-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
                                        </svg>
                                        ${{ number_format($voucher->min_purchase, 2) }}
                                    </p>
                                </div>
                                @endif
                            </div>

                            @if($voucher->max_discount)
                            <div>
                                <label class="text-sm font-medium text-gray-500">Maximum Discount</label>
                                <p class="text-gray-900 font-semibold text-lg">${{ number_format($voucher->max_discount, 2) }}</p>
                            </div>
                            @endif

                            @if($voucher->valid_from || $voucher->valid_until)
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @if($voucher->valid_from)
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Valid From</label>
                                    <p class="text-gray-900">{{ $voucher->valid_from->format('d M Y g:i A') }}</p>
                                </div>
                                @endif
                                @if($voucher->valid_until)
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Valid Until</label>
                                    <p class="text-gray-900">{{ $voucher->valid_until->format('d M Y g:i A') }}</p>
                                </div>
                                @endif
                            </div>
                            @endif

                            @if($voucher->usage_limit)
                            <div>
                                <label class="text-sm font-medium text-gray-500">Usage</label>
                                <p class="text-gray-900">{{ $voucher->usage_count }} / {{ $voucher->usage_limit }}</p>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Member Engagement Card -->
                    <div class="bg-white overflow-hidden shadow-md sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Voucher Engagement</h3>
                        
                        <div 
                            x-data="{ 
                                tab: 'claimed',
                                setTab(t) { this.tab = t; }
                            }"
                        >
                            <!-- Tabs -->
                            <div class="mb-4 flex items-center gap-2 border-b">
                                <button
                                    type="button"
                                    @click="setTab('claimed')"
                                    :class="tab === 'claimed' ? 'border-b-2 border-orange-500 text-orange-600 font-semibold' : 'text-gray-600 hover:text-gray-900'"
                                    class="px-4 py-2 text-sm transition"
                                >
                                    Claimed ({{ count($claimedMembers) }})
                                </button>
                                <button
                                    type="button"
                                    @click="setTab('redeemed')"
                                    :class="tab === 'redeemed' ? 'border-b-2 border-orange-500 text-orange-600 font-semibold' : 'text-gray-600 hover:text-gray-900'"
                                    class="px-4 py-2 text-sm transition"
                                >
                                    Redeemed ({{ count($redeemedMembers) }})
                                </button>
                            </div>

                            <!-- Claimed Tab -->
                            <div x-show="tab === 'claimed'" x-cloak>
                                @if(count($claimedMembers) > 0)
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Member</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">FIN</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Claimed At</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                @foreach($claimedMembers as $member)
                                                    <tr class="hover:bg-gray-50">
                                                        <td class="px-6 py-4 whitespace-nowrap">
                                                            <div class="text-sm font-medium text-gray-900">{{ $member['name'] }}</div>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap">
                                                            <div class="text-sm text-gray-500 font-mono">{{ $member['fin'] }}</div>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap">
                                                            <div class="text-sm text-gray-500">{{ $member['email'] }}</div>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap">
                                                            <div class="text-sm text-gray-500">
                                                                {{ $member['claimed_at'] ? \Carbon\Carbon::parse($member['claimed_at'])->format('d M Y g:i A') : 'N/A' }}
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="text-center py-8 text-gray-500">
                                        <p>No members have claimed this voucher yet.</p>
                                    </div>
                                @endif
                            </div>

                            <!-- Redeemed Tab -->
                            <div x-show="tab === 'redeemed'" x-cloak>
                                @if(count($redeemedMembers) > 0)
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Member</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">FIN</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Claimed At</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Redeemed At</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                @foreach($redeemedMembers as $member)
                                                    <tr class="hover:bg-gray-50">
                                                        <td class="px-6 py-4 whitespace-nowrap">
                                                            <div class="text-sm font-medium text-gray-900">{{ $member['name'] }}</div>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap">
                                                            <div class="text-sm text-gray-500 font-mono">{{ $member['fin'] }}</div>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap">
                                                            <div class="text-sm text-gray-500">{{ $member['email'] }}</div>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap">
                                                            <div class="text-sm text-gray-500">
                                                                {{ $member['claimed_at'] ? \Carbon\Carbon::parse($member['claimed_at'])->format('d M Y g:i A') : 'N/A' }}
                                                            </div>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap">
                                                            <div class="text-sm text-gray-500">
                                                                {{ $member['redeemed_at'] ? \Carbon\Carbon::parse($member['redeemed_at'])->format('d M Y g:i A') : 'N/A' }}
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="text-center py-8 text-gray-500">
                                        <p>No members have redeemed this voucher yet.</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Quick Actions -->
                <div class="space-y-6">
                    <!-- Voucher QR Code Card -->
                    <div class="bg-white overflow-hidden shadow-md sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Voucher QR Code</h3>
                        <div 
                            x-data="{
                                qrCodeImage: '{{ $qrCodeImage }}',
                                voucherCode: '{{ $voucher->voucher_code }}',
                                async downloadQR() {
                                    try {
                                        const response = await fetch(this.qrCodeImage);
                                        const blob = await response.blob();
                                        const url = window.URL.createObjectURL(blob);
                                        const a = document.createElement('a');
                                        a.href = url;
                                        a.download = `voucher-qr-${this.voucherCode}.png`;
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
                                            const file = new File([blob], `voucher-qr-${this.voucherCode}.png`, { type: 'image/png' });
                                            await navigator.share({
                                                title: 'Voucher QR Code: {{ $voucher->name }}',
                                                text: `Voucher Code: ${this.voucherCode}`,
                                                files: [file]
                                            });
                                        } else if (navigator.clipboard) {
                                            await navigator.clipboard.writeText(this.voucherCode);
                                            alert('Voucher code copied to clipboard!');
                                        } else {
                                            const textArea = document.createElement('textarea');
                                            textArea.value = this.voucherCode;
                                            document.body.appendChild(textArea);
                                            textArea.select();
                                            document.execCommand('copy');
                                            document.body.removeChild(textArea);
                                            alert('Voucher code copied to clipboard!');
                                        }
                                    } catch (error) {
                                        console.error('Share failed:', error);
                                        try {
                                            await navigator.clipboard.writeText(this.voucherCode);
                                            alert('Voucher code copied to clipboard!');
                                        } catch (e) {
                                            alert('Sharing not available. Voucher Code: ' + this.voucherCode);
                                        }
                                    }
                                }
                            }"
                        >
                            <div class="flex items-center justify-center mb-4">
                                <img :src="qrCodeImage" alt="Voucher QR Code" class="w-full max-w-md h-64 object-contain rounded-lg border border-gray-300" id="qr-code-image">
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

                    <!-- Quick Actions Card -->
                    <div class="bg-white overflow-hidden shadow-md sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Quick Actions</h3>
                        
                        <div class="space-y-3">
                            <a href="{{ route('merchant.vouchers.edit', $voucher->voucher_code) }}" class="flex items-center justify-center gap-2 w-full bg-orange-500 hover:bg-orange-600 hover:-translate-y-0.5 text-white text-center font-semibold py-4 px-4 rounded-lg transition-all duration-200">
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="#ffffff"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg>
                                Edit Voucher
                            </a>
                        </div>
                    </div>

                    <!-- Voucher Details Card -->
                    <div class="bg-white overflow-hidden shadow-md sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Voucher Details</h3>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="text-sm font-medium text-gray-500">Created on</label>
                                <p class="text-gray-900 mt-1">{{ $voucher->created_at->format('d M Y g:i A') }}</p>
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-500">Last Updated on</label>
                                <p class="text-gray-900 mt-1">{{ $voucher->updated_at->format('d M Y g:i A') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
