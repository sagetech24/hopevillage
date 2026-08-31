<div class="min-h-screen flex flex-col justify-center items-center sm:pt-0 bg-transparent">
    <div class="mt-2">
        {{ $logo }}
    </div>

    <div class="w-full sm:max-w-sm my-6 p-6 bg-white shadow-md overflow-hidden rounded-xl relative">
        <div class="absolute top-4 right-4">
            <x-language-dropdown />
        </div>
        <br />
        {{ $slot }}
        <p class="mt-10 text-center text-md text-gray-500">
            @if (request()->routeIs('register'))
                @if(request()->get('lang') === 'bang')
                    ইতিমধ্যে একটি অ্যাকাউন্ট আছে? <a href="{{ route('login') }}{{ request()->get('lang') ? '?lang=' . request()->get('lang') : '' }}" class="text-orange-500 hover:text-orange-600">সাইন ইন</a>
                @elseif(request()->get('lang') === 'zh')
                    已有账户? <a href="{{ route('login') }}{{ request()->get('lang') ? '?lang=' . request()->get('lang') : '' }}" class="text-orange-500 hover:text-orange-600">登录</a>
                @elseif(request()->get('lang') === 'ta')
                    இதில் ஒரு கணக்கு இருக்கிறதா? <a href="{{ route('login') }}{{ request()->get('lang') ? '?lang=' . request()->get('lang') : '' }}" class="text-orange-500 hover:text-orange-600">புகுபதிகை</a>
                @else
                    Already have an account? <a href="{{ route('login') }}{{ request()->get('lang') ? '?lang=' . request()->get('lang') : '' }}" class="text-orange-500 hover:text-orange-600">Sign In</a>
                @endif
            @else
                @if(request()->get('lang') === 'bang')
                    একটি অ্যাকাউন্ট না আছে? <a href="{{ route('register') }}{{ request()->get('lang') ? '?lang=' . request()->get('lang') : '' }}" class="text-orange-500 hover:text-orange-600">সাইন আপ</a>
                @elseif(request()->get('lang') === 'zh')
                    没有账户? <a href="{{ route('register') }}{{ request()->get('lang') ? '?lang=' . request()->get('lang') : '' }}" class="text-orange-500 hover:text-orange-600">注册</a>
                @elseif(request()->get('lang') === 'ta')
                    ஒரு கணக்கு இல்லை? <a href="{{ route('register') }}{{ request()->get('lang') ? '?lang=' . request()->get('lang') : '' }}" class="text-orange-500 hover:text-orange-600">பதிவுசெய்யவும்</a>
                @else
                    Don't have an account? <a href="{{ route('register') }}{{ request()->get('lang') ? '?lang=' . request()->get('lang') : '' }}" class="text-orange-500 hover:text-orange-600">Sign Up</a>
                @endif
            @endif
        </p>
    </div>
</div>
