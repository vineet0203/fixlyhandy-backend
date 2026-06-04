@component('mail::layout')
{{-- Header --}}
@slot('header')
@component('mail::header', ['url' => config('app.url')])
{{ $appName }}
@endcomponent
@endslot

{{-- Body --}}
# Password Changed Successfully

Hello {{ $user->first_name ?? $user->email }},

Your password was recently changed for your {{ $appName }} account. If you made this change, you can safely ignore this email.

## Change Details:
- **Time:** {{ \Carbon\Carbon::parse($timestamp)->format('F j, Y \a\t g:i A T') }}
- **IP Address:** {{ $ipAddress }}
- **Location:** {{ $location }}
- **Device:** {{ $deviceInfo['device'] }} ({{ $deviceInfo['os'] }} / {{ $deviceInfo['browser'] }})

## Security Tips:
✅ Always use a strong, unique password<br>
✅ Never share your password with anyone<br>
✅ Enable two-factor authentication if available<br>
✅ Regularly update your password<br>
✅ Be cautious of phishing attempts

@component('mail::button', ['url' => config('app.url') . '/security', 'color' => 'primary'])
Review Security Settings
@endcomponent

@component('mail::panel')
## Didn't make this change?
If you didn't change your password, please contact our support team immediately at [{{ $supportEmail }}](mailto:{{ $supportEmail }}) to secure your account.
@endcomponent

{{-- Subcopy --}}
@slot('subcopy')
You're receiving this email because a password change was detected on your account. If you believe this was an error, please contact support.
@endslot

{{-- Footer --}}
@slot('footer')
@component('mail::footer')
© {{ $currentYear }} {{ $appName }}. All rights reserved.<br>
[Privacy Policy]({{ config('app.url') }}/privacy) | [Terms of Service]({{ config('app.url') }}/terms)<br>
This is an automated message, please do not reply to this email.
@endcomponent
@endslot
@endcomponent