@component('mail::message')
{{-- Header with Logo --}}
<div style="text-align: center; margin-bottom: 30px;">
    <img src="{{ asset('assets/images/logo/IqraPath-logo.png') }}" alt="IqraPath Logo" style="max-height: 60px; width: auto;">
</div>

# Congratulations {{ $notifiable->name }}!

## Excellent news! You've successfully passed your verification call!

{{-- Verification Results Panel --}}
@component('mail::panel')
**Verification Results:**

**Status:** PASSED
**Completed:** {{ $completedTime }}
**Duration:** Video verification phase complete
@endcomponent

@if($notes)
{{-- Feedback Panel --}}
@component('mail::panel')
**Feedback from our team:**

{{ $notes }}
@endcomponent
@endif

**What Happens Next?**

1. **Document review** (24-48 hours)
2. **Final approval notification**
3. **Access to teacher dashboard**
4. **Start creating courses and teaching!**

**You're Almost There!**

Our document verification team will now review your submitted documents. This typically takes 24-48 hours during business days.

**Pro tip: Use this time to:**
• Plan your first course outline
• Prepare your teaching materials
• Think about your teaching schedule
• Get excited about inspiring students!

**Welcome to the IqraPath teaching family!**

{{-- Footer --}}
Warm regards,<br>
The IqraPath Verification Team

<div style="margin-top: 30px; font-size: 12px; color: #718096; text-align: center;">
    <p>© {{ date('Y') }} IqraPath. All rights reserved.</p>
    <p>If you have any questions, please contact our support team at <a href="mailto:support@iqrapath.com">support@iqrapath.com</a></p>
</div>
@endcomponent
