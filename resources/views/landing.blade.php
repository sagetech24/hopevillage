<x-guest-layout>
    @push('styles')
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        {{-- <link href="https://fonts.googleapis.com/css2?family=Carter+One&display=swap" rel="stylesheet"> --}}
        <link href="https://fonts.googleapis.com/css2?family=Carter+One&family=Sansation:ital,wght@0,300;0,400;0,700;1,300;1,400;1,700&display=swap" rel="stylesheet">
        <style>
            .font-carter-one {
                font-family: 'Carter One', system-ui, sans-serif;
            }

            .sansation-light {
                font-family: "Sansation", sans-serif;
                font-weight: 300;
                font-style: normal;
            }

            .sansation-regular {
                font-family: "Sansation", sans-serif;
                font-weight: 400;
                font-style: normal;
            }

            .sansation-bold {
                font-family: "Sansation", sans-serif;
                font-weight: 700;
                font-style: normal;
            }

            .sansation-light-italic {
                font-family: "Sansation", sans-serif;
                font-weight: 300;
                font-style: italic;
            }

            .sansation-regular-italic {
                font-family: "Sansation", sans-serif;
                font-weight: 400;
                font-style: italic;
            }

            .sansation-bold-italic {
                font-family: "Sansation", sans-serif;
                font-weight: 700;
                font-style: italic;
            }

        </style>
    @endpush

    <div
        x-data="{
            slide: 1,
            total: 4,
            animating: false,
            eventsModalOpen: false,
            go(n) {
                if (n < 1 || n > this.total || n === this.slide || this.animating) return;
                this.animating = true;
                this.slide = n;
                setTimeout(() => { this.animating = false }, 520);
            },
            next() { this.go(this.slide + 1) },
            prev() { this.go(this.slide - 1) },
            openEventsModal() { this.eventsModalOpen = true },
            closeEventsModal() { this.eventsModalOpen = false },
        }"
        class="mx-auto flex h-dvh min-h-dvh w-full max-w-md flex-col px-1 pt-6 pb-[max(1.5rem,env(safe-area-inset-bottom))] sm:pt-10"
        @keydown.escape.window="closeEventsModal()"
    >
        {{-- Progress dots --}}
        <div class="mb-6 flex shrink-0 items-center justify-center gap-2">
            <template x-for="n in total" :key="n">
                <button
                    type="button"
                    @click="go(n)"
                    class="h-2 rounded-full transition-all duration-500 ease-out"
                    :class="slide === n ? 'w-6 bg-orange-500' : 'w-2 bg-[#205076]/25'"
                    :aria-label="'Go to slide ' + n"
                ></button>
            </template>
        </div>

        <div class="relative min-h-0 flex-1 overflow-hidden">
            <div
                class="flex h-full transition-transform duration-500 ease-[cubic-bezier(0.22,1,0.36,1)] will-change-transform"
                :style="'transform: translate3d(-' + ((slide - 1) * 100) + '%, 0, 0)'"
            >
                {{-- Slide 1: Welcome --}}
                <section class="flex h-full w-full shrink-0 flex-col px-0.5" aria-hidden="false" :aria-hidden="slide !== 1">
                    <div class="flex min-h-0 flex-1 flex-col items-center justify-center overflow-y-auto text-center">
                        <div class="relative flex flex-col items-center justify-center sansation-bold">
                            <span class="text-4xl leading-tight text-[#205076] sm:text-3xl">
                                Welcome
                            </span>
                            <span class="text-2xl font-sansation-bold text-[#205076]">to</span>
                            <img
                                src="{{ asset('hv-logo.png') }}"
                                alt="Hope Village"
                                class="my-2 w-30 object-contain sm:w-32"
                            >
                            <span class="absolute -bottom-7 inline-block drop-shadow-xl font-sansation-bold text-2xl sm:text-xl text-[#205076] -rotate-[3deg]">@ KBRC</span>
                        </div>

                        <p class="mt-16 text-md font-bold text-[#205076]/80">
                            About Hope Village @ KBRC
                        </p>

                        <p class="mt-4 max-w-md text-sm leading-relaxed text-[#205076]">
                            Operated by Hope Initiative Alliance in partnership with Advancer IFM Pte Ltd., 
                            under appointment by the Ministry of Manpower, Hope Village @ KBRC is a holistic 
                            community hub integrating recreation, skills development, health and legal support, 
                            and social connection. More than a physical space, it reflects a commitment to ensuring 
                            migrant workers feel valued, supported, and at home during their time in Singapore.
                        </p>
                    </div>

                    <div class="mt-auto shrink-0 pt-6 pb-2 flex gap-2">
                        {{-- add a login button here --}}
                        <a href="{{ route('login') }}" class="inline-flex w-full items-center uppercase justify-center rounded-full border border-orange-500 bg-white px-6 py-3.5 text-xs font-semibold text-orange-600 transition hover:bg-orange-50">
                            Login
                        </a>
                        <button
                            type="button"
                            @click="next()"
                            class="inline-flex w-full items-center justify-center rounded-full bg-orange-500 px-6 py-3.5 text-xs font-semibold uppercase tracking-wider text-white transition hover:bg-orange-600"
                        >
                            Get Started
                            {{-- add arrow right icon here --}}
                            <svg class="ml-1 size-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>
                </section>

                {{-- Slide 2: Events & Points --}}
                <section class="flex h-full w-full shrink-0 flex-col px-0.5" :aria-hidden="slide !== 2">
                    <div class="flex min-h-0 flex-1 flex-col justify-center overflow-y-auto">
                        <div class="flex justify-center">
                            <img src="{{ asset('onboarding-1.svg') }}" alt="Events & Points" class="w-72 object-contain" loading="lazy">
                        </div>

                        <h2 class="text-center text-2xl font-extrabold text-[#205076]">
                            Events &amp; Points
                        </h2>
                        <p class="text-center text-sm text-[#205076]/70">
                            Join in, show up, earn rewards
                        </p>

                        <div class="mt-8">
                            <p class="text-center text-sm leading-relaxed text-[#205076]/70">
                                Browse upcoming activities at Hope Village — recreation, skills development, health talks, and more. Favourite events you care about and earn points when you attend.
                            </p>

                            <div class="mt-5 flex justify-center">
                                <button
                                    type="button"
                                    @click="openEventsModal()"
                                    class="inline-flex items-center justify-center gap-2 rounded-full border border-orange-500 bg-white px-5 py-2.5 text-sm font-semibold text-orange-600 transition hover:bg-orange-50"
                                >
                                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    View Upcoming Events
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="mt-auto shrink-0 flex gap-3 pt-6 pb-2">
                        <button
                            type="button"
                            @click="prev()"
                            class="inline-flex flex-1 items-center justify-center rounded-full border-2 border-[#205076] px-4 py-3 text-sm font-semibold text-[#205076] transition hover:bg-[#205076]/5"
                        >
                            Back
                        </button>
                        <button
                            type="button"
                            @click="next()"
                            class="inline-flex flex-1 items-center justify-center rounded-full bg-orange-500 px-4 py-3 text-sm font-semibold uppercase tracking-wider text-white transition hover:bg-orange-600"
                        >
                            Next
                        </button>
                    </div>
                </section>

                {{-- Slide 3: Marketplace & Vouchers --}}
                <section class="flex h-full w-full shrink-0 flex-col px-0.5" :aria-hidden="slide !== 3">
                    <div class="flex min-h-0 flex-1 flex-col justify-center overflow-y-auto">
                        <div class="mb-6 flex justify-center">
                            <div class="flex size-36 items-center justify-center">
                                <img src="{{ asset('marketplace-1.svg') }}" alt="Marketplace & Vouchers" class="w-full object-contain" loading="lazy">
                            </div>
                        </div>

                        <h2 class="text-center text-2xl font-extrabold text-[#205076]">
                            Marketplace &amp; Vouchers
                        </h2>
                        <p class="text-center text-sm text-[#205076]/70">
                            Spend points. Redeem rewards.
                        </p>

                        <div class="mt-8 space-y-4">
                            <p class="mt-2 text-sm leading-relaxed text-gray-600">
                                Use your points in the marketplace to get items and rewards available at Hope Village. 
                                Claim partner and Hope Village vouchers — meals, services, and special offers from merchants who support the community.
                                Show your QR code or voucher at a partner merchant to redeem. Fast, simple, and tracked in your account.
                            </p>
                        </div>
                    </div>

                    <div class="mt-auto shrink-0 flex gap-3 pt-6 pb-2">
                        <button
                            type="button"
                            @click="prev()"
                            class="inline-flex flex-1 items-center justify-center rounded-full border-2 border-[#205076] px-4 py-3 text-sm font-semibold text-[#205076] transition hover:bg-[#205076]/5"
                        >
                            Back
                        </button>
                        <button
                            type="button"
                            @click="next()"
                            class="inline-flex flex-1 items-center justify-center rounded-full bg-orange-500 px-4 py-3 text-sm font-semibold uppercase tracking-wider text-white transition hover:bg-orange-600"
                        >
                            Next
                        </button>
                    </div>
                </section>

                {{-- Slide 4: Login / Register --}}
                <section class="flex h-full w-full shrink-0 flex-col px-0.5" :aria-hidden="slide !== 4">
                    <div class="flex min-h-0 flex-1 flex-col items-center justify-center overflow-y-auto text-center">
                        <img
                            src="{{ asset('hv-logo.png') }}"
                            alt="Hope Village"
                            class="mb-6 w-24 object-contain"
                        >

                        <h2 class="text-2xl font-extrabold text-[#205076]">
                            Ready to begin?
                        </h2>
                        <p class="mt-3 max-w-xs text-sm leading-relaxed text-[#205076]/80">
                            Log in to your account or register to join Hope Village — events, points, marketplace, and vouchers await.
                        </p>
                    </div>

                    <div class="mt-auto shrink-0 space-y-3 pt-6 pb-2">
                        <a
                            href="{{ route('login') }}"
                            class="inline-flex w-full items-center justify-center rounded-full bg-orange-500 px-6 py-3.5 text-sm font-semibold uppercase tracking-wider text-white transition hover:bg-orange-600"
                        >
                            Log in
                        </a>
                        <a
                            href="{{ route('register') }}"
                            class="inline-flex w-full items-center justify-center rounded-full bg-[#205076] px-6 py-3.5 text-sm font-semibold uppercase tracking-wider text-white transition hover:bg-[#1a4260]"
                        >
                            Register
                        </a>
                        <button
                            type="button"
                            @click="prev()"
                            class="inline-flex w-full items-center justify-center py-2 text-sm font-medium text-[#205076]/70 border border-[#205076]/70 rounded-full transition hover:text-[#205076]"
                        >
                            Back
                        </button>
                    </div>
                </section>
            </div>
        </div>

        {{-- Upcoming events modal --}}
        <div
            x-show="eventsModalOpen"
            x-cloak
            class="fixed inset-0 z-50 flex items-end justify-center sm:items-center"
            role="dialog"
            aria-modal="true"
            aria-labelledby="upcoming-events-title"
        >
            <div
                x-show="eventsModalOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="absolute inset-0 bg-slate-900/50"
                @click="closeEventsModal()"
            ></div>

            <div
                x-show="eventsModalOpen"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-6 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-6 sm:translate-y-0 sm:scale-95"
                class="relative z-10 mx-1 flex max-h-[min(85dvh,36rem)] w-full max-w-md flex-col overflow-hidden rounded-2xl bg-white shadow-xl sm:mb-0"
                @click.stop
            >
                <div class="flex shrink-0 items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
                    <h3 id="upcoming-events-title" class="text-lg font-bold text-[#205076]">
                        Upcoming Events
                    </h3>
                    <button
                        type="button"
                        @click="closeEventsModal()"
                        class="inline-flex size-9 items-center justify-center rounded-full text-gray-500 transition hover:bg-gray-100 hover:text-gray-800"
                        aria-label="Close"
                    >
                        <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto px-4 py-4">
                    @if ($upcomingEvents->isNotEmpty())
                        <div class="space-y-3">
                            @foreach ($upcomingEvents as $event)
                                <article class="overflow-hidden rounded-2xl bg-orange-50/60 ring-1 ring-black/5">
                                    <div class="flex gap-3 p-3">
                                        <div class="h-16 w-16 shrink-0 overflow-hidden rounded-xl bg-orange-100">
                                            @if ($event->thumbnail_url)
                                                <img
                                                    src="{{ $event->thumbnail_url }}"
                                                    alt="{{ $event->title }}"
                                                    class="h-full w-full object-cover"
                                                    loading="lazy"
                                                >
                                            @else
                                                <div class="flex h-full w-full items-center justify-center text-orange-400">
                                                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="min-w-0 flex-1">
                                            <h4 class="text-sm font-semibold text-gray-900">{{ $event->title }}</h4>
                                            <p class="mt-0.5 truncate text-xs text-gray-600">{{ $event->formattedSchedule() }}</p>
                                            @if ($event->venue || $event->location?->name)
                                                <p class="mt-0.5 truncate text-xs text-gray-500">
                                                    {{ $event->venue ?: $event->location?->name }}
                                                </p>
                                            @endif
                                            <span class="mt-1.5 inline-flex rounded-full px-2 py-0.5 text-[0.65rem] font-medium {{ $event->scheduleBadgeClasses() }}">
                                                {{ $event->scheduleLabel() }}
                                            </span>
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        <p class="mt-4 text-center text-xs text-gray-500">
                            <a href="{{ route('login') }}" class="font-semibold text-orange-600 hover:text-orange-500">Log in</a>
                            to join events and see more details.
                        </p>
                    @else
                        <div class="flex flex-col items-center justify-center px-4 py-10 text-center">
                            <div class="mb-3 flex size-12 items-center justify-center rounded-full bg-orange-100 text-orange-500">
                                <svg class="size-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <p class="text-sm font-semibold text-[#205076]">No upcoming events right now</p>
                            <p class="mt-1 text-xs text-gray-500">Check back soon for new activities at Hope Village.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
