<div
    x-cloak
    x-data="{ 
        loaded: false,
        {{-- imageLoaded: false, --}}
        interestedLoading: false,
        joinLoading: false,
        registrationStatus: @js($event['registration_status'] ?? null),
        minLoadTime: 1000,
        loadingTimers: {},
        startLoading(type) {
            this[type + 'Loading'] = true;
            const startTime = Date.now();
            this.loadingTimers[type] = startTime;
        },
        stopLoading(type) {
            if (!this.loadingTimers[type]) return;
            const elapsed = Date.now() - this.loadingTimers[type];
            const remaining = this.minLoadTime - elapsed;
            if (remaining > 0) {
                setTimeout(() => {
                    this[type + 'Loading'] = false;
                    delete this.loadingTimers[type];
                }, remaining);
            } else {
                this[type + 'Loading'] = false;
                delete this.loadingTimers[type];
            }
        },
        confirmRegister() {
            const isRegistered = this.registrationStatus === 'registered';
            const message = isRegistered 
                ? 'Are you sure you want to unregister from this event?' 
                : 'Are you sure you want to register for this event?';
            
            if (confirm(message)) {
                this.startLoading('join');
                $wire.join();
            }
        }
    }"
    @event-updated.window="
        if ($event.detail.status !== undefined) {
            registrationStatus = $event.detail.status;
        }
        if (interestedLoading) stopLoading('interested');
        if (joinLoading) stopLoading('join');
    "
    @event-updated.window="
        if (interestedLoading) stopLoading('interested');
        if (joinLoading) stopLoading('join');
    "
    x-init="
        loaded = true;
        $watch('loaded', (value) => {
            if (value) {
                $el.style.opacity = '0';
                $el.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    $el.style.transition = 'all 0.6s ease-out';
                    $el.style.opacity = '1';
                    $el.style.transform = 'translateY(0)';
                }, 10);
            }
        });
    "
    class="card card-bordered bg-white shadow-sm hover:shadow-md transition-all duration-300 cursor-pointer"
    @click="window.location.href = '/member/event/{{ $event['event_code'] }}'"
