@component('mail::message')
{{-- Header with Logo --}}
<div style="text-align: center; margin-bottom: 30px;">
    <img src="{{ asset('assets/images/logo/IqraPath-logo.png') }}" alt="IqraPath Logo" style="max-height: 60px; width: auto;">
</div>

# Assalamu Alaikum, {{ $name }}!

## Reset Your Password

You are receiving this email because we received a password reset request for your account at IqraPath.

@component('mail::button', ['url' => $url, 'color' => 'success'])
Reset Password
@endcomponent

This password reset link will expire in {{ $count }} minutes.

If you did not request a password reset, no further action is required.

{{-- Additional Information --}}
@component('mail::panel')
If you're having trouble clicking the "Reset Password" button, copy and paste the URL below into your web browser:
<a href="{{ $url }}">{{ $url }}</a>
@endcomponent

{{-- Footer --}}
Warm regards,<br>
The IqraPath Team

<div style="margin-top: 30px; font-size: 12px; color: #718096; text-align: center;">
    <p>© {{ date('Y') }} IqraPath. All rights reserved.</p>
    <p>If you have any questions, please contact our support team at <a href="mailto:support@iqrapath.com">support@iqrapath.com</a></p>
</div>
@endcomponent 