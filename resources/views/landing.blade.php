<x-guest-layout>
    <div class="mx-auto min-h-screen w-full max-w-3xl py-8 sm:py-12">
        <header class="mb-8 flex items-center justify-center gap-4">
            <img
                src="{{ asset('hv-logo.png') }}"
                alt="Hope Village"
                class="w-30 object-contain"
            >
        </header>

        @if ($upcomingEvents->isNotEmpty())
            <section class="mb-8">
                <h2 class="mb-2 text-lg font-bold text-gray-900">Upcoming Events</h2>

                <div class="space-y-2">
                    @foreach ($upcomingEvents as $event)
                        <article class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-black/5">
                            <div class="flex gap-4 p-4">
                                <div class="h-20 w-20 shrink-0 overflow-hidden rounded-xl bg-orange-100">
                                    @if ($event->thumbnail_url)
                                        <img
                                            src="{{ $event->thumbnail_url }}"
                                            alt="{{ $event->title }}"
                                            class="h-full w-full object-cover"
                                            loading="lazy"
                                        >
                                    @else
                                        <div class="flex h-full w-full items-center justify-center text-orange-400">
                                            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                    @endif
                                </div>

                                <div class="min-w-0 flex-1">
                                    <h3 class="text-md font-semibold text-gray-900">{{ $event->title }}</h3>
                                    <p class="mt-1 text-xs text-gray-600 truncate">{{ $event->formattedSchedule() }}</p>
                                    @if ($event->venue || $event->location?->name)
                                        <p class="mt-1 truncate text-xs text-gray-500">
                                            Venue: {{ $event->venue ?: $event->location?->name }}
                                        </p>
                                    @endif
                                    <span class="mt-2 inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $event->scheduleBadgeClasses() }}">
                                        {{ $event->scheduleLabel() }}
                                    </span>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <p class="mt-3 text-center text-sm text-gray-600">
                    <a href="{{ route('login') }}" class="font-semibold text-orange-600 hover:text-orange-500">
                        Log in
                    </a>
                    to join events and see more details.
                </p>
                <div class="mt-8 flex justify-center gap-2">
                    <a
                        href="{{ route('login') }}"
                        class="inline-flex w-full max-w-xs items-center justify-center rounded-full bg-orange-500 px-6 py-3 text-sm font-semibold uppercase tracking-wider text-white transition hover:bg-orange-600 sm:w-auto sm:min-w-[12rem]"
                    >
                        Login
                    </a>
                    <a
                        href="{{ route('register') }}"
                        class="inline-flex w-full max-w-xs items-center justify-center rounded-full bg-[#205076] px-6 py-3 text-sm font-semibold uppercase tracking-wider text-white transition hover:bg-[#205076]/90 sm:w-auto sm:min-w-[12rem]"
                    >
                        Register
                    </a>
                </div>
            </section>
        @endif

        <section class="mt-12">
            <h2 class="text-md font-bold text-[#205076]">About Hope Village @ KBRC</h2>
            <p class="mt-2 text-xs leading-snug text-[#205076]">
                Operated by Hope Initiative Alliance in partnership with Advancer IFM Pte Ltd., under appointment by the Ministry of Manpower, Hope Village @ KBRC is a holistic community hub integrating recreation, skills development, health and legal support, and social connection. More than a physical space, it reflects a commitment to ensuring migrant workers feel valued, supported, and at home during their time in Singapore.
            </p>
        </section>
    </div>
</x-guest-layout>
