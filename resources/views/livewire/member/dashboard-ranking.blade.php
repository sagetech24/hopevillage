<div>
    @if($variant === 'header')
        <div class="">
            <span class="text-white text-md font-medium">Ranking</span>
            <p class="text-white text-2xl font-bold leading-tight">
                @if($this->rank > 0 && $this->memberCount > 0)
                    #{{ $this->rank }}
                    <span class="text-white/80 text-[16px] font-medium tracking-wider">of {{ number_format($this->memberCount) }}</span>
                @else
                    —
                @endif
            </p>
        </div>
    @else
        <div class="card bg-white shadow-md border border-base-300 rounded-2xl mb-6">
            <div class="card-body p-4">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-lg font-bold text-orange-500 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-4.5A3.375 3.375 0 0 0 12.75 10.5h-1.5A3.375 3.375 0 0 0 8 14.25v4.5m7.5-4.5V9.75a3.75 3.75 0 1 0-7.5 0v3.75" />
                        </svg>
                        Top Members
                    </h2>
                    @if($this->rank > 0)
                        <span class="text-xs font-semibold text-orange-500 bg-orange-50 border border-orange-200 px-2.5 py-1 rounded-full">
                            You: #{{ $this->rank }}
                        </span>
                    @endif
                </div>

                <ul class="divide-y divide-gray-100">
                    @forelse($this->topMembers as $index => $member)
                        @php
                            $isCurrent = auth()->id() === $member->id;
                        @endphp
                        <li class="flex items-center gap-3 py-2.5 {{ $isCurrent ? 'bg-orange-50 -mx-2 px-2 rounded-xl' : '' }}">
                            <div @class([
                                'shrink-0 w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold',
                                'bg-yellow-500 text-white' => $index === 0,
                                'bg-gray-300 text-gray-700' => $index === 1,
                                'bg-amber-700 text-white' => $index === 2,
                                'bg-orange-100 text-orange-600' => $index > 2,
                            ])>
                                {{ $index + 1 }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-base-content truncate">
                                    {{ $member->name }}
                                    @if($isCurrent)
                                        <span class="text-orange-500 font-medium">(You)</span>
                                    @endif
                                </p>
                            </div>
                            <div class="shrink-0 flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="text-sm font-semibold text-yellow-600">{{ number_format($member->total_points ?? 0) }}</span>
                            </div>
                        </li>
                    @empty
                        <li class="py-4 text-center text-sm text-base-content/60">No members yet</li>
                    @endforelse
                </ul>
            </div>
        </div>
    @endif
</div>
