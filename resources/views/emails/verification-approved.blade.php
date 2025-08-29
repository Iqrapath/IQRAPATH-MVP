@component('mail::message')
{{-- Header with Logo --}}
<div style="text-align: center; margin-bottom: 30px;">
    <img src="{{ asset('assets/images/logo/IqraPath-logo.png') }}" alt="IqraPath Logo" style="max-height: 60px; width: auto;">
</div>

# Congratulations {{ $notifiable->name }}!

## Your teacher verification has been APPROVED!

After careful review of your documents and video verification, we're thrilled to officially welcome you to the IqraPath teaching community!

{{-- Verification Complete Panel --}}
@component('mail::panel')
**Verification Complete:**

**Status:** FULLY APPROVED
**Approved Date:** {{ $approvedDate }}
**Profile Status:** Active Teacher
@endcomponent

**Your Teaching Journey Starts Now!**

**Immediate Next Steps (15 minutes):**
1. **Complete Your Profile** - Add your bio and teaching specialties
2. **Setup Payment Info** - Configure how you'll receive earnings
3. **Set Your Availability** - Let students know when you're free

**Start Teaching (This Week):**
4. **Create Your First Course** - Share your expertise
5. **Offer Tutoring Sessions** - One-on-one teaching
6. **Join Our Teacher Community** - Connect with fellow educators

**Earning Potential**
• **Hourly Tutoring:** $15-50+ per hour
• **Course Sales:** Passive income from course enrollments
• **Group Sessions:** Teach multiple students simultaneously
• **Flexible Schedule:** Teach when it works for you

**Teacher Benefits**
• Access to 10,000+ active students
• Professional teacher dashboard
• Automated payment processing
• Marketing support for your courses
• 24/7 technical support
• Teacher community and resources

**Fun Fact**

You're joining a community of **500+ verified teachers** who are making a real impact on students' lives worldwide!

@component('mail::button', ['url' => config('app.url') . '/teacher/dashboard', 'color' => 'success'])
Start Your Teaching Journey
@endcomponent

**Questions?**

Our teacher success team is here to help you succeed!

**Welcome to IqraPath - Where Teaching Transforms Lives!**

{{-- Footer --}}
Excited to see you teach!<br>
The IqraPath Team

<div style="margin-top: 30px; font-size: 12px; color: #718096; text-align: center;">
    <p>© {{ date('Y') }} IqraPath. All rights reserved.</p>
    <p>If you have any questions, please contact our support team at <a href="mailto:support@iqrapath.com">support@iqrapath.com</a></p>
</div>
@endcomponent
