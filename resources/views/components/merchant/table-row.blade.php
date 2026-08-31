@props([
    'merchant',
])

@php
    $location = trim(implode(', ', array_filter([$merchant->city, $merchant->province, $merchant->postal_code])));
    $addressText = $merchant->address
        ? trim($merchant->address.($location !== '' ? ', '.$location : ''))
        : null;
    $initials = strtoupper(substr((string) $merchant->name, 0, 2));
@endphp

<tr {{ $attributes->merge(['class' => 'hover:bg-orange-50/40 transition-colors']) }}>
    <td class="px-5 py-4">
        <div class="flex items-start gap-3 min-w-0">
            <div class="size-11 rounded-lg overflow-hidden shrink-0 bg-orange-50 border border-orange-100 flex items-center justify-center">
                @if($merchant->logo_url)
                    <img src="{{ $merchant->logo_url }}" alt="{{ $merchant->name }}" class="size-full object-cover">
                @else
                    <span class="text-orange-500 font-bold text-sm">{{ $initials }}</span>
                @endif
            </div>
            <div class="min-w-0">
                <a
                    href="{{ route('admin.merchants.profile', $merchant->merchant_code) }}"
                    class="block text-sm font-semibold text-gray-900 hover:text-orange-600 truncate transition-colors"
                >
                    {{ $merchant->name }}
                </a>
                <p class="mt-0.5 text-xs text-gray-500 font-mono truncate">{{ $merchant->merchant_code }}</p>
                @if(! $merchant->is_active)
                    <div class="mt-2">
                        <x-merchant.approval-actions :merchant="$merchant" />
                    </div>
                @endif
            </div>
        </div>
    </td>
    <td class="px-5 py-4 whitespace-nowrap">
        <x-merchant.status-badge :merchant="$merchant" />
    </td>
    <td class="px-5 py-4">
        <div class="space-y-1 max-w-xs">
            <p class="text-sm {{ $addressText ? 'text-gray-700' : 'text-gray-400 italic' }} line-clamp-2">
                {{ $addressText ?? 'No address provided' }}
            </p>
            @if($merchant->email)
                <p class="flex items-center gap-1.5 text-xs text-gray-500 min-w-0">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-3.5 text-gray-400 shrink-0">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                    </svg>
                    <span class="truncate">{{ $merchant->email }}</span>
                </p>
            @endif
            @if($merchant->phone)
                <p class="flex items-center gap-1.5 text-xs text-gray-500">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-3.5 text-gray-400 shrink-0">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                    </svg>
                    <span>{{ $merchant->phone }}</span>
                </p>
            @endif
        </div>
    </td>
    <td class="px-5 py-4 whitespace-nowrap">
        <span class="text-sm font-medium text-gray-800">{{ $merchant->vouchers_count }}</span>
        <span class="text-xs text-gray-500">{{ (int) $merchant->vouchers_count === 1 ? 'voucher' : 'vouchers' }}</span>
    </td>
    <td class="px-5 py-4 text-right">
        <x-merchant.actions :merchant="$merchant" variant="table" />
    </td>
</tr>
