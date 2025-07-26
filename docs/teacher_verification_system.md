# Teacher Verification System

This document outlines the requirements and implementation details for the teacher verification system in the IQRAPATH platform.

## Overview

The teacher verification system allows administrators to approve teachers before they can offer services on the platform. It also provides teachers with a way to request verification and track their verification status.

## Current Implementation

### Data Models

1. **TeacherProfile**
   - `verified` (boolean): Indicates if a teacher is verified
   - `languages` (array): Languages the teacher speaks
   - `teaching_type` (string): Online, In-person, etc.
   - `teaching_mode` (string): One-to-One, Group, etc.

2. **VerificationRequest**
   - `teacher_profile_id`: Foreign key to teacher profile
   - `status`: 'pending', 'verified', 'rejected'
   - `submitted_at`: When the request was submitted
   - `reviewed_by`: Admin user ID who reviewed the request
   - `reviewed_at`: When the request was reviewed
   - `rejection_reason`: Reason for rejection if applicable

3. **Document**
   - Linked to teacher profiles
   - Types include ID verification, certificates, resume

### Admin Features (Implemented)

- View list of teachers with verification status
- Filter teachers by status (Approved, Pending, Inactive)
- Approve teachers (sets verified=true)
- Reject teachers with reason
- View teacher documents

## Planned Enhancements

### Teacher Verification Flow

1. **Teacher Registration**
   - Teacher registers on the platform
   - Initial status: Unverified

2. **Document Submission**
   - Teacher uploads required documents:
     - ID verification (required)
     - Educational certificates (required)
     - Teaching certificates (optional)
     - Resume/CV (required)

3. **Verification Request**
   - Teacher completes profile information
   - Submits verification request
   - Status changes to "Pending Verification"

4. **Admin Review**
   - Admin reviews teacher profile and documents
   - Approves or rejects with reason
   - If approved, status changes to "Verified"
   - If rejected, status changes to "Rejected" with reason

5. **Reapplication**
   - If rejected, teacher can address issues and reapply after 7 days
   - Maximum 3 attempts within 30 days

### Required Features for Teacher Dashboard

1. **Verification Status Display**
   - Clear indication of current status
   - Progress tracker for verification steps

2. **Document Upload Interface**
   - Upload ID verification
   - Upload educational certificates
   - Upload teaching certificates
   - Upload resume/CV
   - View uploaded documents

3. **Verification Request Form**
   - Submit verification request when all requirements are met
   - View pending request status

4. **Rejection Feedback**
   - View rejection reasons
   - Guidance on addressing issues
   - Countdown to when reapplication is possible

### Required Features for Admin Dashboard

1. **Verification Queue**
   - List of pending verification requests
   - Sorted by submission date
   - Quick filters for different request types

2. **Teacher Verification Detail View**
   - Teacher profile information
   - Document previews
   - Verification history
   - Action buttons (Approve/Reject)

3. **Rejection Form**
   - Standardized rejection reasons
   - Custom rejection message
   - Guidance suggestions for teacher

4. **Verification Analytics**
   - Approval/rejection rates
   - Average verification time
   - Common rejection reasons

## Implementation Tasks

### Backend Tasks

1. **Create Teacher Verification Request Controller**
   ```php
   public function requestVerification(Request $request)
   {
       $teacher = auth()->user();
       
       // Check if already verified
       if ($teacher->teacherProfile->verified) {
           return back()->with('info', 'Your account is already verified.');
       }
       
       // Check for existing pending requests
       $pendingRequest = VerificationRequest::where('teacher_profile_id', $teacher->teacherProfile->id)
           ->where('status', 'pending')
           ->exists();
           
       if ($pendingRequest) {
           return back()->with('info', 'You already have a pending verification request.');
       }
       
       // Check cooldown period if previously rejected
       $lastRejection = VerificationRequest::where('teacher_profile_id', $teacher->teacherProfile->id)
           ->where('status', 'rejected')
           ->latest()
           ->first();
           
       if ($lastRejection && $lastRejection->reviewed_at->addDays(7) > now()) {
           $daysLeft = now()->diffInDays($lastRejection->reviewed_at->addDays(7));
           return back()->with('error', "Please wait {$daysLeft} more days before submitting a new verification request.");
       }
       
       // Check if required documents are uploaded
       $hasIdVerification = $teacher->teacherProfile->idVerifications()->exists();
       $hasCertificates = $teacher->teacherProfile->certificates()->exists();
       $hasResume = $teacher->teacherProfile->resume();
       
       if (!$hasIdVerification || !$hasCertificates || !$hasResume) {
           return back()->with('error', 'Please upload all required documents before requesting verification.');
       }
       
       // Create new verification request
       VerificationRequest::create([
           'teacher_profile_id' => $teacher->teacherProfile->id,
           'status' => 'pending',
           'submitted_at' => now(),
       ]);
       
       return back()->with('success', 'Verification request submitted successfully.');
   }
   ```

2. **Add Document Upload Controller Methods**
   ```php
   public function uploadDocument(Request $request)
   {
       $validated = $request->validate([
           'document' => 'required|file|max:10240',
           'type' => 'required|in:id_verification,certificate,resume',
           'name' => 'required|string|max:255',
           'description' => 'nullable|string|max:1000',
       ]);
       
       $path = $request->file('document')->store('documents/teacher/' . auth()->id());
       
       Document::create([
           'teacher_profile_id' => auth()->user()->teacherProfile->id,
           'type' => $validated['type'],
           'name' => $validated['name'],
           'description' => $validated['description'] ?? null,
           'path' => $path,
           'uploaded_at' => now(),
           'status' => 'pending',
       ]);
       
       return back()->with('success', 'Document uploaded successfully.');
   }
   ```

3. **Enhance Admin Verification Controller**
   - Add analytics methods
   - Add bulk approval/rejection
   - Add verification history

### Frontend Tasks

1. **Teacher Verification Status Component**
   - Status badge with tooltip
   - Progress steps visualization
   - Action buttons based on current status

2. **Document Upload Interface**
   - Drag and drop file upload
   - Document type selection
   - Document preview
   - Delete/replace functionality

3. **Admin Verification Queue**
   - Sortable/filterable table
   - Quick action buttons
   - Status indicators

4. **Admin Verification Detail View**
   - Document preview tabs
   - Teacher information panel
   - Verification history timeline
   - Action buttons with confirmation

## Business Rules

1. **Verification Requirements**
   - Complete teacher profile
   - Valid ID verification document
   - At least one educational certificate
   - Updated resume/CV
   - Profile picture

2. **Cooldown Periods**
   - 7 days after rejection before reapplication
   - Maximum 3 attempts within 30 days

3. **Automatic Rejections**
   - Incomplete profile information
   - Missing required documents
   - Poor quality documents (admin review)

4. **Verification Expiry**
   - Verification valid for 1 year
   - Renewal process starts 30 days before expiry

## Notifications

1. **Teacher Notifications**
   - Verification request received
   - Verification approved/rejected
   - Document approval/rejection
   - Verification expiry warning

2. **Admin Notifications**
   - New verification request
   - Verification queue threshold alerts
   - Document requiring review

## Future Enhancements

1. **AI-Assisted Document Verification**
   - Automatic ID validation
   - Certificate authenticity checking
   - Face matching with profile photo

2. **Tiered Verification Levels**
   - Basic verification (ID only)
   - Standard verification (ID + certificates)
   - Premium verification (ID + certificates + background check)

3. **Verification Badges**
   - Display verification level on teacher profile
   - Special badges for highly qualified teachers

4. **Integration with External Verification Services**
   - Background check services
   - Educational credential verification
   - Professional certification validation 