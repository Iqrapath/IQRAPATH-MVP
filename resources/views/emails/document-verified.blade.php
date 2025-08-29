@component('mail::message')
{{-- Header with Logo --}}
<div style="text-align: center; margin-bottom: 30px;">
    <img src="{{ asset('assets/images/logo/IqraPath-logo.png') }}" alt="IqraPath Logo" style="max-height: 60px; width: auto;">
</div>

# Excellent news, {{ $notifiable->name }}!

## Your document has been successfully verified!

{{-- Document Verification Complete Panel --}}
@component('mail::panel')
**Document Verification Complete:**

**Document Name:** {{ $document->name }}
**Document Type:** {{ $documentType }}
**Verification Status:** APPROVED
**Verified Date:** {{ $verifiedDate }}
**Reviewer:** IqraPath Verification Team
@endcomponent

{{-- Why This Matters Panel --}}
@component('mail::panel')
**Why This Matters:**

• **Quality Confirmed** - Your document meets our high standards
• **Trust Building** - Students can trust your credentials
• **Progress Made** - You're moving closer to full verification
• **Professional Standing** - Part of our verified teacher community
@endcomponent

{{-- Verification Journey Panel --}}
@component('mail::panel')
**Your Verification Journey:**

1. **Document submission** ✅ DONE
2. **Document review** ✅ APPROVED
3. **Video verification** ⏳ In progress
4. **Final approval** ⏳ Pending
@endcomponent

**What's Next?**

Almost there! Here's what happens next:

**Video Verification (if not completed)**
• Schedule your verification call
• 15-20 minute friendly interview
• Verify your identity and qualifications

**Remaining Documents (if any)**
• Upload any additional required documents
• Each gets reviewed within 24-48 hours

**Final Review**
• All components reviewed together
• Final approval notification sent
• Welcome to the teaching community!

**Your Progress**

Based on your current status, you're **~75% complete** with the verification process!

**Keep up the great work!** You're making excellent progress toward becoming a verified IqraPath teacher.

@component('mail::button', ['url' => config('app.url') . '/teacher/verification', 'color' => 'success'])
Continue Verification Process
@endcomponent

**Questions?**

If you have any questions about the next steps, our support team is here to help!
• **Email:** support@iqrapath.com
• **Response Time:** Under 4 hours

{{-- Footer --}}
Congratulations on this milestone!<br>
The IqraPath Verification Team

<div style="margin-top: 30px; font-size: 12px; color: #718096; text-align: center;">
    <p>© {{ date('Y') }} IqraPath. All rights reserved.</p>
    <p>If you have any questions, please contact our support team at <a href="mailto:support@iqrapath.com">support@iqrapath.com</a></p>
</div>
@endcomponent
