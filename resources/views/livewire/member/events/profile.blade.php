<div>
    <x-slot name="header">
        @livewire('member.points-header')
    </x-slot>

    <div class="min-h-screen max-w-md mx-auto px-4 sm:px-6 lg:px-8 bg-gray-100">
            <div class="py-8 flex justify-end">
                <a href="{{ route('member.events') }}" class="text-sm text-gray-600 hover:text-orange-500">
                    ← Back to Events
                </a>
            </div>
            @if (session()->has('message'))
                @php
                    $type = session('message_type', 'success');
                    $classes = $type === 'error'
                        ? 'bg-red-100 border-red-400 text-red-700'
                        : 'bg-green-100 border-green-400 text-green-700';
                @endphp
                <div class="mb-4 border px-4 py-3 rounded {{ $classes }}" role="alert">
                    <span class="block sm:inline">{{ session('message') }}</span>
                </div>
            @endif

            @php
                $location = $event->location;

                $heroImageUrl = $event->thumbnail_url ?? null;
                if (!$heroImageUrl) {
                    $apiKey = config('services.google_maps.api_key');
                    if ($apiKey && $location) {
                        if (isset($location->latitude) && isset($location->longitude) && $location->latitude && $location->longitude) {
                            $lat = $location->latitude;
                            $lng = $location->longitude;
                            $heroImageUrl = "https://maps.googleapis.com/maps/api/staticmap?center={$lat},{$lng}&zoom=15&size=1200x450&markers=color:red|{$lat},{$lng}&key={$apiKey}";
                        } elseif (!empty($location->address)) {
                            $address = urlencode(trim($location->address . ', ' . $location->city . ', ' . $location->province . ', ' . $location->postal_code, ', '));
                            $heroImageUrl = "https://maps.googleapis.com/maps/api/staticmap?center={$address}&zoom=15&size=1200x450&markers=color:red|{$address}&key={$apiKey}";
                        }
                    }
                }

                $start = $event->start_date;
                $end = $event->end_date;
                $schedule = 'TBA';
                if ($start && $end) {
                    $schedule = $start->format('d M Y');
                    if ($start->format('Y-m-d') === $end->format('Y-m-d')) {
                        $schedule .= ' • ' . $start->format('g:i A') . ' - ' . $end->format('g:i A');
                    } else {
                        $schedule = $start->format('M d, Y g:i A') . ' - ' . $end->format('M d, Y g:i A');
                    }
                } elseif ($start) {
                    $schedule = $start->format('M d, Y g:i A');
                }

                $isFull = $event->max_participants && $event->max_participants > 0
                    ? ($event->registrations_count >= $event->max_participants)
                    : false;
            @endphp

            <div class="grid grid-cols-1 gap-6">
                <div class="space-y-6">
                    @if(!empty($heroImageUrl ?? null))
                        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
                            <img
                                src="{{ $heroImageUrl ?? '' }}"
                                alt="{{ $event->title }}"
                                class="w-full h-72 object-cover"
                                loading="lazy"
                            >
                        </div>
                    @endif
                    <div class="space-y-2">
                        <h2 class="text-2xl font-bold text-gray-800">{{ $event->title }}</h2>
                        <p class="text-xs text-gray-600 italic">{{ $schedule }}</p>
                    </div>

                    <div class="bg-white border border-gray-200 rounded-xl p-6">
                        <div class="space-y-2 text-sm text-gray-700">
                            <div class="flex flex-col gap-1">
                                <span class="font-bold text-xs text-gray-600">Description:</span>
                                <div class="prose prose-sm max-w-none prose-headings:text-gray-900 prose-p:text-gray-700">
                                    {!! $event->description !!}
                                </div>
                            </div>
                            <div>
                                <span class="font-bold text-xs text-gray-600">Location:</span>
                                <span>{{ $location?->name ?? 'Unknown location' }}</span>
                            </div>
                            @if($event->venue)
                                <div>
                                    <span class="font-bold text-xs text-gray-600">Venue:</span>
                                    <span>{{ $event->venue }}</span>
                                </div>
                            @endif
                            @if($location?->address)
                                <div>
                                    <span class="font-bold text-xs text-gray-600">Address:</span>
                                    <span>
                                        {{ $location->address }}
                                        @if($location->city || $location->province || $location->postal_code)
                                            , {{ trim(implode(', ', array_filter([$location->city, $location->province, $location->postal_code]))) }}
                                        @endif
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-white border border-gray-200 rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-3">Registration</h3>

                        <div class="flex items-center justify-between text-sm text-gray-600">
                            <span>Participants</span>
                            <span class="font-semibold text-gray-800">
                                {{ $event->registrations_count }}
                                @if($event->max_participants && $event->max_participants > 0)
                                    / {{ $event->max_participants }}
                                @else
                                    / No limit
                                @endif
                            </span>
                        </div>

                        <div class="mt-4">
                            @if($event->is_registered)
                                <button type="button" disabled class="w-full px-4 py-2 text-sm font-semibold rounded-lg bg-gray-100 text-gray-500 border border-gray-200">
                                    Added to interested list
                                </button>
                            @elseif($isFull)
                                <button type="button" disabled class="w-full px-4 py-2 text-sm font-semibold rounded-lg bg-gray-100 text-gray-500 border border-gray-200">
                                    Full
                                </button>
                            @else
                                <button
                                    type="button"
                                    wire:click="join"
                                    wire:loading.attr="disabled"
                                    class="w-full px-4 py-2 text-sm font-semibold rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white transition disabled:opacity-50"
                                >
                                    <span wire:loading.remove wire:target="join">Add to interested list</span>
                                    <span wire:loading wire:target="join">Adding to interested list...</span>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
    </div>

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('scroll-to-top', () => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        });
    </script>
</div>


