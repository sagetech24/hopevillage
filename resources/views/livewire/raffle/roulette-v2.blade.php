<div>
    @can('can_manage_lucky_draw')
        <x-slot name="header">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Luck Draw Spinning Wheel') }}
                </h2>
                <div class="flex gap-2">
                    <a href="{{ route('admin.dashboard') }}" class="text-sm text-gray-600 hover:text-gray-900">
                        ← Back
                    </a>
                </div>
            </div>
        </x-slot>

        <div class="py-10" x-data="{ isSpinning: false }" 
            @spin-wheel.window="
                if (window.RaffleWheel) {
                    isSpinning = true;
                    // Livewire 3 passes named parameters in $event.detail
                    const detail = $event.detail || {};
                    const index = detail.index !== undefined ? detail.index : ($event.detail || 0);
                    window.RaffleWheel.spinToItem(index, 4000);
                } else {
                    console.warn('RaffleWheel not loaded yet');
                }
            "
            @wheel-rest.window="isSpinning = false">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    <div>
                        <!-- Controls -->
                        <div class="bg-white shadow-md rounded-lg p-6 lg:col-span-1 max-h-full overflow-y-auto">
                            <h3 class="text-lg font-bold text-gray-900 mb-4">Select entries source</h3>
        
                            <div class="space-y-3">
                                <label class="flex items-center gap-2" x-bind:class="isSpinning ? 'opacity-50 cursor-not-allowed' : ''">
                                    <input type="radio" wire:model.live="source" value="range" class="text-indigo-600" x-bind:disabled="isSpinning">
                                    <span class="text-sm text-gray-800 font-semibold">Set a range of numbers</span>
                                </label>
                                {{-- <label class="flex items-center gap-2" x-bind:class="isSpinning ? 'opacity-50 cursor-not-allowed' : ''">
                                    <input type="radio" wire:model.live="source" value="members_fin" class="text-indigo-600" x-bind:disabled="isSpinning">
                                    <span class="text-sm text-gray-800 font-semibold">All members FIN/NIRC</span>
                                </label>
                                <label class="flex items-center gap-2" x-bind:class="isSpinning ? 'opacity-50 cursor-not-allowed' : ''">
                                    <input type="radio" wire:model.live="source" value="members_qr_code" class="text-indigo-600" x-bind:disabled="isSpinning">
                                    <span class="text-sm text-gray-800 font-semibold">QR Code of the members</span>
                                </label> --}}
                                <label class="flex items-start gap-2" x-bind:class="isSpinning ? 'opacity-50 cursor-not-allowed' : ''">
                                    <input type="radio" wire:model.live="source" value="event_attendees" class="text-indigo-600" x-bind:disabled="isSpinning">
                                    <span class="text-sm text-gray-800 font-semibold">Event attendees</span>
                                </label>
                            </div>
        
                            <div class="mt-5 space-y-4">
                                @if($source === 'range')
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="text-xs font-semibold text-gray-600">Start</label>
                                            <input type="number" wire:model.live="rangeStart" class="mt-1 w-full text-gray-700 rounded-lg border-gray-300 focus:border-orange-500 focus:ring-orange-500" x-bind:disabled="isSpinning">
                                            @error('rangeStart') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label class="text-xs font-semibold text-gray-600">End</label>
                                            <input type="number" wire:model.live="rangeEnd" class="mt-1 w-full text-gray-700 rounded-lg border-gray-300 focus:border-orange-500 focus:ring-orange-500" x-bind:disabled="isSpinning">
                                            @error('rangeEnd') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                        </div>
                                    </div>
                                @endif
        
                                @if($source === 'event_attendees')
                                    <div>
                                        <label class="text-xs font-semibold text-gray-600">Select Event</label>
                                        <select wire:model.live="selectedEventId" class="mt-1 w-full rounded-lg text-gray-800 border-gray-300 focus:border-orange-500 focus:ring-orange-500" x-bind:disabled="isSpinning">
                                            <option value="">-- choose --</option>
                                            @foreach($events as $e)
                                                <option value="{{ $e['id'] }}">{{ $e['title'] }} ({{ $e['attendee_count'] }} {{ $e['attendee_count'] == 1 ? 'attendee' : 'attendees' }})</option>
                                            @endforeach
                                        </select>
                                        @error('selectedEventId') <p class="text-xs text-red-600 mt-1">{{ 'Please select an event' }}</p> @enderror
                                    </div>
                                    @if($selectedEventId)
                                        <div class="mt-3">
                                            <label class="text-xs font-semibold text-gray-600 block mb-2">Type of work</label>
                                            <p class="text-xs text-gray-500 mb-2">Choose which types to include in the wheel. Default: All.</p>
                                            <div class="space-y-1.5">
                                                <label class="flex items-center gap-2 my-2" x-bind:class="isSpinning ? 'opacity-50 cursor-not-allowed' : ''">
                                                    <input type="checkbox" wire:model.live="typeOfWorkAll" class="rounded border-gray-300 text-indigo-600 focus:ring-orange-500 p-2" x-bind:disabled="isSpinning">
                                                    <span class="text-sm text-gray-800 font-medium text-nowrap">All</span>
                                                </label>
                                                @foreach(\App\Livewire\Raffle\RouletteV2::TYPE_OF_WORK_OPTIONS as $option)
                                                    @php
                                                        $isTop20 = $option === 'Highest points - Top 20%';
                                                        $othersDisabledByTop20 = "(\$wire.selectedTypeOfWork && \$wire.selectedTypeOfWork.includes('Highest points - Top 20%'))";
                                                    @endphp
                                                    <label 
                                                        class="flex items-center gap-2 my-2" 
                                                        x-bind:class="{{ $isTop20 ? "isSpinning || \$wire.typeOfWorkAll" : "isSpinning || \$wire.typeOfWorkAll || " . $othersDisabledByTop20 }} ? 'opacity-50 cursor-not-allowed' : ''"
                                                    >
                                                        <input 
                                                            type="checkbox" 
                                                            wire:model.live="selectedTypeOfWork" 
                                                            value="{{ $option }}" 
                                                            class="rounded border-gray-300 text-indigo-600 focus:ring-orange-500 p-2" 
                                                            x-bind:disabled="{{ $isTop20 ? "isSpinning || \$wire.typeOfWorkAll" : "isSpinning || \$wire.typeOfWorkAll || " . $othersDisabledByTop20 }}"
                                                        >
                                                        <span 
                                                            class="text-sm text-nowrap capitalize truncate" 
                                                            x-bind:class="{{ $isTop20 ? "\$wire.typeOfWorkAll" : "\$wire.typeOfWorkAll || " . $othersDisabledByTop20 }} ? 'opacity-60 text-gray-400' : 'opacity-100 text-gray-700'"
                                                        >
                                                            {{ $option }}
                                                        </span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                @endif
        
                                <br />
        
                                <div class="flex">
                                    <button 
                                        type="button" 
                                        wire:click="spin" 
                                        x-bind:disabled="isSpinning"
                                        x-bind:class="isSpinning ? 'opacity-50 cursor-not-allowed' : 'hover:bg-orange-600'"
                                        class="flex-1 bg-orange-500 cursor-pointer rounded-full text-white font-semibold text-sm py-2 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        <span x-text="isSpinning ? 'Spinning...' : 'Start Raffle'">Start Raffle</span>
                                    </button>
                                </div>
        
                                @error('entries')
                                    <p class="text-xs text-red-600">{{ $message }}</p>
                                @enderror
        
                            </div>
                        </div>
                        <br />
                        <div class="bg-white shadow-md rounded-lg p-6 max-h-full overflow-y-auto">
                            <div class="text-sm text-gray-700 mb-3 space-y-1">
                                <div>
                                    <span class="font-semibold">Total Entries:</span> {{ count($entries) }}
                                </div>
                            </div>
                            
                            @if(count($entries) > 0)
                                <div class="mt-4">
                                    <h4 class="text-sm font-bold text-gray-900 mb-2">Entries Preview</h4>
                                    <div class="max-h-96 overflow-y-auto border border-gray-200 rounded-lg">
                                        <table class="w-full text-sm text-gray-700">
                                            <thead class="bg-gray-50 sticky top-0">
                                                <tr>
                                                    {{-- <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 border-b border-gray-200">#</th> --}}
                                                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 border-b border-gray-200">
                                                        @if($source === 'range')
                                                            Number
                                                        @elseif($source === 'members_fin')
                                                            FIN/NIRC
                                                        @elseif($source === 'members_qr_code')
                                                            Member QR Code
                                                        @elseif($source === 'event_attendees')
                                                            Event Attendee (QR Code)
                                                        @endif
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                @foreach(array_slice($entries, 0, 50) as $index => $item)
                                                    @php
                                                        // Ensure item is a string
                                                        $itemValue = is_string($item) ? $item : (string) $item;
                                                        // Check if this entry is a winner
                                                        $winnerInfo = collect($winners)->firstWhere('value', $itemValue);

                                                        $top20Label = \App\Livewire\Raffle\RouletteV2::TYPE_OF_WORK_OPTIONS[0];
                                                        $isTop20Mode = $source === 'event_attendees'
                                                            && in_array($top20Label, $selectedTypeOfWork ?? [], true);
                                                    @endphp
                                                    <tr class="hover:bg-gray-50 {{ $winnerInfo ? 'bg-gray-100' : '' }}">
                                                        {{-- <td class="px-3 py-1.5 text-xs text-gray-500">{{ $index + 1 }}</td> --}}
                                                        <td class="px-3 py-1.5 text-xs text-gray-700">
                                                            <div class="flex items-center gap-2">
                                                                @if($isTop20Mode)
                                                                    <span>
                                                                        {{ $itemValue }}
                                                                        @if(!empty($top20PointsByQr[$itemValue] ?? null))
                                                                            ({{ $top20PointsByQr[$itemValue] }} pts)
                                                                        @endif
                                                                    </span>
                                                                @else
                                                                    <span>{{ $itemValue }}</span>
                                                                @endif

                                                                @if($winnerInfo)
                                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold {{  $winnerInfo['place'] == 1 ? 'text-green-500 bg-green-100' : ($winnerInfo['place'] == 2 ? 'text-teal-500 bg-teal-100' : ($winnerInfo['place'] == 3 ? 'text-yellow-500 bg-yellow-100' : 'text-orange-500 bg-orange-100'))}}">
                                                                        {{ $winnerInfo['place'] ?? '' }}{{ isset($winnerInfo['place']) && $winnerInfo['place'] == 1 ? 'st' : (isset($winnerInfo['place']) && $winnerInfo['place'] == 2 ? 'nd' : (isset($winnerInfo['place']) && $winnerInfo['place'] == 3 ? 'rd' : 'th')) }} Place
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                                @if(count($entries) > 50)
                                                    <tr>
                                                        <td colspan="2" class="px-3 py-2 text-xs text-gray-500 text-center italic">+{{ count($entries) - 50 }} more entries</td>
                                                    </tr>
                                                @endif
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Wheel -->
                    <div class="lg:col-span-3">
                        @if(count($entries) > 0)
                            <div class="flex flex-col items-center">
                                <div class="relative w-full max-w-2xl" style="aspect-ratio: 1;">
                                    <!-- Pointer -->
                                    <div class="absolute top-2 left-1/2 -translate-x-1/2 z-20">
                                        <div class="w-0 h-0 border-l-12 border-r-12 border-t-20 border-l-transparent border-r-transparent border-t-orange-500"></div>
                                    </div>

                                    <!-- Wheel Container -->
                                    <div 
                                        id="wheel-container" 
                                        wire:ignore
                                        class="absolute inset-0 w-full h-full"
                                        x-init="initRaffleWheelContainer($wire, $watch, $dispatch)"
                                    ></div>

                                    <!-- Center Circle -->
                                    <div class="absolute inset-0 z-20 flex items-center justify-center pointer-events-none">
                                        <div class="w-24 h-24 rounded-full bg-orange-200 border-2 border-[#af7d0f] shadow-lg flex items-center justify-center">
                                            <span class="text-sm font-bold text-gray-700" x-text="isSpinning ? 'Spinning...' : 'Ready'">Ready</span>
                                        </div>
                                    </div>
                                </div>

                                <p class="mt-6 text-xs text-gray-500 text-center max-w-xl mx-auto">
                                    Tip: For "Event attendees", only registrations with status <span class="font-mono">attended</span> are included.
                                    Confirmed winners are stored in this browser and excluded from later draws for this event until <span class="font-semibold">event end</span>.
                                </p>
                            </div>
                        @else
                            <!-- Placeholder when no items loaded -->
                            <div class="flex flex-col items-center justify-center h-full min-h-[400px]">
                                <div class="text-center">
                                    <svg class="w-24 h-24 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <p class="text-lg font-semibold text-gray-600 mb-2">No items loaded</p>
                                    <p class="text-sm text-gray-500">Please select entry option and click Load</p>
                                </div>
                            </div>
                        @endif

                        @if($source === 'event_attendees' && $selectedEventId)
                            <div
                                class="mt-6"
                                wire:key="stored-raffle-winners-{{ $selectedEventId }}"
                                x-data="{
                                    items: [],
                                    load() {
                                        if ($wire.source !== 'event_attendees' || !$wire.selectedEventId) {
                                            this.items = [];
                                            return;
                                        }
                                        this.items = hopeVillageReadStoredWinnersFromLocalStorage($wire.selectedEventId);
                                    },
                                    clearStoredWinners() {
                                        const eventId = $wire.selectedEventId;
                                        if (!eventId) return;

                                        const ok = window.confirm(
                                            'Reset Winners will CLEAR the locally saved winners for this event on this browser. Continue?'
                                        );
                                        if (!ok) return;

                                        try {
                                            const key = hopeVillageRaffleStorageKey(eventId);
                                            localStorage.removeItem(key);
                                        } catch (e) {}

                                        // Clear the current session wheel exclusions too.
                                        $wire.clearWinners();
                                        $wire.syncPersistedExcludedFromClient([]);

                                        this.items = [];
                                        hopeVillageRaffleStoredWinnersRefresh();
                                    }
                                }"
                                x-init="load()"
                                @raffle-stored-winners-refresh.window="load()"
                            >
                                <div class="bg-white shadow-md rounded-lg p-6" x-show="items.length > 0">
                                    <div class="flex items-center justify-between">
                                        <h3 class="text-lg font-bold text-gray-900 mb-4">
                                            Raffle Winners (<span x-text="items.length"></span>)
                                        </h3>
                                        <button
                                            type="button"
                                            x-show="items.length > 0"
                                            @click="clearStoredWinners()"
                                            class="mt-2 text-sm text-red-500 hover:text-red-800 transition-all duration-300 cursor-pointer bg-red-50 border border-red-100 px-2 py-1 rounded-lg"
                                        >
                                            Reset Winners
                                        </button>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-2 mb-4">
                                        List shows confirmed winners saved in this browser for this event. Reset clears the current session wheel only; stored winners stay excluded until event end or site data is cleared.
                                    </p>
                                    <div class="space-y-2 text-xs">
                                        <template x-for="(row, idx) in items" :key="(row.qr_code || '') + '-' + (row.winner)">
                                            <div class="flex items-start gap-4 border-b border-gray-200 pb-2">
                                                <span
                                                    class="text-gray-700 text-md font-semibold flex items-center gap-2 border rounded-lg px-2 py-1 shrink-0"
                                                    :class="{
                                                        'border-green-400 bg-green-100': row.winner == 1,
                                                        'border-teal-400 bg-teal-100': row.winner == 2,
                                                        'border-yellow-400 bg-yellow-100': row.winner == 3,
                                                        'border-orange-300 bg-orange-100': row.winner > 3
                                                    }"
                                                >
                                                    <svg height="20px" width="20px" version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" xml:space="preserve" fill="#000000">
                                                        <g stroke-width="0"></g><g stroke-linecap="round" stroke-linejoin="round"></g><g>
                                                            <path style="fill:#FFEA8A;" d="M512,120.242c0-17.11-13.92-31.03-31.03-31.03c-17.11,0-31.03,13.92-31.03,31.03 c0,8.555,3.48,16.313,9.098,21.931l-94.431,94.433l-90.909-90.909c8.048-5.612,13.334-14.921,13.334-25.454 c0-17.11-13.92-31.03-31.03-31.03s-31.03,13.92-31.03,31.03c0,10.533,5.286,19.842,13.334,25.454l-90.909,90.909l-94.431-94.433 c5.618-5.618,9.098-13.376,9.098-21.931c0-17.11-13.92-31.03-31.03-31.03S0,103.132,0,120.242c0,14.428,9.911,26.551,23.273,30.009 v272.536h465.455V150.252C502.089,146.794,512,134.67,512,120.242z"></path>
                                                            <path style="fill:#FFDB2D;" d="M480.97,89.212c-17.11,0-31.03,13.92-31.03,31.03c0,8.555,3.48,16.313,9.098,21.931l-94.431,94.433 l-90.909-90.909c8.048-5.612,13.334-14.921,13.334-25.454c0-17.11-13.92-31.03-31.03-31.03v333.576h232.727V150.252 C502.089,146.794,512,134.67,512,120.242C512,103.132,498.08,89.212,480.97,89.212z"></path>
                                                        </g>
                                                    </svg>
                                                    <span x-text="'Winner #' + row.winner"></span>
                                                </span>
                                                <div class="min-w-0">
                                                    <span class="text-gray-600 text-md break-all font-mono" x-text="row.qr_code"></span>
                                                    <div class="text-gray-700 mt-1" x-show="row.name">
                                                        <div class="text-xl font-bold" x-text="row.name"></div>
                                                        <div class="text-gray-500 text-sm" x-show="row.email_address" x-text="row.email_address"></div>
                                                        <div class="text-gray-500 text-sm" x-show="row.whatsapp_number" x-text="row.whatsapp_number"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        @elseif(count($winners) > 0)
                            <div class="mt-6">
                                <div class="bg-white shadow-md rounded-lg p-6">
                                    <div class="flex items-center justify-between">
                                        <h3 class="text-lg font-bold text-gray-900 mb-4">Raffle Winners ({{ count($winners) }})</h3>
                                        @if(count($winners) > 0)
                                            <button 
                                                type="button" 
                                                wire:click="clearWinners" 
                                                class="mt-2 text-sm text-red-500 hover:text-red-800 transition-all duration-300 cursor-pointer bg-red-50 border border-red-100 px-2 py-1 rounded-lg"
                                            >
                                                Reset Winners
                                            </button>
                                        @endif
                                    </div>

                                    <div class="space-y-2 text-xs">
                                        @foreach($winners as $winner)

                                            @php
                                                $value = is_string($winner['value'] ?? null) ? $winner['value'] : (string) ($winner['value'] ?? '');
                                                $member = $winner['member'] ?? null;
                                            @endphp

                                            <div class="flex items-start gap-4 border-b border-gray-200 pb-2">
                                                <span class="text-gray-700 text-md font-semibold flex items-center gap-2 border {{  $winner['place'] == 1 ? 'border-green-400 bg-green-100' : ($winner['place'] == 2 ? 'border-teal-400 bg-teal-100' : ($winner['place'] == 3 ? 'border-yellow-400 bg-yellow-100' : 'border-orange-300 bg-orange-100'))}} rounded-lg px-2 py-1">
                                                    <svg height="20px" width="20px" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 512 512" xml:space="preserve" fill="#000000">
                                                        <g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path style="fill:#FFEA8A;" d="M512,120.242c0-17.11-13.92-31.03-31.03-31.03c-17.11,0-31.03,13.92-31.03,31.03 c0,8.555,3.48,16.313,9.098,21.931l-94.431,94.433l-90.909-90.909c8.048-5.612,13.334-14.921,13.334-25.454 c0-17.11-13.92-31.03-31.03-31.03s-31.03,13.92-31.03,31.03c0,10.533,5.286,19.842,13.334,25.454l-90.909,90.909l-94.431-94.433 c5.618-5.618,9.098-13.376,9.098-21.931c0-17.11-13.92-31.03-31.03-31.03S0,103.132,0,120.242c0,14.428,9.911,26.551,23.273,30.009 v272.536h465.455V150.252C502.089,146.794,512,134.67,512,120.242z"></path> <path style="fill:#FFDB2D;" d="M480.97,89.212c-17.11,0-31.03,13.92-31.03,31.03c0,8.555,3.48,16.313,9.098,21.931l-94.431,94.433 l-90.909-90.909c8.048-5.612,13.334-14.921,13.334-25.454c0-17.11-13.92-31.03-31.03-31.03v333.576h232.727V150.252 C502.089,146.794,512,134.67,512,120.242C512,103.132,498.08,89.212,480.97,89.212z"></path> </g>
                                                    </svg>
                                                    {{ 'Winner #' . $winner['place'] }}
                                                </span>
                                                <div>
                                                    @if(!in_array($source, ['members_fin', 'members_qr_code', 'event_attendees']))
                                                        <span class="text-gray-600 text-md break-all text-xl">{{ $winner['value'] }}</span>
                                                    @else
                                                        <span class="text-gray-600 text-md break-all">{{ $winner['value'] }}</span>
                                                    @endif
                                                    @if($winner['member'] && isset($winner['member']['name']))
                                                        <div class="text-gray-700 mt-1">
                                                            <div class="text-xl font-bold">{{ $member['name'] }}</div>
                                                            @if(isset($member['email']) && $member['email'])
                                                                <div class="text-gray-500 text-sm">{{ $member['email'] }}</div>
                                                            @endif
                                                            {{-- @if(isset($member['whatsapp_number']) && $member['whatsapp_number'])
                                                                <div class="text-gray-500 text-sm">{{ $member['whatsapp_number'] }}</div>
                                                            @endif --}}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>

        <!-- Winner Modal -->
        <dialog 
            id="winner-modal"
            x-data="{ 
                openModal() { 
                    const modal = document.getElementById('winner-modal');
                    if (modal) {
                        modal.showModal();
                    }
                },
                closeModal() { 
                    const modal = document.getElementById('winner-modal');
                    if (modal) {
                        modal.close();
                        setTimeout(() => {
                            $wire.updateWheelAfterModalClose(false);
                        }, 100);
                    }
                },
                closeModalConfirm() {
                    const modal = document.getElementById('winner-modal');
                    if (modal) {
                        modal.close();
                        setTimeout(() => {
                            $wire.updateWheelAfterModalClose(true);
                        }, 100);
                    }
                },
                closeModalWithoutUpdate() { 
                    const modal = document.getElementById('winner-modal');
                    if (modal) {
                        modal.close();
                    }
                }
            }"
            x-init="
                const modal = document.getElementById('winner-modal');
                
                // Listen for Livewire event
                Livewire.on('show-winner-modal', () => {
                    // Small delay to ensure DOM is ready
                    setTimeout(() => {
                        openModal();
                    }, 200);
                });
                
                // Also listen for browser event as fallback
                window.addEventListener('show-winner-modal-browser', () => {
                    setTimeout(() => {
                        openModal();
                    }, 200);
                });
                
            "
            class="modal"
            @click.away="closeModal()"
        >
            <div class="modal-box text-center bg-white rounded-lg min-w-5xl">
                <h3 class="font-bold text-2xl mb-4 text-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 mx-auto mb-3 text-yellow-500">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
                    </svg>
                    🎉 Congratulations! 🎉
                </h3>
                @if($latestWinner)
                    @php
                        $winnerData = $latestWinner;
                        $member = $winnerData['member'] ?? null;
                    @endphp
                    <div class="py-6">
                        <p class="text-4xl font-bold text-primary break-all px-4 mb-6">
                            {{ 'Winner #' . $winnerData['place'] }}
                            {{-- {{ $winnerData['place'] ?? '' }}{{ isset($winnerData['place']) && $winnerData['place'] == 1 ? 'st' : (isset($winnerData['place']) && $winnerData['place'] == 2 ? 'nd' : (isset($winnerData['place']) && $winnerData['place'] == 3 ? 'rd' : 'th')) }} Place --}}
                        </p>
                        
                        <p class="text-xl text-gray-600 mb-3">The winner is:</p>
                        <p class="text-8xl max-w-2xl mx-auto font-bold text-slate-800 break-all px-4 mb-6">
                            {{ $member['name'] ?? $winnerData['value'] }}
                        </p>
                        
                        @if($member)
                            <div class="mt-6 p-4 bg-gray-50 rounded-lg text-left">
                                <div class="space-y-2 text-sm max-w-2xl mx-auto">
                                    <h4 class="font-bold text-gray-900 mb-3 text-sm">Member Details:</h4>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600 font-medium">Name:</span>
                                        <span class="text-gray-900 text-lg font-semibold">{{ $member['name'] ?? 'N/A' }}</span>
                                    </div>
                                    @if(isset($member['email']) && $member['email'])
                                        <div class="flex justify-between">
                                            <span class="text-gray-600 font-medium">Email:</span>
                                            <span class="text-gray-900">{{ $member['email'] }}</span>
                                        </div>
                                    @endif
                                    {{-- @if(isset($member['whatsapp_number']) && $member['whatsapp_number'])
                                        <div class="flex justify-between">
                                            <span class="text-gray-600 font-medium">WhatsApp:</span>
                                            <span class="text-gray-900">{{ $member['whatsapp_number'] }}</span>
                                        </div>
                                    @endif --}}
                                    @if(isset($member['fin']) && $member['fin'])
                                        <div class="flex justify-between">
                                            <span class="text-gray-600 font-medium">FIN/NIRC:</span>
                                            <span class="text-gray-900 font-mono">{{ $member['fin'] }}</span>
                                        </div>
                                    @endif
                                    @if(isset($member['qr_code']) && $member['qr_code'])
                                        <div class="flex justify-between">
                                            <span class="text-gray-600 font-medium">QR Code:</span>
                                            <span class="text-gray-900 font-mono">{{ $member['qr_code'] }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @else
                            <p class="text-sm text-gray-500 italic mt-4">No member details available for this entry.</p>
                        @endif
                    </div>
                @elseif($winner)
                    <div class="py-6">
                        <p class="text-sm text-gray-600 mb-3">The winner is:</p>
                        <p class="text-4xl font-bold text-primary break-all px-4">
                            {{ is_string($winner) ? $winner : (is_array($winner) ? ($winner['value'] ?? '') : (string) $winner) }}
                        </p>
                    </div>
                @else
                    <p class="text-gray-600 py-4">No winner selected.</p>
                @endif
                <div class="modal-action justify-center gap-3 max-w-2xl mx-auto">
                    <button 
                        wire:click="reSpin"
                        @click="closeModalWithoutUpdate()"
                        class="bg-blue-500 hover:bg-blue-600 transition-all duration-300 cursor-pointer text-lg flex-1 text-white px-4 py-2 rounded-full font-bold border-none"
                    >
                        Redraw
                    </button>
                    <button 
                        type="button"
                        @click="closeModalConfirm()"
                        class="bg-orange-500 hover:bg-orange-600 transition-all duration-300 cursor-pointer text-lg flex-1 text-white px-4 py-2 rounded-full font-bold border-none"
                    >
                        Confirm Winner
                    </button>
                </div>
            </div>
            <form method="dialog" class="modal-backdrop">
                <button>close</button>
            </form>
        </dialog>
    @else
        @php abort(403, 'Unauthorized.'); @endphp
    @endcan
</div>

<script>
    function hopeVillageRaffleStorageKey(eventId) {
        return 'hopevillage_event_raffle_winners_' + eventId;
    }

    /** @returns {Array<{winner:number,name:string,qr_code:string,whatsapp_number:string,email_address:string}>} */
    function hopeVillageReadStoredWinnersFromLocalStorage(eventId) {
        if (!eventId) {
            return [];
        }
        const key = hopeVillageRaffleStorageKey(eventId);
        try {
            const raw = localStorage.getItem(key);
            if (!raw) {
                return [];
            }
            const data = JSON.parse(raw);
            const eventEnd = data && data.event_end ? new Date(data.event_end) : null;
            if (eventEnd && !isNaN(eventEnd.getTime()) && Date.now() > eventEnd.getTime()) {
                return [];
            }
            return Array.isArray(data.winners) ? data.winners : [];
        } catch (e) {
            return [];
        }
    }

    function hopeVillageRaffleStoredWinnersRefresh() {
        window.dispatchEvent(new CustomEvent('raffle-stored-winners-refresh'));
    }

    function hopeVillageSyncExcludedFromLocalStorage($wire) {
        const eventId = $wire.selectedEventId;
        if (!eventId) {
            return Promise.resolve();
        }
        const key = hopeVillageRaffleStorageKey(eventId);
        let raw = null;
        try {
            raw = localStorage.getItem(key);
        } catch (e) {
            return $wire.syncPersistedExcludedFromClient([]);
        }
        if (!raw) {
            return $wire.syncPersistedExcludedFromClient([]);
        }
        let data;
        try {
            data = JSON.parse(raw);
        } catch (e) {
            return $wire.syncPersistedExcludedFromClient([]);
        }
        const eventEnd = data && data.event_end ? new Date(data.event_end) : null;
        if (eventEnd && !isNaN(eventEnd.getTime()) && Date.now() > eventEnd.getTime()) {
            try {
                localStorage.removeItem(key);
            } catch (e2) {}
            return $wire.syncPersistedExcludedFromClient([]);
        }
        const winners = Array.isArray(data.winners) ? data.winners : [];
        const qrs = winners
            .map(function (w) {
                return String(w.qr_code || '').toUpperCase().trim();
            })
            .filter(Boolean);
        return $wire.syncPersistedExcludedFromClient(qrs);
    }

    function hopeVillagePersistWinnerToLocalStorage(payload) {
        const eventId = payload.eventId;
        if (!eventId) {
            return;
        }
        const key = hopeVillageRaffleStorageKey(eventId);
        let data = { event_end: payload.eventEnd || '', event_qr_code: payload.eventQrCode || '', winners: [] };
        try {
            const existing = localStorage.getItem(key);
            if (existing) {
                const parsed = JSON.parse(existing);
                if (parsed && typeof parsed === 'object') {
                    data.event_end = parsed.event_end || data.event_end;
                    data.event_qr_code = parsed.event_qr_code || data.event_qr_code;
                    data.winners = Array.isArray(parsed.winners) ? parsed.winners : [];
                }
            }
        } catch (e) {}

        data.event_end = payload.eventEnd || data.event_end;
        data.event_qr_code = payload.eventQrCode || data.event_qr_code;

        const qrNorm = String(payload.qrCode || '').toUpperCase().trim();
        if (!qrNorm) {
            return;
        }

        const already = data.winners.some(function (w) {
            return String(w.qr_code || '').toUpperCase().trim() === qrNorm;
        });
        if (!already) {
            const nums = data.winners.map(function (w) {
                return parseInt(w.winner, 10) || 0;
            });
            const nextWinner = (nums.length ? Math.max.apply(null, nums) : 0) + 1;
            data.winners.push({
                winner: nextWinner,
                name: payload.name || '',
                qr_code: qrNorm,
                whatsapp_number: payload.whatsappNumber || '',
                email_address: payload.emailAddress || '',
            });
        }
        try {
            localStorage.setItem(key, JSON.stringify(data));
        } catch (e) {}
        hopeVillageRaffleStoredWinnersRefresh();
    }

    document.addEventListener('livewire:init', function () {
        Livewire.on('raffle-persist-winner', function (payload) {
            hopeVillagePersistWinnerToLocalStorage(payload);
        });
        Livewire.on('raffle-url-update', function (payload) {
            const params = payload?.params || {};
            const qs = new URLSearchParams(params).toString();
            const nextUrl = window.location.pathname + (qs ? `?${qs}` : '');
            try {
                window.history.replaceState({}, '', nextUrl);
            } catch (e) {}
        });
        Livewire.hook('message.processed', function () {
            hopeVillageRaffleStoredWinnersRefresh();
        });
    });

    function initRaffleWheelContainer($wire, $watch, $dispatch) {
        let wheelInitialized = false;
        let lastEntriesHash = '';
        
        function getEntriesHash(entries) {
            if (!entries || entries.length === 0) return '';
            // Create a more comprehensive hash that includes length and first/last items
            const firstFew = entries.slice(0, 3).join('-');
            const lastFew = entries.length > 3 ? entries.slice(-3).join('-') : '';
            return entries.length + '-' + firstFew + '-' + lastFew;
        }
        
        function initWheel(entries, force = false, excludeWinners = true) {
            if (!window.RaffleWheel) {
                console.warn('RaffleWheel not loaded yet');
                return;
            }
            
            const container = document.getElementById('wheel-container');
            if (container && entries && entries.length > 0) {
                let entriesToUse = entries;
                
                // Only filter out winners if explicitly requested
                if (excludeWinners) {
                    const winners = $wire.winners || [];
                    const winnerValues = winners.map(w => String(w.value || ''));
                    entriesToUse = entries.filter(entry => {
                        const entryStr = String(entry);
                        return !winnerValues.includes(entryStr);
                    });
                }
                
                if (entriesToUse.length === 0) {
                    container.innerHTML = '<div class="flex items-center justify-center h-full text-gray-500">All entries have been selected as winners</div>';
                    lastEntriesHash = '';
                    wheelInitialized = false;
                    return;
                }
                
                const currentHash = getEntriesHash(entriesToUse);
                // Always reinitialize if entries changed or force is true
                if (force || currentHash !== lastEntriesHash) {
                    console.log('Reinitializing wheel with', entriesToUse.length, 'entries' + (excludeWinners ? ' (excluding winners)' : ''));
                    window.RaffleWheel.initRaffleWheel(container, entriesToUse, true);
                    lastEntriesHash = currentHash;
                    wheelInitialized = true;
                }
            } else if (container && (!entries || entries.length === 0)) {
                // Clear wheel if no entries
                container.innerHTML = '<div class="flex items-center justify-center h-full text-gray-500">No entries loaded</div>';
                lastEntriesHash = '';
                wheelInitialized = false;
            }
        }
        
        // Preserve wheel during Livewire updates
        document.addEventListener('livewire:init', () => {
            Livewire.hook('morph.updated', ({ el, component }) => {
                // Check if wheel container still has canvas
                const container = document.getElementById('wheel-container');
                if (container && container.querySelector('canvas')) {
                    // Wheel is still there, don't reinitialize
                    return;
                }
            });
        });
        
        // Initialize wheel when entries are loaded - always reinitialize when entries change
        // But don't exclude winners yet - they will be excluded when modal closes
        $watch('$wire.entries', (entries) => {
            // Reinitialize wheel when entries change, but keep winners visible until modal closes
            if (window.RaffleWheel) {
                initWheel(entries, true, false); // Don't exclude winners on entries change
            } else {
                // Wait for RaffleWheel to be available
                const checkRaffleWheel = setInterval(() => {
                    if (window.RaffleWheel) {
                        clearInterval(checkRaffleWheel);
                        // Force reinitialize when entries change, but keep winners visible
                        initWheel(entries, true, false);
                    }
                }, 100);
                
                // Timeout after 5 seconds
                setTimeout(() => clearInterval(checkRaffleWheel), 5000);
            }
        });
        
        // Don't watch winners changes - wheel will be updated when modal closes
        // This prevents the wheel from updating before the modal is shown
        
        // Also watch entries length for immediate updates
        // But don't exclude winners - they will be excluded when modal closes
        $watch('$wire.entries.length', (length) => {
            const entries = $wire.entries;
            const currentHash = getEntriesHash(entries);
            
            // Reinitialize if hash changed, but keep winners visible
            if (currentHash !== lastEntriesHash) {
                if (window.RaffleWheel) {
                    initWheel(entries, true, false); // Don't exclude winners
                } else {
                    const checkRaffleWheel = setInterval(() => {
                        if (window.RaffleWheel) {
                            clearInterval(checkRaffleWheel);
                            initWheel(entries, true, false);
                        }
                    }, 100);
                    setTimeout(() => clearInterval(checkRaffleWheel), 5000);
                }
            }
        });
        
        // Listen for explicit entries-loaded event from Livewire
        // This is called when modal closes - exclude winners now
        Livewire.on('entries-loaded', () => {
            const run = async () => {
                if ($wire.source === 'event_attendees' && $wire.selectedEventId) {
                    try {
                        await hopeVillageSyncExcludedFromLocalStorage($wire);
                    } catch (e) {
                        console.warn('Raffle localStorage sync failed', e);
                    }
                }
                const entries = $wire.entries;
                if (entries && entries.length > 0 && window.RaffleWheel) {
                    const currentHash = getEntriesHash(entries);
                    if (currentHash !== lastEntriesHash) {
                        setTimeout(() => {
                            initWheel(entries, true, true); // Exclude winners after modal closes
                        }, 100);
                    }
                }
            };
            run();
        });
        
        // Listen for trigger-respin event to spin after wheel is updated
        Livewire.on('trigger-respin', () => {
            // Wait for wheel to be updated, then trigger spin
            setTimeout(() => {
                // Call the spin method which will handle everything
                $wire.spin();
            }, 300); // Wait 300ms for wheel to update
        });
        
               // Listen for wheel rest event
               window.addEventListener('wheel-rest', (event) => {
                   // Ensure wheel is still visible after spin
                   const container = document.getElementById('wheel-container');
                   if (container && window.RaffleWheel) {
                       // Use the ensureWheelVisible function
                       const isVisible = window.RaffleWheel.ensureWheelVisible(container);
                       if (!isVisible && $wire.entries && $wire.entries.length > 0) {
                           // Canvas missing, reinitialize (but keep winners visible)
                           console.warn('Wheel canvas missing after spin, reinitializing...');
                           setTimeout(() => {
                               initWheel($wire.entries, true, false); // Don't exclude winners
                           }, 50);
                       }
                   }
            
                   // Small delay to ensure wheel is fully stopped
                   setTimeout(() => {
                       $wire.onSpinComplete(event.detail.currentIndex);
                   }, 100);
               });
               
               // Preserve wheel after Livewire updates
               document.addEventListener('livewire:update', () => {
                   const container = document.getElementById('wheel-container');
                   if (container && window.RaffleWheel) {
                       const wheelInstance = window.RaffleWheel.getWheelInstance();
                       // Check if canvas still exists after update
                       if (wheelInstance && !container.querySelector('canvas') && $wire.entries && $wire.entries.length > 0) {
                           // Canvas was removed, reinitialize (but keep winners visible)
                           console.warn('Wheel canvas removed during Livewire update, reinitializing...');
                           setTimeout(() => {
                               initWheel($wire.entries, true, false); // Don't exclude winners
                           }, 50);
                       }
                   }
               });
        
               // Initialize on mount if entries exist
               if ($wire.entries && $wire.entries.length > 0) {
                   const checkRaffleWheel = setInterval(() => {
                       if (window.RaffleWheel) {
                           clearInterval(checkRaffleWheel);
                           initWheel($wire.entries, true, false); // Don't exclude winners on initial load
                       }
                   }, 100);
                   
                   setTimeout(() => clearInterval(checkRaffleWheel), 5000);
               }
    }
</script>
