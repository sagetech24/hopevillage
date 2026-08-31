@props([
    'merchant',
])

@php
    $location = trim(implode(', ', array_filter([$merchant->city, $merchant->province, $merchant->postal_code])));
    $addressText = $merchant->address
        ? trim($merchant->address.($location !== '' ? ', '.$location : ''))
        : null;
    $initials = strtoupper(substr((string) $merchant->name, 0, 2));
    $isPending = ! $merchant->is_active;
@endphp

<article {{ $attributes->merge([
    'class' => 'group flex flex-col h-full bg-white rounded-xl border overflow-hidden shadow-sm hover:shadow-md transition-all duration-200 '.($isPending
        ? 'border-amber-200 hover:border-amber-300'
        : 'border-gray-200 hover:border-orange-200'),
]) }}>
    <div class="flex gap-4 p-4 flex-1">
        <div class="size-20 rounded-xl overflow-hidden shrink-0 bg-orange-50 border border-orange-100 flex items-center justify-center">
            @if($merchant->logo_url)
                <img src="{{ $merchant->logo_url }}" alt="{{ $merchant->name }}" class="size-full object-cover">
            @else
                <span class="text-orange-500 text-xl font-bold tracking-wide">{{ $initials }}</span>
            @endif
        </div>

        <div class="min-w-0 flex-1">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <a
                        href="{{ route('admin.merchants.profile', $merchant->merchant_code) }}"
                        class="block text-base font-semibold text-gray-900 hover:text-orange-600 truncate transition-colors"
                    >
                        {{ $merchant->name }}
                    </a>
                    <p class="mt-0.5 inline-flex items-center gap-1 text-xs text-gray-500">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-3.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                        </svg>
                        <span class="font-mono">{{ $merchant->merchant_code }}</span>
                    </p>
                </div>
                <x-merchant.status-badge :merchant="$merchant" class="shrink-0" />
            </div>

            <ul class="mt-3 space-y-1.5 text-sm text-gray-600">
                <li class="flex items-start gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4 text-gray-400 shrink-0 mt-0.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                    </svg>
                    <span class="line-clamp-2 {{ $addressText ? '' : 'text-gray-400 italic' }}">
                        {{ $addressText ?? 'No address provided' }}
                    </span>
                </li>
                @if($merchant->email)
                    <li class="flex items-center gap-2 min-w-0">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4 text-gray-400 shrink-0">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                        </svg>
                        <span class="truncate">{{ $merchant->email }}</span>
                    </li>
                @endif
                @if($merchant->phone)
                    <li class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4 text-gray-400 shrink-0">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                        </svg>
                        <span>{{ $merchant->phone }}</span>
                    </li>
                @endif
            </ul>
        </div>
    </div>

    <div class="mt-auto px-4 py-3 border-t {{ $isPending ? 'border-amber-100 bg-amber-50/60' : 'border-gray-100 bg-gray-50/80' }} flex flex-wrap items-center justify-between gap-3">
        <span class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-600 bg-white border border-gray-200 rounded-full px-2.5 py-1">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-3.5 text-gray-400">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 11.25v8.25a1.5 1.5 0 0 1-1.5 1.5H5.25a1.5 1.5 0 0 1-1.5-1.5v-8.25M12 4.875A2.625 2.625 0 1 0 9.375 7.5H12m0-2.625V7.5m0-2.625A2.625 2.625 0 1 1 14.625 7.5H12m-8.25 3.75h16.5m-16.5 3.75h16.5" />
            </svg>
            {{ $merchant->vouchers_count }} {{ (int) $merchant->vouchers_count === 1 ? 'Voucher' : 'Vouchers' }}
        </span>

        <div class="flex flex-wrap items-center gap-3">
            @if($isPending)
                <x-merchant.approval-actions :merchant="$merchant" />
            @endif
            <x-merchant.actions :merchant="$merchant" variant="card" />
        </div>
    </div>
</article>
