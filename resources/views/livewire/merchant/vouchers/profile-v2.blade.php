@php
    $statusLabel = $voucher->getDisplayStatusLabel();
    $statusCategory = $voucher->getDisplayStatusCategory();
    $isFullyClaimed = $voucher->usage_limit && $voucher->usage_count >= $voucher->usage_limit;
    $remainingClaims = $voucher->usage_limit !== null
        ? max(0, (int) $voucher->usage_limit - (int) $voucher->usage_count)
        : null;
    $usagePercent = $voucher->usage_limit
        ? min(100, (int) round(((int) $voucher->usage_count / (int) $voucher->usage_limit) * 100))
        : null;
    $claimedCount = count($claimedMembers);
    $redeemedCount = count($redeemedMembers);
    $merchantIsActive = (bool) ($merchant?->is_active);

    if ($statusLabel === 'Not Yet Valid') {
        $statusClass = 'bg-sky-50 text-sky-700 border-sky-200';
        $statusDot = 'bg-sky-500';
    } else {
        $statusClass = match ($statusCategory) {
            'active' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'pending_approval' => 'bg-amber-50 text-amber-800 border-amber-200',
            'not_yet_valid' => 'bg-sky-50 text-sky-700 border-sky-200',
            default => 'bg-slate-100 text-slate-600 border-slate-200',
        };
        $statusDot = match ($statusCategory) {
            'active' => 'bg-emerald-500',
            'pending_approval' => 'bg-amber-500',
            'not_yet_valid' => 'bg-sky-500',
            default => 'bg-slate-400',
        };
    }

    $statusHelp = match ($statusLabel) {
        'Active' => $isFullyClaimed
            ? 'This offer is approved, but all available claims have been used.'
            : 'This offer is live and can be claimed by members.',
        'Pending Approval' => 'Waiting for Hope Village admin approval before members can see this offer.',
        'Not Yet Valid' => 'This offer is scheduled and is not available yet.',
        'Expired' => 'This offer is past its validity date.',
        default => null,
    };

    if (($voucher->discount_type ?? null) === 'percentage') {
        $valueText = rtrim(rtrim((string) ($voucher->discount_value ?? 0), '0'), '.').'% off';
    } elseif (($voucher->discount_type ?? null) === 'item') {
        $valueText = 'Free Item';
    } else {
        $valueText = '$'.number_format((float) ($voucher->discount_value ?? 0), 2).' off';
    }

    $visibility = $voucher->visibility_to_type_of_work ?? [];
@endphp

