<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <img src="{{ asset('hv-logo.png') }}" alt="hope village Logo" class="w-32">
        </x-slot>


        <x-validation-errors class="mb-4" />

        <form method="POST" action="{{ route('password.update') }}">
            @csrf

            @php
                $lang = request()->get('lang', 'en');
            @endphp

            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div class="block">
                <x-label for="email" 
                    value="{{ 
                        match($lang) {
                            'bang' => 'যোগাযোগ নম্বর বা ইমেল ঠিকানা',
                            'zh' => '联系电话或电子邮箱',
                            'ta' => 'மொபைல் எண் அல்லது மின்னஞ்சல் முகவரி',
                            default => 'Email Address',
                        }
                    }}"
                />
                <input 
                    id="email" 
                    class="disabled:bg-gray-100 disabled:cursor-not-allowed mt-2 w-full rounded-full px-4 py-2 border border-orange-400 focus:border-orange-500 focus:ring-orange-500"
                    type="email" 
                    name="email"
                    disabled 
                    value="{{ old('email', $request->email) }}" 
                    required 
                    autofocus 
                    autocomplete="username" 
                    placeholder="{{ 
                        match($lang) {
                            'bang' => 'email@example.com',
                            'zh' => 'email@example.com',
                            'ta' => 'email@example.com',
                            default => 'email@example.com',
                        }
                    }}"
                />
            </div>

            <div class="mt-4">
                <x-label for="password" 
                    value="{{ 
                        match($lang) {
                            'bang' => 'পাসওয়ার্ড',
                            'zh' => '密码',
                            'ta' => 'கடவுச்சொல்',
                            default => 'Password',
                        }
                    }}"
                />
                <input 
                    id="password" 
                    class="mt-2 w-full rounded-full px-4 py-2 border border-orange-400 focus:border-orange-500 focus:ring-orange-500"
                    type="password" 
                    name="password" 
                    required 
                    autocomplete="new-password" 
                    placeholder="{{ 
                        match($lang) {
                            'bang' => 'পাসওয়ার্ড',
                            'zh' => '密码',
                            'ta' => 'கடவுச்சொல்',
                            default => 'Password',
                        }
                    }}"
                />
            </div>

            <div class="mt-4">
                <x-label for="password_confirmation" value="{{ __('Confirm Password') }}" />
                <input 
                    id="password_confirmation" 
                    class=" mt-2 w-full rounded-full px-4 py-2 border border-orange-400 focus:border-orange-500 focus:ring-orange-500"
                    type="password" 
                    name="password_confirmation" 
                    required 
                    autocomplete="new-password" 
                    placeholder="{{ 
                        match($lang) {
                            'bang' => 'পাসওয়ার্ড',
                            'zh' => '密码',
                            'ta' => 'கடவுச்சொல்',
                            default => 'Confirm Password',
                        }
                    }}"
                />

                <div class="mt-4 p-3 bg-orange-50 border border-orange-200 rounded-lg">
                    <p class="text-xs font-semibold text-gray-700 mb-1">
                        {{ match($lang) {
                            'bang' => 'পাসওয়ার্ডের প্রয়োজনীয়তা:',
                            'zh' => '密码要求：',
                            'ta' => 'கடவுச்சொல் தேவை:',
                            default => 'Password Requirements:',
                        } }}
                    </p>
                    <ul class="text-xs text-gray-600 space-y-0.5 list-disc list-inside">
                        <li>
                            {{ match($lang) {
                                'bang' => 'সর্বনিম্ন ৮টি অক্ষর',
                                'zh' => '至少 8 个字符',
                                'ta' => 'குறைந்தபட்ச 8 எழுத்துகள்',
                                default => 'Minimum 8 characters',
                            } }}
                        </li>
                        <li>
                            {{ match($lang) {
                                'bang' => 'অন্তত ১টি বড় হাতের অক্ষর (A-Z)',
                                'zh' => '至少 1 个大写字母 (A-Z)',
                                'ta' => 'குறைந்தபட்ச 1 பொருள் மீதி எழுத்து (A-Z)',
                                default => 'At least 1 uppercase letter (A-Z)',
                            } }}
                        </li>
                        <li>
                            {{ match($lang) {
                                'bang' => 'অন্তত ১টি ছোট হাতের অক্ষর (a-z)',
                                'zh' => '至少 1 个小写字母 (a-z)',
                                'ta' => 'குறைந்தபட்ச 1 சிறிய எழுத்து (a-z)',
                                default => 'At least 1 lowercase letter (a-z)',
                            } }}
                        </li>
                        <li>
                            {{ match($lang) {
                                'bang' => 'অন্তত ১টি সংখ্যা (0-9)',
                                'zh' => '至少 1 个数字 (0-9)',
                                'ta' => 'குறைந்தபட்ச 1 எண் (0-9)',
                                default => 'At least 1 number (0-9)',
                            } }}
                        </li>
                        <li>
                            {{ match($lang) {
                                'bang' => 'অন্তত ১টি বিশেষ অক্ষর (!@#$%^&*...)',
                                'zh' => '至少 1 个特殊字符 (!@#$%^&*...)',
                                'ta' => 'குறைந்தபட்ச 1 பயன்பாட்டு எழுத்து (!@#$%^&*...)',
                                default => 'At least 1 special character (!@#$%^&*...)',
                            } }}
                        </li>
                    </ul>
                    <p class="text-xs text-gray-600 mt-2">
                        <span class="font-semibold">
                            {{ match($lang) {
                                'bang' => 'উদাহরণ:',
                                'zh' => '示例：',
                                'ta' => 'எடுத்துக்காட்டு:',
                                default => 'Example:',
                            } }}
                        </span>
                        <span class="font-mono text-gray-700">MyP@ssw0rd</span>
                    </p>
                </div>
            </div>

            <div class="flex items-center justify-center mt-8">
                <x-button class="w-3/4 flex justify-center py-4 text-white bg-orange-500 hover:bg-orange-600 active:bg-orange-700 focus:bg-orange-600 rounded-full">
                    {{ 
                        match($lang) {
                            'bang' => 'পাসওয়ার্ড রিসেট করুন',
                            'zh' => '重置密码',
                            'ta' => 'கடவுச்சொல் மறுப்பு',
                            default => 'Reset Password',
                        }
                    }}
                </x-button>
            </div>
        </form>
    </x-authentication-card>
</x-guest-layout>
