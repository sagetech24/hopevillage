@php
    $isEditing = (bool) $voucherCode;
    $pageTitle = $isEditing ? 'Edit Voucher' : 'Create Voucher';
    $pageSubtitle = $isEditing
        ? 'Update offer details for your store.'
        : 'Set up a new store offer for Hope Village members.';

    $statusLabel = $voucher?->getDisplayStatusLabel() ?? 'Pending Approval';
    $statusCategory = $voucher?->getDisplayStatusCategory() ?? 'pending_approval';
    $isFullyClaimed = $voucher
        && $voucher->usage_limit
        && $voucher->usage_count >= $voucher->usage_limit;

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
        'Pending Approval' => $isEditing
            ? 'Waiting for Hope Village admin approval before members can see this offer.'
            : 'New vouchers are submitted for admin approval and stay inactive until approved.',
        'Not Yet Valid' => 'This offer is scheduled and is not available yet.',
        'Expired' => 'This offer is past its validity date.',
        default => null,
    };

    $backUrl = $isEditing && $voucher
        ? route('merchant.vouchers.profile', $voucher->voucher_code)
        : route('merchant.vouchers.index');
    $backLabel = $isEditing && $voucher ? 'Back to Voucher' : 'Back to Vouchers';

    $discountValueHint = match ($discount_type) {
        'item' => 'Free Item always uses 100% off the item price.',
        'percentage' => 'Enter a percentage (e.g. 10 for 10% off).',
        'fixed' => 'Enter a fixed amount (e.g. 5.00 for SGD 5.00 off).',
        default => null,
    };

    $inputClass = 'w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-800 shadow-sm placeholder:text-slate-400 focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-500/20';
    $errorInputClass = 'border-red-400 focus:border-red-500 focus:ring-red-500/20';
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
                <a href="{{ $backUrl }}" class="inline-flex items-center gap-1.5 text-orange-200 hover:text-white text-sm font-medium transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                    </svg>
                    {{ $backLabel }}
                </a>
                <h1 class="text-white text-2xl sm:text-3xl font-bold tracking-tight mt-2">
                    {{ $isEditing && filled($name) ? $name : $pageTitle }}
                </h1>
                <div class="mt-2 flex flex-wrap items-center justify-between gap-3">
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
                        @if($isEditing && $voucher)
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium border border-white/20 bg-white/10 text-white/90 font-mono">
                                {{ $voucher->voucher_code }}
                            </span>
                        @endif
                    </div>
                    <p class="text-white/70 text-sm">{{ $pageSubtitle }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-xl md:max-w-2xl lg:max-w-6xl mx-auto px-4 sm:px-6 -mt-10 relative z-10">
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
                x-cloak
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-out duration-300"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
                role="alert"
            >
                {{ session('message') }}
            </div>
        @endif

        @if($isEditing && $statusHelp)
            <div @class([
                'mb-4 rounded-2xl border px-4 py-3 text-sm',
                'border-amber-200 bg-amber-50 text-amber-900' => $statusCategory === 'pending_approval',
                'border-sky-200 bg-sky-50 text-sky-900' => $statusCategory === 'not_yet_valid' || $statusLabel === 'Not Yet Valid',
                'border-slate-200 bg-slate-100 text-slate-700' => $statusCategory === 'expired',
                'border-emerald-200 bg-emerald-50 text-emerald-900' => $statusCategory === 'active',
            ])>
                <p class="font-semibold">{{ $statusLabel }}</p>
                <p class="mt-0.5 opacity-90">{{ $statusHelp }}</p>
            </div>
        @endif

        <form wire:submit="save" class="space-y-5">
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-5">
                {{-- Image --}}
                <section class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-5">
                    <h3 class="text-sm font-semibold text-slate-800">Voucher Image</h3>
                    <p class="mt-1 text-xs text-slate-500">Shown on member and merchant voucher cards. JPEG, PNG, or WebP up to 2MB.</p>

                    <div class="mt-4">
                        @if($existingVoucherImage && !$voucherImage)
                            <div class="relative">
                                <img
                                    src="{{ $existingVoucherImage }}"
                                    alt="Current voucher image"
                                    class="w-full aspect-[4/3] object-cover rounded-2xl border border-slate-200 bg-slate-100"
                                >
                                <button
                                    type="button"
                                    wire:click="removeVoucherImage"
                                    title="Remove Image"
                                    wire:confirm="Are you sure you want to remove the voucher image?"
                                    wire:loading.attr="disabled"
                                    wire:loading.class="opacity-50 cursor-not-allowed"
                                    class="absolute top-3 right-3 inline-flex items-center justify-center size-9 rounded-full border border-red-200 bg-white text-red-600 shadow-sm hover:bg-red-50 transition-colors"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        @elseif($voucherImage)
                            <img
                                src="{{ $voucherImage->temporaryUrl() }}"
                                alt="Preview"
                                class="w-full h-52 object-cover rounded-2xl border border-slate-200 bg-slate-100"
                            >
                        @else
                            <div class="w-full h-52 rounded-2xl border border-dashed border-slate-300 bg-slate-50 flex flex-col items-center justify-center text-slate-400">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-8 mb-2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z" />
                                </svg>
                                <p class="text-sm font-medium">No image yet</p>
                                <p class="text-xs mt-0.5">Upload a photo for this offer</p>
                            </div>
                        @endif

                        <label class="mt-4 flex flex-col gap-2">
                            <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Upload image</span>
                            <input
                                type="file"
                                id="voucherImage"
                                wire:model="voucherImage"
                                accept="image/jpeg,image/png,image/webp"
                                class="block w-full text-sm text-slate-500 file:mr-3 file:rounded-full file:border-0 file:bg-orange-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-orange-700 hover:file:bg-orange-100 cursor-pointer"
                            >
                        </label>
                        <div wire:loading wire:target="voucherImage" class="mt-2 text-xs text-slate-500">Uploading…</div>
                        @error('voucherImage') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </section>

                {{-- Basics + Offer --}}
                <div class="lg:col-span-3 space-y-5">
                    <section class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-5">
                        <h3 class="text-sm font-semibold text-slate-800">Basic Information</h3>
                        <p class="mt-1 text-xs text-slate-500">Name and description shown to members when browsing offers.</p>

                        <div class="mt-4 space-y-4">
                            <div>
                                <label for="name" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1.5">
                                    Voucher Name <span class="text-red-500">*</span>
                                </label>
                                <input
                                    placeholder="e.g. 10% off weekend meal"
                                    type="text"
                                    id="name"
                                    wire:model.blur="name"
                                    class="{{ $inputClass }} @error('name') {{ $errorInputClass }} @enderror"
                                >
                                @error('name') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="description" class="block text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1.5">
                                    Description
                                </label>
                                <textarea
                                    placeholder="Optional details about what is included or any conditions"
                                    id="description"
                                    wire:model.blur="description"
                                    rows="4"
                                    class="{{ $inputClass }} @error('description') {{ $errorInputClass }} @enderror"
                                ></textarea>
                                @error('description') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </section>

                    <section class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-5">
                        <h3 class="text-sm font-semibold text-slate-800">Offer Details</h3>
                        <p class="mt-1 text-xs text-slate-500">How the discount works and how many times it can be claimed.</p>

                        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="discount_type" class="block text-xs uppercase tracking-wide text-slate-500 mb-2">
                                    Deal Type <span class="text-red-500">*</span>
                                </label>
                                <select
                                    id="discount_type"
                                    wire:model.live="discount_type"
                                    class="w-full {{ $inputClass }} @error('discount_type') {{ $errorInputClass }} @enderror"
                                >
                                    @foreach($discountTypes as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('discount_type') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            @if($discount_type !== '')
                                <div>
                                    <label for="discount_value" class="block text-xs mb-2 uppercase tracking-wide text-slate-500">
                                        @if($discount_type === 'percentage')
                                            Discount Percentage <span class="text-red-500">*</span>
                                        @elseif($discount_type === 'item')
                                            <span class="inline-flex items-center gap-2">
                                                Item Value (%)
                                                <span class="normal-case tracking-normal rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-semibold text-slate-600">Read-Only</span>
                                                <span class="text-red-500">*</span>
                                            </span>
                                        @else
                                            Discount Amount ($) <span class="text-red-500">*</span>
                                        @endif
                                    </label>
                                    <input
                                        placeholder="{{ $discount_type === 'percentage' || $discount_type === 'item' ? '0' : '0.00' }}"
                                        type="number"
                                        id="discount_value"
                                        wire:model.blur="discount_value"
                                        step="0.01"
                                        min="0"
                                        {{ $discount_type === 'item' ? 'readonly' : '' }}
                                        class="{{ $inputClass }} @error('discount_value') {{ $errorInputClass }} @enderror {{ $discount_type === 'item' ? 'bg-slate-100 cursor-not-allowed' : '' }}"
                                    >
                                    @if($discountValueHint)
                                        <p class="mt-1.5 text-xs text-slate-500">{{ $discountValueHint }}</p>
                                    @endif
                                    @error('discount_value') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                            @endif

                            <div class="sm:col-span-2">
                                <label for="usage_limit" class="block text-xs mb-2 uppercase tracking-wide text-slate-500">
                                    Claim Limit
                                </label>
                                <input
                                    placeholder="Leave blank for unlimited claims"
                                    type="number"
                                    id="usage_limit"
                                    wire:model.blur="usage_limit"
                                    min="1"
                                    class="{{ $inputClass }} max-w-full @error('usage_limit') {{ $errorInputClass }} @enderror"
                                >
                                <p class="mt-1.5 text-xs text-slate-500">
                                    Optional. Total number of times members can claim this voucher.
                                    @if($isEditing && $voucher && $voucher->usage_limit)
                                        Currently {{ number_format((int) $voucher->usage_count) }} / {{ number_format((int) $voucher->usage_limit) }} claimed.
                                    @endif
                                </p>
                                @error('usage_limit') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-5 gap-5">
                <section class="lg:col-span-3 bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-5">
                    <h3 class="text-sm uppercase tracking-wide font-semibold text-slate-800">Validity Period</h3>
                    <p class="mt-1 text-xs text-slate-500">Optional schedule. Leave blank for no start or end date.</p>

                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="valid_from" class="block text-xs uppercase tracking-wide text-slate-500 mb-2">
                                Valid From
                            </label>
                            <div class="relative">
                                <input
                                    type="datetime-local"
                                    id="valid_from"
                                    wire:model.blur="valid_from"
                                    class="{{ $inputClass }} @error('valid_from') {{ $errorInputClass }} @enderror"
                                >
                            </div>
                            @error('valid_from') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="valid_until" class="block text-xs mb-2 uppercase tracking-wide text-slate-500">
                                Valid Until
                            </label>
                            <div class="relative">
                                <input
                                    type="datetime-local"
                                    id="valid_until"
                                    wire:model.blur="valid_until"
                                    class="{{ $inputClass }} @error('valid_until') {{ $errorInputClass }} @enderror"
                                >
                            </div>
                            @error('valid_until') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </section>

                <section class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-5">
                    <h3 class="text-sm uppercase tracking-wide font-semibold text-slate-800">Visibility</h3>
                    <p class="mt-1 text-xs text-slate-500">Choose which member types can see this voucher.</p>

                    <div class="mt-4 space-y-2 @error('visibilityToTypeOfWork') rounded-xl ring-1 ring-red-400 p-1 @enderror">
                        @foreach($typeOfWorkOptions ?? [] as $option)
                            <label class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 px-3 py-2.5 cursor-pointer transition-colors">
                                <input
                                    type="checkbox"
                                    wire:model.live="visibilityToTypeOfWork"
                                    value="{{ $option }}"
                                    class="size-4 rounded border-slate-300 text-orange-600 focus:ring-orange-500"
                                >
                                <span class="text-sm font-medium text-slate-800">{{ $option }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('visibilityToTypeOfWork') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror

                    <div class="mt-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500 mb-2">Visible to</p>
                        <div class="flex flex-wrap gap-1.5">
                            @if(empty($visibilityToTypeOfWork))
                                <span class="inline-flex rounded-full bg-orange-100 text-orange-600 px-2.5 py-1 text-xs font-medium">All members</span>
                                <p class="w-full text-xs text-slate-500 mt-1">No types selected — voucher is visible to all members.</p>
                            @else
                                @foreach($visibilityToTypeOfWork as $selected)
                                    <span class="inline-flex rounded-full bg-orange-100 text-orange-600 px-2.5 py-1 text-xs font-medium">{{ $selected }}</span>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </section>
            </div>

            @if(! $isEditing || ($voucher && ! $voucher->is_active))
                <div class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3.5">
                    <div class="flex items-start gap-3">
                        <span class="mt-0.5 size-8 shrink-0 rounded-xl bg-sky-100 text-sky-700 flex items-center justify-center">
                            <svg class="size-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-sky-900">Admin approval required</p>
                            <p class="mt-0.5 text-sm text-sky-800/90">
                                @if($isEditing)
                                    This voucher is still pending administrator approval. Members cannot claim it until it is approved. Saving your changes will not activate it.
                                @else
                                    Your voucher will be submitted for administrator approval. It stays inactive until Hope Village approves it.
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-5">
                <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3">
                    <a
                        href="{{ $backUrl }}"
                        class="inline-flex items-center justify-center rounded-full border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors"
                    >
                        Cancel
                    </a>
                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-70 cursor-wait"
                        class="inline-flex items-center justify-center gap-2 rounded-full bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold px-6 py-2.5 transition-colors"
                    >
                        <span wire:loading.remove wire:target="save">
                            {{ $isEditing ? 'Save Changes' : 'Create Voucher' }}
                        </span>
                        <span wire:loading wire:target="save">Saving…</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
