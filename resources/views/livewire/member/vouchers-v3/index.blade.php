<div
    @if(! $showClaimConfirm && ! $showQr) wire:poll.5s @endif
    x-data="{
        tab: @entangle('tab'),
        setTab(next) {
            this.tab = next;
            const params = new URLSearchParams(window.location.search);
            params.set('tab', next);
            const newUrl = `${window.location.pathname}?${params.toString()}`;
            window.history.replaceState({}, '', newUrl);
        },
        init() {
            const tabParam = new URLSearchParams(window.location.search).get('tab');
            if (['active', 'claimed', 'redeemed'].includes(tabParam)) {
                this.tab = tabParam;
            }
            this.setTab(this.tab);
        },
    }"
    x-init="init()"
    class="space-y-4 pb-24"
>
    <div class="sticky top-0 z-10 backdrop-blur-sm py-2 w-full">
        <div class="w-full flex justify-evenly items-center gap-3 border-b border-gray-200">
            <button type="button" @click="setTab('active')" class="pb-3 text-xs uppercase tracking-wider whitespace-nowrap transition-colors"
                :class="tab === 'active' ? 'text-red-600 border-b-2 border-red-600 font-semibold' : 'text-gray-600'">
                Active ({{ $this->activeItems->count() }})
            </button>
            <button type="button" @click="setTab('claimed')" class="pb-3 text-xs uppercase tracking-wider whitespace-nowrap transition-colors"
                :class="tab === 'claimed' ? 'text-red-600 border-b-2 border-red-600 font-semibold' : 'text-gray-600'">
                Claimed ({{ $this->claimedItems->count() }})
            </button>
            <button type="button" @click="setTab('redeemed')" class="pb-3 text-xs uppercase tracking-wider whitespace-nowrap transition-colors"
                :class="tab === 'redeemed' ? 'text-red-600 border-b-2 border-red-600 font-semibold' : 'text-gray-600'">
                Redeemed ({{ $this->redeemedItems->count() }})
            </button>
        </div>
    </div>

    <div x-show="tab === 'active'" x-cloak class="space-y-3">
        @forelse($this->activeItems as $item)
            @include('livewire.member.vouchers-v3.partials.ticket-row', [
                'item' => $item,
                'tab' => 'active',
                'userPoints' => (int) (auth()->user()->total_points ?? 0),
            ])
        @empty
            <div class="rounded-lg border border-dashed border-gray-300 p-6 text-center bg-white">
                <p class="text-gray-700 font-semibold">No active vouchers available</p>
                <p class="text-sm text-gray-500 mt-1">Check back later for new rewards.</p>
            </div>
        @endforelse
    </div>

    <div x-show="tab === 'claimed'" x-cloak class="space-y-3">
        @forelse($this->claimedItems as $item)
            @include('livewire.member.vouchers-v3.partials.ticket-row', [
                'item' => $item,
                'tab' => 'claimed',
            ])
        @empty
            <div class="rounded-lg border border-dashed border-gray-300 p-6 text-center bg-white">
                <p class="text-gray-700 font-semibold">No claimed vouchers yet</p>
                <p class="text-sm text-gray-500 mt-1">Claim a voucher from the Active tab first.</p>
            </div>
        @endforelse
    </div>

    <div x-show="tab === 'redeemed'" x-cloak class="space-y-3">
        @forelse($this->redeemedItems as $item)
            @include('livewire.member.vouchers-v3.partials.ticket-row', [
                'item' => $item,
                'tab' => 'redeemed',
            ])
        @empty
            <div class="rounded-lg border border-dashed border-gray-300 p-6 text-center bg-white">
                <p class="text-gray-700 font-semibold">No redeemed vouchers yet</p>
                <p class="text-sm text-gray-500 mt-1">Your redeemed history will appear here.</p>
            </div>
        @endforelse
    </div>

    <div
        x-cloak
        x-show="$wire.showClaimConfirm"
        x-transition.opacity
        class="fixed inset-0 z-90 bg-black/80 flex items-center justify-center p-6"
        @click="$wire.closeClaimConfirm()"
        @keydown.escape.window="if ($wire.showClaimConfirm) $wire.closeClaimConfirm()"
    >
        <div class="bg-white w-full max-w-sm rounded-2xl p-5" @click.stop>
            <h3 class="text-lg font-bold text-gray-900 text-center">Confirm Claim</h3>
            <p class="mt-1 text-xs text-gray-500 text-center">Please review this voucher before claiming.</p>

            <div class="mt-4 rounded-xl border border-gray-200 bg-gray-50 p-4 space-y-2">
                <p class="text-sm font-bold text-gray-900 leading-tight">{{ $confirmName }}</p>
                @if($confirmMerchantName)
                    <p class="text-xs text-gray-600">Redeemable at <span class="font-semibold text-gray-800">{{ $confirmMerchantName }}</span></p>
                @endif
                @if($confirmDiscountLabel)
                    <p class="text-xs text-gray-600">Discount: <span class="font-semibold text-gray-800">{{ $confirmDiscountLabel }}</span></p>
                @endif
                @if($pendingClaimType === 'admin')
                    <p class="text-xs text-gray-600">Points cost: <span class="font-semibold text-gray-800">{{ number_format($confirmPointsCost ?? 0) }}</span></p>
                @endif
                @if($confirmValidUntil)
                    <p class="text-xs text-gray-600">Valid until <span class="font-semibold text-gray-800">{{ $confirmValidUntil }}</span></p>
                @endif
            </div>

            @if($pendingClaimType === 'admin')
                <div class="mt-3">
                    <p class="text-md font-semibold text-gray-900 leading-snug">
                        <span class="font-bold">-{{ number_format($confirmPointsCost ?? 0) }} points</span>
                        will be deducted from your points wallet.
                    </p>
                    {{-- <p class="text-sm text-orange-800 mt-1">
                        Current balance: {{ number_format($confirmUserPoints ?? 0) }}
                        → After claim: {{ number_format(max(0, (int) ($confirmUserPoints ?? 0) - (int) ($confirmPointsCost ?? 0))) }}
                    </p> --}}
                </div>
            @endif

            <div class="mt-5 flex items-center gap-3">
                <button
                    type="button"
                    wire:click="closeClaimConfirm"
                    wire:loading.attr="disabled"
                    wire:target="confirmClaim"
                    class="flex-1 py-2.5 rounded-full border border-gray-300 text-sm font-semibold text-gray-700"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    wire:click="confirmClaim"
                    wire:loading.attr="disabled"
                    wire:target="confirmClaim"
                    @class([
                        'flex-1 py-2.5 rounded-full text-sm font-semibold text-white',
                        'bg-teal-600' => $pendingClaimType === 'admin',
                        'bg-orange-500' => $pendingClaimType !== 'admin',
                    ])
                >
                    <span wire:loading.remove wire:target="confirmClaim">Confirm</span>
                    <span wire:loading wire:target="confirmClaim">...</span>
                </button>
            </div>
        </div>
    </div>

    <div
        x-cloak
        x-show="$wire.showQr"
        x-transition.opacity
        class="fixed inset-0 z-90 bg-black/80 flex items-center justify-center p-6"
        @click="$wire.closeQr()"
        @keydown.escape.window="if ($wire.showQr) $wire.closeQr()"
    >
        <div class="bg-white w-full max-w-lg flex flex-col items-center justify-center rounded-2xl p-5" @click.stop>
            <h3 class="text-md font-bold text-gray-800 text-center leading-tight">{{ $qrVoucherName }}</h3>
            <p class="mb-2 text-xs text-gray-600 text-center font-semibold">({{ $qrVoucherCode }})</p>
            @if($qrImage)
                <img src="{{ $qrImage }}" alt="Voucher QR" class="w-64">
            @endif
            @if($qrRedeemableAt)
                <p class="text-sm text-gray-700 text-center mt-2">
                    Redeemable at <span class="font-semibold">{{ $qrRedeemableAt }}</span>
                </p>
            @endif
        </div>
    </div>
</div>
