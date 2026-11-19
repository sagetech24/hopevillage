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
                            <img src="{{ asset('onboarding-1.svg') }}" alt="Events & Points" class="w-72 object-contain">
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
                                <svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 512 512" xml:space="preserve" fill="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><!-- Uploaded to: SVG Repo, www.svgrepo.com, Generator: SVG Repo Mixer Tools --> <polygon style="fill:#FFD652;" points="311.582,503.983 200.418,503.983 217.52,367.165 294.48,367.165 "></polygon> <polygon style="fill:#FB9D46;" points="294.48,367.165 217.52,367.165 211.106,418.472 300.894,418.472 "></polygon> <path style="fill:#E5E5E5;" d="M467.641,392.818H44.359c-17.71,0-32.067-14.356-32.067-32.067V40.084 c0-17.71,14.356-32.067,32.067-32.067h423.282c17.711,0,32.067,14.356,32.067,32.067v320.668 C499.708,378.462,485.351,392.818,467.641,392.818z"></path> <g> <polygon style="fill:#FFFFFF;" points="288.78,392.818 322.806,392.818 499.708,215.917 499.708,181.89 "></polygon> <polygon style="fill:#FFFFFF;" points="376.251,392.818 499.708,269.361 499.708,231.415 338.305,392.818 "></polygon> </g> <path style="fill:#FFD652;" d="M12.292,341.511v19.24c0,17.71,14.356,32.067,32.067,32.067h423.282 c17.711,0,32.067-14.356,32.067-32.067v-19.24H12.292z"></path> <path style="fill:#CCCCCC;" d="M42.221,360.752V40.084c0-17.71,14.356-32.067,32.067-32.067H44.359 c-17.71,0-32.067,14.356-32.067,32.067v320.668c0,17.71,14.356,32.067,32.067,32.067h29.929 C56.578,392.818,42.221,378.462,42.221,360.752z"></path> <path style="fill:#FB9D46;" d="M42.221,360.752v-19.24H12.292v19.24c0,17.71,14.356,32.067,32.067,32.067h29.929 C56.578,392.818,42.221,378.462,42.221,360.752z"></path> <polygon style="fill:#FF7452;" points="285.929,67.34 285.929,40.618 251.724,40.618 251.724,67.34 230.347,67.34 268.827,98.338 307.307,67.34 "></polygon> <rect x="251.724" y="40.618" style="fill:#E24642;" width="34.205" height="17.102"></rect> <path d="M499.708,80.167c4.428,0,8.017-3.589,8.017-8.017V40.084C507.724,17.981,489.744,0,467.641,0H44.359 C22.257,0,4.276,17.981,4.276,40.084v320.668c0,22.103,17.981,40.083,40.084,40.083h59.858c4.427,0,8.017-3.589,8.017-8.017 c0-4.427-3.589-8.017-8.017-8.017H44.359c-13.262,0-24.05-10.788-24.05-24.05v-11.223h471.382v11.223 c0,13.262-10.789,24.05-24.05,24.05H142.697c-4.427,0-8.017,3.589-8.017,8.017c0,4.427,3.589,8.017,8.017,8.017h62.535 l-11.891,95.132h-61.332c-4.427,0-8.017,3.589-8.017,8.017c0,4.427,3.589,8.017,8.017,8.017h247.983 c4.428,0,8.017-3.589,8.017-8.017c0-4.427-3.588-8.017-8.017-8.017H318.66l-11.891-95.132h160.873 c22.103,0,40.083-17.981,40.083-40.083V112.768c0-4.427-3.588-8.017-8.017-8.017s-8.017,3.589-8.017,8.017v220.727H20.309V40.084 c0-13.262,10.788-24.05,24.05-24.05h423.282c13.261,0,24.05,10.788,24.05,24.05V72.15C491.691,76.578,495.279,80.167,499.708,80.167 z M302.501,495.967h-93.002l11.891-95.132h69.219L302.501,495.967z"></path> <path d="M264.551,375.182c4.428,0,8.017-3.589,8.017-8.017c0-4.427-3.588-8.017-8.017-8.017h-17.102 c-4.427,0-8.017,3.589-8.017,8.017c0,4.427,3.589,8.017,8.017,8.017H264.551z"></path> <path d="M357.668,258.535l42.756-128.267c0.816-2.445,0.405-5.132-1.102-7.223c-1.506-2.091-3.926-3.33-6.503-3.33h-55.575 c-0.018,0-0.036,0-0.055,0H200.457c-0.015,0-0.03,0-0.044,0h-50.889l-14.133-45.661c-1.04-3.356-4.144-5.646-7.659-5.646H72.15 c-4.427,0-8.017,3.589-8.017,8.017s3.589,8.017,8.017,8.017h49.671l53.835,173.928c1.039,3.357,4.143,5.646,7.659,5.646h166.747 C353.514,264.017,356.578,261.808,357.668,258.535z M276.843,247.983v-26.722h33.189l-5.435,26.722H276.843z M233.056,247.983 l-5.435-26.722h33.189v26.722H233.056z M167.721,178.505h34.843l5.435,26.722h-32.006L167.721,178.505z M218.925,178.505h41.885 v26.722h-36.45L218.925,178.505z M260.81,162.472h-45.146l-5.435-26.722h50.581V162.472z M276.843,135.749h50.581l-5.435,26.722 h-45.146V135.749z M276.843,205.228v-26.722h41.885l-5.435,26.722H276.843z M335.091,178.505h32.354l-8.907,26.722h-28.881 L335.091,178.505z M372.788,162.472h-34.438l5.435-26.722h37.91L372.788,162.472z M193.867,135.749l5.435,26.722h-36.544 l-8.271-26.722H193.867z M180.955,221.261h30.304l5.435,26.722h-27.468L180.955,221.261z M320.959,247.983l5.435-26.722h26.798 l-8.907,26.722H320.959z"></path> <path d="M286.463,294.48c0,16.209,13.187,29.395,29.395,29.395c16.208,0,29.395-13.186,29.395-29.395 c0-16.209-13.187-29.395-29.395-29.395C299.65,265.086,286.463,278.271,286.463,294.48z M329.219,294.48 c0,7.368-5.993,13.361-13.361,13.361s-13.361-5.993-13.361-13.361s5.993-13.361,13.361-13.361S329.219,287.112,329.219,294.48z"></path> <path d="M192.401,294.48c0,16.209,13.186,29.395,29.395,29.395s29.395-13.186,29.395-29.395c0-16.209-13.186-29.395-29.395-29.395 S192.401,278.271,192.401,294.48z M235.157,294.48c0,7.368-5.993,13.361-13.361,13.361s-13.361-5.993-13.361-13.361 s5.993-13.361,13.361-13.361S235.157,287.112,235.157,294.48z"></path> <path d="M285.929,32.067h-34.205c-4.427,0-8.017,3.589-8.017,8.017v19.775h-13.361c-3.4,0-6.429,2.144-7.56,5.35 c-1.131,3.207-0.115,6.777,2.531,8.909l38.48,30.998c1.468,1.182,3.248,1.773,5.029,1.773c1.781,0,3.562-0.591,5.029-1.773 l38.48-30.998c2.648-2.134,3.662-5.704,2.531-8.909c-1.131-3.206-4.16-5.35-7.56-5.35h-13.361V40.084 C293.946,35.656,290.357,32.067,285.929,32.067z M268.827,88.578L252.96,75.796c3.841-0.594,6.781-3.914,6.781-7.922V48.1h18.171 v19.775c0,4.007,2.941,7.327,6.781,7.923L268.827,88.578z"></path> </g></svg>
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
