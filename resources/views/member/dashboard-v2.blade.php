<x-app-layout>
    {{-- <div class="min-h-screen pb-20"> --}}
        <div class="max-w-md mx-auto min-h-screen bg-gray-100/60 pb-16">
            <!-- Header Section with Avatar and Greeting -->
            <div class="bg-linear-to-br from-orange-300 to-orange-400 p-6 pb-8 rounded-b-4xl shadow-lg">
                <div class="flex items-center justify-between animate-fade-in">
                    <div class="flex items-start gap-3 w-full min-w-0">
                        <div class="min-w-0 w-full">
                            <div class="flex flex-nowrap items-start gap-3 w-full">
                                <p class="text-orange-100 text-2xl font-bold mb-4 drop-shadow shrink-0">Welcome back!</p>
                                {{-- <div class="ml-auto shrink-0">
                                    @if(auth()->user()->is_verified)
                                        <span class="text-[11px] font-normal bg-green-400/40 text-white border border-green-400/50 px-2.5 py-1 rounded-full whitespace-nowrap">Verified</span>
                                    @else
                                        <span class="text-[11px] font-normal bg-red-600/40 text-white border border-red-600/50 px-2.5 py-1 rounded-full whitespace-nowrap">Unverified</span>
                                    @endif
                                </div> --}}
                            </div>
                            <div class="flex items-start gap-2">
                                <div class="border-2 border-white bg-white/70 shadow-lg rounded-xl p-2">
                                    <svg class="size-8" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M8 13H16C17.7107 13 19.1506 14.2804 19.3505 15.9795L20 21.5M8 13C5.2421 12.3871 3.06717 10.2687 2.38197 7.52787L2 6M8 13V18C8 19.8856 8 20.8284 8.58579 21.4142C9.17157 22 10.1144 22 12 22C13.8856 22 14.8284 22 15.4142 21.4142C16 20.8284 16 19.8856 16 18V17" stroke="#ffffff" stroke-width="1.5" stroke-linecap="round"></path> <circle cx="12" cy="6" r="4" stroke="#ffffff" stroke-width="1.5"></circle> </g>
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-orange-900 text-3xl font-bold flex items-center gap-2 drop-shadow leading-tight">
                                        {{ auth()->user()->name }}
                                    </h2>
                                    <div class="flex flex-col items-start gap-0">
                                        <span class="text-orange-600 mt-1 text-[0.7rem] font-normal leading-1 capitalize">{{ auth()->user()->type_of_work }}</span>
                                        <p class="flex items-center gap-0 mt-4">
                                            <span class="text-white text-[11px] font-mono tracking-wider font-normal leading-0 capitalize">Code:</span>
                                            <span class="text-white text-[11px] font-mono tracking-wider font-normal leading-0 capitalize">{{ auth()->user()->qr_code }}</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <br />
                            <div class="flex items-start gap-6 flex-wrap justify-between">
                                <livewire:member.dashboard-points />
                                <livewire:member.dashboard-ranking variant="header" wire:key="dashboard-ranking-header" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="px-4 mt-10 relative z-10">
                {{-- <livewire:member.dashboard-ranking variant="list" wire:key="dashboard-ranking-list" /> --}}

                <!-- Search Section -->
                <div class="mb-6 animate-slide-down">
                    <h1 class="text-2xl font-bold text-base-content mb-4">What's on your mind?</h1>
                    <label class="relative">
                        <svg class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 size-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="h-5 w-5 opacity-70">
                            <path fill-rule="evenodd" d="M9.965 11.026a5 5 0 1 1 1.06-1.06l2.755 2.754a.75.75 0 1 1-1.06 1.06l-2.755-2.754ZM10.5 7a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0Z" clip-rule="evenodd" />
                        </svg>
                        <input 
                            type="text" 
                            wire:model.live.debounce.300ms="search"
                            placeholder="Ask anything about Hope Village" 
                            id="location-search" 
                            class="w-full pl-10 py-3 border border-gray-300 rounded-full focus:ring-2 focus:ring-orange-500 focus:border-orange-500 placeholder:text-gray-400" 
                        />
                    </label>
                </div>

                <!-- Categories Section -->
                <div class="mb-8 animate-slide-up delay-100">
                    <h2 class="text-xl font-bold text-orange-400 mb-4">Quick Actions</h2>
                    <div class="grid grid-cols-3 gap-4">
                        @php
                            $categories = [
                                [
                                    'icon' => '<img width="30" height="30" src="https://img.icons8.com/pulsar-gradient/48/business-group.png" alt="business-group"/>',
                                    'name' => 'Activities',
                                    'route' => route('member.activities'),
                                    'color' => 'primary'
                                ],
                                [
                                    'icon' => '<img width="30" height="30" src="https://img.icons8.com/cotton/64/events--v1.png" alt="events--v1"/>',
                                    'name' => 'Events',
                                    'count' => \App\Models\Event::where('status', 'published')->where('end_date', '>', now())->count(),
                                    'route' => route('member.events'),
                                    'color' => 'secondary'
                                ],
                                [
                                    'icon' => '<img width="30" height="30" src="https://img.icons8.com/dusk/64/discount-ticket.png" alt="discount-ticket"/>',
                                    'name' => 'Vouchers',
                                    'count' => auth()->user()->getClaimableVouchersCount(),
                                    'route' => route('member.vouchers'),
                                    'color' => 'accent'
                                ],
                                [
                                    'icon' => '<img width="30" height="30" src="https://img.icons8.com/fluency/48/shopping-cart.png" alt="marketplace"/>',
                                    'name' => 'Marketplace',
                                    'route' => route('member.marketplace.index'),
                                    'color' => 'info'
                                ],
                                [
                                    'icon' => '<img width="30" height="30" src="https://img.icons8.com/cotton/50/online-store.png" alt="online-store"/>',
                                    'name' => 'Merchants',
                                    'route' => route('member.merchants.index'),
                                    'color' => 'secondary'
                                ],
                                [
                                    'icon' => '<img width="40" height="40" src="https://img.icons8.com/plasticine/50/share-2.png" alt="share-2"/>',
                                    'name' => 'Refer A Friend',
                                    'route' => route('member.referral-system'),
                                    'color' => 'success'
                                ],
                                [
                                    'icon' => '<img width="30" height="30" src="https://img.icons8.com/dusk/50/news.png" alt="news"/>',
                                    'name' => 'News',
                                    'route' => route('member.news'),
                                    'color' => 'warning'
                                ],
                            ];
                        @endphp

                        @foreach($categories as $index => $category)
                            @php
                                $colorClasses = [
                                    'primary' => 'text-primary',
                                    'secondary' => 'text-secondary',
                                    'accent' => 'text-accent',
                                    'info' => 'text-info',
                                    'success' => 'text-success',
                                    'warning' => 'text-warning',
                                ];
                                $iconColorClass = $colorClasses[$category['color']] ?? 'text-primary';
                            @endphp

                            <a href="{{ $category['route'] }}" 
                               class="card bg-white shadow-md hover:shadow-xl border border-base-300 rounded-2xl p-4 transition-all duration-300 hover:scale-105 hover:-translate-y-1 animate-fade-in"
                               style="animation-delay: {{ ($index + 1) * 50 }}ms">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <div class="{{ $iconColorClass }} mb-1">
                                        {!! $category['icon'] !!}
                                    </div>
                                    <p class="text-xs font-semibold text-base-content text-center leading-tight">
                                        {{ $category['name'] }}
                                        {{ isset($category['count']) && $category['count'] > 0 ? '(' . $category['count'] . ')' : '' }}
                                    </p>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>

                <!-- Popular Section -->
                <div class="mt-18 mb-6 animate-slide-up delay-200">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-bold text-orange-500">Recreation Centers</h2>
                        <a href="{{ route('member.events') }}" class="text-orange-500 text-sm font-medium flex items-center gap-1 hover:gap-2 transition-all">
                            See All
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                            </svg>
                        </a>
                    </div>

                    @php
                        $popularLocations = \App\Models\Location::where('is_active', true)
                            ->with('media')
                            ->take(6)
                            ->get();
                    @endphp

                    <div class="carousel carousel-center space-x-4 pb-4 overflow scrollbar-hide" style="scroll-snap-type: x mandatory;">
                        @forelse($popularLocations as $index => $location)
                            @php
                                $apiKey = config('services.google_maps.api_key');
                                $locationImageUrl = $location->thumbnail_url ?? null;
                                if (!$locationImageUrl && $apiKey && $location->address) {
                                    $address = urlencode(trim($location->address . ', ' . $location->city . ', ' . $location->province . ', ' . $location->postal_code, ', '));
                                    $locationImageUrl = "https://maps.googleapis.com/maps/api/staticmap?center={$address}&zoom=15&size=400x200&markers=color:red|{$address}&key={$apiKey}";
                                }
                                
                                // Calculate distance (mock for now, you can implement real distance calculation)
                                $distance = rand(5, 50);
                            @endphp
                            <div class="carousel-item flex-none w-64 animate-slide-in" style="animation-delay: {{ ($index + 1) * 100 }}ms; scroll-snap-align: start;">
                                <a href="{{ route('member.events') }}" class="border border-gray-300 card bg-gray-100 shadow-lg rounded-2xl hover:shadow-md transition-all duration-300 w-full">
                                    <figure class="h-40 bg-gray-200 overflow-hidden">
                                        @if($locationImageUrl)
                                            <img src="{{ $locationImageUrl }}" alt="{{ $location->name }}" class="w-full h-full object-cover hover:scale-105 transition-transform duration-500" loading="lazy">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 text-base-300">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
                                                </svg>
                                            </div>
                                        @endif
                                    </figure>
                                    <div class="card-body p-4">
                                        <p class="text-xs text-primary font-semibold mb-1">{{ $distance }} km away</p>
                                        <h3 class="card-title text-base font-bold text-base-content line-clamp-2">{{ $location->name }}</h3>
                                    </div>
                                </a>
                            </div>
                        @empty
                            <div class="card bg-gray-100 shadow-lg rounded-2xl w-64">
                                <div class="card-body items-center justify-center h-64">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 text-base-300 mb-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                    </svg>
                                    <p class="text-sm text-base-300 font-medium">No locations available</p>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    {{-- </div> --}}

    @if(auth()->user()->qr_code)
        @push('scripts')
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
                            const thumbnail = document.getElementById('qr-code-thumbnail');
                            const full = document.getElementById('qr-code-full');
                            if (thumbnail) thumbnail.src = data.image;
                            if (full) full.src = data.image;
                        }
                    })
                    .catch(error => {
                        console.error('Error loading QR code:', error);
                    });
                }

                function openQrCodeModal() {
                    const modal = document.getElementById('qr-code-modal');
                    if (modal) {
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
                            const full = document.getElementById('qr-code-full');
                            if (data.image && full) {
                                full.src = data.image;
                            }
                            modal.showModal();
                        })
                        .catch(error => {
                            console.error('Error loading full QR code:', error);
                            modal.showModal();
                        });
                    }
                }
            </script>
        @endpush
    @endif

    @push('styles')
        <style>
            @keyframes fade-in {
                from {
                    opacity: 0;
                    transform: translateY(10px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            @keyframes slide-down {
                from {
                    opacity: 0;
                    transform: translateY(-20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            @keyframes slide-up {
                from {
                    opacity: 0;
                    transform: translateY(30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            @keyframes slide-in {
                from {
                    opacity: 0;
                    transform: translateX(30px);
                }
                to {
                    opacity: 1;
                    transform: translateX(0);
                }
            }

            @keyframes wave {
                0%, 100% {
                    transform: rotate(0deg);
                }
                25% {
                    transform: rotate(20deg);
                }
                75% {
                    transform: rotate(-20deg);
                }
            }

            @keyframes pulse-slow {
                0%, 100% {
                    opacity: 1;
                }
                50% {
                    opacity: 0.8;
                }
            }

            @keyframes bounce-slow {
                0%, 100% {
                    transform: translateY(0);
                }
                50% {
                    transform: translateY(-10px);
                }
            }

            .animate-fade-in {
                animation: fade-in 0.6s ease-out forwards;
                opacity: 0;
            }

            .animate-slide-down {
                animation: slide-down 0.6s ease-out forwards;
            }

            .animate-slide-up {
                animation: slide-up 0.6s ease-out forwards;
                opacity: 0;
            }

            .animate-slide-in {
                animation: slide-in 0.6s ease-out forwards;
                opacity: 0;
            }

            .animate-wave {
                display: inline-block;
                animation: wave 1s ease-in-out infinite;
                transform-origin: 70% 70%;
            }

            .animate-pulse-slow {
                animation: pulse-slow 3s ease-in-out infinite;
            }

            .animate-bounce-slow {
                animation: bounce-slow 2s ease-in-out infinite;
            }

            .delay-100 {
                animation-delay: 100ms;
            }

            .delay-200 {
                animation-delay: 200ms;
            }

            .scrollbar-hide {
                -ms-overflow-style: none;
                scrollbar-width: none;
            }

            .scrollbar-hide::-webkit-scrollbar {
                display: none;
            }

            .line-clamp-2 {
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }
        </style>
    @endpush
</x-app-layout>