>
    <div class="card-body flex-row gap-4 p-4">
        <!-- Event Image -->
        <div class="shrink-0">
            <div class="w-20 h-20 border border-gray-300 rounded-lg bg-white overflow-hidden relative">
                @if($event['thumbnail_url'] ?? null)
                    <img
                        src="{{ $event['thumbnail_url'] }}"
                        alt="{{ $event['title'] }}"
                        class="w-full h-full object-cover"
                        {{-- @load="imageLoaded = true"
                        x-show="imageLoaded" --}}
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                    >
                @else
                    @php
                        $dateInfo = $this->formatDateForThumbnail($event['start_date'] ?? null);
                    @endphp
                    <div class="w-full h-full flex flex-col items-center justify-center border-4 border-orange-300 bg-orange-50/50 rounded-lg p-2">
                        <div class="text-md font-semibold text-orange-400 uppercase">{{ $dateInfo['month'] }}</div>
                        <div class="text-3xl font-bold text-orange-300">{{ $dateInfo['day'] }}</div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Event Details -->
        <div class="flex-1 min-w-0">
            <div class="flex items-start justify-between gap-2 mb-1">
                <!-- Event Title -->
                <a href="/member/event/{{ $event['event_code'] }}" class="text-gray-900 text-xl font-bold hover:text-orange-600 transition-colors flex-1">{{ $event['title'] }}</a>
            </div>
            {{-- add the description here with truncate and add ... at the end if the description is longer than 100 characters --}}
            <p class="text-gray-800 text-xs line-clamp-2 italic">{{ str()->words(strip_tags($event['description'] ?? ''), 15) }}</p>

            {{-- add the event date here with the format of M d, Y g:i A --}}
            <p class="text-gray-800 text-xs flex gap-1 items-center mt-1">
                <span class="font-bold">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z" />
                    </svg>
                </span>
                @if(isset($event['start_date']) && $event['start_date'])
                    {{ \Carbon\Carbon::parse($event['start_date'])->format('d M Y g:i A') }}
                @else
                    TBA
                @endif
            </p>

            {{-- add the venue here --}}
            <p class="text-gray-800 text-xs flex gap-1 items-center mt-1">
                <span class="font-bold">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                    </svg>
                </span>
                @if(isset($event['venue']) && $event['venue'])
                    {{ $event['venue'] }}
                @else
                    TBA
                @endif
            </p>
            
            @if($isMyEvents)
                {{-- Show status badge only in my-events --}}
                @if(isset($event['status']))
                    @php
                        $statusConfig = [
                            'favorited' => ['label' => 'Favorited', 'class' => 'border border-orange-500 text-orange-500'],
                            'interested' => ['label' => 'Liked', 'class' => 'border border-orange-500 text-orange-500'],
                            'registered' => ['label' => 'Registered', 'class' => 'border border-orange-500 text-orange-500'],
                            'attended' => ['label' => 'Attended', 'class' => 'border border-orange-500 text-orange-500'],
                        ];
                        $status = $statusConfig[$event['status']] ?? null;
                    @endphp
                    @if($status)
                        <div class="mt-3">
                            <span class="badge {{ $status['class'] }} badge-sm whitespace-nowrap">{{ $status['label'] }}</span>
                        </div>
                    @endif
                @endif
            @else
                {{-- Show action buttons only in browse component --}}
                @php
                    $status = $event['registration_status'] ?? null;
                    $interestedLabel = $status === 'interested' ? 'Liked' : 'Like';
                    $registerLabel = $status === 'registered' ? 'Registered' : 'Register';
                    
                    $interestedClass = $status === 'interested' 
                        ? 'text-orange-400 hover:text-orange-500' 
                        : 'text-gray-500 hover:text-gray-600';
                    $registerClass = $status === 'registered' 
                        ? 'text-orange-400 hover:text-orange-500' 
                        : 'text-gray-500 hover:text-gray-600';
                @endphp
                <!-- Action Links -->
                <div class="mt-3 flex flex-wrap items-center gap-2 text-sm">
                    {{-- <button
                        type="button"
                        @click.stop="startLoading('interested')"
                        wire:click="markInterested"
                        :disabled="interestedLoading"
                        :class="interestedLoading ? 'opacity-50 cursor-not-allowed' : ''"
                        class="{{ $interestedClass }} font-medium cursor-pointer transition-colors disabled:opacity-50 flex items-center gap-1 text-sm"
                    >
                        <svg 
                            x-show="!interestedLoading"
                            x-cloak
                            class="w-4 h-4" 
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.633 10.25c.806 0 1.533-.446 2.031-1.08a9.041 9.041 0 0 1 2.861-2.4c.723-.384 1.35-.956 1.653-1.715a4.498 4.498 0 0 0 .322-1.672V2.75a.75.75 0 0 1 .75-.75 2.25 2.25 0 0 1 2.25 2.25c0 1.152-.26 2.243-.723 3.218-.266.558.107 1.282.725 1.282m0 0h3.126c1.026 0 1.945.694 2.054 1.715.045.422.068.85.068 1.285a11.95 11.95 0 0 1-2.649 7.521c-.388.482-.987.729-1.605.729H13.48c-.483 0-.964-.078-1.423-.23l-3.114-1.04a4.501 4.501 0 0 0-1.423-.23H5.904m10.598-9.75H14.25M5.904 18.5c.083.205.173.405.27.602.197.4-.078.898-.523.898h-.908c-.889 0-1.713-.518-1.972-1.368a12 12 0 0 1-.521-3.507c0-1.553.295-3.036.831-4.398C3.387 9.953 4.167 9.5 5 9.5h1.053c.472 0 .745.556.5.96a8.958 8.958 0 0 0-1.302 4.665c0 1.194.232 2.333.654 3.375Z" />
                        </svg>
                        <svg 
                            x-show="interestedLoading"
                            x-cloak
                            class="animate-spin w-4 h-4" 
                            fill="none" 
                            viewBox="0 0 24 24"
                        >
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-show="!interestedLoading" x-cloak>{{ $interestedLabel }}</span>
                        <span x-show="interestedLoading" x-cloak x-text="registrationStatus === 'interested' ? 'Liking...' : 'Unliking...'"></span>
                    </button> --}}
                    {{-- <button
                        type="button"
                        @click.stop="confirmRegister()"
                        :disabled="joinLoading"
                        :class="joinLoading ? 'opacity-50 cursor-not-allowed' : ''"
                        class="{{ $registerClass }} flex items-center font-medium cursor-pointer transition-colors text-sm"
                    >
                        <svg 
                            x-show="!joinLoading"
                            x-cloak
                            class="w-4 h-4" 
                            fill="none" 
                            stroke="currentColor" 
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                        </svg>

                        <svg 
                            x-show="joinLoading"
                            x-cloak
                            class="animate-spin w-4 h-4" 
                            fill="none" 
                            viewBox="0 0 24 24"
                        >
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-show="!joinLoading" x-cloak>{{ $registerLabel }}</span>
                        <span x-show="joinLoading" x-cloak x-text="registrationStatus === 'registered' ? 'Registering...' : 'Unregistering...'"></span>
                    </button> --}}
                </div>
            @endif
        </div>
    </div>
</div>

