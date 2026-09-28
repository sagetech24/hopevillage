<div>
    @if($variant === 'header')
        <div class="">
            <span class="text-white text-md font-medium">My Ranking</span>
            <p class="text-white text-2xl font-bold leading-tight text-right flex items-center gap-1">
                @if($this->rank > 0 && $this->memberCount > 0)
                    <svg fill="#ffffff" height="22px" width="22px" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="-25.6 -25.6 563.20 563.20" xml:space="preserve" stroke="#ffffff" stroke-width="25.6">
                        <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                        <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round" stroke="#f5f5f5" stroke-width="47.104"> 
                            <path d="M256,0C114.843,0,0,114.843,0,256s114.843,256,256,256s256-114.843,256-256S397.157,0,256,0z M256,493.037 C125.296,493.037,18.963,386.704,18.963,256S125.296,18.963,256,18.963S493.037,125.296,493.037,256S386.704,493.037,256,493.037z "></path> 
                            <path d="M427.333,209.815c-1.12-3.435-4.083-5.935-7.657-6.454l-107.222-15.584L264.5,90.62c-3.185-6.482-13.815-6.482-17,0 l-47.954,97.157L92.324,203.361c-3.574,0.518-6.537,3.018-7.657,6.454c-1.111,3.426-0.185,7.195,2.398,9.713l77.592,75.63 l-18.315,106.796c-0.611,3.556,0.852,7.148,3.768,9.269c2.935,2.13,6.806,2.407,9.991,0.722L256,361.528l95.898,50.417 c1.389,0.732,2.907,1.093,4.417,1.093c1.963,0,3.917-0.611,5.574-1.815c2.917-2.121,4.38-5.713,3.768-9.269l-18.315-106.796 l77.592-75.63C427.519,217.009,428.444,213.241,427.333,209.815z M330.537,285.065c-2.231,2.176-3.25,5.315-2.722,8.389 l15.907,92.768l-83.305-43.796c-1.389-0.732-2.898-1.093-4.417-1.093c-1.518,0-3.028,0.361-4.417,1.093l-83.305,43.796 l15.907-92.768c0.528-3.074-0.491-6.213-2.722-8.389l-67.398-65.704l93.139-13.537c3.093-0.444,5.759-2.389,7.139-5.185 L256,116.241l41.657,84.398c1.38,2.796,4.047,4.741,7.139,5.185l93.139,13.537L330.537,285.065z"></path> </g> </g> </g><g id="SVGRepo_iconCarrier"> <g> <g> <path d="M256,0C114.843,0,0,114.843,0,256s114.843,256,256,256s256-114.843,256-256S397.157,0,256,0z M256,493.037 C125.296,493.037,18.963,386.704,18.963,256S125.296,18.963,256,18.963S493.037,125.296,493.037,256S386.704,493.037,256,493.037z "></path> </g> </g> <g> <g> <path d="M427.333,209.815c-1.12-3.435-4.083-5.935-7.657-6.454l-107.222-15.584L264.5,90.62c-3.185-6.482-13.815-6.482-17,0 l-47.954,97.157L92.324,203.361c-3.574,0.518-6.537,3.018-7.657,6.454c-1.111,3.426-0.185,7.195,2.398,9.713l77.592,75.63 l-18.315,106.796c-0.611,3.556,0.852,7.148,3.768,9.269c2.935,2.13,6.806,2.407,9.991,0.722L256,361.528l95.898,50.417 c1.389,0.732,2.907,1.093,4.417,1.093c1.963,0,3.917-0.611,5.574-1.815c2.917-2.121,4.38-5.713,3.768-9.269l-18.315-106.796 l77.592-75.63C427.519,217.009,428.444,213.241,427.333,209.815z M330.537,285.065c-2.231,2.176-3.25,5.315-2.722,8.389 l15.907,92.768l-83.305-43.796c-1.389-0.732-2.898-1.093-4.417-1.093c-1.518,0-3.028,0.361-4.417,1.093l-83.305,43.796 l15.907-92.768c0.528-3.074-0.491-6.213-2.722-8.389l-67.398-65.704l93.139-13.537c3.093-0.444,5.759-2.389,7.139-5.185 L256,116.241l41.657,84.398c1.38,2.796,4.047,4.741,7.139,5.185l93.139,13.537L330.537,285.065z"></path> 
                        </g> 
                    </svg>
                    {{ $this->rank }}
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
