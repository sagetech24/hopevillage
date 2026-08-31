<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <img src="{{ asset('hv-logo.png') }}" alt="hope village Logo" class="w-32">
        </x-slot>

        <x-validation-errors class="mb-4" />

        @session('status')
            <div class="mb-4 font-medium text-sm text-green-600">
                {{ $value }}
            </div>
        @endsession

        <form method="POST" action="{{ route('login') }}" class="mt-8 mb-4" x-data="{ submitting: false }" @submit="submitting = true">
            @csrf

            @php
                $lang = request()->get('lang', 'en');
            @endphp

            <div>
                <x-label for="email" value="{{ 
                    match($lang) {
                        'bang' => 'যোগাযোগ নম্বর বা ইমেল ঠিকানা',
                        'zh' => '联系电话或电子邮箱',
                        'ta' => 'மொபைல் எண் அல்லது மின்னஞ்சல் முகவரி',
                        default => 'Mobile Number or Email Address',
                    }
                 }}" />
                <input 
                    id="email" 
                    placeholder="{{ 
                        match($lang) {
                            'bang' => '+65XXXXXX বা email@example.com',
                            'zh' => '+65XXXXXX 或 email@example.com',
                            'ta' => '+65XXXXXX அல்லது email@example.com',
                            default => '+65XXXXXX or email@example.com',
                        }
                    }}" 
                    class="mt-2 w-full rounded-full px-4 py-2 border border-orange-400 focus:border-orange-500 focus:ring-orange-500"
                    type="text" 
                    name="email" 
                    value="{{ old('email') }}" 
                    required 
                    autofocus 
                    autocomplete="username" 
                />
                <p class="mt-1 text-xs text-gray-500">
                    {{ 
                        match($lang) {
                            'bang' => 'আপনি আপনার ইমেল ঠিকানা দিয়ে লগ ইন করতে পারেন।',
                            'zh' => '您可以使用电子邮箱登录。',
                            'ta' => 'நீங்கள் மின்னஞ்சல் முகவரியுடன் உள்நுழையலாம்.',
                            default => 'You can login using your email address.',
                        }
                    }}
                </p>
            </div>

            <div class="mt-4">
                <x-label for="password" value="{{ 
                    match($lang) {
                        'bang' => 'পাসওয়ার্ড',
                        'zh' => '密码',
                        'ta' => 'கடவுச்சொல்',
                        default => 'Password',
                    }
                }}" />
                <div class="relative">
                    <input 
                        id="password" 
                        placeholder="{{ 
                            match($lang) {
                                'bang' => 'পাসওয়ার্ড',
                                'zh' => '密码',
                                'ta' => 'கடவுச்சொல்',
                                default => 'Password',
                            }
                        }}" 
                        class="mt-2 w-full rounded-full px-4 py-2 pr-12 border border-orange-400 focus:border-orange-500 focus:ring-orange-500" 
                        type="password" 
                        name="password" 
                        required 
                        autocomplete="current-password" 
                    />
                    <button 
                        type="button" 
                        id="togglePassword" 
                        class="absolute right-4 top-[1.85rem] transform -translate-y-1/2 text-gray-500 hover:text-orange-500 focus:outline-none cursor-pointer"
                        onclick="togglePasswordVisibility()"
                        aria-label="Toggle password visibility"
                    >
                        <svg id="eyeIcon" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        <svg id="eyeOffIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="block mt-4">
                <label for="remember_me" class="flex items-center">
                    <x-checkbox id="remember_me" name="remember" />
                    <span class="ms-2 text-sm text-gray-600">{{ 
                        match($lang) {
                            'bang' => 'মনে রাখুন',
                            'zh' => '记住我',
                            'ta' => 'என்னை நினைவில் வையுங்கள்',
                            default => 'Remember me',
                        }
                    }}</span>
                </label>
            </div>

            <div class="flex items-center justify-center mt-6">
                <button 
                    type="submit"
                    x-bind:disabled="submitting"
                    :class="submitting ? 'opacity-80 cursor-not-allowed bg-gray-400 hover:bg-gray-400 active:bg-gray-400 focus:bg-gray-400 disabled:bg-gray-400 disabled:hover:bg-gray-400 disabled:active:bg-gray-400 disabled:focus:bg-gray-400 disabled:opacity-80 disabled:cursor-not-allowed' : ''"
                    class="cursor-pointer text-lg font-semibold uppercase tracking-wider w-3/4 flex justify-center py-3 text-white bg-orange-500 hover:bg-orange-600 duration-300 transition-all rounded-full"
                >
                    <span x-show="!submitting">
                        {{ 
                            match($lang) {
                                'bang' => 'লগ ইন করুন',
                                'zh' => '登录',
                                'ta' => 'புகுபதிகை',
                                default => 'Log in',
                            }
                        }}
                    </span>
                    <span x-show="submitting" x-cloak>
                        {{ 
                            match($lang) {
                                'bang' => 'লগ ইন হচ্ছে...',
                                'zh' => '登录中...',
                                'ta' => 'உள்நுழைகிறது...',
                                default => 'Logging in...',
                            }
                        }}
                    </span>
                </button>
            </div>

            <div class="flex items-center justify-center mt-4">
                <a href="{{ route('password.request') }}" class="text-sm text-orange-600 hover:text-orange-500 underline">
                    {{ 
                        match($lang) {
                            'bang' => 'পাসওয়ার্ড ভুলে গেছেন?',
                            'zh' => '忘记密码？',
                            'ta' => 'கடவுச்சொல் மறந்துவிட்டீர்களா?',
                            default => 'Forgot Password?',
                        }
                    }}
                </a>
            </div>

            @if(request()->query('singpass') === 'true')
                <div class="mt-6">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-2 bg-white text-gray-500">
                                {{ 
                                    match($lang) {
                                        'bang' => 'অথবা',
                                        'zh' => '或者',
                                        'ta' => 'அல்லது',
                                        default => 'Or',
                                    }
                                }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-6">
                        <a href="{{ route('singpass.redirect') }}" 
                        class="w-full flex items-center justify-center font-bold px-4 py-3 text-white rounded-lg shadow-sm tracking-wide text-lg bg-[#F4333D]">
                            {{ 
                                match($lang) {
                                    'bang' => 'Singpass দিয়ে লগ ইন করুন',
                                    'zh' => '使用 Singpass 登录',
                                    'ta' => 'Singpass உடன் உள்நுழைய',
                                    default => 'Log in with Singpass',
                                }
                            }}
                        </a>
                    </div>
                </div>
            @endif
        </form>
    </x-authentication-card>

    <script>
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            const eyeOffIcon = document.getElementById('eyeOffIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('hidden');
                eyeOffIcon.classList.add('hidden');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.add('hidden');
                eyeOffIcon.classList.remove('hidden');
            }
        }
    </script>
</x-guest-layout>
