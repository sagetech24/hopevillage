<div>
    @can('event.profile')
        <x-slot name="header">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 md:px-0 xl:px-0 px-3">
                <div class="flex md:flex-row flex-col md:gap-0 gap-4 justify-between items-center">
                    <div class="flex items-center gap-4">
                        <h2 class="font-semibold md:text-xl text-2xl text-gray-800 leading-tight">
                            {{ $event->title }}
                        </h2>
                    </div>
                    <a href="{{ route('admin.events.index') }}" class="text-orange-600 md:text-base text-md hover:text-orange-700 font-normal py-2 px-4 rounded-full">
                        ← Back to Events
                    </a>
                </div>
            </div>
        </x-slot>

        <div class="py-12">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 md:px-0 xl:px-0 px-3">            
                <div class="mt-4 grid grid-cols-1 lg:grid-cols-3 gap-6 md:mx-0 mx-4">
                    <!-- Left Column - Event Details -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Event Thumbnail -->
                        @if($event->thumbnail_url)
                        <div class="bg-white overflow-hidden shadow-md sm:rounded-lg">
                            <img src="{{ $event->thumbnail_url }}" alt="{{ $event->title }}" class="w-full h-64 object-cover">
                        </div>
                        @endif

                        <!-- Event Information Card -->
                        <div class="bg-white overflow-hidden shadow-md sm:rounded-lg p-6">
                            <div class="mb-4">
                                <label class="text-sm font-semibold text-gray-500">Title:</label>
                                <h3 class="text-2xl font-semibold text-gray-800">{{ $event->title }}</h3>
                            </div>
                            
                            <div class="space-y-4">
                                @if($event->description)
                                    <div>
                                        <label class="text-sm font-semibold text-gray-500">Description:</label>
                                        <div class="prose prose-sm sm:prose max-w-none prose-headings:text-gray-900 prose-p:text-gray-700">
                                            {!! $event->description !!}
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Date & Time Information Card -->
                        <div class="bg-white overflow-hidden shadow-md sm:rounded-lg p-6">
                            <h3 class="text-sm font-semibold text-gray-800 mb-4 border-b pb-2">Date & Time</h3>
                            <div class="flex items-start gap-2">
                                <svg viewBox="0 0 1024 1024" class="size-14" version="1.1" xmlns="http://www.w3.org/2000/svg" fill="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><path d="M716 190.9v-67.8h-44v67.8H352v-67.8h-44v67.8H92v710h840v-710H716z m-580 44h172v69.2h44v-69.2h320v69.2h44v-69.2h172v151.3H136V234.9z m752 622H136V402.2h752v454.7z" fill="#39393A"></path><path d="M319 565.7m-33 0a33 33 0 1 0 66 0 33 33 0 1 0-66 0Z" fill="#ff7300"></path><path d="M510 565.7m-33 0a33 33 0 1 0 66 0 33 33 0 1 0-66 0Z" fill="#ff7300"></path><path d="M701.1 565.7m-33 0a33 33 0 1 0 66 0 33 33 0 1 0-66 0Z" fill="#ff7300"></path><path d="M319 693.4m-33 0a33 33 0 1 0 66 0 33 33 0 1 0-66 0Z" fill="#ff7300"></path><path d="M510 693.4m-33 0a33 33 0 1 0 66 0 33 33 0 1 0-66 0Z" fill="#ff7300"></path><path d="M701.1 693.4m-33 0a33 33 0 1 0 66 0 33 33 0 1 0-66 0Z" fill="#ff7300"></path></g></svg>
                                <div class="flex flex-col mt-1">
                                    <p class="text-gray-900">
                                        <span class="font-bold">Start:</span> {{ $event->start_date->format('d M Y g:i A') }}
                                    </p>
                                    <p class="text-gray-900">
                                        <span class="font-bold mr-3.5">End:</span>{{ $event->end_date->format('d M Y g:i A') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Location Information Card -->
                        <div class="bg-white overflow-hidden shadow-md sm:rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Location Information</h3>
                            
                            @php
                                $location = $event->location;
                                $mapThumbnailUrl = null;
                                if ($location) {
                                    $apiKey = config('services.google_maps.api_key');
                                    if ($apiKey) {
                                        if (isset($location->latitude) && isset($location->longitude) && $location->latitude && $location->longitude) {
                                            $lat = $location->latitude;
                                            $lng = $location->longitude;
                                            $mapThumbnailUrl = "https://maps.googleapis.com/maps/api/staticmap?center={$lat},{$lng}&zoom=15&size=800x300&markers=color:red|{$lat},{$lng}&key={$apiKey}";
                                        } elseif (!empty($location->address)) {
                                            $address = urlencode(trim($location->address . ', ' . $location->city . ', ' . $location->province . ', ' . $location->postal_code, ', '));
                                            $mapThumbnailUrl = "https://maps.googleapis.com/maps/api/staticmap?center={$address}&zoom=15&size=800x300&markers=color:red|{$address}&key={$apiKey}";
                                        }
                                    }
                                }
                            @endphp

                            <div class="space-y-4">
                                @if($location && $location->address)
                                <div class="flex flex-col items-start gap-1">
                                    <div class="flex items-center gap-1">
                                        <svg class="size-5" version="1.0" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 64 64" enable-background="new 0 0 64 64" xml:space="preserve" fill="#000000">
                                            <g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <g> <g> <path fill="#394240" d="M32,0C18.745,0,8,10.745,8,24c0,5.678,2.502,10.671,5.271,15l17.097,24.156C30.743,63.686,31.352,64,32,64 s1.257-0.314,1.632-0.844L50.729,39C53.375,35.438,56,29.678,56,24C56,10.745,45.255,0,32,0z M48.087,39h-0.01L32,61L15.923,39 h-0.01C13.469,35.469,10,29.799,10,24c0-12.15,9.85-22,22-22s22,9.85,22,22C54,29.799,50.281,35.781,48.087,39z"></path> <path fill="#394240" d="M32,14c-5.523,0-10,4.478-10,10s4.477,10,10,10s10-4.478,10-10S37.523,14,32,14z M32,32 c-4.418,0-8-3.582-8-8s3.582-8,8-8s8,3.582,8,8S36.418,32,32,32z"></path> <path fill="#394240" d="M32,10c-7.732,0-14,6.268-14,14s6.268,14,14,14s14-6.268,14-14S39.732,10,32,10z M32,36 c-6.627,0-12-5.373-12-12s5.373-12,12-12s12,5.373,12,12S38.627,36,32,36z"></path> </g> <g> <path fill="#F76D57" d="M32,12c-6.627,0-12,5.373-12,12s5.373,12,12,12s12-5.373,12-12S38.627,12,32,12z M32,34 c-5.522,0-10-4.477-10-10s4.478-10,10-10s10,4.477,10,10S37.522,34,32,34z"></path> <path fill="#F76D57" d="M32,2c-12.15,0-22,9.85-22,22c0,5.799,3.469,11.469,5.913,15h0.01L32,61l16.077-22h0.01 C50.281,35.781,54,29.799,54,24C54,11.85,44.15,2,32,2z M32,38c-7.732,0-14-6.268-14-14s6.268-14,14-14s14,6.268,14,14 S39.732,38,32,38z"></path> </g> <path opacity="0.2" fill="#231F20" d="M32,12c-6.627,0-12,5.373-12,12s5.373,12,12,12s12-5.373,12-12S38.627,12,32,12z M32,34 c-5.522,0-10-4.477-10-10s4.478-10,10-10s10,4.477,10,10S37.522,34,32,34z"></path> </g> </g>
                                        </svg>
                                        <p class="text-gray-900 font-bold text-lg">
                                            <a href="{{ route('admin.locations.profile', $location->location_code) }}" class="text-indigo-500 hover:text-indigo-600">
                                                {{ $location->name }}
                                            </a>
                                        </p>
                                    </div>
                                    <p class="text-gray-900 text-sm pl-6">
                                        {{ $location->address }}
                                        @if($location->city || $location->province || $location->postal_code)
                                            <br>
                                            {{ trim(implode(', ', array_filter([$location->city, $location->province, $location->postal_code]))) }}
                                        @endif
                                    </p>
                                </div>
                                @endif

                                @if($mapThumbnailUrl)
                                    <div class="pt-2">
                                        <img
                                            src="{{ $mapThumbnailUrl }}"
                                            alt="{{ $location?->name ?? 'Location' }} map"
                                            class="w-full h-56 object-cover rounded-lg border border-gray-200"
                                            loading="lazy"
                                        >
                                    </div>
                                @endif
                            </div>
                        </div>

                    </div>

                    <!-- Right Column - Quick Actions & Registrations -->
                    <div class="space-y-6">
                        <!-- Event QR Code Card -->
                        <div class="bg-white overflow-hidden shadow-md sm:rounded-lg p-6">
                            <div 
                                x-data="{
                                    qrCodeImage: '{{ $qrCodeImage }}',
                                    eventCode: '{{ $event->event_code }}',
                                    async downloadQR() {
                                        try {
                                            const response = await fetch(this.qrCodeImage);
                                            const blob = await response.blob();
                                            const url = window.URL.createObjectURL(blob);
                                            const a = document.createElement('a');
                                            a.href = url;
                                            a.download = `event-qr-${this.eventCode}.png`;
                                            document.body.appendChild(a);
                                            a.click();
                                            window.URL.revokeObjectURL(url);
                                            document.body.removeChild(a);
                                        } catch (error) {
                                            console.error('Download failed:', error);
                                            alert('Failed to download QR code. Please try again.');
                                        }
                                    },
                                    async shareQR() {
                                        try {
                                            if (navigator.share) {
                                                const response = await fetch(this.qrCodeImage);
                                                const blob = await response.blob();
                                                const file = new File([blob], `event-qr-${this.eventCode}.png`, { type: 'image/png' });
                                                await navigator.share({
                                                    title: 'Event QR Code: {{ $event->title }}',
                                                    text: `Event Code: ${this.eventCode}`,
                                                    files: [file]
                                                });
                                            } else if (navigator.clipboard) {
                                                await navigator.clipboard.writeText(this.eventCode);
                                                alert('Event code copied to clipboard!');
                                            } else {
                                                // Fallback: copy event code to clipboard manually
                                                const textArea = document.createElement('textarea');
                                                textArea.value = this.eventCode;
                                                document.body.appendChild(textArea);
                                                textArea.select();
                                                document.execCommand('copy');
                                                document.body.removeChild(textArea);
                                                alert('Event code copied to clipboard!');
                                            }
                                        } catch (error) {
                                            console.error('Share failed:', error);
                                            // Fallback to copy event code
                                            try {
                                                await navigator.clipboard.writeText(this.eventCode);
                                                alert('Event code copied to clipboard!');
                                            } catch (e) {
                                                alert('Sharing not available. Event Code: ' + this.eventCode);
                                            }
                                        }
                                    }
                                }"
                            >
                                <div class="flex items-center justify-center mb-4">
                                    <img :src="qrCodeImage" alt="Event QR Code" class="w-full h-50 object-contain" id="event-qr-image">
                                </div>
                                <div class="flex gap-3 justify-center">
                                    <button 
                                        @click="downloadQR()"
                                        class="flex items-center text-xs gap-1 px-3 py-1 bg-transparent hover:bg-gray-200 cursor-pointer text-gray-500 border border-gray-500 font-medium rounded-lg transition-colors duration-200"
                                    >
                                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                        </svg>
                                        Download
                                    </button>
                                    <button 
                                        @click="shareQR()"
                                        class="flex items-center text-xs gap-1 px-3 py-1 bg-green-600 hover:bg-green-700 cursor-pointer text-white border border-green-600 font-medium rounded-lg transition-colors duration-200"
                                    >
                                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 1 0 0 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186 9.566-5.314m-9.566 7.5 9.566 5.314m0 0a2.25 2.25 0 1 0 3.935 2.186 2.25 2.25 0 0 0-3.935-2.186Zm0-12.814a2.25 2.25 0 1 0 3.933-2.185 2.25 2.25 0 0 0-3.933 2.185Z" />
                                        </svg>
                                        Share
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions Card -->
                        <div class="bg-white overflow-hidden shadow-md sm:rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Quick Actions</h3>
                            
                            <div class="space-y-3">
                                @if(! $event->end_date->isPast())
                                <button
                                    type="button"
                                    onclick="openEventQrScanner()"
                                    class="flex items-center justify-center gap-2 w-full bg-indigo-500 hover:bg-indigo-600 hover:-translate-y-0.5 text-white text-center font-semibold py-4 px-4 rounded-lg transition-all duration-200"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 stroke-white">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 3.75 9.375v-4.5ZM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 13.5 9.375v-4.5Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 6.75h.75v.75h-.75v-.75ZM6.75 16.5h.75v.75h-.75v-.75ZM16.5 6.75h.75v.75h-.75v-.75ZM13.5 13.5h.75v.75h-.75v-.75ZM13.5 19.5h.75v.75h-.75v-.75ZM19.5 13.5h.75v.75h-.75v-.75ZM19.5 19.5h.75v.75h-.75v-.75ZM16.5 16.5h.75v.75h-.75v-.75Z" />
                                    </svg>
                                    Scan Member QR Code
                                </button>
                                @endif
                                <a href="{{ route('admin.locations.events.edit', [$event->location->location_code, $event->id]) }}" class="flex items-center justify-center gap-2 w-full bg-yellow-600 hover:bg-yellow-700 hover:-translate-y-0.5 text-white text-center font-semibold py-4 px-4 rounded-lg transition-all duration-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                                    </svg>
                                    Update Event
                                </a>
                                <a href="{{ route('admin.locations.events.index', $event->location->location_code) }}" class="flex items-center justify-center gap-2 w-full bg-orange-500 hover:bg-orange-600 hover:-translate-y-0.5 text-white text-center font-semibold py-4 px-4 rounded-lg transition-all duration-200">
                                    <svg class="size-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M3 9H21M7 3V5M17 3V5M6 12H8M11 12H13M16 12H18M6 15H8M11 15H13M16 15H18M6 18H8M11 18H13M16 18H18M6.2 21H17.8C18.9201 21 19.4802 21 19.908 20.782C20.2843 20.5903 20.5903 20.2843 20.782 19.908C21 19.4802 21 18.9201 21 17.8V8.2C21 7.07989 21 6.51984 20.782 6.09202C20.5903 5.71569 20.2843 5.40973 19.908 5.21799C19.4802 5 18.9201 5 17.8 5H6.2C5.0799 5 4.51984 5 4.09202 5.21799C3.71569 5.40973 3.40973 5.71569 3.21799 6.09202C3 6.51984 3 7.07989 3 8.2V17.8C3 18.9201 3 19.4802 3.21799 19.908C3.40973 20.2843 3.71569 20.5903 4.09202 20.782C4.51984 21 5.07989 21 6.2 21Z" stroke="#ffffff" stroke-width="2" stroke-linecap="round"></path> </g></svg>
                                    All Events
                                </a>
                            </div>
                        </div>


                        <!-- Event Statistics Card -->
                        <div class="bg-white overflow-hidden shadow-md sm:rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Event Statistics</h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Attended Participants</label>
                                    <p class="text-2xl font-bold text-gray-900">
                                        {{ $event->registrations()->where('status', 'attended')->count() }}
                                        @if($event->max_participants)
                                            / {{ $event->max_participants }}
                                        @endif
                                    </p>
                                </div>
                                @if($event->max_participants)
                                    <div>
                                        <label class="text-sm font-medium text-gray-500">Available Spots</label>
                                        <p class="text-2xl font-bold text-gray-900">
                                            {{ max(0, $event->max_participants - $event->registrations->count()) }}
                                        </p>
                                    </div>
                                @endif
                                <div class="flex justify-between items-center gap-2">
                                    <div class="flex flex-col gap-1">
                                        <label class="text-sm font-medium text-gray-500">Created By:</label>
                                        <p class="text-gray-900">{{ $event->creator->name ?? 'N/A' }}</p>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <label class="text-sm font-medium text-gray-500">Created At:</label>
                                        <p class="text-gray-900">{{ $event->created_at->format('M d, Y') }}</p>
                                    </div>
                                </div>
                                <div class="flex justify-between items-center gap-2">
                                    <div>
                                        <label class="text-sm font-medium text-gray-500">Status</label>
                                        <p>
                                            @php
                                                $isEventFinished = $event->end_date->isPast();
                                                $displayStatus = $isEventFinished ? 'Finished' : ($event->status ?: 'Finished');
                                                $statusClass = $isEventFinished 
                                                    ? 'bg-gray-100 text-gray-800 border border-gray-500'
                                                    : match($event->status) {
                                                        'published' => 'bg-green-100 text-green-800 border border-green-500',
                                                        'cancelled' => 'bg-red-100 text-red-800 border border-red-500',
                                                        'completed' => 'bg-gray-100 text-gray-800 border border-gray-500',
                                                        default => 'bg-yellow-100 text-yellow-800 border border-yellow-500',
                                                    };
                                            @endphp
                                            <span class="px-3 py-1 inline-flex text-md leading-5 font-semibold rounded-full {{ $statusClass }}">
                                                {{ ucfirst($displayStatus) }}
                                            </span>
                                        </p>
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium text-gray-500">Event Code</label>
                                        <p class="text-gray-900 font-mono">{{ $event->event_code }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Member Registrants Card -->
                @livewire('events.participants', ['event_code' => $event->event_code])

                @if(! $event->end_date->isPast())
                <!-- Event Member QR Scanner Modal -->
                <div
                    id="event-qr-scanner-modal"
                    class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center"
                    style="display: none;"
                >
                    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-800">Scan Member QR Code</h3>
                            <button
                                type="button"
                                onclick="closeEventQrScanner()"
                                class="text-gray-400 hover:text-gray-600"
                            >
                                <svg class="size-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </div>

                        <p class="text-sm text-gray-600 mb-3">
                            Scanning for event <span class="font-mono font-semibold">{{ $event->event_code }}</span>.
                            Point camera at the member's QR code to mark attendance.
                        </p>

                        <div class="mb-4">
                            <div class="rounded-xl overflow-hidden border border-gray-200 bg-black">
                                <video id="event-qr-scanner-video" class="w-full h-64 object-cover" playsinline autoplay></video>
                            </div>
                            <div id="event-qr-scanner-error" class="mt-3 text-sm text-red-600 hidden"></div>
                            <div id="event-qr-scanner-loading" class="mt-3 text-sm text-gray-600 hidden">Initializing camera...</div>

                            <div id="event-qr-scan-response" class="mt-3 hidden">
                                <div id="event-qr-scan-response-content" class="rounded-lg p-3 text-sm font-semibold"></div>
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <button
                                type="button"
                                onclick="restartEventQrScanner()"
                                class="flex-1 bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-4 rounded-lg transition-all"
                            >
                                Restart Scanner
                            </button>
                            <button
                                type="button"
                                onclick="closeEventQrScanner()"
                                class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-2 px-4 rounded-lg transition-all"
                            >
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>

                <script>
                    (function () {
                        const EVENT_CODE = @json($event->event_code);
                        const SCAN_ENDPOINT = @json(route('api.event-registration.scan'));

                        const state = {
                            stream: null,
                            detector: null,
                            scanTimer: null,
                            isScanning: false,
                            lastScannedAt: 0,
                            lastScannedValue: null,
                            useJsQR: false,
                            canvas: null,
                            context: null,
                        };

                        function $(id) { return document.getElementById(id); }

                        function loadJsQR() {
                            if (window.jsQR) return Promise.resolve(true);
                            return new Promise((resolve, reject) => {
                                const script = document.createElement('script');
                                script.src = 'https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js';
                                script.onload = () => resolve(true);
                                script.onerror = () => reject(new Error('Failed to load jsQR library'));
                                document.head.appendChild(script);
                            });
                        }

                        async function detectQrCode(video) {
                            if (state.useJsQR) {
                                if (!window.jsQR || !state.canvas || !state.context) return null;
                                if (video.readyState !== video.HAVE_ENOUGH_DATA) return null;
                                state.canvas.width = video.videoWidth;
                                state.canvas.height = video.videoHeight;
                                state.context.drawImage(video, 0, 0, state.canvas.width, state.canvas.height);
                                const imageData = state.context.getImageData(0, 0, state.canvas.width, state.canvas.height);
                                const code = window.jsQR(imageData.data, imageData.width, imageData.height);
                                return code?.data ?? null;
                            }

                            if (!state.detector) return null;
                            const codes = await state.detector.detect(video);
                            return codes && codes.length > 0 ? codes[0].rawValue : null;
                        }

                        function log(action, data = {}) {
                            console.log('[Event QR Scanner]', { action, event_code: EVENT_CODE, ...data });
                        }

                        function showError(message) {
                            const el = $('event-qr-scanner-error');
                            if (!el) return;
                            el.textContent = message;
                            el.classList.remove('hidden');
                        }

                        function hideError() {
                            const el = $('event-qr-scanner-error');
                            if (!el) return;
                            el.classList.add('hidden');
                            el.textContent = '';
                        }

                        function showResponse(type, message) {
                            const wrapper = $('event-qr-scan-response');
                            const content = $('event-qr-scan-response-content');
                            if (!wrapper || !content) return;

                            content.className = 'rounded-lg p-3 text-sm font-semibold';
                            if (type === 'success') {
                                content.classList.add('bg-green-100', 'border', 'border-green-400', 'text-green-800');
                            } else {
                                content.classList.add('bg-red-100', 'border', 'border-red-400', 'text-red-800');
                            }
                            content.textContent = message;
                            wrapper.classList.remove('hidden');

                            setTimeout(() => wrapper.classList.add('hidden'), 5000);
                        }

                        function showToast(type, message) {
                            window.dispatchEvent(new CustomEvent('hv-toast', {
                                detail: { type, message },
                                bubbles: true,
                                cancelable: true,
                            }));
                        }

                        window.openEventQrScanner = function () {
                            const modal = $('event-qr-scanner-modal');
                            if (!modal) return;

                            const response = $('event-qr-scan-response');
                            if (response) response.classList.add('hidden');

                            hideError();
                            modal.classList.remove('hidden');
                            modal.classList.add('flex');
                            modal.style.display = 'flex';
                            log('Scanner opened');
                            startScanner();
                        };

                        window.closeEventQrScanner = function () {
                            stopScanner();
                            const modal = $('event-qr-scanner-modal');
                            if (!modal) return;
                            modal.classList.add('hidden');
                            modal.classList.remove('flex');
                            modal.style.display = 'none';
                            log('Scanner closed');
                        };

                        window.restartEventQrScanner = function () {
                            log('Scanner restart requested');
                            stopScanner();
                            setTimeout(startScanner, 100);
                        };

                        async function startScanner() {
                            const video = $('event-qr-scanner-video');
                            const loading = $('event-qr-scanner-loading');
                            if (!video) return;

                            hideError();
                            if (loading) loading.classList.remove('hidden');

                            if (!navigator.mediaDevices?.getUserMedia) {
                                showError('Camera is not supported on this device/browser.');
                                if (loading) loading.classList.add('hidden');
                                return;
                            }

                            try {
                                state.stream = await navigator.mediaDevices.getUserMedia({
                                    video: { facingMode: { ideal: 'environment' } },
                                    audio: false,
                                });
                                video.srcObject = state.stream;
                                await video.play();
                                log('Camera access granted');
                            } catch (e) {
                                showError('Camera permission denied or camera not available.');
                                log('Camera access denied', { error: e.message });
                                if (loading) loading.classList.add('hidden');
                                return;
                            }

                            // Prefer the native BarcodeDetector (Chrome/Android, etc.) and
                            // fall back to the jsQR library for iOS Safari and other
                            // browsers without native QR support.
                            if ('BarcodeDetector' in window) {
                                try {
                                    state.detector = new BarcodeDetector({ formats: ['qr_code'] });
                                    state.useJsQR = false;
                                } catch (e) {
                                    state.useJsQR = true;
                                }
                            } else {
                                state.useJsQR = true;
                            }

                            if (state.useJsQR) {
                                try {
                                    await loadJsQR();
                                    if (!state.canvas) {
                                        state.canvas = document.createElement('canvas');
                                        state.context = state.canvas.getContext('2d');
                                    }
                                } catch (e) {
                                    showError('Failed to load QR scanner library. Please check your connection and try again.');
                                    if (loading) loading.classList.add('hidden');
                                    stopScanner();
                                    return;
                                }
                            }

                            if (loading) loading.classList.add('hidden');
                            state.isScanning = true;

                            state.scanTimer = setInterval(async () => {
                                if (!video || !state.isScanning) return;
                                try {
                                    const value = await detectQrCode(video);
                                    if (value) {
                                        // Debounce: ignore the same code re-detected within 2.5s.
                                        const now = Date.now();
                                        if (value === state.lastScannedValue && (now - state.lastScannedAt) < 2500) {
                                            return;
                                        }
                                        state.lastScannedValue = value;
                                        state.lastScannedAt = now;
                                        await handleScan(value);
                                    }
                                } catch (e) {
                                    // Ignore transient detection errors
                                }
                            }, 300);
                        }

                        function stopScanner() {
                            state.isScanning = false;
                            if (state.scanTimer) {
                                clearInterval(state.scanTimer);
                                state.scanTimer = null;
                            }
                            if (state.stream) {
                                state.stream.getTracks().forEach(t => t.stop());
                                state.stream = null;
                            }
                            const video = $('event-qr-scanner-video');
                            if (video) video.srcObject = null;
                            state.detector = null;
                        }

                        function resumeScanning() {
                            const video = $('event-qr-scanner-video');
                            if (!video || !state.stream) {
                                stopScanner();
                                setTimeout(startScanner, 100);
                                return;
                            }
                            if (state.scanTimer) {
                                clearInterval(state.scanTimer);
                                state.scanTimer = null;
                            }
                            // Re-create the detector for BarcodeDetector users; the
                            // jsQR path simply reuses the already-loaded library and
                            // canvas, so no extra setup is required.
                            if (!state.useJsQR && !state.detector && 'BarcodeDetector' in window) {
                                try {
                                    state.detector = new BarcodeDetector({ formats: ['qr_code'] });
                                } catch (e) {
                                    return;
                                }
                            }
                            state.isScanning = true;
                            state.scanTimer = setInterval(async () => {
                                if (!video || !state.isScanning) return;
                                try {
                                    const value = await detectQrCode(video);
                                    if (value) {
                                        const now = Date.now();
                                        if (value === state.lastScannedValue && (now - state.lastScannedAt) < 2500) {
                                            return;
                                        }
                                        state.lastScannedValue = value;
                                        state.lastScannedAt = now;
                                        await handleScan(value);
                                    }
                                } catch (e) {
                                    // Ignore transient detection errors
                                }
                            }, 300);
                        }

                        async function handleScan(memberQrCode) {
                            if (!memberQrCode || !memberQrCode.trim()) {
                                showToast('error', 'Invalid QR code. Please try again.');
                                return;
                            }

                            // Pause detection to prevent rapid duplicate posts while we await
                            // the API; the camera stream is kept alive for instant resume.
                            state.isScanning = false;

                            const loading = $('event-qr-scanner-loading');
                            if (loading) {
                                loading.textContent = 'Processing scan...';
                                loading.classList.remove('hidden');
                            }

                            try {
                                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                                log('Sending scan to API', { qr_code: memberQrCode });

                                const response = await fetch(SCAN_ENDPOINT, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest',
                                        ...(csrf && { 'X-CSRF-TOKEN': csrf }),
                                    },
                                    body: JSON.stringify({
                                        event_code: EVENT_CODE,
                                        qr_code: memberQrCode.trim(),
                                        // Tells the API this scan came from the admin
                                        // event profile page so it records the
                                        // registration as `user_qr_code`. Public
                                        // external scanners omit this and fall back
                                        // to the `external_scanner` type.
                                        source: 'admin',
                                    }),
                                });

                                const data = await response.json();
                                if (loading) loading.classList.add('hidden');

                                if (response.ok && data.success) {
                                    const memberName = data.data?.member?.name ?? 'Member';
                                    // Prefer the new top-level flag; fall back to the legacy nested
                                    // flag so older API responses still work during rollout.
                                    const alreadyAttended = (data.data?.already_attended ?? data.data?.activity?.already_logged) === true;
                                    const pointsAwarded = Number(data.data?.points_awarded ?? 0);

                                    let message;
                                    if (alreadyAttended) {
                                        message = `${memberName} has already attended this event.`;
                                    } else if (pointsAwarded > 0) {
                                        message = `${memberName} attended! ${pointsAwarded} point${pointsAwarded === 1 ? '' : 's'} awarded.`;
                                    } else {
                                        message = `${memberName} attended successfully.`;
                                    }

                                    log('Scan succeeded', { already_attended: alreadyAttended, points_awarded: pointsAwarded });
                                    showToast(alreadyAttended ? 'info' : 'success', message);
                                    showResponse(alreadyAttended ? 'error' : 'success', message);

                                    if (pointsAwarded > 0) {
                                        window.dispatchEvent(new CustomEvent('points-updated'));
                                    }
                                } else {
                                    const errorMessage = data.message || data.error || 'Failed to record attendance.';
                                    log('Scan failed', { response: data });
                                    showToast('error', errorMessage);
                                    showResponse('error', errorMessage);
                                }
                            } catch (e) {
                                if (loading) loading.classList.add('hidden');
                                log('Scan network error', { error: e.message });
                                showToast('error', 'Network error. Please check your connection and try again.');
                                showResponse('error', 'Network error. Please check your connection and try again.');
                            } finally {
                                setTimeout(resumeScanning, 1800);
                            }
                        }

                        window.addEventListener('beforeunload', stopScanner);
                    })();
                </script>
                @endif
            </div>
        </div>
    @else
        @php abort(403, 'Unauthorized.'); @endphp
    @endcan
</div>
