@if($voucher)
    @php
        $isAdminVoucher = $type === 'admin';
        $statusReason = $voucher->getStatusReason();
        $isExpired = $statusReason === 'Expired' || ($voucher->valid_until && $voucher->valid_until->isPast());
        $isInactive = ! $voucher->is_active;
        $isFull = $isAdminVoucher && $voucher->usage_limit && $voucher->usage_count >= $voucher->usage_limit;

        if ($isExpired) {
            $statusLabel = 'Expired';
        } elseif ($isFull) {
            $statusLabel = 'Full';
        } elseif ($isInactive) {
            $statusLabel = 'Inactive';
        } elseif ($voucher->isValid()) {
            $statusLabel = 'Active';
        } else {
            $statusLabel = $statusReason ?? 'Inactive';
        }

        $profileUrl = $isAdminVoucher
            ? route('admin.admin-vouchers.profile', $voucher->voucher_code)
            : route('admin.vouchers.profile', $voucher->voucher_code);
    @endphp

    <div>
        <a
            href="{{ $profileUrl }}"
            class="block group hover:-translate-y-0.5 transition-all duration-300 hover:shadow-sm {{$isAdminVoucher ? 'bg-teal-50' : 'bg-orange-50'}} focus:outline-none focus-visible:ring-2 {{$isAdminVoucher ? 'ring-green-400' : 'ring-orange-400'}} rounded-r-lg"
            title="View voucher profile"
        >
            <x-merchant.voucher-ticket
                :voucher="$voucher"
                :type="$isAdminVoucher ? 'admin' : 'merchant'"
                :merchant-label="$isAdminVoucher ? 'Admin Voucher' : optional($voucher->merchant)->name"
                :status-label="$statusLabel"
                class="transition {{$isAdminVoucher ? 'group-hover:border-teal-600' : 'group-hover:border-orange-400'}} group-hover:shadow-sm {{$isAdminVoucher ? 'group-hover:bg-teal-50' : 'group-hover:bg-orange-50'}}"
            >
                <x-slot:meta>
                    <p class="text-gray-500 text-[0.7rem]">
                        {{ number_format($claimedCount) }} Claimed · {{ number_format($redeemedCount) }} Redeemed
                        @if($voucher->usage_limit)
                            · {{ number_format((int) $voucher->usage_limit) }} Total Quantity
                        @endif
                    </p>
                </x-slot:meta>
            </x-merchant.voucher-ticket>
        </a>
    </div>
@endif
