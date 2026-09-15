<x-slot name="header">
    @livewire('member.points-header')
</x-slot>

<div class="max-w-md mx-auto min-h-screen pb-24">
    <div class="px-4 mt-6">
        <div class="sticky top-0 z-20 -mx-4 px-4 py-3 bg-gray-50/95 backdrop-blur-sm">
            <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-4">
                <label class="relative block">
                    <span class="sr-only">Search activities</span>
                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 size-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M9.965 11.026a5 5 0 1 1 1.06-1.06l2.755 2.754a.75.75 0 1 1-1.06 1.06l-2.755-2.754ZM10.5 7a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0Z" clip-rule="evenodd" />
                    </svg>
                    <input
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        class="w-full pl-11 pr-4 py-2.5 border border-gray-200 rounded-full focus:ring-2 focus:ring-orange-500 focus:border-orange-500 placeholder:text-gray-400 text-sm"
                        placeholder="Search type, place, or voucher..."
                    />
                </label>

                <div class="mt-3 flex gap-1.5 overflow-x-auto scrollbar-hide -mx-1 px-1 pb-0.5" role="tablist" aria-label="Filter by date">
                    @php
                        $dateFilters = [
                            'all' => 'All time',
                            'today' => 'Today',
                            'week' => '7 days',
                            'month' => '30 days',
                        ];
                    @endphp
                    @foreach($dateFilters as $value => $label)
                        <button
                            type="button"
                            wire:click="setDateFilter('{{ $value }}')"
                            wire:loading.attr="disabled"
                            class="shrink-0 px-3 py-1.5 rounded-full text-xs font-semibold transition-all duration-200 {{ $dateFilter === $value ? 'bg-orange-500 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                <p class="text-xs text-base-content/60 mt-3">
                    @if($totalCount === 0)
                        No matching activities
                    @else
                        Showing {{ $loadedCount }} of {{ $totalCount }} {{ $totalCount === 1 ? 'activity' : 'activities' }}
                    @endif
                </p>
            </div>
        </div>

        <div wire:loading.flex wire:target="search,setDateFilter" class="items-center justify-center py-10">
            <span class="inline-block size-6 animate-spin rounded-full border-2 border-orange-200 border-t-orange-500" aria-hidden="true"></span>
            <span class="sr-only">Loading activities</span>
        </div>

        <div wire:loading.remove wire:target="search,setDateFilter" class="mt-2">
            @forelse($groupedActivities as $dateKey => $dayActivities)
                <section class="mb-8" wire:key="day-{{ $dateKey }}">
                    <div class="flex items-baseline justify-between gap-3 mb-3 px-1">
                        <h2 class="text-sm font-bold text-gray-900 tracking-tight">
                            {{ $this->dateHeading($dateKey) }}
                        </h2>
                        <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide">
                            {{ \Carbon\Carbon::parse($dateKey)->format('M d, Y') }}
                        </p>
                    </div>

                    <ol class="relative ms-3 border-s-2 border-orange-100">
                        @foreach($dayActivities as $activity)
                            @php
                                $isVoid = ($activity->metadata['status'] ?? null) === 'void';
                                $typeName = $activity->activityType->name ?? '';
                                $typeLabel = $typeName === 'marketplace_redeem'
                                    ? 'Marketplace Purchase'
                                    : ($activity->activityType->description ?: str_replace('_', ' ', $typeName ?: 'Activity'));
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
                            <li class="relative ms-6 pb-5 last:pb-1" wire:key="activity-{{ $activity->id }}">
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
                                                datetime="{{ $activity->activity_time->toIso8601String() }}"
                                                class="shrink-0 justify-center flex flex-col items-center {{$badgeClass}} rounded-lg px-2 py-4"
                                                title="{{ $activity->activity_time->format('M d, Y g:i A') }}"
                                            >
                                                <span class="block text-sm font-bold {{ $isVoid ? 'text-gray-400' : 'text-gray-800' }} leading-none">
                                                    {{ $activity->activity_time->format('g:i') }}
                                                </span>
                                                <span class="block text-md font-sans-serif font-semibold {{ $isVoid ? 'text-gray-400' : 'text-gray-800' }} leading-none">
                                                    {{ $activity->activity_time->format('A') }}
                                                </span>
                                                {{-- <span class="block text-[11px] text-gray-400 mt-1">
                                                    {{ $activity->activity_time->diffForHumans() }}
                                                </span> --}}
                                            </time>
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center gap-2 flex-wrap">
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
                                                    @if($isVoid)
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
                        @endforeach
                    </ol>
                </section>
            @empty
                <div class="bg-white rounded-2xl border border-dashed border-gray-200 px-6 py-12 text-center shadow-sm">
                    <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-full bg-orange-50 text-orange-400">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-7">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-1">No activities yet</h3>
                    <p class="text-sm text-gray-500 max-w-xs mx-auto">
                        @if($search || $dateFilter !== 'all')
                            Try a different search or date range to see more of your history.
                        @else
                            Visit a location, join an event, or claim a voucher and your timeline will fill in here.
                        @endif
                    </p>
                </div>
            @endforelse

            @if($hasMore)
                <div class="mt-2 mb-4 text-center">
                    <button
                        type="button"
                        wire:click="loadMore"
                        wire:loading.attr="disabled"
                        wire:target="loadMore"
                        class="inline-flex items-center justify-center gap-2 min-w-[160px] px-6 py-2.5 text-sm font-semibold text-orange-600 bg-white border border-orange-200 rounded-full shadow-sm hover:bg-orange-50 hover:border-orange-300 disabled:opacity-50 disabled:cursor-not-allowed transition"
                    >
                        <span wire:loading.remove wire:target="loadMore">Load more</span>
                        <span wire:loading wire:target="loadMore" class="inline-flex items-center gap-2">
                            <span class="inline-block size-4 animate-spin rounded-full border-2 border-orange-200 border-t-orange-500"></span>
                            Loading...
                        </span>
                    </button>
                    <p class="mt-2 text-[11px] text-gray-400">
                        {{ $totalCount - $loadedCount }} more {{ ($totalCount - $loadedCount) === 1 ? 'activity' : 'activities' }}
                    </p>
                </div>
            @elseif($loadedCount > 0)
                <p class="mb-6 text-center text-[11px] text-gray-400">
                    You're all caught up
                </p>
            @endif
        </div>
    </div>
</div>
