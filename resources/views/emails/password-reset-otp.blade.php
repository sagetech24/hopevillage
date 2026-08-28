@php($appName = config('app.name', 'HopeVillage'))

<p>Hello,</p>

<p>Your <strong>{{ $appName }}</strong> password reset verification code is:</p>

<p style="font-size: 24px; font-weight: bold; letter-spacing: 2px;">{{ $code }}</p>

<p>This code expires in {{ $expiresMinutes }} minutes.</p>

<p>If you did not request a password reset, you can ignore this email.</p>
