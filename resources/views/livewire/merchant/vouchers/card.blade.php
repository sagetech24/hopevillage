@php
    $statusCategory = $voucher->getDisplayStatusCategory();
    $status = $voucher->getDisplayStatusLabel();
    $statusClass = match ($statusCategory) {
        'active' => 'bg-green-100 text-green-800',
        'pending_approval' => 'bg-yellow-100 text-yellow-800',
        'inactive' => 'bg-gray-100 text-gray-800',
        'expired' => 'bg-red-100 text-red-800',
        default => 'bg-gray-100 text-gray-800',
    };
    $isActiveVoucher = $statusCategory === 'active';
@endphp

<x-merchant.voucher-ticket
    :voucher="$voucher"
    type="merchant"
    :merchant-label="$merchant->name"
    :status-label="$status"
    :status-class="$statusClass"
    @class([
        'ring-2 ring-green-400/60 shadow-md' => $isActiveVoucher,
    ])
>
    <x-slot:footer>
        @if($merchant->is_active)
            @if($isActiveVoucher)
                <div class="space-y-3 pt-3 border-t border-gray-100">
                    <button
                        type="button"
                        title="View QR Code: {{ $voucher->voucher_code }}"
                        @click="$dispatch('open-qr-modal', {
                            qrCode: @js($voucher->voucher_code),
                            qrImage: @js($qrCodeImageFull),
                            title: @js($voucher->name)
                        })"
                        class="w-full flex items-center justify-center gap-2 py-3 px-4 bg-orange-500 hover:bg-orange-600 active:bg-orange-700 text-white text-sm font-semibold rounded-xl shadow-sm transition-colors"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5 shrink-0">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 3.75 9.375v-4.5ZM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 13.5 9.375v-4.5Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.25 15.75h4.5M17.25 12.75v6" />
                        </svg>
                        View QR Code
                    </button>

                    <div class="grid grid-cols-3 gap-2">
                        <a
                            href="{{ route('merchant.vouchers.profile', $voucher->voucher_code) }}"
                            title="View voucher details"
                            class="flex flex-col items-center justify-center gap-1 py-2.5 px-2 rounded-xl border border-gray-200 bg-white hover:bg-orange-50 hover:border-orange-300 text-xs font-semibold text-gray-700 transition-colors"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4 text-orange-500">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                            Details
                        </a>
                        <button
                            type="button"
                            wire:click="edit"
                            title="Edit voucher"
                            class="flex flex-col items-center justify-center gap-1 py-2.5 px-2 rounded-xl border border-gray-200 bg-white hover:bg-sky-50 hover:border-sky-300 text-xs font-semibold text-gray-700 transition-colors"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4 text-sky-500">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                            </svg>
                            Edit
                        </button>
                        <button
                            type="button"
                            wire:confirm="Are you sure you want to delete this voucher?"
                            wire:click="delete"
                            title="Delete voucher"
                            class="flex flex-col items-center justify-center gap-1 py-2.5 px-2 rounded-xl border border-red-200 bg-white hover:bg-red-50 hover:border-red-300 text-xs font-semibold text-red-600 transition-colors"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                            Delete
                        </button>
                    </div>
                </div>
            @else
                <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-gray-100">
                    <a
                        href="{{ route('merchant.vouchers.profile', $voucher->voucher_code) }}"
                        title="View voucher details"
                        class="inline-flex items-center gap-1.5 py-2 px-3 rounded-lg border border-gray-200 bg-white hover:bg-gray-50 text-xs font-semibold text-gray-700 transition-colors"
                    >
                        Details
                    </a>
                    @if($statusCategory !== 'expired')
                        <button
                            type="button"
                            wire:click="edit"
                            title="Edit voucher"
                            class="inline-flex items-center gap-1.5 py-2 px-3 rounded-lg border border-gray-200 bg-white hover:bg-gray-50 text-xs font-semibold text-gray-700 transition-colors"
                        >
                            Edit
                        </button>
                    @endif
                    <button
                        type="button"
                        wire:confirm="Are you sure you want to delete this voucher?"
                        wire:click="delete"
                        title="Delete voucher"
                        class="inline-flex items-center gap-1.5 py-2 px-3 rounded-lg border border-red-200 bg-white hover:bg-red-50 text-xs font-semibold text-red-600 transition-colors"
                    >
                        Delete
                    </button>
                </div>
            @endif
        @else
            <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-gray-100">
                <span class="py-2 px-3 rounded-lg border border-gray-200 text-xs text-gray-400 cursor-not-allowed" title="Your merchant account is pending approval">
                    Edit unavailable
                </span>
            </div>
        @endif
    </x-slot:footer>
</x-merchant.voucher-ticket>
