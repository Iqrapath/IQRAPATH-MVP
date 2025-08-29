@component('mail::message')
{{-- Header with Logo --}}
<div style="text-align: center; margin-bottom: 30px;">
    <img src="{{ asset('assets/images/logo/IqraPath-logo.png') }}" alt="IqraPath Logo" style="max-height: 60px; width: auto;">
</div>

# Hello {{ $notifiable->name }}!

## Your verification call is now LIVE!

{{-- Immediate Action Required Panel --}}
@component('mail::panel')
**Immediate Action Required:**

**Current Time:** {{ $currentTime }}
**Status:** Active & Waiting for You
**Our Verification Team:** Ready and waiting
@endcomponent

@if($verificationRequest->meeting_link)
**Meeting Room:** [Click here to join NOW]({{ $verificationRequest->meeting_link }})
@endif

**Quick Checklist (30 seconds):**
• Camera working and good lighting?
• Microphone clear and no background noise?
• ID and teaching documents within reach?
• Stable internet connection?

**Pro Tips:**
• **Join immediately** - Don't keep the team waiting
• **Speak clearly** and maintain eye contact
• **Have documents ready** for quick verification
• **Stay calm and confident** - you've got this!

**Time is of the essence - Our team is ready for you now!**

@if($verificationRequest->meeting_link)
@component('mail::button', ['url' => $verificationRequest->meeting_link, 'color' => 'error'])
JOIN LIVE CALL NOW
@endcomponent
@endif

**Technical issues?** If you can't join, contact support immediately.

**Your teaching journey with IqraPath starts here!**

{{-- Footer --}}
Best of luck!<br>
The IqraPath Verification Team

<div style="margin-top: 30px; font-size: 12px; color: #718096; text-align: center;">
    <p>© {{ date('Y') }} IqraPath. All rights reserved.</p>
    <p>If you have any questions, please contact our support team at <a href="mailto:support@iqrapath.com">support@iqrapath.com</a></p>
</div>
@endcomponent
