@php
    $inputClass = 'w-full rounded-full border border-orange-400 bg-white px-4 py-2.5 text-sm text-gray-800 shadow-sm placeholder:text-gray-400 focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-500/20';
    $textareaClass = 'w-full rounded-2xl border border-orange-400 bg-white px-4 py-2.5 text-sm text-gray-800 shadow-sm placeholder:text-gray-400 focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-500/20';
    $errorClass = 'border-red-400 focus:border-red-500 focus:ring-red-500/20';
@endphp

<div>
    {{-- Loading Overlay --}}
    <div
        wire:loading.flex
        wire:target="submit"
        class="fixed inset-0 z-50 items-center justify-center bg-slate-900/50 backdrop-blur-sm"
    >
        <div class="mx-4 flex max-w-sm flex-col items-center gap-4 rounded-2xl bg-white px-8 py-7 shadow-xl">
            <div class="h-11 w-11 animate-spin rounded-full border-[3px] border-orange-200 border-t-orange-500"></div>
            <div class="text-center">
                <p class="text-sm font-semibold text-gray-800">Submitting your application</p>
                <p class="mt-1 text-xs text-gray-500">Please wait a moment…</p>
            </div>
        </div>
    </div>

    <div class="relative min-h-screen overflow-hidden py-8 sm:py-12">
        <div class="pointer-events-none absolute inset-0" aria-hidden="true">
            <div class="absolute -top-24 -right-16 size-72 rounded-full bg-orange-300/40 blur-3xl"></div>
            <div class="absolute top-1/3 -left-20 size-80 rounded-full bg-sky-200/30 blur-3xl"></div>
            <div class="absolute -bottom-28 right-1/4 size-72 rounded-full bg-amber-200/40 blur-3xl"></div>
        </div>

        <div class="relative mx-auto flex w-full max-w-2xl flex-col items-center px-1">
            @if($showSuccess)
                <div class="w-full overflow-hidden rounded-2xl border border-white/70 bg-white/95 shadow-xl shadow-orange-900/5 backdrop-blur">
                    <div class="bg-[#3a5870] px-6 py-8 text-center sm:px-10">
                        <img src="{{ asset('hv-logo.png') }}" alt="Hope Village" class="mx-auto h-16 w-16 object-contain drop-shadow sm:h-20 sm:w-20">
                        <p class="mt-3 text-xs font-semibold uppercase tracking-[0.2em] text-white/70">Hope Village</p>
                    </div>
                    <div class="px-6 py-10 text-center sm:px-10">
                        <div class="mx-auto flex size-14 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 ring-8 ring-emerald-50/60">
                            <svg class="size-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h2 class="mt-5 text-2xl font-bold text-gray-900">Application submitted</h2>
                        <p class="mx-auto mt-3 max-w-md text-sm leading-relaxed text-gray-600">
                            Thank you for applying. Your merchant application is pending admin review.
                            We’ll email you once a decision has been made.
                        </p>
                        <a
                            href="{{ route('login') }}"
                            class="mt-8 inline-flex items-center justify-center rounded-full bg-orange-500 px-6 py-3 text-sm font-semibold text-white transition hover:bg-orange-600"
                        >
                            Go to login
                        </a>
                    </div>
                </div>
            @else
                <div class="w-full overflow-hidden rounded-2xl border border-white/70 bg-white/95 shadow-xl shadow-orange-900/5 backdrop-blur">
                    {{-- Header --}}
                    <div class="relative overflow-hidden bg-[#3a5870] px-6 pb-8 pt-7 sm:px-10 sm:pb-10 sm:pt-9">
                        {{-- <div class="pointer-events-none absolute inset-0 opacity-70" aria-hidden="true">
                            <div class="absolute -right-10 -top-10 size-40 rounded-full bg-orange-400/50 blur-2xl"></div>
                            <div class="absolute -bottom-12 -left-8 size-44 rounded-full bg-sky-400/30 blur-2xl"></div>
                        </div> --}}

                        <div class="relative flex flex-col items-center gap-5 text-center sm:flex-row sm:items-end sm:text-left">
                            <img
                                src="{{ asset('hv-logo.png') }}"
                                alt="Hope Village"
                                class="h-16 w-16 shrink-0 object-contain drop-shadow sm:h-20 sm:w-20"
                            >
                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-white/65">Merchant Portal</p>
                                <h1 class="mt-1 text-2xl font-bold tracking-tight text-white sm:text-3xl">
                                    Merchant Application
                                </h1>
                                <p class="mt-2 max-w-xl text-sm text-white/75">
                                    Share a few details about your business. Our team will review your application and get back to you.
                                </p>
                            </div>
                        </div>
                    </div>

                    <form
                        class="px-5 py-7 sm:px-10 sm:py-9"
                        x-data
                        @submit.prevent="
                            const token = (typeof grecaptcha !== 'undefined') ? grecaptcha.getResponse() : '';
                            await $wire.set('gRecaptchaResponse', token);
                            await $wire.submit();
                        "
                    >
                        {{-- Section: Business --}}
                        <section>
                            <div class="mb-5 flex items-center gap-3">
                                <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-orange-500 text-xs font-bold text-white">1</span>
                                <div>
                                    <h2 class="text-base font-bold text-gray-900">Business Details</h2>
                                    <p class="text-xs text-gray-500">How your store will appear to Hope Village members</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                                {{-- Logo --}}
                                <div class="lg:col-span-4">
                                    <label for="logo" class="mb-2 block text-sm font-medium text-gray-700">
                                        Business logo
                                        <span class="font-normal text-gray-400">(optional)</span>
                                    </label>
                                    <label
                                        for="logo"
                                        class="group relative flex h-40 w-full cursor-pointer flex-col items-center justify-center overflow-hidden rounded-2xl border-2 border-dashed border-orange-200 bg-orange-50/40 transition hover:border-orange-400 hover:bg-orange-50"
                                    >
                                        @if($logo)
                                            <img
                                                src="{{ $logo->temporaryUrl() }}"
                                                alt="Logo preview"
                                                class="absolute inset-0 h-full w-full object-cover"
                                            >
                                            <span class="absolute inset-x-0 bottom-0 bg-black/50 py-1.5 text-center text-xs font-medium text-white">
                                                Change logo
                                            </span>
                                        @else
                                            <svg class="mb-2 size-9 text-orange-300 transition group-hover:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                            <span class="text-sm font-medium text-gray-600">Click to upload</span>
                                            <span class="mt-1 text-xs text-gray-400">JPG, PNG or WebP · max 2MB</span>
                                        @endif
                                    </label>
                                    <input
                                        type="file"
                                        id="logo"
                                        wire:model="logo"
                                        accept="image/jpeg,image/png,image/webp"
                                        class="hidden"
                                    >
                                    <div wire:loading wire:target="logo" class="mt-2 text-xs font-medium text-orange-500">
                                        Uploading…
                                    </div>
                                    @error('logo')
                                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="space-y-4 lg:col-span-8">
                                    <div>
                                        <label for="name" class="mb-1.5 block text-sm font-medium text-gray-700">
                                            Merchant Name <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            id="name"
                                            wire:model="name"
                                            class="{{ $inputClass }} @error('name') {{ $errorClass }} @enderror"
                                            placeholder="Your business name"
                                            required
                                        >
                                        @error('name')
                                            <p class="mt-1.5 text-sm text-red-500">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="description" class="mb-1.5 block text-sm font-medium text-gray-700">
                                            Short description about your business
                                            <span class="font-normal text-gray-400">(optional)</span>
                                        </label>
                                        <textarea
                                            id="description"
                                            wire:model="description"
                                            rows="4"
                                            class="{{ $textareaClass }} @error('description') {{ $errorClass }} @enderror"
                                            placeholder="Tell members a little about your business…"
                                        ></textarea>
                                        @error('description')
                                            <p class="mt-1.5 text-sm text-red-500">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </section>

                        <div class="my-8 border-t border-orange-100"></div>

                        {{-- Section: Address --}}
                        <section>
                            <div class="mb-5 flex items-center gap-3">
                                <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-orange-500 text-xs font-bold text-white">2</span>
                                <div>
                                    <h2 class="text-base font-bold text-gray-900">Business address</h2>
                                    <p class="text-xs text-gray-500">Where members can find you</p>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                    <div class="sm:col-span-2">
                                        <label for="address" class="mb-1.5 block text-sm font-medium text-gray-700">
                                            Street address
                                        </label>
                                        <input
                                            type="text"
                                            id="address"
                                            wire:model="address"
                                            class="{{ $inputClass }} @error('address') {{ $errorClass }} @enderror"
                                            placeholder="Street name, building name, etc."
                                        >
                                        @error('address')
                                            <p class="mt-1.5 text-sm text-red-500">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="unitNumber" class="mb-1.5 block text-sm font-medium text-gray-700">
                                            Unit number
                                            <span class="font-normal text-gray-400">(optional)</span>
                                        </label>
                                        <input
                                            type="text"
                                            id="unitNumber"
                                            wire:model="unitNumber"
                                            class="{{ $inputClass }} @error('unitNumber') {{ $errorClass }} @enderror"
                                            placeholder="e.g. 01-01"
                                        >
                                        @error('unitNumber')
                                            <p class="mt-1.5 text-sm text-red-500">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                    <div>
                                        <label for="city" class="mb-1.5 block text-sm font-medium text-gray-700">City</label>
                                        <input
                                            type="text"
                                            id="city"
                                            wire:model="city"
                                            class="{{ $inputClass }} @error('city') {{ $errorClass }} @enderror"
                                            placeholder="City"
                                        >
                                        @error('city')
                                            <p class="mt-1.5 text-sm text-red-500">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="province" class="mb-1.5 block text-sm font-medium text-gray-700">Province / state</label>
                                        <input
                                            type="text"
                                            id="province"
                                            wire:model="province"
                                            class="{{ $inputClass }} @error('province') {{ $errorClass }} @enderror"
                                            placeholder="Province"
                                        >
                                        @error('province')
                                            <p class="mt-1.5 text-sm text-red-500">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="postal_code" class="mb-1.5 block text-sm font-medium text-gray-700">Postal code</label>
                                        <input
                                            type="text"
                                            id="postal_code"
                                            wire:model="postal_code"
                                            class="{{ $inputClass }} @error('postal_code') {{ $errorClass }} @enderror"
                                            placeholder="123456"
                                        >
                                        @error('postal_code')
                                            <p class="mt-1.5 text-sm text-red-500">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </section>

                        <div class="my-8 border-t border-orange-100"></div>

                        {{-- Section: Contact --}}
                        <section>
                            <div class="mb-5 flex items-center gap-3">
                                <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-orange-500 text-xs font-bold text-white">3</span>
                                <div>
                                    <h2 class="text-base font-bold text-gray-900">Contact Information</h2>
                                    <p class="text-xs text-gray-500">How we can reach you about your application</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                                <div>
                                    <label for="contact_name" class="mb-1.5 block text-sm font-medium text-gray-700">
                                        Contact person <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        id="contact_name"
                                        wire:model="contact_name"
                                        class="{{ $inputClass }} @error('contact_name') {{ $errorClass }} @enderror"
                                        placeholder="Full name"
                                        required
                                    >
                                    @error('contact_name')
                                        <p class="mt-1.5 text-sm text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="phone" class="mb-1.5 block text-sm font-medium text-gray-700">
                                        Phone number <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="tel"
                                        id="phone"
                                        wire:model="phone"
                                        class="{{ $inputClass }} @error('phone') {{ $errorClass }} @enderror"
                                        placeholder="+65XXXXXXXX"
                                        maxlength="12"
                                        required
                                    >
                                    <p class="mt-1.5 text-xs text-gray-500">Singapore numbers are normalized to +65.</p>
                                    @error('phone')
                                        <p class="mt-1.5 text-sm text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="email" class="mb-1.5 block text-sm font-medium text-gray-700">
                                        Email address
                                        <span class="font-normal text-gray-400">(optional)</span>
                                    </label>
                                    <input
                                        type="email"
                                        id="email"
                                        wire:model="email"
                                        class="{{ $inputClass }} @error('email') {{ $errorClass }} @enderror"
                                        placeholder="business@example.com"
                                    >
                                    @error('email')
                                        <p class="mt-1.5 text-sm text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="website" class="mb-1.5 block text-sm font-medium text-gray-700">
                                        Website
                                        <span class="font-normal text-gray-400">(optional)</span>
                                    </label>
                                    <input
                                        type="url"
                                        id="website"
                                        wire:model="website"
                                        class="{{ $inputClass }} @error('website') {{ $errorClass }} @enderror"
                                        placeholder="https://www.example.com"
                                    >
                                    @error('website')
                                        <p class="mt-1.5 text-sm text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </section>

                        <div class="my-8 border-t border-orange-100"></div>

                        {{-- Section: Credentials --}}
                        <section>
                            <div class="mb-5 flex items-center gap-3">
                                <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-orange-500 text-xs font-bold text-white">4</span>
                                <div>
                                    <h2 class="text-base font-bold text-gray-900">Login credentials</h2>
                                    <p class="text-xs text-gray-500">Create a password for your merchant account</p>
                                </div>
                            </div>

                            <div class="mb-5 rounded-2xl border border-orange-100 bg-orange-50/70 px-4 py-3.5 text-sm text-gray-600">
                                <p>
                                    You’ll sign in with your
                                    <span class="font-semibold text-orange-600">phone number</span>
                                    @if(!empty($email))
                                        or
                                        <span class="font-semibold text-orange-600">email</span>
                                    @else
                                        <span class="text-gray-500">(or email, if you provide one)</span>
                                    @endif.
                                </p>
                                @if(!empty($email) || !empty($phone))
                                    <p class="mt-2 space-y-0.5 font-mono text-xs text-gray-500">
                                        @if(!empty($phone))
                                            <span class="block">Phone: {{ $phone }}</span>
                                        @endif
                                        @if(!empty($email))
                                            <span class="block">Email: {{ $email }}</span>
                                        @endif
                                    </p>
                                @endif
                            </div>

                            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                                <div>
                                    <label for="password" class="mb-1.5 block text-sm font-medium text-gray-700">
                                        Password <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative" x-data="{ showPassword: false }">
                                        <input
                                            type="password"
                                            id="password"
                                            wire:model="password"
                                            x-bind:type="showPassword ? 'text' : 'password'"
                                            class="{{ $inputClass }} pr-11 @error('password') {{ $errorClass }} @enderror"
                                            placeholder="Create a password"
                                            required
                                        >
                                        <button
                                            type="button"
                                            @click="showPassword = !showPassword"
                                            class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 transition hover:text-orange-500"
                                            tabindex="-1"
                                            aria-label="Toggle password visibility"
                                        >
                                            <svg x-show="showPassword" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                            </svg>
                                            <svg x-show="!showPassword" x-cloak xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                                            </svg>
                                        </button>
                                    </div>
                                    @error('password')
                                        <p class="mt-1.5 text-sm text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-gray-700">
                                        Confirm password <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative" x-data="{ showPasswordConfirm: false }">
                                        <input
                                            type="password"
                                            id="password_confirmation"
                                            wire:model="password_confirmation"
                                            x-bind:type="showPasswordConfirm ? 'text' : 'password'"
                                            class="{{ $inputClass }} pr-11 @error('password_confirmation') {{ $errorClass }} @enderror"
                                            placeholder="Confirm password"
                                        >
                                        <button
                                            type="button"
                                            @click="showPasswordConfirm = !showPasswordConfirm"
                                            class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 transition hover:text-orange-500"
                                            tabindex="-1"
                                            aria-label="Toggle confirm password visibility"
                                        >
                                            <svg x-show="showPasswordConfirm" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                            </svg>
                                            <svg x-show="!showPasswordConfirm" x-cloak xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                                            </svg>
                                        </button>
                                    </div>
                                    @error('password_confirmation')
                                        <p class="mt-1.5 text-sm text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-600">Password requirements</p>
                                <ul class="mt-2 space-y-1.5 text-sm text-slate-600">
                                    <li class="flex items-start gap-2">
                                        <span class="mt-1.5 size-1.5 shrink-0 rounded-full bg-orange-400"></span>
                                        At least 8 characters
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="mt-1.5 size-1.5 shrink-0 rounded-full bg-orange-400"></span>
                                        At least one number (0–9)
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="mt-1.5 size-1.5 shrink-0 rounded-full bg-orange-400"></span>
                                        Not a commonly leaked password
                                    </li>
                                </ul>
                            </div>
                        </section>

                        {{-- Terms --}}
                        @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
                            <div class="mt-8 rounded-2xl border border-gray-100 bg-gray-50/80 px-4 py-4">
                                <label for="terms" class="flex cursor-pointer items-start gap-3">
                                    <input
                                        type="checkbox"
                                        id="terms"
                                        wire:model="terms"
                                        required
                                        class="mt-0.5 size-4 shrink-0 rounded border-gray-300 text-orange-500 focus:ring-orange-500"
                                    >
                                    <span class="text-sm leading-relaxed text-gray-600">
                                        I agree that my data may be stored for this application. Learn more in our
                                        <a
                                            target="_blank"
                                            href="https://www.hia.sg/privacy-policy"
                                            class="font-semibold text-orange-500 underline-offset-2 hover:text-orange-600 hover:underline"
                                        >Privacy Policy</a>.
                                    </span>
                                </label>
                                @error('terms')
                                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif

                        {{-- reCAPTCHA --}}
                        @if(config('services.recaptcha.site_key'))
                            <div class="mt-6">
                                <div wire:ignore id="recaptcha-container">
                                    <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}" data-callback="onRecaptchaCallback"></div>
                                </div>
                                @error('gRecaptchaResponse')
                                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif

                        {{-- Actions --}}
                        <div class="mt-9 flex flex-col-reverse items-stretch justify-between gap-4 border-t border-orange-100 pt-7 sm:flex-row sm:items-center">
                            <p class="text-center text-sm text-gray-500 sm:text-left">
                                Already have an account?
                                <a href="{{ route('login') }}" class="font-semibold text-orange-500 transition hover:text-orange-600">
                                    Log in
                                </a>
                            </p>

                            <button
                                type="submit"
                                wire:target="submit"
                                wire:loading.attr="disabled"
                                class="inline-flex items-center justify-center gap-2 rounded-full bg-orange-500 px-7 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-orange-600 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <span wire:loading.remove wire:target="submit">Submit application</span>
                                <span wire:loading wire:target="submit" class="inline-flex items-center gap-2">
                                    <svg class="size-4 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Submitting…
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
@if(config('services.recaptcha.site_key'))
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <script>
        function onRecaptchaCallback(token) {
            @this.set('gRecaptchaResponse', token);
        }

        document.addEventListener('livewire:init', () => {
            Livewire.on('reset-recaptcha', () => {
                if (typeof grecaptcha === 'undefined') {
                    return;
                }
                const widget = document.querySelector('#recaptcha-container .g-recaptcha');
                if (!widget) {
                    return;
                }
                try {
                    grecaptcha.reset();
                } catch (e) {
                    // Widget torn down between check and reset
                }
                @this.set('gRecaptchaResponse', '');
            });
        });
    </script>
@endif
@endpush
