<div>
    <x-slot name="header">
        <div class="flex md:flex-row flex-col md:gap-0 gap-4 justify-between items-center">
            <div class="flex items-center gap-4">
                <h2 class="font-semibold md:text-xl text-2xl text-gray-800 leading-tight">
                    {{ $voucher->name }} - Profile
                </h2>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <a href="{{ route('admin.admin-vouchers.index') }}" class="text-gray-600 hover:text-gray-900 md:mx-0 mx-4">
                ← Back to Admin Vouchers
            </a>
            
            <div class="mt-4 grid grid-cols-1 lg:grid-cols-4 gap-6 md:mx-0 mx-4">
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
                                    $statusLabel = 'Active';
                                    $statusClass = 'bg-green-500 text-white';
                                }
                            @endphp
                            <span class="px-3 uppercase py-1 inline-flex text-lg tracking-widest rounded-sm {{ $statusClass }}">
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
                            </div>

                            @php
                                $pointsCost = max(0, (int) $voucher->points_cost);
                                $costPerPoint = max(0, (float) $voucher->amount_cost);
                                $costPerVoucher = round($pointsCost * $costPerPoint, 2);
                            @endphp
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Points Cost</label>
                                    <p class="text-gray-900 font-semibold text-lg flex items-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-orange-500">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                                        </svg>
                                        {{ number_format($pointsCost) }} pts
                                    </p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Cost Per Point</label>
                                    <p class="text-gray-900 font-semibold text-lg flex items-center gap-1">
                                        <svg class="w-5 h-5 text-teal-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
                                        </svg>
                                        SGD {{ number_format($costPerPoint, 2) }}
                                    </p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Cost Per Voucher</label>
                                    <p class="text-gray-900 font-semibold text-lg flex items-center gap-1">
                                        <svg class="w-5 h-5 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
                                        </svg>
                                        SGD {{ number_format($costPerVoucher, 2) }}
                                    </p>
                                </div>
                            </div>

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

                            @php
                                $claimedOnlyCount = (int) $claimedMembersTotal;
                                $redeemedCount = (int) $redeemedMembersTotal;
                                $claimedTally = $claimedOnlyCount + $redeemedCount;
                                $totalReleased = $voucher->usage_limit !== null ? (int) $voucher->usage_limit : null;
                            @endphp
                            <div>
                                <label class="text-sm font-medium text-gray-500">Usage</label>
                                <p class="text-gray-900 font-semibold text-lg tracking-wide">
                                    C-{{ number_format($claimedTally) }}
                                    <span class="text-gray-400 font-normal">|</span>
                                    R - {{ number_format($redeemedCount) }}
                                    <span class="text-gray-400 font-normal">|</span>
                                    T = {{ $totalReleased !== null ? number_format($totalReleased) : '∞' }}
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    C = Claimed · R = Redeemed · T = Total Quantity
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Merchant Redemption Summary Card -->
                    @livewire('admin-vouchers.merchant-redemption-summary', ['voucherCode' => $voucher->voucher_code], key('merchant-redemption-summary-' . $voucher->voucher_code))

                    <!-- Member Card -->
                    <div class="bg-white overflow-hidden shadow-md sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Voucher Engagement</h3>
                        
                        <div>
                            <!-- Tabs -->
                            <div class="mb-4 flex items-center gap-2 border-b">
                                <button
                                    type="button"
                                    wire:click="$set('engagementTab', 'claimed')"
                                    class="px-4 py-2 text-sm transition {{ $engagementTab === 'claimed' ? 'border-b-2 border-orange-500 text-orange-600 font-semibold' : 'text-gray-600 hover:text-gray-900' }}"
                                >
                                    Claimed ({{ $claimedMembersTotal }})
                                </button>
                                <button
                                    type="button"
                                    wire:click="$set('engagementTab', 'redeemed')"
                                    class="px-4 py-2 text-sm transition {{ $engagementTab === 'redeemed' ? 'border-b-2 border-orange-500 text-orange-600 font-semibold' : 'text-gray-600 hover:text-gray-900' }}"
                                >
                                    Redeemed ({{ $redeemedMembersTotal }})
                                </button>
                            </div>

                            <!-- Claimed Tab -->
                            @if($engagementTab === 'claimed')
                                <div>
                                    @if($claimedMembersTotal > 0)
                                        <div class="mb-4">
                                            <label for="claimedMemberSearch" class="sr-only">Search claimed members</label>
                                            <div class="relative">
                                                <input
                                                    type="text"
                                                    id="claimedMemberSearch"
                                                    wire:model.live.debounce.300ms="claimedMemberSearch"
                                                    placeholder="Search by name, email, FIN, or QR code..."
                                                    class="w-1/2 px-4 py-2 border border-gray-300 rounded-lg focus:ring-0 focus:outline-none focus:ring-orange-500 focus:border-orange-500"
                                                >
                                                <div
                                                    wire:loading
                                                    wire:target="claimedMemberSearch"
                                                    class="absolute inset-y-0 right-3 flex items-center"
                                                >
                                                    <svg class="h-4 w-4 animate-spin text-orange-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                        </div>

                                        <div wire:loading.class="opacity-40" wire:target="claimedMemberSearch">
                                            @if($claimedMembers->total() > 0)
                                                <div class="overflow-x-auto">
                                                    <table class="min-w-full divide-y divide-gray-200">
                                                        <thead class="bg-gray-50">
                                                            <tr>
                                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Member</th>
                                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Claimed At</th>
                                                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="bg-white divide-y divide-gray-200">
                                                            @foreach($claimedMembers as $member)
                                                                <tr class="hover:bg-gray-50" wire:key="claimed-member-{{ $member['id'] }}">
                                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                                        <div class="text-md font-medium text-gray-900">{{ $member['name'] }}</div>
                                                                        <div class="text-xs text-gray-500">
                                                                            Member Code: <strong>{{ $member['qr_code'] ?? '—' }}</strong>
                                                                        </div>
                                                                    </td>
                                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                                        <div class="text-sm text-gray-500">
                                                                            {{ $member['claimed_at'] ? \Carbon\Carbon::parse($member['claimed_at'])->format('d M Y g:i A') : 'N/A' }}
                                                                        </div>
                                                                    </td>
                                                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                                                        <button
                                                                            type="button"
                                                                            wire:click="openVoidModal({{ $member['id'] }})"
                                                                            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-red-700 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition"
                                                                        >
                                                                            Void
                                                                        </button>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                                @if($claimedMembers->hasPages())
                                                    <div class="mt-4">
                                                        {{ $claimedMembers->links() }}
                                                    </div>
                                                @endif
                                            @else
                                                <div class="text-center py-8 text-gray-500">
                                                    <p>No claimed members match “{{ trim($claimedMemberSearch) }}”.</p>
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        <div class="text-center py-8 text-gray-500">
                                            <p>No members have claimed this voucher yet.</p>
                                        </div>
                                    @endif
                                </div>
                            @endif

                            <!-- Redeemed Tab -->
                            @if($engagementTab === 'redeemed')
                                <div>
                                    @if($redeemedMembersTotal > 0)
                                        <div class="mb-4">
                                            <label for="redeemedMemberSearch" class="sr-only">Search redeemed members</label>
                                            <div class="relative">
                                                <input
                                                    type="search"
                                                    id="redeemedMemberSearch"
                                                    wire:model.live.debounce.300ms="redeemedMemberSearch"
                                                    placeholder="Search by name, email, FIN, or QR code..."
                                                    class="w-1/2 px-4 py-2 border border-gray-300 rounded-lg focus:ring-0 focus:outline-none focus:ring-orange-500 focus:border-orange-500"
                                                >
                                                <div
                                                    wire:loading
                                                    wire:target="redeemedMemberSearch"
                                                    class="absolute inset-y-0 right-3 flex items-center"
                                                >
                                                    <svg class="h-4 w-4 animate-spin text-orange-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                        </div>

                                        <div wire:loading.class="opacity-40" wire:target="redeemedMemberSearch">
                                            @if($redeemedMembers->total() > 0)
                                                <div class="overflow-x-auto">
                                                    <table class="min-w-full divide-y divide-gray-200">
                                                        <thead class="bg-gray-50">
                                                            <tr>
                                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Member</th>
                                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Timestamps</th>
                                                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Merchant</th>
                                                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="bg-white divide-y divide-gray-200">
                                                            @foreach($redeemedMembers as $member)
                                                                <tr class="hover:bg-gray-50" wire:key="redeemed-member-{{ $member['id'] }}">
                                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                                        <div class="text-md font-medium text-gray-900">{{ $member['name'] }}</div>
                                                                        <div class="text-xs text-gray-500">
                                                                            Member Code: <strong>{{ $member['qr_code'] ?? '—' }}</strong>
                                                                        </div>
                                                                    </td>
                                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                                        <div class="text-xs text-gray-500 mb-1">
                                                                            Claimed At: {{ $member['claimed_at'] ? \Carbon\Carbon::parse($member['claimed_at'])->format('d M Y g:i A') : 'N/A' }}
                                                                        </div>
                                                                        <div class="text-xs text-gray-500">
                                                                            Redeemed At: {{ $member['redeemed_at'] ? \Carbon\Carbon::parse($member['redeemed_at'])->format('d M Y g:i A') : 'N/A' }}
                                                                        </div>
                                                                    </td>
                                                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                                                        <div class="text-sm text-gray-500">
                                                                            @if($member['merchant'])
                                                                                <span class="inline-flex items-center gap-1 px-2 py-1 bg-indigo-100 text-indigo-800 rounded text-xs">
                                                                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72M6.75 18h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z" />
                                                                                    </svg>
                                                                                    {{ $member['merchant']->name }}
                                                                                </span>
                                                                            @else
                                                                                <span class="text-gray-400">N/A</span>
                                                                            @endif
                                                                        </div>
                                                                    </td>
                                                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                                                        <button
                                                                            type="button"
                                                                            wire:click="openVoidModal({{ $member['id'] }})"
                                                                            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-red-700 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition"
                                                                        >
                                                                            Void
                                                                        </button>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                                @if($redeemedMembers->hasPages())
                                                    <div class="mt-4">
                                                        {{ $redeemedMembers->links() }}
                                                    </div>
                                                @endif
                                            @else
                                                <div class="text-center py-8 text-gray-500">
                                                    <p>No redeemed members match “{{ trim($redeemedMemberSearch) }}”.</p>
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        <div class="text-center py-8 text-gray-500">
                                            <p>No members have redeemed this voucher yet.</p>
                                        </div>
                                    @endif
                                </div>
                            @endif
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
                                            // Fallback: copy voucher code to clipboard manually
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
                                        // Fallback to copy voucher code
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
                            <a href="{{ route('admin.admin-vouchers.edit', $voucher->voucher_code) }}" class="flex items-center justify-center gap-2 w-full bg-indigo-500 hover:bg-indigo-600 hover:-translate-y-0.5 text-white text-center font-semibold py-4 px-4 rounded-lg transition-all duration-200">
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="#ffffff"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg>
                                Edit Voucher
                            </a>
                            <button
                                type="button"
                                wire:click="openAwardModal"
                                class="flex items-center justify-center gap-2 w-full bg-orange-500 hover:bg-orange-600 hover:-translate-y-0.5 text-white text-center font-semibold py-4 px-4 rounded-lg transition-all duration-200"
                            >
                                <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 0 0 7.5 9.75c0 .896.393 1.7 1.016 2.25M5.25 4.236c.982.143 1.954.317 2.916.52M5.25 4.236V4.5c0 2.108 4.284 3.818 9.75 3.818S24.75 6.608 24.75 4.5V4.236m0 0A6.003 6.003 0 0 0 18.75 9.75c0 .896-.393 1.7-1.016 2.25" />
                                </svg>
                                Award to Member
                            </button>
                        </div>
                    </div>

                    <!-- Redeemable Merchants Card -->
                    <div class="bg-white overflow-hidden shadow-md sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Redeemable Merchants</h3>
                        
                        @if($voucher->merchants->isNotEmpty())
                            <div class="space-y-2">
                                @foreach($voucher->merchants as $merchant)
                                    <div class="flex items-start gap-2 p-3 bg-gray-50 rounded-lg border border-gray-200">
                                        <svg class="w-5 h-5 text-indigo-600 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72M6.75 18h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z" />
                                        </svg>
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-900">{{ $merchant->name }}</p>
                                            @if($merchant->email)
                                                <p class="text-xs text-gray-500 mt-0.5">{{ $merchant->email }}</p>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-4 text-gray-500">
                                <p class="text-sm">No merchants assigned</p>
                            </div>
                        @endif
                    </div>

                    <!-- Voucher Details Card -->
                    <div class="bg-white overflow-hidden shadow-md sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Voucher Details</h3>
                        
                        <div class="space-y-4">
                            @if($voucher->createdBy)
                            <div>
                                <label class="text-sm font-medium text-gray-500">Created By</label>
                                <p class="text-gray-900 mt-1">{{ $voucher->createdBy->name }}</p>
                            </div>
                            @endif

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

    <x-dialog-modal wire:model="showAwardModal" maxWidth="2xl">
        <x-slot name="title">
            Award Voucher to Members
        </x-slot>

        <x-slot name="content">
            <p class="text-sm text-gray-600 mb-4">
                Assign <span class="font-semibold text-gray-900">{{ $voucher->name }}</span> ({{ $voucher->voucher_code }}) to one or more members. Selected members keep their checks while you search again. Vouchers appear in their Claimed tab without deducting points.
            </p>

            @php
                $claimedForAward = (int) $voucher->usage_count;
                $usageLimit = $voucher->usage_limit;
                $remainingForAward = $remainingForAward ?? ($usageLimit !== null
                    ? max(0, (int) $usageLimit - $claimedForAward)
                    : null);
                $selectionAtMax = $remainingForAward !== null
                    && count($selectedMemberIds) >= $remainingForAward;
                $noSlotsRemaining = $remainingForAward === 0;
            @endphp
            <div class="mb-4 rounded-lg border {{ $noSlotsRemaining ? 'border-red-200 bg-red-50' : 'border-gray-200 bg-gray-50' }} px-3 py-2">
                <p class="text-sm text-gray-700">
                    <span class="font-medium text-gray-900">Available Vouchers:</span>
                    @if($remainingForAward === null)
                        <span class="font-semibold text-green-700">Unlimited</span>
                    @else
                        <span class="font-semibold {{ $remainingForAward > 0 ? 'text-green-700' : 'text-red-600' }}">
                            {{ number_format($remainingForAward) }}
                        </span>
                        <span class="text-gray-500">
                            ({{ number_format($claimedForAward) }}/{{ number_format($usageLimit) }})
                        </span>
                    @endif
                </p>
                @if($noSlotsRemaining)
                    <p class="mt-1 text-sm text-red-600">
                        No vouchers remaining. You cannot award this voucher to more members.
                    </p>
                @elseif($selectionAtMax)
                    <p class="mt-1 text-sm text-orange-700">
                        Selection limit reached. Uncheck a member to choose someone else.
                    </p>
                @endif
            </div>

            <div class="mb-4">
                <label for="memberSearch" class="block text-sm font-medium text-gray-700 mb-2">Search Members</label>
                <input
                    type="text"
                    id="memberSearch"
                    wire:model.live.debounce.300ms="memberSearch"
                    placeholder="Search by name, email, or FIN..."
                    @disabled($noSlotsRemaining)
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-0 focus:outline-none focus:ring-orange-500 focus:border-orange-500 disabled:bg-gray-100 disabled:cursor-not-allowed"
                >
            </div>

            <div class="mb-4">
                <label for="awardReason" class="block text-sm font-medium text-gray-700 mb-2">Reason (optional)</label>
                <textarea
                    id="awardReason"
                    wire:model="awardReason"
                    rows="2"
                    placeholder="e.g. Volunteered at the community event"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-0 focus:outline-none focus:ring-orange-500 focus:border-orange-500 @error('awardReason') border-red-500 @enderror"
                ></textarea>
                @error('awardReason') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            @error('selectedMemberIds') <div class="mb-4 text-red-500 text-sm">{{ $message }}</div> @enderror
            @error('selectedMemberIds.*') <div class="mb-4 text-red-500 text-sm">{{ $message }}</div> @enderror

            @if(count($selectedMemberIds) > 0)
                <div class="mb-4">
                    <div class="flex items-center gap-2 justify-between w-full mb-2">
                        <p class="text-sm font-medium text-orange-500 ">
                            {{ count($selectedMemberIds) }} member{{ count($selectedMemberIds) === 1 ? '' : 's' }} selected
                        </p>
                        <button
                            type="button"
                            wire:click="clearSelectedMembers"
                            class="shrink-0 text-xs font-semibold text-orange-500 hover:text-orange-600"
                        >
                            Clear selection
                        </button>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach($selectedMembers as $member)
                                <span class="font-medium flex items-center gap-1 text-white border border-orange-500 rounded-full px-3 py-1.5 bg-orange-500 mr-1">
                                    {{ $member->name }}
                                    <button type="button" wire:click="removeSelectedMember({{ $member->id }})" class="cursor-pointer stroke-white hover:stroke-orange-200 hover:scale-110 transition-all duration-200">
                                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <div class="relative min-h-[8rem]">
                <div
                    wire:loading.flex
                    wire:target="memberSearch"
                    class="absolute inset-0 z-10 flex items-center justify-center rounded-lg bg-white/80"
                >
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                        <svg class="h-5 w-5 animate-spin text-orange-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Searching members...
                    </div>
                </div>

                <div wire:loading.class="opacity-40" wire:target="memberSearch">
                    @if($noSlotsRemaining)
                        <div class="flex max-h-64 items-center justify-center overflow-y-auto rounded-lg border border-dashed border-red-200 bg-red-50 py-8 text-center">
                            <div>
                                <h3 class="text-sm font-medium text-red-800">No vouchers available</h3>
                                <p class="mt-1 text-sm text-red-600">This voucher has reached its usage limit. New members cannot be awarded.</p>
                            </div>
                        </div>
                    @elseif($awardableMembers && $awardableMembers->count() > 0)
                        <div class="max-h-80 space-y-2 overflow-y-auto pr-1">
                            @foreach($awardableMembers as $member)
                                @php
                                    $isSelected = in_array((int) $member->id, array_map('intval', $selectedMemberIds), true);
                                    $checkboxDisabled = $selectionAtMax && ! $isSelected;
                                @endphp
                                <label
                                    wire:key="award-member-{{ $member->id }}"
                                    class="flex items-center p-3 rounded-lg border {{ $isSelected ? 'border-orange-300 bg-orange-50' : 'bg-gray-50 border-gray-200' }} {{ $checkboxDisabled ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100 cursor-pointer' }}"
                                >
                                    <input
                                        type="checkbox"
                                        wire:model.live="selectedMemberIds"
                                        value="{{ $member->id }}"
                                        @disabled($checkboxDisabled)
                                        class="w-4 h-4 text-orange-600 border-gray-300 rounded focus:ring-orange-500 disabled:cursor-not-allowed"
                                    >
                                    <div class="ml-3 flex items-center gap-2 justify-between w-full">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">{{ $member->name }}</p>
                                            <p class="text-sm text-gray-500">{{ $member->email }}</p>
                                        </div>
                                        @if($member->fin)
                                            <p class="text-xs text-gray-400 mt-0.5">FIN: {{ $member->fin }}</p>
                                        @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @elseif($showAwardModal && trim($memberSearch) !== '')
                        <div class="flex max-h-64 items-center justify-center overflow-y-auto py-8 text-center">
                            <div>
                                <h3 class="text-sm font-medium text-gray-900">No members found</h3>
                                <p class="mt-1 text-sm text-gray-500">No eligible members match “{{ trim($memberSearch) }}”.</p>
                            </div>
                        </div>
                    @elseif($showAwardModal)
                        <div class="flex max-h-64 items-center justify-center overflow-y-auto rounded-lg border border-dashed border-gray-200 py-8 text-center">
                            <div>
                                <h3 class="text-sm font-medium text-gray-900">Search for members</h3>
                                <p class="mt-1 text-sm text-gray-500">Start typing a name, email, or FIN, then check members to award in bulk.</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="closeAwardModal">
                Cancel
            </x-secondary-button>

            <button
                type="button"
                class="ms-3 inline-flex items-center px-4 py-3 bg-orange-600 rounded-lg border border-transparent font-semibold text-xs text-white uppercase tracking-widest hover:bg-orange-500 focus:bg-orange-500 active:bg-orange-500 focus:outline-none focus:ring-0 focus:ring-orange-500 focus:ring-offset-2 disabled:opacity-50 transition ease-in-out duration-200"
                wire:click="awardToMembers"
                wire:loading.attr="disabled"
                wire:target="awardToMembers"
                @if(count($selectedMemberIds) === 0 || $noSlotsRemaining) disabled @endif
            >
                <span wire:loading.remove wire:target="awardToMembers">
                    @if(count($selectedMemberIds) > 0)
                        Award to {{ count($selectedMemberIds) }} Member{{ count($selectedMemberIds) === 1 ? '' : 's' }}
                    @else
                        Award Voucher
                    @endif
                </span>
                <span wire:loading wire:target="awardToMembers">Awarding...</span>
            </button>
        </x-slot>
    </x-dialog-modal>

    <x-dialog-modal wire:model="showVoidModal" maxWidth="lg">
        <x-slot name="title">
            Void Voucher Assignment
        </x-slot>

        <x-slot name="content">
            <p class="text-sm text-gray-600 mb-4">
                Void <span class="font-semibold text-gray-900">{{ $voucher->name }}</span>
                ({{ $voucher->voucher_code }}) for
                <span class="font-semibold text-gray-900">{{ $voidMemberName }}</span>.
                Current status:
                <span class="font-semibold uppercase tracking-wide text-gray-800">{{ $voidPreviousStatus }}</span>.
            </p>

            <div class="mb-4 rounded-lg border {{ $voidRefundPreview > 0 ? 'border-green-200 bg-green-50' : 'border-gray-200 bg-gray-50' }} px-3 py-2">
                <p class="text-sm text-gray-700">
                    <span class="font-medium text-gray-900">Points to credit back:</span>
                    <span class="font-semibold {{ $voidRefundPreview > 0 ? 'text-green-700' : 'text-gray-600' }}">
                        {{ number_format($voidRefundPreview) }}
                    </span>
                </p>
                @if($voidRefundPreview === 0)
                    <p class="mt-1 text-sm text-gray-500">
                        No points will be refunded (admin award, or claim points already refunded).
                    </p>
                @else
                    <p class="mt-1 text-sm text-green-700">
                        These points will be credited to the member’s wallet and recorded in the audit log.
                    </p>
                @endif
            </div>

            <div class="mb-2">
                <label for="voidReason" class="block text-sm font-medium text-gray-700 mb-2">Reason (optional)</label>
                <textarea
                    id="voidReason"
                    wire:model="voidReason"
                    rows="2"
                    placeholder="e.g. Issued in error / member requested cancellation"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-0 focus:outline-none focus:ring-orange-500 focus:border-orange-500 @error('voidReason') border-red-500 @enderror"
                ></textarea>
                @error('voidReason') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                @error('voidMemberId') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <p class="text-xs text-gray-500 mt-3">
                This removes the voucher from the member’s Claimed/Redeemed lists, frees one usage slot, and writes an audit PointLog.
                Redeemed voids also update the merchant ledger for that period.
            </p>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="closeVoidModal">
                Cancel
            </x-secondary-button>

            <button
                type="button"
                class="ms-3 inline-flex items-center px-4 py-3 bg-red-600 rounded-lg border border-transparent font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 focus:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-0 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-50 transition ease-in-out duration-200"
                wire:click="voidMemberVoucher"
                wire:loading.attr="disabled"
                wire:target="voidMemberVoucher"
                @if(! $voidMemberId) disabled @endif
            >
                <span wire:loading.remove wire:target="voidMemberVoucher">Confirm Void</span>
                <span wire:loading wire:target="voidMemberVoucher">Voiding...</span>
            </button>
        </x-slot>
    </x-dialog-modal>
</div>
