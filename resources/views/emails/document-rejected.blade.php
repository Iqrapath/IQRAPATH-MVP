@component('mail::message')
{{-- Header with Logo --}}
<div style="text-align: center; margin-bottom: 30px;">
    <img src="{{ asset('assets/images/logo/IqraPath-logo.png') }}" alt="IqraPath Logo" style="max-height: 60px; width: auto;">
</div>

# Hello {{ $notifiable->name }}!

## Document Review Complete - Resubmission Needed

Thank you for submitting your document for verification. Our review is complete and we need you to make some improvements.

{{-- Document Review Results Panel --}}
@component('mail::panel')
**Document Review Results:**

**Document:** {{ $document->name }}
**Type:** {{ $documentType }}
**Review Date:** {{ $reviewedDate }}
**Status:** Needs Improvement
@endcomponent

{{-- Rejection Reason Panel --}}
@component('mail::panel')
**What We Found:**

{{ $rejectionReason }}
@endcomponent

@if($resubmissionInstructions)
**Specific Instructions for Resubmission:**

{{ $resubmissionInstructions }}
@endif

**Easy Fix - Here's Your Action Plan:**

**Step 1: Review the Feedback**
• Read the specific issues mentioned above
• Check if you have a better version of this document
• Ensure you understand all requirements

**Step 2: Fix the Issues**
• Address each point mentioned in the feedback
• Use high-quality scans or clear photos
• Make sure all text is readable and complete
• Verify file format requirements (usually PDF)

**Step 3: Resubmit the Document**
• Upload the improved version
• Double-check before submitting
• Most resubmissions are approved within 24 hours!

**Submission Status:**
**Attempts Used:** {{ $maxAttempts - $remainingAttempts }} of {{ $maxAttempts }}
**Remaining Attempts:** {{ $remainingAttempts }} more chances
**Success Rate:** 95% of resubmissions get approved!

**Don't Worry - This is Normal!**

Document resubmissions are very common and nothing to worry about. Our review process helps ensure all teachers meet the same high standards that students expect.

**Most common fixes:**
• Higher resolution scans
• Complete document pages
• Correct file formats
• Clear, readable text

@component('mail::button', ['url' => config('app.url') . '/teacher/verification/documents', 'color' => 'primary'])
Upload Improved Document
@endcomponent

**Need Help?**

**Stuck on the requirements?** Our support team is here to help!
• **Email:** support@iqrapath.com
• **Response Time:** Under 4 hours
• **Live Chat:** Available on our website

**We believe in your success!** Every piece of feedback helps you meet our quality standards and serve students better.

{{-- Footer --}}
Here to help you succeed,<br>
The IqraPath Document Review Team

<div style="margin-top: 30px; font-size: 12px; color: #718096; text-align: center;">
    <p>© {{ date('Y') }} IqraPath. All rights reserved.</p>
    <p>If you have any questions, please contact our support team at <a href="mailto:support@iqrapath.com">support@iqrapath.com</a></p>
</div>
@endcomponent
