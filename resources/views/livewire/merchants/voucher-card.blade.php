@if($voucher)
    @php
        $isAdminVoucher = $type === 'admin';
        $statusReason = $voucher->getStatusReason();
        $isExpired = $statusReason === 'Expired' || ($voucher->valid_until && $voucher->valid_until->isPast());
        $isInactive = ! $voucher->is_active;
        $isFull = $isAdminVoucher && $voucher->usage_limit && $voucher->usage_count >= $voucher->usage_limit;

        if ($isExpired) {
            $statusLabel = 'Expired';
            $statusBadgeClass = 'bg-red-100 text-red-800 border-red-300';
        } elseif ($isFull) {
            $statusLabel = 'Full';
            $statusBadgeClass = 'bg-orange-100 text-orange-800 border-orange-300';
        } elseif ($isInactive) {
            $statusLabel = 'Inactive';
            $statusBadgeClass = 'bg-gray-100 text-gray-800 border-gray-300';
        } elseif ($voucher->isValid()) {
            $statusLabel = 'Active';
            $statusBadgeClass = 'bg-green-100 text-green-800 border-green-300';
        } else {
            $statusLabel = $statusReason ?? 'Inactive';
            $statusBadgeClass = 'bg-gray-100 text-gray-800 border-gray-300';
        }

        $useModal = $openAsModal && $isAdminVoucher;
        $profileUrl = $isAdminVoucher
            ? route('admin.admin-vouchers.profile', $voucher->voucher_code)
            : route('admin.vouchers.profile', $voucher->voucher_code);

        $cardClass = 'block w-full text-left group hover:-translate-y-0.5 transition-all duration-300 hover:shadow-sm '
            .($isAdminVoucher ? 'bg-teal-50' : 'bg-orange-50')
            .' focus:outline-none focus-visible:ring-2 '
            .($isAdminVoucher ? 'ring-green-400' : 'ring-orange-400')
            .' rounded-r-lg';

        $ticketHoverClass = 'transition '
            .($isAdminVoucher ? 'group-hover:border-teal-600' : 'group-hover:border-orange-400')
            .' group-hover:shadow-sm '
            .($isAdminVoucher ? 'group-hover:bg-teal-50' : 'group-hover:bg-orange-50');

        $merchantNames = $isAdminVoucher
            ? $voucher->merchants->pluck('name')->filter()->values()
            : collect();
    @endphp

    <div>
        @if($useModal)
            <button
                type="button"
                wire:click="openDetailModal"
                class="{{ $cardClass }}"
                title="View voucher details"
            >
                <x-merchant.voucher-ticket
                    :voucher="$voucher"
                    type="admin"
                    merchant-label="Admin Voucher"
                    :status-label="$statusLabel"
                    class="{{ $ticketHoverClass }}"
                >
                    <x-slot:meta>
                        <p class="text-gray-500 text-xs">
                            Claimed: {{ number_format($claimedCount) }} <br> Redeemed: {{ number_format($redeemedCount) }}
                            @if($voucher->usage_limit)
                                <br> Total Quantity: {{ number_format((int) $voucher->usage_limit) }}
                            @endif
                        </p>
                    </x-slot:meta>
                </x-merchant.voucher-ticket>
            </button>
        @else
            <a
                href="{{ $profileUrl }}"
                class="{{ $cardClass }}"
                title="View voucher profile"
            >
                <x-merchant.voucher-ticket
                    :voucher="$voucher"
                    :type="$isAdminVoucher ? 'admin' : 'merchant'"
                    :merchant-label="$isAdminVoucher ? 'Admin Voucher' : optional($voucher->merchant)->name"
                    :status-label="$statusLabel"
                    class="{{ $ticketHoverClass }}"
                >
                    <x-slot:meta>
                        <p class="text-gray-500 text-xs">
                            Claimed: {{ number_format($claimedCount) }} <br> Redeemed: {{ number_format($redeemedCount) }}
                            @if($voucher->usage_limit)
                                <br> Total Quantity: {{ number_format((int) $voucher->usage_limit) }}
                            @endif
                        </p>
                    </x-slot:meta>
                </x-merchant.voucher-ticket>
            </a>
        @endif

        @if($useModal)
            <x-dialog-modal wire:model="showDetailModal" maxWidth="2xl">
                <x-slot name="title">
                    <div class="flex items-start justify-between gap-3 pr-2">
                        <span class="leading-tight">{{ $voucher->name }}</span>
                        <span class="shrink-0 px-2.5 py-1 text-xs font-semibold rounded-full border {{ $statusBadgeClass }}">
                            {{ $statusLabel }}
                        </span>
                    </div>
                </x-slot>

                <x-slot name="content">
                    <div class="space-y-5 max-h-[70vh] overflow-y-auto pr-1">
                        @if($voucher->image_url)
                            <img
                                src="{{ $voucher->image_url }}"
                                alt="{{ $voucher->name }}"
                                class="w-full max-h-48 object-cover rounded-lg border border-gray-200"
                            >
                        @endif

                        @if(filled($voucher->description))
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Description</p>
                                <p class="text-sm text-gray-800">{{ $voucher->description }}</p>
                            </div>
                        @endif

                        <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Voucher Code</p>
                                <p class="text-sm font-mono text-gray-900">{{ $voucher->voucher_code }}</p>
                            </div>
                        </div>

                        @php
                            $pointsCost = max(0, (int) ($voucher->points_cost ?? 0));
                            $costPerPoint = max(0, (float) ($voucher->amount_cost ?? 0));
                            $costPerVoucher = round($pointsCost * $costPerPoint, 2);
                        @endphp
                        <div class="mt-2 grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Points Cost</p>
                                <p class="mt-1 text-sm font-semibold text-gray-900">
                                    {{ number_format($pointsCost) }} pts
                                </p>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Cost Per Point</p>
                                <p class="mt-1 text-sm font-semibold text-gray-900">
                                    SGD {{ number_format($costPerPoint, 2) }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Cost Per Voucher</p>
                                <p class="mt-1 text-sm font-semibold text-gray-900">
                                    SGD {{ number_format($costPerVoucher, 2) }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Valid From</p>
                                <p class="text-sm text-gray-900">
                                    {{ $voucher->valid_from ? $voucher->valid_from->format('d M Y g:i A') : '—' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Valid Until</p>
                                <p class="mt-1 text-sm text-gray-900">
                                    {{ $voucher->valid_until ? $voucher->valid_until->format('d M Y g:i A') : '—' }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-2">
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Usage</p>
                            <div class="grid grid-cols-3 gap-2">
                                <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-center">
                                    <p class="text-lg font-bold text-gray-900">{{ number_format($claimedCount) }}</p>
                                    <p class="text-[0.65rem] text-gray-500 uppercase">Claimed</p>
                                </div>
                                <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-center">
                                    <p class="text-lg font-bold text-gray-900">{{ number_format($redeemedCount) }}</p>
                                    <p class="text-[0.65rem] text-gray-500 uppercase">Redeemed</p>
                                </div>
                                <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-center">
                                    <p class="text-lg font-bold text-gray-900">
                                        {{ $voucher->usage_limit !== null ? number_format((int) $voucher->usage_limit) : '∞' }}
                                    </p>
                                    <p class="text-[0.65rem] text-gray-500 uppercase">Total Qty</p>
                                </div>
                            </div>
                        </div>

                        @if($merchantNames->isNotEmpty())
                            <div class="mt-4">
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Redeemable At</p>
                                <p class="text-sm text-gray-900">{{ $merchantNames->implode(', ') }}</p>
                            </div>
                        @endif
                    </div>
                </x-slot>

                <x-slot name="footer">
                    <button
                        type="button"
                        wire:click="closeDetailModal"
                        class="px-4 py-2 text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition"
                    >
                        Close
                    </button>
                </x-slot>
            </x-dialog-modal>
        @endif
    </div>
@endif
