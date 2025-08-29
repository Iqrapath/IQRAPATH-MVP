{{-- resources/views/emails/verification-call-scheduled.blade.php --}}
@component('mail::message')
{{-- Header with Logo --}}
<div style="text-align: center; margin-bottom: 30px;">
    <img src="{{ asset('assets/images/logo/IqraPath-logo.png') }}" alt="IqraPath Logo" style="max-height: 60px; width: auto;">
</div>

# Hello {{ $notifiable->name }}!

## Great news! Your teacher verification call has been successfully scheduled.

{{-- Meeting Details Panel --}}
@component('mail::panel')
**Meeting Details:**

**Date:** {{ $scheduledDate->format('l, F j, Y') }}
**Time:** {{ $scheduledDate->format('g:i A T') }}
**Platform:** {{ $platformLabel }}
**Duration:** Approximately 15-20 minutes

@if($meetingLink)
**Meeting Link:** [Click here to join]({{ $meetingLink }})
@endif

@if($notes)
**Special Notes:** {{ $notes }}
@endif
@endcomponent

{{-- What to Prepare Section --}}
**What to Prepare:**

• Valid government-issued ID (passport, driver's license, or national ID)
• Your teaching certificates or qualifications
• Stable internet connection and good lighting
• Quiet environment without distractions
• Join 5 minutes early to test your audio/video

{{-- During the Call Section --}}
**During the Call:**

• Our verification team will verify your identity
• Review your teaching qualifications
• Discuss your teaching experience and goals
• Answer any questions you might have

{{-- Calendar Link --}}
**Add to Your Calendar:**

Don't forget about your call! [Add to Google Calendar]({{ $calendarUrl }}) to set a reminder.

{{-- Call to Action Button --}}
@if($meetingLink)
@component('mail::button', ['url' => $meetingLink, 'color' => 'success'])
Join Verification Call
@endcomponent
@endif

{{-- Additional Information --}}
**Need to reschedule?** Please contact our support team at least 2 hours before your scheduled time.

We're excited to welcome you to the IqraPath teaching community!

{{-- Troubleshooting Panel --}}
@if($meetingLink)
@component('mail::panel')
If you're having trouble clicking the "Join Verification Call" button, copy and paste the URL below into your web browser:
<a href="{{ $meetingLink }}">{{ $meetingLink }}</a>
@endcomponent
@endif

{{-- Footer --}}
Warm regards,<br>
The IqraPath Team

<div style="margin-top: 30px; font-size: 12px; color: #718096; text-align: center;">
    <p>© {{ date('Y') }} IqraPath. All rights reserved.</p>
    <p>If you have any questions, please contact our support team at <a href="mailto:support@iqrapath.com">support@iqrapath.com</a></p>
</div>
@endcomponent
