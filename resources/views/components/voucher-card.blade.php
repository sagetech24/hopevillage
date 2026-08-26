@props([
    'voucher', // Voucher or AdminVoucher model
    'type' => 'merchant', // 'merchant' or 'admin'
    'showActions' => true, // Whether to show action buttons
])

@php
    $isAdminVoucher = $voucher instanceof \App\Models\AdminVoucher;
    $imageUrl = $voucher->getFirstMediaUrl('image');
    
    // Determine status
    $statusReason = $voucher->getStatusReason();
    $isExpired = $statusReason === 'Expired';

    if ($isAdminVoucher) {
        $isFull = $voucher->usage_limit && $voucher->usage_count >= $voucher->usage_limit;
        
        if (!$voucher->is_active) {
            $status = 'Inactive';
            $statusClass = 'bg-red-200 text-red-700';
        } elseif ($isExpired) {
            $status = 'Expired';
            $statusClass = 'bg-gray-200 text-gray-700';
        } elseif ($isFull) {
            $status = 'Full';
            $statusClass = 'bg-orange-200 text-orange-700';
        } elseif ($voucher->isValid()) {
            $status = 'Active';
            $statusClass = 'bg-green-200 text-green-700';
        } else {
            $status = $statusReason ?? 'Inactive';
            $statusClass = 'bg-yellow-200 text-yellow-700';
        }
    } else {
        if (!$voucher->is_active) {
            $status = 'Pending';
            $statusClass = 'bg-red-400 text-red-100';
        } elseif ($voucher->isValid()) {
            $status = 'Active';
            $statusClass = 'bg-green-200 text-green-700';
        } else {
            $statusReason = $voucher->getStatusReason();
            $status = $statusReason ?? 'Inactive';
            $statusClass = 'bg-gray-300 text-gray-700';
        }
    }
    
    // Determine color scheme for image/placeholder
    $isActive = !$isExpired && $voucher->is_active && $voucher->isValid();
    $isPending = !$isAdminVoucher && !$voucher->is_active;
    
    // Check if voucher is within validity date range
    $isWithinValidityRange = true;
    $now = now();
    
    if ($voucher->valid_from) {
        $validFrom = $voucher->valid_from instanceof \Carbon\Carbon 
            ? $voucher->valid_from 
            : \Carbon\Carbon::parse($voucher->valid_from);
        if ($now->lt($validFrom)) {
            $isWithinValidityRange = false; // Not yet started
        }
    }
    
    if ($voucher->valid_until) {
        $validUntil = $voucher->valid_until instanceof \Carbon\Carbon 
            ? $voucher->valid_until 
            : \Carbon\Carbon::parse($voucher->valid_until);
        if ($now->gt($validUntil)) {
            $isWithinValidityRange = false; // Already expired
        }
    }
    
    // Generate QR code if needed
    $qrCodeImageFull = null;
    if ($showActions) {
        $qrCodeService = app(\App\Services\QrCodeService::class);
        $qrCodeImageFull = $qrCodeService->generateQrCodeImage($voucher->voucher_code, 400);
    }
@endphp