<div class="min-h-screen bg-slate-50 pb-28">
    <div class="relative overflow-hidden bg-[#3a5870]">
        <div class="pointer-events-none absolute inset-0 opacity-30" aria-hidden="true">
            <div class="absolute -top-16 -right-10 size-56 rounded-full bg-orange-400/40 blur-3xl"></div>
            <div class="absolute -bottom-20 -left-10 size-64 rounded-full bg-sky-400/20 blur-3xl"></div>
        </div>

        <div class="relative max-w-full lg:max-w-5xl w-full mx-auto px-4 sm:px-6 pt-5 pb-16">
            <div class="flex items-center justify-between gap-3">
                <a href="{{ route('merchant.dashboard.v2') }}" class="flex items-center gap-2.5 min-w-0">
                    <img src="{{ asset('hv-logo.png') }}" alt="Hope Village" class="md:w-15 md:h-15 w-11 h-11 object-contain drop-shadow drop-shadow-white/50">
                    <div class="min-w-0">
                        <p class="text-white font-semibold leading-tight md:text-2xl text-sm">Merchant Portal</p>
                        <p class="text-white/70 truncate md:text-lg text-xs">Hope Village Merchants Center</p>
                    </div>
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-full bg-white/10 hover:bg-white/20 text-white text-xs font-semibold px-3 py-2 border border-white/15 transition-colors">
                        <svg class="size-4" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" fill="none">
                            <g style="fill:none;stroke:#ffffff;stroke-width:12px;stroke-linecap:round;stroke-linejoin:round;">
                                <path d="m 50,10 0,35"></path>
                                <path d="M 26,20 C -3,48 16,90 51,90 79,90 89,67 89,52 89,37 81,26 74,20"></path>
                            </g>
                        </svg>
                        Logout
                    </button>
                </form>
            </div>

            <div class="mt-6">
                <a href="{{ route('merchant.vouchers.index') }}" class="inline-flex items-center gap-1.5 text-orange-200 hover:text-white text-sm font-medium transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                    </svg>
                    Back to Vouchers
                </a>
                <h1 class="text-white text-2xl sm:text-3xl font-bold tracking-tight mt-2">{{ $voucher->name }}</h1>
                <div class="mt-2 flex justify-between items-center gap-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold border {{ $statusClass }}">
                            <span class="size-1.5 rounded-full {{ $statusDot }}"></span>
                            {{ $statusLabel }}
                        </span>
                        @if($isFullyClaimed)
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold border bg-orange-50 text-orange-700 border-orange-200">
                                Fully claimed
                            </span>
                        @endif
                    </div>
                    <div
                        class="flex flex-wrap items-center gap-2"
                        x-data="{
                            qrOpen: false,
                            qrCodeImage: @js($qrCodeImage),
                            voucherCode: @js($voucher->voucher_code),
                            copied: false,
                            openQr() {
                                this.qrOpen = true;
                                document.body.style.overflow = 'hidden';
                            },
                            closeQr() {
                                this.qrOpen = false;
                                this.copied = false;
                                document.body.style.overflow = '';
                            },
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
                                    alert('Failed to download QR code. Please try again.');
                                }
                            },
                            async copyCode() {
                                try {
                                    await navigator.clipboard.writeText(this.voucherCode);
                                    this.copied = true;
                                    setTimeout(() => this.copied = false, 2000);
                                } catch (e) {
                                    alert(this.voucherCode);
                                }
                            }
                        }"
                    >
                        <button
                            type="button"
                            x-on:click="openQr()"
                            class="flex items-center justify-center text-xs gap-1 rounded-full bg-white hover:bg-slate-100 text-slate-700 font-semibold py-2 px-3 transition-all duration-200"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 3.75 9.375v-4.5ZM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 13.5 9.375v-4.5Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.25 15.75h4.5M17.25 12.75v6" />
                            </svg>
                            View QR
                        </button>
                        <template x-teleport="body">
                            <div
                                x-show="qrOpen"
                                x-cloak
                                x-transition:enter="ease-out duration-200"
                                x-transition:enter-start="opacity-0"
                                x-transition:enter-end="opacity-100"
                                x-transition:leave="ease-in duration-150"
                                x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"
                                class="fixed inset-0 z-[9999] bg-black/60 flex items-center justify-center p-4"
                                x-on:keydown.escape.window="qrOpen && closeQr()"
                                x-on:click="closeQr()"
                                style="display: none;"
                                role="dialog"
                                aria-modal="true"
                                aria-labelledby="voucher-qr-modal-title"
                            >
                                <div
                                    x-on:click.stop
                                    x-show="qrOpen"
                                    x-transition:enter="ease-out duration-200"
                                    x-transition:enter-start="opacity-0 scale-95"
                                    x-transition:enter-end="opacity-100 scale-100"
                                    x-transition:leave="ease-in duration-150"
                                    x-transition:leave-start="opacity-100 scale-100"
                                    x-transition:leave-end="opacity-0 scale-95"
                                    class="bg-white rounded-2xl border border-slate-200 shadow-xl w-full max-w-md p-4 sm:p-5 relative"
                                >
                                    <button
                                        type="button"
                                        x-on:click="closeQr()"
                                        class="absolute top-4 right-4 text-slate-400 hover:text-slate-700 transition-colors"
                                        aria-label="Close"
                                    >
                                        <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>

                                    <h3 id="voucher-qr-modal-title" class="text-sm font-semibold text-slate-800 pr-8">Voucher QR code</h3>
                                    <p class="mt-1 text-xs text-slate-500">Members can scan this to claim the offer.</p>

                                    <div class="mt-4 flex items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                        <img :src="qrCodeImage" alt="Voucher QR Code" class="w-full max-w-[220px] aspect-square object-contain bg-white rounded-xl p-2">
                                    </div>

                                    <div class="flex items-center justify-center gap-2 mt-1 transition-all duration-300">
                                        <span class="font-mono">{{ $voucher->voucher_code }}</span>
                                        <span class="text-slate-400 text-sm italic" x-text="copied ? 'Copied' : ''"></span>
                                    </div>

                                    <div class="mt-3 grid grid-cols-2 gap-2">
                                        <button
                                            type="button"
                                            x-on:click="downloadQR()"
                                            class="inline-flex items-center justify-center gap-1.5 rounded-full border border-slate-300 hover:border-orange-400 hover:text-orange-600 text-slate-700 text-sm font-semibold px-4 py-2.5 transition-colors"
                                        >
                                            Download QR Code
                                        </button>
                                        <button
                                            type="button"
                                            x-on:click="copyCode()"
                                            class="inline-flex items-center justify-center gap-1.5 rounded-full bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold px-4 py-2.5 transition-colors"
                                        >
                                            Copy Voucher Code
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                        @if($merchantIsActive)
                            <a href="{{ route('merchant.vouchers.edit', $voucher->voucher_code) }}" class="flex items-center justify-center text-xs gap-1 rounded-full bg-orange-500 hover:bg-orange-600 text-white font-semibold py-2 px-3 transition-all duration-200">
                                {{-- edit icon --}}
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                </svg>
                                Edit
                            </a>
                        @else
                            <div class="flex items-center justify-center text-xs gap-1 rounded-full bg-orange-200/50 text-orange-700 font-semibold py-1 px-3 cursor-not-allowed">
                                Edit locked
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-xl md:max-w-2xl lg:max-w-6xl mx-auto px-4 sm:px-6 -mt-10 relative z-10">
        <section class="bg-white rounded-2xl border border-slate-200/80 shadow-lg shadow-slate-900/5 overflow-hidden">
            <div class="p-4 sm:p-5 flex items-center gap-4">
                <div class="size-20 sm:size-24 rounded-2xl overflow-hidden shrink-0 bg-orange-500 flex items-center justify-center border border-orange-100">
                    @if($voucher->image_url)
                        <img src="{{ $voucher->image_url }}" alt="{{ $voucher->name }}" class="size-full object-cover">
                    @else
                        <span class="text-white text-2xl font-bold">%</span>
                    @endif
                </div>

                <div class="min-w-0 flex-1">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Voucher Value</p>
                    <p class="mt-0.5 text-xl sm:text-2xl font-bold text-slate-900">{{ $valueText }}</p>

                    @if($voucher->description)
                        <p class="text-sm text-slate-600 whitespace-pre-line">{{ $voucher->description }}</p>
                    @endif

                    @if($statusHelp)
                        <p class="mt-3 text-sm text-slate-500">{{ $statusHelp }}</p>
                    @endif
                </div>
            </div>
        </section>

        @if(! $merchantIsActive)
            <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                <p class="font-semibold">Store pending approval</p>
                <p class="mt-0.5 text-amber-800/90">You can review this voucher, but editing stays locked until an admin approves your store.</p>
            </div>
        @endif

        <section class="mt-5 grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Claimed</p>
                    <span class="size-8 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z" />
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-3xl font-bold text-slate-900 tabular-nums">{{ number_format($claimedCount) }}</p>
                <p class="mt-1 text-xs text-slate-500">Waiting to redeem</p>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Redeemed</p>
                    <span class="size-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-3xl font-bold text-slate-900 tabular-nums">{{ number_format($redeemedCount) }}</p>
                <p class="mt-1 text-xs text-slate-500">Completed at this store</p>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Claims left</p>
                    <span class="size-8 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-3xl font-bold text-slate-900 tabular-nums">
                    {{ $remainingClaims === null ? '∞' : number_format($remainingClaims) }}
                </p>
                <p class="mt-1 text-xs text-slate-500">
                    @if($voucher->usage_limit)
                        {{ number_format((int) $voucher->usage_count) }} / {{ number_format((int) $voucher->usage_limit) }} used
                    @else
                        Unlimited claims
                    @endif
                </p>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Status</p>
                    <span class="size-8 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-lg font-bold text-slate-900 leading-tight">{{ $statusLabel }}</p>
                <p class="mt-1 text-xs text-slate-500">
                    @if($voucher->valid_until)
                        Until {{ $voucher->valid_until->format('d M Y') }}
                    @else
                        No end date
                    @endif
                </p>
            </div>
        </section>

        <div class="mt-6 grid grid-cols-1 lg:grid-cols-5 gap-6">
            <section class="lg:col-span-3 space-y-6">
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-5">
                    <h3 class="text-sm font-semibold text-slate-800">Voucher Information</h3>

                    <dl class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-slate-500">Voucher Code</dt>
                            <dd class="mt-1 text-sm font-mono text-slate-900">{{ $voucher->voucher_code }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-slate-500">Voucher Value</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $valueText }}</dd>
                        </div>
                        @if($voucher->min_purchase)
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-slate-500">Minimum Purchase</dt>
                                <dd class="mt-1 text-sm text-slate-900">${{ number_format((float) $voucher->min_purchase, 2) }}</dd>
                            </div>
                        @endif
                        @if($voucher->max_discount)
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-slate-500">Maximum Discount</dt>
                                <dd class="mt-1 text-sm text-slate-900">${{ number_format((float) $voucher->max_discount, 2) }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-slate-500">Validity</dt>
                            <dd class="mt-1 text-sm text-slate-900">
                                @if($voucher->valid_from)
                                    {{-- From {{ $voucher->valid_from->format('d M Y g:i A') }} --}}
                                    {{ $voucher->valid_from->format('d M Y') }}
                                @else
                                    N/A
                                @endif
                                @if($voucher->valid_until)
                                    -
                                    {{ $voucher->valid_until->format('d M Y') }}
                                @else
                                    N/A
                                @endif
                            </dd>
                        </div>
                    </dl>

                    @if($voucher->usage_limit)
                        <div class="mt-5">
                            <div class="flex items-center justify-between gap-3 text-xs font-semibold text-slate-500">
                                <span>Claim usage</span>
                                <span class="tabular-nums text-slate-700">{{ number_format((int) $voucher->usage_count) }} / {{ number_format((int) $voucher->usage_limit) }}</span>
                            </div>
                            <div class="mt-2 h-4 border border-slate-200 rounded-full bg-slate-100 overflow-hidden">
                                <div class="h-full rounded-full {{ $isFullyClaimed ? 'bg-orange-500' : 'bg-emerald-500' }}" style="width: {{ $usagePercent }}%"></div>
                            </div>
                        </div>
                    @endif

                    <div class="mt-5 flex items-center">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mr-1">Visible to</p>
                        <div class="flex flex-wrap gap-1.5">
                            @if(empty($visibility))
                                <span class="inline-flex rounded-full bg-orange-100 text-orange-500 px-2.5 py-1 text-xs font-medium">All members</span>
                            @else
                                @foreach($visibility as $type)
                                    <span class="inline-flex rounded-full bg-orange-100 text-orange-500 px-2.5 py-1 text-xs font-medium">{{ $type }}</span>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>

                <div
                    class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden"
                    x-data="{ tab: 'claimed' }"
                >
                    <div class="px-4 sm:px-5 pt-4 sm:pt-5 flex items-center justify-between gap-3">
                        <h3 class="text-sm font-semibold text-slate-800">Member Activity</h3>
                    </div>

                    <div class="mt-3 px-4 sm:px-5 flex items-center gap-2 border-b border-slate-200">
                        <button
                            type="button"
                            @click="tab = 'claimed'"
                            :class="tab === 'claimed' ? 'border-orange-500 text-orange-600 font-semibold' : 'border-transparent text-slate-500 hover:text-slate-800'"
                            class="px-1 pb-3 text-sm border-b-2 transition-colors"
                        >
                            Claimed ({{ $claimedCount }})
                        </button>
                        <button
                            type="button"
                            @click="tab = 'redeemed'"
                            :class="tab === 'redeemed' ? 'border-orange-500 text-orange-600 font-semibold' : 'border-transparent text-slate-500 hover:text-slate-800'"
                            class="px-1 pb-3 text-sm border-b-2 transition-colors"
                        >
                            Redeemed ({{ $redeemedCount }})
                        </button>
                    </div>

                    <div x-show="tab === 'claimed'" x-cloak>
                        @if($claimedCount > 0)
                            <ul class="max-h-96 overflow-y-auto overscroll-contain divide-y divide-slate-100">
                                @foreach($claimedMembers as $member)
                                    <li class="px-4 sm:px-5 py-3.5">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <p class="font-semibold text-slate-900 truncate">{{ $member['name'] }}</p>
                                                <p class="text-sm text-slate-600 truncate mt-0.5">{{ $member['email'] }}</p>
                                                @if($member['qr_code'])
                                                    <p class="text-[11px] text-slate-400 font-mono mt-1">{{ $member['qr_code'] }}</p>
                                                @endif
                                            </div>
                                            <div class="text-right shrink-0">
                                                <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide bg-orange-50 text-orange-700">
                                                    Claimed
                                                </span>
                                                <p class="text-xs text-slate-500 mt-1.5">
                                                    {{ $member['claimed_at'] ? \Carbon\Carbon::parse($member['claimed_at'])->format('d M Y g:i A') : 'N/A' }}
                                                </p>
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <div class="h-96 px-6 py-12 text-center">
                                <p class="font-semibold text-slate-800">No claims yet</p>
                                <p class="text-sm text-slate-500 mt-1">Members who claim this offer will appear here until they redeem it.</p>
                            </div>
                        @endif
                    </div>

                    <div x-show="tab === 'redeemed'" x-cloak>
                        @if($redeemedCount > 0)
                            <ul class="max-h-96 overflow-y-auto overscroll-contain divide-y divide-slate-100">
                                @foreach($redeemedMembers as $member)
                                    <li class="px-4 sm:px-5 py-3.5">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <p class="font-semibold text-slate-900 truncate">{{ $member['name'] }}</p>
                                                <p class="text-sm text-slate-600 truncate mt-0.5">{{ $member['email'] }}</p>
                                                @if($member['qr_code'])
                                                    <p class="text-[11px] text-slate-400 font-mono mt-1">{{ $member['qr_code'] }}</p>
                                                @endif
                                            </div>
                                            <div class="text-right shrink-0">
                                                <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide bg-emerald-50 text-emerald-700">
                                                    Redeemed
                                                </span>
                                                <p class="text-xs text-slate-500 mt-1.5">
                                                    {{ $member['redeemed_at'] ? \Carbon\Carbon::parse($member['redeemed_at'])->format('d M Y g:i A') : 'N/A' }}
                                                </p>
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <div class="h-96 px-6 py-12 text-center">
                                <p class="font-semibold text-slate-800">No redemptions yet</p>
                                <p class="text-sm text-slate-500 mt-1">When members redeem this voucher at your store, they show up here.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </section>

            <section class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-5">
                    <h3 class="text-sm font-semibold text-slate-800">Record</h3>
                    <dl class="mt-4 space-y-3">
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-slate-500">Created</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $voucher->created_at->format('d M Y g:i A') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-slate-500">Last updated</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $voucher->updated_at->format('d M Y g:i A') }}</dd>
                        </div>
                    </dl>
                </div>
            </section>
        </div>
    </div>
</div>
