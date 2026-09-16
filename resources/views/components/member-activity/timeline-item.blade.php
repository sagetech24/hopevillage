@props([
    'activity',
    'showVoidButton' => false,
])

@php
    $activityTime = $activity->activity_time ?? $activity->created_at;
    $isVoid = ($activity->metadata['status'] ?? null) === 'void';
    $typeName = $activity->activityType?->name ?? '';
    $typeLabel = $typeName === 'marketplace_redeem'
        ? 'Marketplace Purchase'
        : ($activity->activityType?->description ?: str_replace('_', ' ', $typeName ?: 'Some Activity'));
    $points = $activity->pointLog?->points;
    $voucherCode = $activity->metadata['voucher_code'] ?? null;
    $theme = match (true) {
        $isVoid => 'void',
        in_array($typeName, ['member_entry_location'], true) => 'entry',
        in_array($typeName, ['member_join_event', 'member_attend_event'], true) => 'event',
        in_array($typeName, ['member_claim_voucher', 'member_claim_admin_voucher', 'member_redeem_voucher', 'member_redeem_admin_voucher', 'admin_award_admin_voucher'], true) => 'voucher',
        in_array($typeName, ['marketplace_redeem'], true) => 'shop',
        in_array($typeName, ['marketplace_refund', 'admin_void_admin_voucher'], true) => 'refund',
        in_array($typeName, ['member_referral'], true) => 'people',
        in_array($typeName, ['account_verification', 'member_registration'], true) => 'check',
        default => 'default',
    };
    $dotClass = match ($theme) {
        'void' => 'bg-gray-300 text-gray-600 ring-gray-100',
        'entry' => 'bg-emerald-500 text-white ring-emerald-100',
        'event' => 'bg-blue-500 text-white ring-blue-100',
        'voucher' => 'bg-orange-500 text-white ring-orange-100',
        'shop' => 'bg-violet-500 text-white ring-violet-100',
        'refund' => 'bg-rose-500 text-white ring-rose-100',
        'people' => 'bg-teal-500 text-white ring-teal-100',
        'check' => 'bg-purple-500 text-white ring-purple-100',
        default => 'bg-orange-400 text-white ring-orange-100',
    };
    $badgeClass = match ($theme) {
        'void' => 'bg-gray-100 text-gray-500',
        'entry' => 'bg-emerald-50 text-emerald-700',
        'event' => 'bg-blue-50 text-blue-700',
        'voucher' => 'bg-orange-50 text-orange-700',
        'shop' => 'bg-violet-50 text-violet-700',
        'refund' => 'bg-rose-50 text-rose-700',
        'people' => 'bg-teal-50 text-teal-700',
        'check' => 'bg-purple-50 text-purple-700',
        default => 'bg-orange-50 text-orange-700',
    };
@endphp

<li {{ $attributes->merge(['class' => 'relative ms-6 pb-5 last:pb-1']) }}>
    <span class="absolute -start-[31px] top-3 flex size-7 items-center justify-center rounded-full ring-4 {{ $dotClass }}">
        @if($theme === 'entry')
            <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
            </svg>
        @elseif($theme === 'event')
            <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5A2.25 2.25 0 0 1 5.25 5.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25A2.25 2.25 0 0 1 18.75 21H5.25A2.25 2.25 0 0 1 3 18.75Z" />
            </svg>
        @elseif($theme === 'voucher')
            <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z" />
            </svg>
        @elseif($theme === 'shop')
            <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
            </svg>
        @elseif($theme === 'people')
            <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
            </svg>
        @elseif($theme === 'check')
            <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
        @elseif($theme === 'refund' || $theme === 'void')
            <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" />
            </svg>
        @else
            <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
            </svg>
        @endif
    </span>

    <article class="{{ $isVoid ? 'bg-gray-100/90' : 'bg-white' }} rounded-2xl border {{ $isVoid ? 'border-gray-200' : 'border-gray-100' }} shadow-sm hover:shadow-md transition-shadow duration-200 overflow-hidden">
        <div class="p-4">
            <div class="flex items-start justify-between gap-3">
                <time
                    @if($activityTime)
                        datetime="{{ $activityTime->toIso8601String() }}"
                        title="{{ $activityTime->format('M d, Y g:i A') }}"
                    @endif
                    class="shrink-0 justify-center flex flex-col items-center {{ $badgeClass }} rounded-lg px-2 py-4 min-w-[3.25rem]"
                >
                    <span class="block text-sm font-bold {{ $isVoid ? 'text-gray-400' : 'text-gray-800' }} leading-none">
                        {{ $activityTime?->format('g:i') ?? '-' }}
                    </span>
                    <span class="block text-xs font-semibold {{ $isVoid ? 'text-gray-400' : 'text-gray-800' }} leading-none mt-1">
                        {{ $activityTime?->format('A') ?? '' }}
                    </span>
                </time>

                <div class="min-w-0 flex-1">
                    <div class="flex justify-between items-center gap-2 flex-wrap">
                        <div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold capitalize {{ $badgeClass }}">
                                {{ $typeLabel }}
                            </span>
                            @if($points !== null && $points > 0)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700">
                                    +{{ $points }} {{ $points === 1 ? 'pt' : 'pts' }}
                                </span>
                            @elseif($points !== null && $points < 0)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-rose-50 text-rose-700">
                                    {{ $points }} {{ abs((int) $points) === 1 ? 'pt' : 'pts' }}
                                </span>
                            @endif
                        </div>
                        @if($showVoidButton)
                            <livewire:members.set-activity-void-button
                                :member-activity="$activity"
                                :key="'void-'.$activity->id"
                            />
                        @elseif($isVoid)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-gray-200 text-gray-500">Void</span>
                        @endif
                    </div>

                    @if($activity->description)
                        <p class="mt-2 text-sm font-medium {{ $isVoid ? 'text-gray-500' : 'text-gray-900' }} leading-snug">
                            {{ $activity->description }}
                        </p>
                    @endif

                    <ul class="mt-2.5 space-y-1">
                        @if($activity->location?->name)
                            <li class="flex items-center gap-1.5 text-xs text-gray-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                </svg>
                                <span class="truncate">{{ $activity->location->name }}@if($activity->amenity?->name) · {{ $activity->amenity->name }}@endif</span>
                            </li>
                        @elseif($activity->amenity?->name)
                            <li class="flex items-center gap-1.5 text-xs text-gray-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72M6.75 18h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z" />
                                </svg>
                                <span class="truncate">{{ $activity->amenity->name }}</span>
                            </li>
                        @endif

                        @if($activity->event?->title)
                            <li class="flex items-center gap-1.5 text-xs text-gray-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5A2.25 2.25 0 0 1 5.25 5.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25A2.25 2.25 0 0 1 18.75 21H5.25A2.25 2.25 0 0 1 3 18.75Z" />
                                </svg>
                                <span class="truncate">{{ $activity->event->title }}</span>
                            </li>
                        @endif

                        @if($voucherCode)
                            <li class="flex items-center gap-1.5 text-xs text-gray-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z" />
                                </svg>
                                <span class="font-mono tracking-wide">{{ $voucherCode }}</span>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </article>
</li>
