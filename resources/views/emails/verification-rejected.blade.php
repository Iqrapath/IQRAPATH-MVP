@component('mail::message')
{{-- Header with Logo --}}
<div style="text-align: center; margin-bottom: 30px;">
    <img src="{{ asset('assets/images/logo/IqraPath-logo.png') }}" alt="IqraPath Logo" style="max-height: 60px; width: auto;">
</div>

# Hello {{ $notifiable->name }}!

## Verification Application Update - Action Required

Thank you for submitting your teacher verification application to IqraPath.

{{-- Application Review Results Panel --}}
@component('mail::panel')
**Application Review Results:**

**Review Date:** {{ $reviewDate }}
**Status:** Requires Additional Information
**Application ID:** #{{ $verificationRequest->id }}
@endcomponent

{{-- What We Found Panel --}}
@component('mail::panel')
**What We Found:**

{{ $rejectionReason }}
@endcomponent

{{-- Good News Panel --}}
@component('mail::panel')
**Good News - This is Fixable!**

Don't worry! Most applications need some adjustments. This is completely normal and doesn't affect your ability to become a teacher with us.
@endcomponent

**Your Action Plan:**

**Step 1: Review & Fix**
• Carefully read the feedback above
• Gather any missing documents or information
• Ensure all documents are clear and readable

**Step 2: Get Help (Optional)**
• Contact our support team for clarification
• Schedule a call if you need guidance
• Join our teacher preparation webinar

**Step 3: Resubmit When Ready**
• Update your application with improvements
• Submit for review again
• Most resubmissions are approved within 24 hours!

**Success Stats**
• **85%** of resubmitted applications get approved
• **Average time** to approval after resubmission: 1-2 days
• **Support response time:** Under 4 hours

**We're Here to Help You Succeed**

Our goal is to help you become a successful IqraPath teacher. Every piece of feedback is designed to help you meet our quality standards and serve students better.

@component('mail::button', ['url' => config('app.url') . '/teacher/verification', 'color' => 'primary'])
Update Application
@endcomponent

**Need Help?**

Our support team is here to guide you through the process:
• **Email:** support@iqrapath.com
• **Response Time:** Under 4 hours
• **Live Chat:** Available on our website

**Remember:** This is just a temporary setback. With the right adjustments, you'll be on your way to becoming a verified IqraPath teacher!

{{-- Footer --}}
Here to help you succeed,<br>
The IqraPath Verification Team

<div style="margin-top: 30px; font-size: 12px; color: #718096; text-align: center;">
    <p>© {{ date('Y') }} IqraPath. All rights reserved.</p>
    <p>If you have any questions, please contact our support team at <a href="mailto:support@iqrapath.com">support@iqrapath.com</a></p>
</div>
@endcomponent
