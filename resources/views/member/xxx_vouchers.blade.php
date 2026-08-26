<x-app-layout>
    <x-slot name="header">
        @livewire('member.points-header')
    </x-slot>

    <div class="py-10 md:px-0 px-4">
        @php
            $status = request()->query('status');
            $initialTab = $status === 'my-vouchers' ? 'mine' : 'active';
        @endphp

        <div
            class="max-w-7xl mx-auto sm:px-6 lg:px-8"
            x-cloak
            x-data="{
                tab: '{{ $initialTab }}',
                startX: null,
                syncUrl() {
                    const params = new URLSearchParams(window.location.search);
                    params.set('status', this.tab === 'mine' ? 'my-vouchers' : 'activeVouchers');
                    const newUrl = `${window.location.pathname}?${params.toString()}`;
                    window.history.replaceState({}, '', newUrl);
                },
                setTab(next) {
                    this.tab = next;
                    this.syncUrl();
                },
                onTouchStart(e) { this.startX = e.touches?.[0]?.clientX ?? null },
                onTouchEnd(e) {
                    if (this.startX === null) return;
                    const endX = e.changedTouches?.[0]?.clientX ?? null;
                    if (endX === null) return;
                    const dx = endX - this.startX;
                    if (dx > 60) this.setTab('mine');      // swipe right -> claimed/redeemed
                    if (dx < -60) this.setTab('active');   // swipe left -> active/claimable
                    this.startX = null;
                },
            }"
            x-init="(() => {
                const status = new URLSearchParams(window.location.search).get('status');
                if (status === 'my-vouchers') tab = 'mine';
                if (status === 'activeVouchers') tab = 'active';
                syncUrl();
            })()"
            @touchstart.passive="onTouchStart($event)"
            @touchend.passive="onTouchEnd($event)"
        >
            <!-- Tabs (same style as Events) -->
            <div class="mb-6 flex items-center justify-center">
                <div class="inline-flex rounded-xl bg-gray-100 p-1 border border-slate-400">
                    <button
                        type="button"
                        @click="setTab('active')"
                        :class="tab === 'active' ? 'bg-orange-500 shadow text-white' : 'text-gray-600 hover:text-gray-900'"
                        class="px-4 py-2 text-sm font-semibold rounded-lg transition"
                    >
                        Active & Claimable
                    </button>
                    <button
                        type="button"
                        @click="setTab('mine')"
                        :class="tab === 'mine' ? 'bg-orange-500 shadow text-white' : 'text-gray-600 hover:text-gray-900'"
                        class="px-4 py-2 text-sm font-semibold rounded-lg transition"
                    >
                        Claimed & Redeemed
                    </button>
                </div>
            </div>

            <!-- Active / Claimable -->
            <div x-show="tab === 'active'" x-cloak>
                <!-- Merchant Vouchers -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">Merchant Vouchers</h3>
                    @livewire('member.vouchers.browse', key('member-vouchers-browse-page'))
                </div>
                
                <!-- Admin Vouchers -->
                <div class="mt-8">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">Admin Vouchers (Points Exchange)</h3>
                    @livewire('member.admin-vouchers.browse', key('member-admin-vouchers-browse-page'))
                </div>
            </div>

            <!-- Claimed / Redeemed -->
            <div x-show="tab === 'mine'" x-cloak>
                @livewire('member.vouchers.my-vouchers', key('member-vouchers-my-vouchers-page'))
            </div>
        </div>
    </div>
    <br />
    <br />
</x-app-layout>


