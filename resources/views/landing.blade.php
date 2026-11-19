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
            go(n) {
                if (n < 1 || n > this.total || n === this.slide || this.animating) return;
                this.animating = true;
                this.slide = n;
                setTimeout(() => { this.animating = false }, 520);
            },
            next() { this.go(this.slide + 1) },
            prev() { this.go(this.slide - 1) },
        }"
        class="mx-auto flex h-dvh min-h-dvh w-full max-w-md flex-col px-1 pt-6 pb-[max(1.5rem,env(safe-area-inset-bottom))] sm:pt-10"
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

                    <div class="mt-auto shrink-0 pt-6 pb-2">
                        <button
                            type="button"
                            @click="next()"
                            class="inline-flex w-full items-center justify-center rounded-full bg-orange-500 px-6 py-3.5 text-sm font-semibold uppercase tracking-wider text-white transition hover:bg-orange-600"
                        >
                            Get Started
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
    </div>
</x-guest-layout>