<div class="w-full h-full flex flex-col bg-white overflow-hidden border-2 border-gray-400/30 shadow-md rounded-2xl group hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300">
    @if($imageUrl)
        <div class="relative overflow-hidden h-42 shrink-0">
            <h3 class="absolute inset-0 z-50 flex items-center justify-center leading-tight {{ $isExpired ? 'text-gray-800' : ($isActive ? 'text-green-100' : ($isPending ? 'text-red-300' : 'text-orange-400')) }} text-2xl font-bold drop-shadow-lg text-center">{{ $voucher->name }}</h3>
            @if($type === 'merchant')
                <span class="absolute top-4 left-0 flex items-center gap-1 {{ $statusClass }} rounded-r-lg px-2.5 py-1 z-50 shadow-lg" 
                        title="{{ $voucher->is_active ? ($voucher->isValid() ? 'Voucher is active and valid' : 'Voucher is active but ' . strtolower($status)) : ($isAdminVoucher ? 'Voucher is inactive' : 'Voucher is pending administrator approval') }}">
                    <span class="text-sm tracking-widest uppercase">{{ $statusReason }}</span>
                </span>
            @endif
            <div class="absolute inset-0 bg-linear-to-t z-0 {{ $isExpired ? 'from-gray-700/60 via-gray-200/50 to-gray-700/60' : ($isActive ? 'from-green-800/80 via-green-600/50 to-green-500/40' : ($isPending ? 'from-red-300/50 via-red-200/50 to-red-300/40' : 'from-orange-400/50 via-orange-300/50 to-orange-400/40')) }} transition-all duration-300 rounded-t-2xl"></div>
            <img src="{{ $imageUrl }}" alt="{{ $voucher->name }}" class="w-full h-42 object-cover object-center border border-gray-300 rounded-t-2xl transition-all duration-300 {{ $isExpired ? 'grayscale' : '' }}">
        </div>
    @else
        <div class="relative overflow-hidden flex items-start h-42 shrink-0 bg-linear-to-t {{ $isExpired ? 'from-gray-400/30 via-gray-200/50 to-gray-400/30 grayscale' : ($isActive ? 'from-green-800/40 via-green-100/50 to-green-800/60' : ($isPending ? 'from-red-300/30 via-red-100/50 to-red-300/30' : 'from-orange-400/30 via-orange-100/50 to-orange-400/30')) }}">
            <span class="absolute top-4 left-0 flex items-center gap-1 {{ $statusClass }} rounded-r-lg px-2.5 py-1 z-50 shadow-lg" 
                    title="{{ $voucher->is_active ? ($voucher->isValid() ? 'Voucher is active and valid' : 'Voucher is active but ' . strtolower($status)) : ($isAdminVoucher ? 'Voucher is inactive' : 'Voucher is pending administrator approval') }}">
                <span class="text-sm tracking-widest uppercase">{{ $status }}</span>
            </span>
            <div class="absolute inset-0 bg-linear-to-t {{ $isExpired ? 'from-gray-600/60 via-gray-300/20 to-gray-600/60' : ($isActive ? 'from-green-400/60 via-green-200/20 to-green-400/60' : ($isPending ? 'from-red-300/60 via-red-200/20 to-red-300/60' : 'from-gray-400/60 via-gray-200/10 to-gray-400/60')) }} transition-all duration-300 rounded-t-2xl"></div>
            <div class="w-full max-w-md h-42 rounded-t-2xl flex flex-col items-center justify-center">
                <p class="{{ $isExpired ? 'text-gray-700' : ($isActive ? 'text-green-600/70' : ($isPending ? 'text-red-400/70' : 'text-orange-400/70')) }} text-2xl font-bold drop-shadow-lg capitalize">{{ $voucher->name }}</p>
            </div>
        </div>
    @endif
    <div class="flex flex-col justify-between p-4 flex-1">
        <div class="w-full flex-1">
            <div class="flex flex-col items-start gap-1">
                <p class="text-base text-gray-600">{{ $voucher->description }}</p>

                {{-- show the merchant name --}}
                @if($type === 'merchant')
                    <div class="flex items-center gap-1">
                        <span class="text-sm text-gray-500">Merchant:</span>
                        <span class="text-sm text-gray-500">{{ $voucher->merchant?->name ?? 'Unknown Merchant' }}</span>
                    </div>
                @elseif($type === 'admin')
                    <div class="flex items-start gap-1">
                        <span class="text-sm text-gray-500">Merchants:</span>
                        <span class="text-sm text-gray-500">{{ $voucher->merchants->pluck('name')->filter()->join(', ') ?: 'None' }}</span>
                    </div>
                @endif
                
                <div class="flex justify-between items-center gap-1 w-full">
                    @if($voucher->usage_limit)
                        <span class="flex items-center gap-1 text-gray-600 mt-2 bg-gray-200 rounded-lg py-1 px-3 text-xs">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                            </svg>
                            <span class="text-xs">{{ $voucher->usage_count }}/{{ $voucher->usage_limit }}</span>
                        </span>
                    @endif
                </div>
                
                @if($showActions)
                    <div class="inline-flex flex-wrap justify-start items-start gap-2 mt-4 w-full">
                        @if($type === 'merchant' && !$isAdminVoucher)
                            {{-- Merchant Voucher Actions --}}
                            @if(!$voucher->is_active && $isWithinValidityRange)
                                <button 
                                    wire:click="toggleApproval('{{ $voucher->voucher_code }}')"
                                    wire:confirm="Are you sure you want to approve this voucher?"
                                    title="Approve Voucher"
                                    class="flex gap-1 items-center text-xs bg-green-500 hover:bg-green-600 px-2 py-1 rounded-full cursor-pointer active:-translate-y-0.5 active:bg-green-600 md:hover:-translate-y-0.5 text-white transition-all duration-300 touch-manipulation">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    Click to Approve
                                </button>
                            @endif
                            
                            <a 
                                href="{{ route('admin.vouchers.profile', $voucher->voucher_code) }}"
                                title="View Voucher Details"
                                class="flex gap-1 items-center text-xs bg-gray-100 hover:bg-gray-200 px-2 py-1 border cursor-pointer border-gray-300 rounded-full active:-translate-y-0.5 active:bg-gray-300 md:hover:-translate-y-0.5 text-gray-600 transition-all duration-200 touch-manipulation">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                                Details
                            </a>
                            <button 
                                wire:click="edit('{{ $voucher->voucher_code }}')"
                                title="Edit Voucher"
                                class="flex gap-1 items-center text-xs bg-gray-100 px-2 py-1 rounded-full active:-translate-y-0.5 border border-gray-300 active:bg-sky-100 active:text-sky-600 md:hover:-translate-y-0.5 hover:text-sky-600 cursor-pointer text-gray-600 transition-all duration-200 touch-manipulation">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                                </svg>
                                Edit
                            </button>
                            <button 
                                wire:confirm="Are you sure you want to delete this voucher?" 
                                title="Delete Voucher"
                                wire:click="delete('{{ $voucher->voucher_code }}')"
                                class="flex items-center text-xs bg-gray-100 px-2 py-1 rounded-full active:-translate-y-0.5 border border-gray-300 active:bg-red-100 active:text-red-600 md:hover:-translate-y-0.5 md:hover:text-red-600 cursor-pointer text-gray-600 transition-all duration-200 touch-manipulation">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                </svg>
                                Delete
                            </button>
                        @elseif($type === 'admin' && $isAdminVoucher)
                            {{-- Admin Voucher Actions --}}
                            <a 
                                href="{{ route('admin.admin-vouchers.profile', $voucher->voucher_code) }}"
                                title="View Voucher Details"
                                class="flex gap-1 items-center text-xs bg-orange-500 hover:bg-orange-600 px-2 py-1 rounded-full active:-translate-y-0.5 active:bg-orange-600 md:hover:-translate-y-0.5 text-white transition-all duration-200 touch-manipulation">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                                Details
                            </a>
                            <button 
                                wire:click="edit('{{ $voucher->voucher_code }}')"
                                title="Edit Voucher"
                                class="flex gap-1 items-center text-xs bg-gray-100 px-2 py-1 rounded-full active:-translate-y-0.5 border border-gray-300 active:bg-sky-100 active:text-sky-600 md:hover:-translate-y-0.5 md:hover:text-sky-600 cursor-pointer text-gray-600 transition-all duration-200 touch-manipulation">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                                </svg>
                                Edit
                            </button>
                            <button 
                                wire:confirm="Are you sure you want to delete this admin voucher?" 
                                title="Delete Voucher"
                                wire:click="delete('{{ $voucher->voucher_code }}')"
                                class="flex gap-1 items-center text-xs bg-gray-100 px-2 py-1 rounded-full active:-translate-y-0.5 border border-gray-300 active:bg-red-100 active:text-red-600 md:hover:-translate-y-0.5 md:hover:text-red-600 cursor-pointer text-gray-600 transition-all duration-200 touch-manipulation">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                </svg>
                                Delete
                            </button>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
    {{-- Footer: Validity date range --}}
    <div class="border-t border-gray-200 bg-gray-50 px-4 py-2 rounded-b-2xl shrink-0">
        <div class="flex items-center justify-start gap-1">
            <span class="text-xs font-bold text-gray-500">Validity:</span>
            <span class="text-xs text-gray-500">{{ $voucher->valid_from ? $voucher->valid_from->format('d M Y g:i') : 'N/A' }}</span>
            <span class="text-xs text-gray-600">-</span>
            <span class="text-xs text-gray-500">{{ $voucher->valid_until ? $voucher->valid_until->format('d M Y g:i A') : 'N/A' }}</span>
        </div>
    </div>
</div>
