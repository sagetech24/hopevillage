<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <img src="{{ asset('hv-logo.png') }}" alt="Hope Village Logo" class="w-32">
        </x-slot>

        <div class="mb-6 mt-8 text-sm text-gray-600">
            {{
                request()->get('lang') === 'bang' ? 'আপনার পাসওয়ার্ড রিসেট করতে পাঠানো ৬-অঙ্কের যাচাইকরণ কোড লিখুন।' :
                (request()->get('lang') === 'zh' ? '请输入我们发送的 6 位验证码以重置密码。' : 'Enter the 6-digit verification code we sent to reset your password.')
            }}
        </div>

        @session('status')
            <div class="mb-4 font-medium text-sm text-green-600">
                {{ $value }}
            </div>
        @endsession

        <x-validation-errors class="mb-4" />

        <div class="mb-4 text-sm text-gray-600">
            <p>
                {{
                    request()->get('lang') === 'bang' ? 'যাচাইকরণ কোড পাঠানো হয়েছে:' :
                    (request()->get('lang') === 'zh' ? '验证码已发送至：' : 'Verification code sent to:')
                }}
            </p>
            <p class="mt-1 font-semibold">
                @if ($channel === 'email')
                    {{ request()->get('lang') === 'bang' ? 'ইমেল' : (request()->get('lang') === 'zh' ? '电子邮件' : 'Email') }}: {{ $destination }}
                @elseif ($channel === 'sms')
                    {{ request()->get('lang') === 'bang' ? 'এসএমএস' : (request()->get('lang') === 'zh' ? '短信' : 'SMS') }}: {{ $destination }}
                @else
                    WhatsApp: {{ $destination }}
                @endif
            </p>
            <p class="mt-2 text-xs text-gray-500">
                {{
                    request()->get('lang') === 'bang' ? "এই কোড {$expiresMinutes} মিনিটের মধ্যে মেয়াদ শেষ হবে।" :
                    (request()->get('lang') === 'zh' ? "此验证码将在 {$expiresMinutes} 分钟后过期。" : "This code expires in {$expiresMinutes} minutes.")
                }}
            </p>
        </div>

        <form method="POST" action="{{ route('password.reset.verify.submit') }}{{ request()->get('lang') ? '?lang='.request()->get('lang') : '' }}" id="verify-otp-form">
            @csrf

            <div>
                <x-label for="otp" value="{{
                    request()->get('lang') === 'bang' ? 'যাচাইকরণ কোড' :
                    (request()->get('lang') === 'zh' ? '验证码' : 'Verification Code')
                }}" />
                <x-input
                    id="otp"
                    class="block mt-1 w-full rounded-full px-4 py-2 border border-orange-400 focus:border-orange-500 focus:ring-orange-500"
                    type="text"
                    name="otp"
                    :value="old('otp')"
                    required
                    autofocus
                    autocomplete="one-time-code"
                    inputmode="numeric"
                    maxlength="6"
                    placeholder="123456"
                />
            </div>

            <div class="flex items-center justify-between mt-6">
                <button
                    type="submit"
                    id="verify-submit-btn"
                    class="inline-flex items-center px-6 py-3 bg-orange-500 border border-transparent rounded-full font-semibold text-xs text-white uppercase tracking-widest hover:bg-orange-600 focus:bg-orange-600 active:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 transition ease-in-out duration-150"
                >
                    {{
                        request()->get('lang') === 'bang' ? 'যাচাই করুন' :
                        (request()->get('lang') === 'zh' ? '验证' : 'Verify')
                    }}
                </button>
                <a href="#" onclick="document.getElementById('resend-form').submit(); return false;" class="underline text-sm text-orange-500 hover:text-orange-600">
                    {{
                        request()->get('lang') === 'bang' ? 'কোড পুনরায় পাঠান' :
                        (request()->get('lang') === 'zh' ? '重新发送验证码' : 'Resend code')
                    }}
                </a>
            </div>
        </form>

        <form id="resend-form" method="POST" action="{{ route('password.reset.resend') }}{{ request()->get('lang') ? '?lang='.request()->get('lang') : '' }}" class="hidden">
            @csrf
        </form>

        <div class="mt-4 text-left">
            <a href="{{ route('login') }}" class="text-sm text-orange-600 hover:text-orange-500 underline">
                {{
                    request()->get('lang') === 'bang' ? 'লগইনে ফিরে যান' :
                    (request()->get('lang') === 'zh' ? '返回登录' : 'Back to Login')
                }}
            </a>
        </div>
    </x-authentication-card>
</x-guest-layout>
