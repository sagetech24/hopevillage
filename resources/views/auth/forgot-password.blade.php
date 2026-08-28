<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <img src="{{ asset('hv-logo.png') }}" alt="Hope Village Logo" class="w-32">
        </x-slot>

        @php
            $lang = request()->get('lang', 'en');
        @endphp

        <div class="mb-6 mt-8 text-sm text-gray-600">
            {{
                match ($lang) {
                    'bang' => 'পাসওয়ার্ড ভুলে গেছেন? আপনার নতুন পাসওয়ার্ড কীভাবে গ্রহণ করতে চান তা নির্বাচন করুন।',
                    'zh' => '忘记密码？选择您希望如何接收新密码。',
                    'ta' => 'உங்கள் கடவுச்சொல்லை மறந்துவிட்டீர்களா? புதிய கடவுச்சொல்லை எவ்வாறு பெற விரும்புகிறீர்கள் என்பதைத் தேர்ந்தெடுக்கவும்.',
                    default => 'Forgot your password? Choose how you want to receive your new password.',
                }
            }}
        </div>

        @session('status')
            <div class="mb-4 font-medium text-sm text-green-600">
                {{ $value }}
            </div>
        @endsession

        <x-validation-errors class="mb-4" />

        <form method="POST" action="/forgot-password" id="password-reset-form" data-lang="{{ $lang }}">
            @csrf

            <!-- Reset Method Selection -->
            <div class="mb-4">
                <label class="block text-lg leading-tight font-medium text-gray-700 mb-4">
                    {{
                        match ($lang) {
                            'bang' => 'আপনি কীভাবে আপনার নতুন পাসওয়ার্ড গ্রহণ করতে চান?',
                            'zh' => '您希望如何接收新密码？',
                            'ta' => 'புதிய கடவுச்சொல்லை எவ்வாறு பெற விரும்புகிறீர்கள்?',
                            default => 'How would you like to receive your new password?',
                        }
                    }}
                </label>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input 
                            type="radio" 
                            name="reset_method" 
                            value="email" 
                            class="mr-2"
                            {{ old('reset_method', 'email') === 'email' ? 'checked' : '' }}
                            onchange="toggleInputFields()"
                        >
                        <span class="text-sm text-gray-700">{{
                            match ($lang) {
                                'bang' => 'ইমেল ঠিকানা',
                                'zh' => '电子邮件地址',
                                'ta' => 'மின்னஞ்சல் முகவரி',
                                default => 'Email Address',
                            }
                        }}</span>
                    </label>
                    <label class="flex items-center disabled:opacity-50 disabled:cursor-not-allowed disabled:text-gray-300">
                        <input 
                            type="radio" 
                            name="reset_method" 
                            value="sms" 
                            class="mr-2 disabled:opacity-50 disabled:cursor-not-allowed disabled:text-gray-300"
                            disabled
                            {{ old('reset_method') === 'sms' ? 'checked' : '' }}
                            onchange="toggleInputFields()"
                        >
                        <span class="text-sm text-gray-400">{{
                            match ($lang) {
                                'bang' => 'এসএমএস (মোবাইল নম্বর)',
                                'zh' => '短信（手机号码）',
                                'ta' => 'எஸ்எம்எஸ் (மொபைல் எண்)',
                                default => 'SMS (Mobile Number)',
                            }
                        }}</span>
                        <span class="text-sm ml-2 text-gray-500 font-medium">({{
                            match ($lang) {
                                'bang' => 'শীঘ্রই আসছে',
                                'zh' => '即将推出',
                                'ta' => 'விரைவில் வரும்',
                                default => 'Coming soon',
                            }
                        }})</span>
                    </label>
                    {{-- <label class="flex items-center">
                        <input 
                            type="radio" 
                            name="reset_method" 
                            value="whatsapp" 
                            class="mr-2"
                            {{ old('reset_method') === 'whatsapp' ? 'checked' : '' }}
                            onchange="toggleInputFields()"
                        >
                        <span class="text-sm text-gray-700">WhatsApp</span>
                    </label> --}}
                </div>
            </div>

            <!-- Email Input -->
            <div class="hidden" id="email-field">
                <x-label for="email" value="{{
                    match ($lang) {
                        'bang' => 'ইমেল ঠিকানা',
                        'zh' => '电子邮件地址',
                        'ta' => 'மின்னஞ்சல் முகவரி',
                        default => 'Email Address',
                    }
                }}" />
                <x-input 
                    id="email" 
                    class="block mt-1 w-full rounded-full px-4 py-2 border border-orange-400 focus:border-orange-500 focus:ring-orange-500" 
                    placeholder="{{
                        match ($lang) {
                            'bang' => 'ইমেল ঠিকানা লিখুন',
                            'zh' => '请输入您的电子邮件地址',
                            'ta' => 'உங்கள் மின்னஞ்சல் முகவரியை உள்ளிடவும்',
                            default => 'Enter your email address',
                        }
                    }}"
                    type="email" 
                    name="email" 
                    :value="old('email')" 
                    autocomplete="username" 
                />
            </div>

            <!-- SMS Input -->
            <div class="hidden" id="sms-field">
                <x-label for="sms_number" value="{{
                    match ($lang) {
                        'bang' => 'এসএমএস নম্বর',
                        'zh' => '短信号码',
                        'ta' => 'எஸ்எம்எஸ் எண்',
                        default => 'SMS Number',
                    }
                }}" />
                <div class="relative">
                    <input 
                        id="sms_number" 
                        class="block mt-1 w-full rounded-full px-4 py-2 border border-orange-400 focus:border-orange-500 focus:ring-orange-500" 
                        type="tel" 
                        name="sms_number" 
                        value="{{ old('sms_number') }}" 
                        placeholder="{{
                            match ($lang) {
                                'bang' => 'যোগাযোগ নম্বর',
                                'zh' => '联系电话',
                                'ta' => 'மொபைல் எண் (எ.கா. 91234567)',
                                default => 'Mobile Number (e.g. 91234567)',
                            }
                        }}"
                        autocomplete="tel" 
                    />
                    {{-- Country code dropdown disabled for local testing — number is sent as entered.
                    <input type="hidden" name="sms_country_code" id="sms_country_code" value="{{ old('sms_country_code', '+65') }}" />
                    --}}
                </div>
                <p class="mt-1 text-xs text-gray-500">
                    {{
                        match ($lang) {
                            'bang' => 'আপনার অ্যাকাউন্টে নিবন্ধিত মোবাইল নম্বর লিখুন (যেমন: 91234567)। এসএমএস পাঠানোর সেবা নম্বর নয়।',
                            'zh' => '请输入您账户注册的手机号码（例如：91234567），而非短信发送服务号码。',
                            'ta' => 'உங்கள் கணக்கில் பதிவுசெய்யப்பட்ட மொபைல் எண்ணை உள்ளிடவும் (எ.கா. 91234567). எஸ்எம்எஸ் அனுப்பும் சேவை எண் அல்ல.',
                            default => 'Enter the mobile number registered on your account (e.g. 91234567), not the Twilio sender number.',
                        }
                    }}
                </p>
            </div>

            <!-- WhatsApp Input -->
            <div class="hidden" id="whatsapp-field">
                <x-label for="whatsapp_number" value="WhatsApp Number" />
                <input 
                    id="whatsapp_number" 
                    class="block mt-1 w-full rounded-full px-4 py-2 border border-orange-400 focus:border-orange-500 focus:ring-orange-500" 
                    type="tel" 
                    name="whatsapp_number" 
                    value="{{ old('whatsapp_number') }}" 
                    placeholder="{{
                        match ($lang) {
                            'bang' => 'যোগাযোগ নম্বর',
                            'zh' => '联系电话',
                            'ta' => 'மொபைல் எண் (எ.கா. +6591234567)',
                            default => 'Mobile Number (e.g. +6591234567)',
                        }
                    }}"
                    autocomplete="tel" 
                />
                <p class="mt-1 text-xs text-gray-500">
                    {{
                        match ($lang) {
                            'bang' => 'আপনার WhatsApp নম্বর লিখুন',
                            'zh' => '输入您的 WhatsApp 号码',
                            'ta' => 'உங்கள் கணக்கில் பதிவுசெய்யப்பட்ட WhatsApp எண்ணை உள்ளிடவும்',
                            default => 'Enter your WhatsApp number as stored on your account',
                        }
                    }}
                </p>
            </div>

            <div class="flex items-center justify-end mt-4">
                <button 
                    type="submit" 
                    id="submit-btn"
                    class="w-full flex justify-center items-center gap-2 py-4 text-white bg-orange-500 hover:bg-orange-600 active:bg-orange-700 focus:bg-orange-600 rounded-full disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors"
                >
                    <span id="button-text">
                        {{
                            match ($lang) {
                                'bang' => 'পাসওয়ার্ড রিসেট পাঠান',
                                'zh' => '发送密码重置',
                                'ta' => 'கடவுச்சொல் மீட்டமைப்பை அனுப்பவும்',
                                default => 'Send Password Reset',
                            }
                        }}
                    </span>
                    <svg id="loading-spinner" class="hidden animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
            </div>
        </form>

        <div class="mt-4 text-left">
            <a href="{{ route('login') }}{{ $lang !== 'en' ? '?lang='.$lang : '' }}" class="text-sm text-orange-600 hover:text-orange-500 underline">
                {{
                    match ($lang) {
                        'bang' => 'লগইনে ফিরে যান',
                        'zh' => '返回登录',
                        'ta' => 'உள்நுழைவுக்குத் திரும்பு',
                        default => 'Back to Login',
                    }
                }}
            </a>
        </div>
    </x-authentication-card>

    {{-- intl-tel-input (+65 country code) disabled for local SMS testing
    @push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@19.5.7/build/css/intlTelInput.css">
    <style>
        .iti { width: 100%; }
        .iti__flag-container { z-index: 10; }
        .iti__selected-flag { z-index: 4; padding: 0 8px 0 8px; }
        .iti__selected-dial-code { font-weight: 600; color: #374151; padding: 0 4px; }
        .iti__country-list .iti__dial-code { color: #6b7280; margin-left: 6px; }
        .iti__flag-container + .iti__selected-dial-code { display: inline-block !important; }
        .iti--single-country .iti__selected-flag {
            pointer-events: none; cursor: default;
            border-top-left-radius: 32px; border-bottom-left-radius: 32px;
            color:#fff; background: #ffc28b;
        }
        .iti--single-country .iti__flag-container { pointer-events: none; cursor: default; }
    </style>
    @endpush
    --}}

    @push('scripts')
    <script>
        function toggleInputFields() {
            const resetMethod = document.querySelector('input[name="reset_method"]:checked').value;
            const emailField = document.getElementById('email-field');
            const smsField = document.getElementById('sms-field');
            const whatsappField = document.getElementById('whatsapp-field');
            const emailInput = document.getElementById('email');
            const smsInput = document.getElementById('sms_number');
            const whatsappInput = document.getElementById('whatsapp_number');

            emailField.classList.add('hidden');
            smsField.classList.add('hidden');
            whatsappField.classList.add('hidden');

            emailInput.removeAttribute('required');
            emailInput.removeAttribute('autofocus');
            smsInput.removeAttribute('required');
            smsInput.removeAttribute('autofocus');
            whatsappInput.removeAttribute('required');
            whatsappInput.removeAttribute('autofocus');

            if (resetMethod === 'email') {
                emailField.classList.remove('hidden');
                emailInput.setAttribute('required', 'required');
                emailInput.setAttribute('autofocus', 'autofocus');
            } else if (resetMethod === 'sms') {
                smsField.classList.remove('hidden');
                smsInput.setAttribute('required', 'required');
                smsInput.setAttribute('autofocus', 'autofocus');
            } else if (resetMethod === 'whatsapp') {
                whatsappField.classList.remove('hidden');
                whatsappInput.setAttribute('required', 'required');
                whatsappInput.setAttribute('autofocus', 'autofocus');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            toggleInputFields();

            const form = document.getElementById('password-reset-form');
            const submitBtn = document.getElementById('submit-btn');
            const buttonText = document.getElementById('button-text');
            const loadingSpinner = document.getElementById('loading-spinner');
            const defaultButtonText = buttonText.textContent;

            function resetSubmitButton() {
                submitBtn.disabled = false;
                submitBtn.classList.remove('bg-gray-400');
                submitBtn.classList.add('bg-orange-500', 'hover:bg-orange-600', 'active:bg-orange-700', 'focus:bg-orange-600');
                buttonText.textContent = defaultButtonText;
                loadingSpinner.classList.add('hidden');
            }

            // Restore button after server-side validation or delivery errors (full page reload)
            resetSubmitButton();

            form.addEventListener('submit', function() {
                const lang = form.getAttribute('data-lang') || new URLSearchParams(window.location.search).get('lang') || '';
                let loadingText = 'Sending...';
                if (lang === 'bang') {
                    loadingText = 'পাঠানো হচ্ছে...';
                } else if (lang === 'zh') {
                    loadingText = '发送中...';
                } else if (lang === 'ta') {
                    loadingText = 'அனுப்பப்படுகிறது...';
                }

                submitBtn.disabled = true;
                submitBtn.classList.remove('bg-orange-500', 'hover:bg-orange-600', 'active:bg-orange-700', 'focus:bg-orange-600');
                submitBtn.classList.add('bg-gray-400');
                buttonText.textContent = loadingText;
                loadingSpinner.classList.remove('hidden');
            });
        });
    </script>
    @endpush
</x-guest-layout>
