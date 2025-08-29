@component('mail::message')
{{-- Header with Logo --}}
<div style="text-align: center; margin-bottom: 30px;">
    <img src="{{ asset('assets/images/logo/IqraPath-logo.png') }}" alt="IqraPath Logo" style="max-height: 60px; width: auto;">
</div>

# Hello {{ $notifiable->name }}!

## Verification Call Results - Next Steps Available

Thank you for completing your verification call with our team.

{{-- Verification Results Panel --}}
@component('mail::panel')
**Verification Results:**

**Status:** Needs Improvement
**Completed:** {{ $completedTime }}
**Review Status:** Additional requirements identified
@endcomponent

@if($notes)
{{-- Specific Feedback Panel --}}
@component('mail::panel')
**Specific Feedback:**

{{ $notes }}
@endcomponent
@endif

**Your Next Steps:**

Don't worry! This is a common part of the verification process.

**Option 1: Schedule Another Call**
• Address the feedback points above
• Prepare any additional documents if needed
• Book a new slot when you're ready

**Option 2: Contact Support**
• Get clarification on specific requirements
• Ask questions about the feedback
• Receive guidance for your next attempt

**Remember:**
• Many successful teachers needed multiple attempts
• Each call helps us ensure quality education
• We're here to help you succeed
• Your teaching goals are still achievable!

**Ready to try again?** Take time to address the feedback, then schedule your next verification call.

@component('mail::button', ['url' => config('app.url') . '/teacher/verification', 'color' => 'primary'])
Schedule Next Call
@endcomponent

**Need Help?**

Our support team is here to guide you through the process:
• **Email:** support@iqrapath.com
• **Response Time:** Under 4 hours

{{-- Footer --}}
Keep going - you've got this!<br>
The IqraPath Verification Team

<div style="margin-top: 30px; font-size: 12px; color: #718096; text-align: center;">
    <p>© {{ date('Y') }} IqraPath. All rights reserved.</p>
    <p>If you have any questions, please contact our support team at <a href="mailto:support@iqrapath.com">support@iqrapath.com</a></p>
</div>
@endcomponent
