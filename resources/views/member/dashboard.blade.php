<x-app-layout>
    <x-slot name="header">
        @livewire('member.points-header')
    </x-slot>
    <div class="py-12 md:px-0 px-4">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-12">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-start gap-3">
                        <div 
                            id="qr-code-container" 
                            class="cursor-pointer hover:opacity-80 transition-opacity"
                            onclick="openQrCodeModal()"
                        >
                            <div class="bg-white border-4 border-orange-600 p-0.5 rounded-none shadow-md hover:shadow-lg transition-all duration-200 hover:scale-105 animate-border-blink">
                                <img 
                                    id="qr-code-thumbnail" 
                                    src="" 
                                    alt="QR Code" 
                                    class="w-10 h-10"
                                    loading="lazy"
                                >
                            </div>
                        </div>
                        <div>
                            <div>
                                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Welcome, {{ auth()->user()->name }}!</h1>
                                @php
                                    $isVerified = auth()->user()->is_verified;
                                    $bgClass = $isVerified ? 'bg-green-50' : 'bg-yellow-50';
                                    $textClass = $isVerified ? 'text-green-600' : 'text-yellow-600';
                                    $borderClass = $isVerified ? 'border-green-400' : 'border-yellow-400';
                                @endphp
                                <span class="inline-block {{ $bgClass }} {{ $textClass }} px-2 py-0.5 border {{ $borderClass }} rounded-lg text-xs font-semibold mt-0.5">
                                    {{ $isVerified ? 'Verified' : 'Unverified' }}
                                </span>
                            </div>
                            {{-- <p class="text-sm text-gray-500 mt-1">
                                Your member overview and quick access.
                            </p> --}}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Full Screen QR Code Modal -->
            @if(auth()->user()->qr_code)
            <div 
                id="qr-code-modal" 
                class="fixed inset-0 bg-black bg-opacity-75 z-50 hidden items-center justify-center p-4"
                onclick="closeQrCodeModal(event)"
            >
                <div class="bg-white rounded-lg p-8 max-w-md w-full relative" onclick="event.stopPropagation()">
                    <button 
                        onclick="closeQrCodeModal(event)"
                        class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 transition-colors"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                    <div class="text-center">
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">Your QR Code</h3>
                        <p class="text-gray-600 mb-6">Scan this code for facility entry</p>
                        <div class="bg-white p-4 rounded-lg border-2 border-gray-300 inline-block mb-4">
                            <img 
                                id="qr-code-full" 
                                src="" 
                                alt="QR Code" 
                                class="w-64 h-64 mx-auto"
                            >
                        </div>
                        <p class="text-sm text-gray-500 font-mono">{{ auth()->user()->qr_code }}</p>
                    </div>
                </div>
            </div>
            @endif

            <!-- Anchor targets for bottom navigation -->
            <div id="my-vouchers">
                <div class="mb-4 flex items-end justify-between gap-3">
                    <div>
                        <p class="text-xl font-bold text-gray-700 mb-1">My Vouchers</p>
                        <p class="text-xs text-gray-600">Your most recently claimed vouchers.</p>
                    </div>
                    <a
                        href="{{ route('member.vouchers', ['status' => 'my-vouchers']) }}"
                        class="border border-orange-500/60 rounded-lg px-2 py-1 text-xs font-semibold text-orange-500 hover:text-orange-600"
                    >
                        View all
                    </a>
                </div>

                @php
                    $recentClaimedVouchers = auth()->user()
                        ->vouchers()
                        ->wherePivot('status', 'claimed')
                        ->latest('user_voucher.claimed_at')
                        ->limit(6)
                        ->get();
                @endphp

                <div class="flex flex-nowrap gap-2 min-h-[140px] items-center overflow-x-auto overflow-y-hidden">
                    @forelse($recentClaimedVouchers as $voucher)
                        @livewire('member.vouchers.card', ['value' => (string) $voucher->voucher_code], key('member-voucher-card-' . $voucher->id . '-' . $voucher->voucher_code))
                    @empty
                        <div class="border border-dashed border-gray-300 rounded-lg bg-gray-50/50 p-4 w-full text-center py-8 text-sm text-gray-400/60 font-semibold">
                            No Voucher found
                        </div>
                    @endforelse
                </div>
            </div>

            <div id="my-recent-events" class="mt-12">
                <div class="mb-8 flex items-end justify-between gap-3">
                    <div>
                        <p class="text-xl font-bold text-gray-700 mb-1">My Recent Events</p>
                        <p class="text-xs text-gray-600">Your most recently registered events.</p>
                    </div>
                    <a href="{{ route('member.events') }}" class="border border-orange-500/60 rounded-lg px-2 py-1 text-xs font-semibold text-orange-500 hover:text-orange-600">
                        View all
                    </a>
                </div>

                @php
                    $recentRegistrations = auth()->user()
                        ->eventRegistrations()
                        ->whereNotNull('event_id')
                        ->with('event.location', 'event.media')
                        ->latest('registered_at')
                        ->limit(5)
                        ->get();
                @endphp

                <div class="flex flex-nowrap gap-4 min-h-[120px] items-center overflow-x-auto overflow-y-hidden">
                    @forelse($recentRegistrations as $registration)
                        @php
                            $event = $registration->event;
                            if (!$event) {
                                continue;
                            }

                            $eventImageUrl = $event->thumbnail_url ?? null;
                            if (!$eventImageUrl) {
                                $apiKey = config('services.google_maps.api_key');
                                $location = $event->location;
                                if ($apiKey && $location) {
                                    if (isset($location->latitude) && isset($location->longitude) && $location->latitude && $location->longitude) {
                                        $lat = $location->latitude;
                                        $lng = $location->longitude;
                                        $eventImageUrl = "https://maps.googleapis.com/maps/api/staticmap?center={$lat},{$lng}&zoom=15&size=420x220&markers=color:red|{$lat},{$lng}&key={$apiKey}";
                                    } elseif (!empty($location->address)) {
                                        $address = urlencode(trim($location->address . ', ' . $location->city . ', ' . $location->province . ', ' . $location->postal_code, ', '));
                                        $eventImageUrl = "https://maps.googleapis.com/maps/api/staticmap?center={$address}&zoom=15&size=420x220&markers=color:red|{$address}&key={$apiKey}";
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
                        @endphp

                        <a href="{{ route('member.events.profile', $event->event_code) }}" class="hover:-translate-y-1 hover:shadow-lg transition-all duration-300 shrink-0 w-56 rounded-xl border border-gray-300 bg-white hover:bg-orange-50 overflow-hidden">
                            <div class="h-36 w-full bg-gray-100">
                                @if(!empty($eventImageUrl ?? null))
                                    <img
                                        src="{{ $eventImageUrl ?? '' }}"
                                        alt="{{ $event->title }}"
                                        class="h-36 w-full object-cover"
                                        loading="lazy"
                                    >
                                @else
                                    <div class="h-36 w-full flex items-center justify-center text-xs text-gray-400">
                                        No image
                                    </div>
                                @endif
                            </div>
                            <div class="p-3 min-h-24">
                                <p class="text-md font-bold text-gray-900 leading-snug">
                                    {{ \Illuminate\Support\Str::words($event->title, 5, '...') }}
                                </p>
                                <p class="mt-1 text-xs text-gray-500">
                                    {{ $schedule }}
                                </p>
                            </div>
                        </a>
                    @empty
                        <div class="border border-dashed border-gray-300 rounded-lg bg-gray-50/50 p-4 w-full text-center py-8 text-sm text-gray-400/60 font-semibold">
                            No Event found
                        </div>
                    @endforelse
                </div>
            </div>

            <div id="activities-table" class="mt-12 mb-3">
                <p class="text-lg font-bold text-gray-900">My Recent Activities</p>
                <p class="text-xs text-gray-600">Your recent activities will appear here.</p>
                
                @php
                    $recentActivities = auth()->user()
                        ->pointLogs()
                        ->with(['activityType', 'location', 'amenity'])
                        ->latest('awarded_at')
                        ->limit(5)
                        ->get();

                    // Helper function to get activity label and color classes
                    $getActivityInfo = function($activityTypeName) {
                        return match($activityTypeName) {
                            'account_verification' => ['label' => 'Verified', 'bg' => 'bg-purple-50', 'text' => 'text-purple-600', 'border' => 'border-purple-400'],
                            'member_entry_location' => ['label' => 'Entry', 'bg' => 'bg-green-50', 'text' => 'text-green-600', 'border' => 'border-green-600'],
                            'member_join_event' => ['label' => 'Join', 'bg' => 'bg-yellow-50', 'text' => 'text-yellow-600', 'border' => 'border-yellow-600'],
                            'member_attend_event' => ['label' => 'Attend', 'bg' => 'bg-blue-50', 'text' => 'text-blue-600', 'border' => 'border-blue-600'],
                            'member_claim_voucher' => ['label' => 'Claim', 'bg' => 'bg-orange-100', 'text' => 'text-orange-200', 'border' => 'border-orange-200'],
                            'member_redeem_voucher' => ['label' => 'Redeem', 'bg' => 'bg-blue-100', 'text' => 'text-blue-600', 'border' => 'border-blue-600'],
                            'member_claim_admin_voucher' => ['label' => 'Claim', 'bg' => 'bg-orange-100', 'text' => 'text-orange-600', 'border' => 'border-orange-200'],
                            'member_redeem_admin_voucher' => ['label' => 'Redeem', 'bg' => 'bg-blue-100', 'text' => 'text-blue-600', 'border' => 'border-blue-600'],
                            'admin_void_admin_voucher' => ['label' => 'Void', 'bg' => 'bg-gray-100', 'text' => 'text-gray-600', 'border' => 'border-gray-400'],
                            default => ['label' => ucfirst(str_replace('_', ' ', $activityTypeName)), 'bg' => 'bg-gray-50', 'text' => 'text-gray-600', 'border' => 'border-gray-600'],
                        };
                    };
                @endphp

                <div class="overflow-hidden mt-4">
                    @if($recentActivities->isEmpty())
                        <!-- Empty state -->
                        <div class="text-center py-8">
                            <p class="text-sm text-gray-400/60 font-semibold">No activities found</p>
                            <p class="text-xs text-gray-400/50 mt-1">Your activities will appear here once you start earning points.</p>
                        </div>
                    @else
                        <!-- Mobile-first list -->
                        <div class="space-y-3 sm:hidden overflow-auto max-h-[100px]">
                            @foreach($recentActivities as $activity)
                                @php
                                    $activityInfo = $getActivityInfo($activity->activityType->name ?? '');
                                    $label = $activityInfo['label'];
                                    $bgClass = $activityInfo['bg'];
                                    $textClass = $activityInfo['text'];
                                    $borderClass = $activityInfo['border'];
                                    
                                    $locationName = $activity->location?->name ?? '';
                                    $amenityName = $activity->amenity?->name ?? '';
                                    $displayName = $locationName;
                                    if ($amenityName) {
                                        $displayName .= ' - ' . $amenityName;
                                    }
                                    if (empty($displayName)) {
                                        $displayName = $activity->description ?? 'Activity';
                                    }
                                    
                                    $date = $activity->awarded_at ?? $activity->created_at;
                                @endphp
                                <div class="border border-gray-200 bg-white rounded-lg shadow-sm p-4">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <span class="{{ $textClass }} {{ $bgClass }} px-2 py-1 border {{ $borderClass }} rounded-lg text-xs font-semibold">{{ $label }}</span>
                                            <p class="text-xs text-gray-600">+{{ number_format($activity->points, 0) }} Points</p>
                                        </div>
                                        <span class="text-xs text-gray-500">{{ $date->format('M d, Y • g:i A') }}</span>
                                    </div>
                                    <p class="mt-2 text-sm font-semibold text-gray-900">{{ $displayName }}</p>
                                </div>
                            @endforeach
                        </div>
        
                        <!-- Table for larger screens -->
                        <div class="hidden sm:block overflow-x-auto">
                            <table class="w-full text-sm">
                                <tbody>
                                    @foreach($recentActivities as $activity)
                                        @php
                                            $activityInfo = $getActivityInfo($activity->activityType->name ?? '');
                                            $label = $activityInfo['label'];
                                            $bgClass = $activityInfo['bg'];
                                            $textClass = $activityInfo['text'];
                                            $borderClass = $activityInfo['border'];
                                            
                                            $locationName = $activity->location?->name ?? '';
                                            $amenityName = $activity->amenity?->name ?? '';
                                            $displayName = $locationName;
                                            if ($amenityName) {
                                                $displayName .= ' - ' . $amenityName;
                                            }
                                            if (empty($displayName)) {
                                                $displayName = $activity->description ?? 'Activity';
                                            }
                                            
                                            $date = $activity->awarded_at ?? $activity->created_at;
                                        @endphp
                                        <tr class="border-b border-gray-200">
                                            <td class="py-4">
                                                <span class="{{ $textClass }} {{ $bgClass }} px-2 py-1 border {{ $borderClass }} rounded-lg">{{ $label }}</span>
                                            </td>
                                            <td class="py-2">{{ $displayName }}</td>
                                            <td class="py-2">{{ $date->format('F d, Y - g:i A') }}</td>
                                            <td class="py-2">
                                                <span class="text-green-600 bg-green-50 px-2 py-1 border border-green-600/60 rounded-lg">{{ number_format($activity->points, 0) }} Points</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if(auth()->user()->qr_code)
    @push('scripts')
    <style>
        @keyframes border-blink {
            0%, 100% {
                border-color: rgb(234 88 12); /* orange-600 */
                box-shadow: 0 0 0 0 rgba(234, 88, 12, 0.7);
            }
            50% {
                border-color: rgb(251 146 60); /* orange-400 */
                box-shadow: 0 0 8px 2px rgba(234, 88, 12, 0.5);
            }
        }
        
        .animate-border-blink {
            animation: border-blink 1.5s ease-in-out infinite;
        }
    </style>
    <script>
        // Load QR code on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadQrCode();
        });

        function loadQrCode() {
            fetch('{{ route("member.qr-code") }}', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.image) {
                    document.getElementById('qr-code-thumbnail').src = data.image;
                    document.getElementById('qr-code-full').src = data.image;
                }
            })
            .catch(error => {
                console.error('Error loading QR code:', error);
            });
        }

        function openQrCodeModal() {
            const modal = document.getElementById('qr-code-modal');
            // Load full size QR code
            fetch('{{ route("qr-code.full") }}', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.image) {
                    document.getElementById('qr-code-full').src = data.image;
                }
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.style.overflow = 'hidden';
            })
            .catch(error => {
                console.error('Error loading full QR code:', error);
                // Fallback to thumbnail if full size fails
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.style.overflow = 'hidden';
            });
        }

        function closeQrCodeModal(event) {
            if (event) {
                event.stopPropagation();
            }
            const modal = document.getElementById('qr-code-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = '';
        }

        // Close modal on Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const modal = document.getElementById('qr-code-modal');
                if (modal && !modal.classList.contains('hidden')) {
                    closeQrCodeModal();
                }
            }
        });
    </script>
    @endpush
    @endif
</x-app-layout>

