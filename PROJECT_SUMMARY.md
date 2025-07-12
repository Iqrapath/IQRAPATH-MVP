# Project Summary

## Design System Enforcement (June 2024)

### Overview
This project enforces a strict design system for all UI development. The design system includes a specific color palette, font families, and typography rules. All custom styles and components must adhere to these tokens. Tailwind CSS is used for utility classes, but all color and font utilities are overridden by the design system.

### Key Actions Taken
- **CSS Updated:**
  - Defined only the design system's color palette (Midnight Blue, White, Greyscale 50-900) as CSS variables in `app.css`.
  - Defined only the design system's font families (Nunito, Inter, Poppins) as CSS variables.
  - All heading, body, and button styles are set to match the design system's font, size, weight, and line height.
  - Utility classes for all body and button text styles are included for consistent use.
  - Comments added to clarify that only these tokens should be used—no Tailwind default colors or fonts.
  - `.btn` class updated to match the design system's button style (border-radius, padding, border, color, background, cursor, transition).

## Authentication System Redesign (July 2024)

### Overview
The authentication system UI has been completely redesigned to match the new design system and improve user experience. This includes login, registration, and password reset flows.

### Key Actions Taken
- **Authentication Pages Redesigned:**
  - Login page updated with new layout, form fields, and social login options
  - Registration page redesigned with improved form layout and validation
  - Password reset flow (forgot password and reset password) redesigned for better usability
  - Added show/hide password toggles for better user experience
  - Implemented consistent error states and validation messages
  - Applied the teal color scheme consistently across all auth pages

- **Email Templates Customized:**
  - Created custom password reset notification using Markdown templates
  - Designed branded email templates with IqraPath logo and color scheme
  - Implemented responsive email design that works across devices
  - Added Islamic greeting and professional formatting
  - Created both HTML and text fallback versions for email clients

## Booking and Session Management System (July 2024)

### Overview
A comprehensive booking and session management system has been implemented to handle teacher availability, session scheduling, and attendance tracking. The system integrates with Zoom for virtual sessions and includes automated notifications and reminders.

### Key Actions Taken
- **Database Structure:**
  - Created `teacher_availabilities` table for managing teacher schedules:
    - Day of week and time slot management
    - Active status tracking for availability periods
  - Implemented `bookings` table with comprehensive workflow features:
    - Unique booking identifiers
    - Student, teacher, and subject relationships
    - Date and time scheduling with duration tracking
    - Status workflow (pending, approved, rejected, upcoming, completed, missed, cancelled)
    - Approval and cancellation tracking with timestamps
  - Designed `teaching_sessions` table with detailed session management:
    - Unique session identifiers linked to bookings
    - Comprehensive meeting information (links, platforms, passwords)
    - Zoom integration fields (meeting ID, host ID, join/start URLs)
    - Attendance tracking for both teachers and students
    - Detailed join/leave timestamps for participants
    - Recording URLs and session notes
  - Added support tables for enhanced functionality:
    - `booking_notes` for communication during booking process
    - `booking_notifications` for status updates and reminders
    - `booking_history` for comprehensive audit trail
    - `session_materials` for learning resource management
    - `session_progress` for tracking student achievements

- **Booking Management:**
  - Implemented booking request and approval workflow
  - Created status tracking (pending, approved, rejected, upcoming, completed, missed, cancelled)
  - Added notification system for booking status changes
  - Built comprehensive audit trail for all booking changes

- **Session Management:**
  - Implemented automatic session creation from approved bookings
  - Added attendance tracking for both teachers and students
  - Created session materials management for sharing resources
  - Built progress tracking system with proficiency levels

- **Zoom Integration:**
  - Implemented automatic Zoom meeting creation for virtual sessions
  - Added JWT authentication for secure API communication
  - Created webhook handler for real-time session events
  - Built attendance tracking based on Zoom participant data
  - Implemented automatic session status updates based on meeting events

- **Notification System:**
  - Created email notifications for session reminders
  - Implemented scheduled commands for automated reminders
  - Added in-app notifications for booking and session events

### Implementation Details
- **Teacher Availabilities Migration (`2025_07_15_000000_create_teacher_availabilities_table.php`):**
  - Table schema includes:
    - `id`: Auto-incrementing primary key
    - `teacher_id`: Foreign key to users table with cascade deletion
    - `day_of_week`: TinyInteger (0-6) representing days from Sunday to Saturday
    - `start_time`: Time field for availability start
    - `end_time`: Time field for availability end
    - `is_active`: Boolean for active/inactive status
    - Standard timestamps for creation and updates

- **Bookings Migration (`2025_07_16_000000_create_bookings_table.php`):**
  - Table schema includes:
    - `id`: Auto-incrementing primary key
    - `booking_uuid`: Unique string identifier
    - `student_id`: Foreign key to users table (student)
    - `teacher_id`: Foreign key to users table (teacher)
    - `subject_id`: Foreign key to subjects table
    - `booking_date`: Date field for session date
    - `start_time`: Time field for session start
    - `end_time`: Time field for session end
    - `duration_minutes`: Integer for session length
    - `status`: Enum with comprehensive booking states
    - `notes`: Text field for booking details
    - `created_by_id`: Foreign key to users who created the booking
    - `approved_by_id`: Foreign key to users who approved (nullable)
    - `approved_at`: Timestamp for approval time
    - `cancelled_by_id`: Foreign key to users who cancelled (nullable)
    - `cancelled_at`: Timestamp for cancellation time
    - Standard timestamps for creation and updates

- **Booking Notes Migration (`2025_07_16_000001_create_booking_notes_table.php`):**
  - Table schema for storing communication notes during booking process

- **Booking Notifications Migration (`2025_07_16_000002_create_booking_notifications_table.php`):**
  - Table schema for tracking notifications sent about bookings

- **Booking History Migration (`2025_07_16_000003_create_booking_history_table.php`):**
  - Table schema for maintaining comprehensive audit trail of booking changes

- **Teaching Sessions Migration (`2025_07_17_000000_create_teaching_sessions_table.php`):**
  - Table schema includes:
    - `id`: Auto-incrementing primary key
    - `session_uuid`: Unique string identifier
    - `booking_id`: Foreign key to bookings table (nullable with nullOnDelete)
    - `teacher_id`: Foreign key to users table (teacher)
    - `student_id`: Foreign key to users table (student)
    - `subject_id`: Foreign key to subjects table
    - `session_date`: Date field for session date
    - `start_time`: Time field for session start
    - `end_time`: Time field for session end
    - `actual_duration_minutes`: Integer for actual session length
    - `status`: Enum (scheduled, in_progress, completed, cancelled, no_show)
    - Meeting fields:
      - `meeting_link`: String for session access
      - `meeting_platform`: String for platform name
      - `meeting_password`: String for access password
    - Zoom integration fields:
      - `zoom_meeting_id`: String for Zoom meeting ID
      - `zoom_host_id`: String for Zoom host ID
      - `zoom_join_url`: String for student access URL
      - `zoom_start_url`: String for teacher access URL
      - `zoom_password`: String for Zoom password
    - Attendance tracking fields:
      - `teacher_marked_present`: Boolean for teacher attendance
      - `student_marked_present`: Boolean for student attendance
      - `attendance_data`: JSON for detailed attendance information
      - `teacher_joined_at`: Timestamp for teacher join time
      - `student_joined_at`: Timestamp for student join time
      - `teacher_left_at`: Timestamp for teacher leave time
      - `student_left_at`: Timestamp for student leave time
    - Additional fields:
      - `recording_url`: String for session recording
      - `teacher_notes`: Text for teacher's session notes
      - `student_notes`: Text for student's session notes
    - Standard timestamps for creation and updates

- **Session Materials Migration (`2025_07_17_000001_create_session_materials_table.php`):**
  - Table schema includes:
    - `id`: Auto-incrementing primary key
    - `session_id`: Foreign key to teaching_sessions table
    - `title`: String for material title
    - `description`: Text for material description
    - `file_path`: String for file location
    - `file_type`: String for file MIME type
    - `uploaded_by_id`: Foreign key to users who uploaded
    - Standard timestamps for creation and updates

- **Session Progress Migration (`2025_07_17_000002_create_session_progress_table.php`):**
  - Table schema includes:
    - `id`: Auto-incrementing primary key
    - `session_id`: Foreign key to teaching_sessions table
    - `topic_covered`: String for session topic
    - `proficiency_level`: Enum (beginner, intermediate, advanced)
    - `teacher_assessment`: Text for teacher's evaluation
    - `next_steps`: Text for recommended next actions
    - Standard timestamps for creation and updates

### Developer Guidance
- **Booking System:**
  - Use the `Booking` model's `createSession()` method to generate teaching sessions from approved bookings
  - The booking system handles the scheduling, while the session system tracks the actual teaching events
  - All booking changes should be recorded in the `booking_history` table
  - Use the booking status workflow to manage the lifecycle:
    ```php
    $booking->status = 'approved';
    $booking->approved_by_id = auth()->id();
    $booking->approved_at = now();
    $booking->save();
    
    // Create session automatically after approval
    $session = $booking->createSession();
    ```

- **Session Management:**
  - Sessions are automatically created from approved bookings
  - Use the `TeachingSession` model's methods for attendance tracking:
    ```php
    $session->markTeacherPresent();
    $session->markStudentPresent();
    $session->recordJoinTime('teacher', now());
    ```
  - Session materials should be uploaded through the `SessionMaterial` model:
    ```php
    SessionMaterial::create([
        'session_id' => $session->id,
        'title' => 'Lesson Slides',
        'description' => 'Slides for today\'s lesson',
        'file_path' => $filePath,
        'file_type' => 'application/pdf',
        'uploaded_by_id' => auth()->id()
    ]);
    ```
  - Track session progress for student achievement:
    ```php
    SessionProgress::create([
        'session_id' => $session->id,
        'topic_covered' => 'Introduction to Tajweed',
        'proficiency_level' => 'beginner',
        'teacher_assessment' => 'Student is making good progress',
        'next_steps' => 'Practice pronunciation of heavy letters'
    ]);
    ```

- **Zoom Integration:**
  - Zoom API credentials must be set in the `.env` file:
    ```
    ZOOM_API_KEY=your_zoom_api_key
    ZOOM_API_SECRET=your_zoom_api_secret
    ```
  - For production, set up a Zoom webhook with the endpoint `/api/zoom/webhook`
  - Subscribe to meeting.started, meeting.ended, meeting.participant_joined, and meeting.participant_left events
  - Use the ZoomService for meeting management:
    ```php
    $zoomData = ZoomService::createMeeting([
        'topic' => "Session with {$student->name}",
        'start_time' => $session->session_date . ' ' . $session->start_time,
        'duration' => $session->duration_minutes
    ]);
    
    $session->update([
        'zoom_meeting_id' => $zoomData['id'],
        'zoom_host_id' => $zoomData['host_id'],
        'zoom_join_url' => $zoomData['join_url'],
        'zoom_start_url' => $zoomData['start_url'],
        'zoom_password' => $zoomData['password']
    ]);
    ```

- **Teacher Availability:**
  - Manage teacher schedules through the TeacherAvailability model:
    ```php
    TeacherAvailability::create([
        'teacher_id' => $teacher->id,
        'day_of_week' => 1, // Monday
        'start_time' => '09:00:00',
        'end_time' => '12:00:00',
        'is_active' => true
    ]);
    ```
  - Check availability before booking:
    ```php
    $isAvailable = $teacher->isAvailableAt(
        $date,
        $startTime,
        $endTime
    );
    ```

- **Notification System:**
  - Create booking notifications:
    ```php
    BookingNotification::create([
        'user_id' => $booking->student_id,
        'booking_id' => $booking->id,
        'type' => 'booking_approved',
        'message' => 'Your booking has been approved',
        'is_read' => false
    ]);
    ```
  - Send email reminders:
    ```php
    Mail::to($booking->student->email)
        ->send(new BookingReminderMail($booking));
    ```

## Financial Management System (July 2024)

### Overview
A comprehensive financial management system has been implemented to handle teacher earnings, transactions, and payout requests. The system supports various transaction types, automated balance calculations, and separate interfaces for teachers and administrators.

### Key Actions Taken
- **Database Structure:**
  - Created `teacher_earnings` table for tracking wallet balances and earnings
  - Implemented `transactions` table with multiple transaction types
  - Designed `payout_requests` table with approval workflow
  - Added support for various payment methods

- **Transaction Management:**
  - Implemented multiple transaction types (session payments, referral bonuses, withdrawals, etc.)
  - Created automatic balance updates when transactions are processed
  - Built comprehensive transaction history with filtering options

- **Payout System:**
  - Implemented payout request workflow with approval process
  - Added support for multiple payment methods (bank transfer, PayPal, mobile money)
  - Built admin interface for processing payouts

- **Financial Reporting:**
  - Created earnings dashboard for teachers
  - Built financial overview for administrators
  - Implemented detailed transaction logs and filtering

### Developer Guidance
- **Financial Service:**
  - Use the `FinancialService` class for all financial operations
  - Transaction creation should always go through this service to ensure proper balance updates
  - Payment calculations should be centralized in this service

- **Transaction Types:**
  - `session_payment`: Payment for completed teaching sessions
  - `referral_bonus`: Bonus for referring new users
  - `withdrawal`: Teacher withdrawal of funds
  - `system_adjustment`: Manual adjustment by administrators
  - `refund`: Refund of a previous transaction

- **Payout Workflow:**
  - Teachers create payout requests
  - Administrators approve/decline requests
  - Approved requests generate withdrawal transactions
  - Administrators mark payouts as paid after processing

## Subscription Management System (July 2024)

### Overview
A comprehensive subscription management system has been implemented to handle plan creation, user subscriptions, payments, and renewals. The system supports multiple currencies, various billing cycles, and customizable plan features.

### Key Actions Taken
- **Database Structure:**
  - Created `subscription_plans` table for managing available plans
  - Implemented `subscriptions` table to track user subscriptions
  - Designed `subscription_transactions` table for payment history
  - Added support for multiple currencies (Naira and Dollar)

- **Plan Management:**
  - Implemented plan creation with customizable features and pricing
  - Created plan status management (active/inactive)
  - Built admin interface for plan management
  - Added support for plan duplication and modification

- **Subscription Handling:**
  - Implemented subscription purchase workflow
  - Created automatic renewal system with opt-in/opt-out
  - Built subscription status tracking (active, expired, cancelled)
  - Added transaction history for subscriptions

- **Payment Integration:**
  - Prepared structure for payment gateway integration
  - Created transaction status tracking
  - Built refund processing capability
  - Implemented currency conversion support

### Developer Guidance
- **Subscription Plans:**
  - Use the `SubscriptionPlan` model for creating and managing plans
  - Plans can have multiple features stored as JSON
  - Plans support both Naira and Dollar pricing

- **User Subscriptions:**
  - Use the `SubscriptionService` for all subscription operations
  - Subscription status changes should always go through the service
  - Check subscription status using the helper methods on the `Subscription` model

- **Payment Integration:**
  - The system is prepared for payment gateway integration
  - Currently uses simulated payments for demonstration
  - To integrate a real payment gateway, modify the `purchase` and `renew` methods in `SubscriptionController`

- **Billing Cycles:**
  - Supported billing cycles: monthly, quarterly, biannually, annually
  - Auto-renewal is optional and can be toggled by users
  - Expired subscriptions are automatically detected and processed

## Student Profile and Learning Progress System (July 2024)

### Overview
The student profile and learning progress system has been enhanced to provide comprehensive tracking of student information, learning preferences, and academic progress. The system now supports detailed progress tracking across subjects, milestone achievements, and certificate management. These enhancements align with the UI requirements and provide a solid foundation for the frontend implementation.

### Key Actions Taken
- **Database Structure:**
  - Enhanced `student_profiles` table with learning preferences and payment integration:
    - Removed unused `special_needs` field
    - Added `preferred_learning_times` as JSON field to store time slots
    - Added `age_group` field for demographic segmentation
    - Added `payment_id` field for integration with payment systems
  - Created `student_learning_progress` table with comprehensive tracking fields:
    - `progress_percentage` for overall subject completion
    - `completed_sessions` and `total_sessions` for progress calculation
    - `milestones_completed` as JSON field for achievement tracking
    - `certificates_earned` as JSON field for credential management
    - `last_updated_at` for tracking progress updates
  - Implemented proper foreign key constraints to users and subjects tables
  - Added timestamps for audit trail on all records

- **Student Profile Management:**
  - Updated `StudentProfile` model with new fillable properties:
    - Added JSON casting for `subjects_of_interest` and `preferred_learning_times`
    - Added date casting for `date_of_birth`
    - Implemented proper relationship to guardian accounts
  - Enhanced relationship with User model for seamless access
  - Added direct access to learning progress through relationship methods
  - Implemented proper data validation and type casting

- **Learning Progress Tracking:**
  - Created new `StudentLearningProgress` model with comprehensive features:
    - Implemented per-subject progress tracking with user and subject relationships
    - Created session completion counting with automatic percentage calculation
    - Built milestone achievement recording with timestamp tracking
    - Added certificate issuance and management with metadata storage
    - Implemented automatic progress calculation based on session completion
  - Added helper methods for common operations:
    - `updateProgressPercentage()` for recalculating completion percentage
    - `incrementCompletedSessions()` for tracking completed learning sessions
    - `addMilestone()` for recording achievement milestones with timestamps
    - `addCertificate()` for issuing and recording earned certificates

- **Model Relationships:**
  - Updated `User` model with new relationship to learning progress:
    - Added `learningProgress()` relationship for direct access to progress records
    - Maintained existing relationship to student profile
  - Created bidirectional relationships between `User`, `StudentProfile`, and `StudentLearningProgress`
  - Implemented `Subject` relationship for subject-specific progress tracking
  - Added proper foreign key constraints and cascade deletion rules

### Implementation Details
- **Student Profiles Migration (`2025_07_10_195816_create_student_profiles_table.php`):**
  - Table schema includes:
    - `id`: Auto-incrementing primary key
    - `user_id`: Foreign key to users table with cascade deletion
    - `date_of_birth`: Nullable date field for age calculation
    - `grade_level`: Student's current academic grade
    - `school_name`: Student's current school
    - `guardian_id`: Optional foreign key to users table for parent/guardian
    - `learning_goals`: Text field for educational objectives
    - `subjects_of_interest`: JSON array of preferred subjects
    - `preferred_learning_times`: JSON array of time slots
    - `age_group`: String field for age categorization
    - `payment_id`: Reference to payment system
    - Standard timestamps for creation and updates

- **Learning Progress Migration (`2025_07_23_000000_create_student_learning_progress_table.php`):**
  - Table schema includes:
    - `id`: Auto-incrementing primary key
    - `user_id`: Foreign key to users table with cascade deletion
    - `subject_id`: Foreign key to subjects table with cascade deletion
    - `progress_percentage`: Integer field (0-100) for completion tracking
    - `completed_sessions`: Counter for finished learning sessions
    - `total_sessions`: Target number of sessions for the subject
    - `milestones_completed`: JSON array with milestone details and timestamps
    - `certificates_earned`: JSON array with certificate details and issue dates
    - `last_updated_at`: Timestamp for progress updates
    - Standard timestamps for creation and updates

- **StudentProfile Model (`app/Models/StudentProfile.php`):**
  - Implements:
    - Proper fillable attributes for mass assignment
    - Type casting for dates and JSON fields
    - Relationship to User model (belongsTo)
    - Relationship to Guardian user (belongsTo)
    - Relationship to learning progress records (hasMany)

- **StudentLearningProgress Model (`app/Models/StudentLearningProgress.php`):**
  - Implements:
    - Custom table name specification
    - Comprehensive fillable attributes
    - Type casting for integers, arrays, and dates
    - Relationships to User, Subject, and StudentProfile models
    - Helper methods for progress management:
      - Progress percentage calculation based on session counts
      - Session increment with automatic progress update
      - Milestone recording with metadata and timestamps
      - Certificate recording with metadata and issue dates

- **User Model Updates (`app/Models/User.php`):**
  - Added relationship method to access learning progress records
  - Maintained existing relationship to student profile

### Developer Guidance
- **Student Profiles:**
  - Use the `StudentProfile` model for managing student information
  - Store preferred learning times and subjects of interest as JSON arrays:
    ```php
    $profile->preferred_learning_times = ['morning', 'evening'];
    $profile->subjects_of_interest = ['quran', 'tajweed', 'arabic'];
    ```
  - Access guardian information through the guardian relationship:
    ```php
    $guardian = $studentProfile->guardian;
    ```
  - Link students to guardians using the guardian_id field:
    ```php
    $studentProfile->guardian_id = $guardianUser->id;
    ```

- **Learning Progress:**
  - Use the `StudentLearningProgress` model for tracking academic achievement
  - Create new progress records for each subject a student studies:
    ```php
    $progress = StudentLearningProgress::create([
        'user_id' => $student->id,
        'subject_id' => $subject->id,
        'total_sessions' => 20,
    ]);
    ```
  - Progress is tracked per subject with the following helper methods:
    - `updateProgressPercentage()`: Recalculates progress based on completed sessions
      ```php
      $progress->updateProgressPercentage();
      ```
    - `incrementCompletedSessions()`: Increases the session count and updates progress
      ```php
      $progress->incrementCompletedSessions();
      ```
    - `addMilestone()`: Records achievement of learning milestones
      ```php
      $progress->addMilestone([
          'title' => 'Completed Chapter 1',
          'description' => 'Successfully recited Surah Al-Fatiha',
      ]);
      ```
    - `addCertificate()`: Records earned certificates with timestamps
      ```php
      $progress->addCertificate([
          'title' => 'Tajweed Basics',
          'level' => 'Beginner',
          'instructor' => 'Sheikh Abdullah',
      ]);
      ```
  - Access learning progress through the User model's `learningProgress()` relationship:
    ```php
    $progressRecords = $user->learningProgress;
    $quranProgress = $user->learningProgress()->where('subject_id', $quranSubject->id)->first();
    ```

- **Progress Calculation:**
  - Progress percentage is automatically calculated based on completed vs. total sessions:
    ```php
    // If 5 of 20 sessions are completed, progress will be 25%
    $progress->completed_sessions = 5;
    $progress->total_sessions = 20;
    $progress->updateProgressPercentage(); // Sets progress_percentage to 25
    ```
  - Milestones and certificates are stored as JSON arrays with timestamps:
    ```php
    // Example milestone structure
    [
        {
            "title": "Completed Chapter 1",
            "description": "Successfully recited Surah Al-Fatiha",
            "completed_at": "2024-07-23 15:30:45"
        }
    ]
    
    // Example certificate structure
    [
        {
            "title": "Tajweed Basics",
            "level": "Beginner",
            "instructor": "Sheikh Abdullah",
            "issued_at": "2024-07-23 16:45:22"
        }
    ]
    ```
  - The `last_updated_at` field tracks when progress was last modified and is automatically updated by helper methods

- **Frontend Integration:**
  - The student profile data structure supports UI requirements for:
    - Student personal information display
    - Learning preferences visualization
    - Age-appropriate content filtering
    - Payment system integration
  - The learning progress system supports:
    - Progress bars and completion percentages
    - Milestone achievement displays
    - Certificate galleries and downloads
    - Session counting and tracking

## Student Wallet and Payment System (July 2024)

### Overview
A comprehensive student wallet and payment system has been implemented to handle student finances, payment methods, and transaction history. This system integrates with the subscription management system and provides a foundation for payment processing through multiple gateways.

### Key Actions Taken
- **Database Structure:**
  - Created `student_wallets` table for managing student financial accounts:
    - Balance tracking with decimal precision
    - Total spent and refunded amount tracking
    - Payment method storage and default selection
    - Auto-renewal preference for subscriptions
  - Implemented `wallet_transactions` table for detailed transaction history:
    - Credit and debit transaction types
    - Amount and description fields
    - Transaction status tracking
    - Timestamp for transaction date
    - Metadata storage for additional information
  - Designed `payment_gateway_logs` table for external payment processing:
    - Multiple gateway support (Paystack, Flutterwave, etc.)
    - Transaction reference and ID tracking
    - Status tracking (pending, success, failed, abandoned)
    - Request and response data storage
    - Webhook data capture for asynchronous updates

- **Wallet Management:**
  - Implemented wallet creation and balance management
  - Added support for multiple payment methods per student
  - Created default payment method selection
  - Built auto-renewal preference toggle

- **Transaction Processing:**
  - Implemented credit and debit transaction types
  - Created transaction status tracking system
  - Built comprehensive transaction history
  - Added metadata storage for transaction details

- **Payment Gateway Integration:**
  - Prepared structure for multiple payment gateway integration
  - Implemented transaction reference generation and tracking
  - Created webhook handling for asynchronous payment updates
  - Built verification system for payment confirmation

### Implementation Details
- **Student Wallets Migration (`2025_07_22_000000_create_student_wallets_table.php`):**
  - Table schema includes:
    - `id`: Auto-incrementing primary key
    - `user_id`: Foreign key to users table with cascade deletion
    - `balance`: Decimal field (10,2) for current wallet balance
    - `total_spent`: Decimal field (10,2) for lifetime spending
    - `total_refunded`: Decimal field (10,2) for refunded amounts
    - `payment_methods`: JSON array for stored payment methods
    - `default_payment_method`: String for selected default method
    - `auto_renew_enabled`: Boolean for subscription auto-renewal
    - Standard timestamps for creation and updates

- **Wallet Transactions Migration (`2025_07_22_000002_create_wallet_transactions_table.php`):**
  - Table schema includes:
    - `id`: Auto-incrementing primary key
    - `wallet_id`: Foreign key to student_wallets table
    - `transaction_type`: Enum ('credit', 'debit')
    - `amount`: Decimal field (10,2) for transaction amount
    - `description`: String field for transaction description
    - `status`: Enum ('pending', 'completed', 'failed')
    - `transaction_date`: Timestamp for when transaction occurred
    - `metadata`: JSON field for additional transaction data
    - Standard timestamps for creation and updates

- **Payment Gateway Logs Migration (`2025_07_22_000001_create_payment_gateway_logs_table.php`):**
  - Table schema includes:
    - `id`: Auto-incrementing primary key
    - `gateway`: String field for payment provider name
    - `reference`: Unique string for transaction reference
    - `transaction_id`: String for gateway's transaction ID
    - `user_id`: Foreign key to users table
    - `subscription_transaction_id`: Optional foreign key to subscription_transactions
    - `status`: Enum ('pending', 'success', 'failed', 'abandoned')
    - `amount`: Decimal field (10,2) for transaction amount
    - `currency`: String field for currency code
    - `request_data`: JSON field for API request data
    - `response_data`: JSON field for API response data
    - `webhook_data`: JSON field for webhook payload
    - `verified_at`: Timestamp for verification time
    - Standard timestamps for creation and updates

### Developer Guidance
- **Student Wallet:**
  - Use the `StudentWallet` model for managing student finances
  - Access wallet through the User model's relationship:
    ```php
    $wallet = $user->wallet;
    // Or create if it doesn't exist
    $wallet = $user->getOrCreateWallet();
    ```
  - Store payment methods as JSON array:
    ```php
    $wallet->payment_methods = [
        [
            'type' => 'card',
            'last4' => '4242',
            'expiry' => '12/25',
            'brand' => 'visa',
            'token' => 'tok_visa'
        ],
        [
            'type' => 'bank_account',
            'bank' => 'Access Bank',
            'last4' => '1234',
            'token' => 'ba_123456'
        ]
    ];
    ```
  - Set default payment method:
    ```php
    $wallet->default_payment_method = 'tok_visa';
    ```

- **Wallet Transactions:**
  - Create transactions for all financial activities:
    ```php
    WalletTransaction::create([
        'wallet_id' => $wallet->id,
        'transaction_type' => 'credit',
        'amount' => 50.00,
        'description' => 'Wallet topup',
        'status' => 'completed',
        'transaction_date' => now(),
        'metadata' => [
            'payment_method' => 'card',
            'reference' => 'ref_123456'
        ]
    ]);
    ```
  - Query transaction history:
    ```php
    $transactions = $wallet->transactions()->latest()->get();
    $credits = $wallet->transactions()->where('transaction_type', 'credit')->get();
    ```

- **Payment Gateway Integration:**
  - Log all payment attempts:
    ```php
    PaymentGatewayLog::create([
        'gateway' => 'paystack',
        'reference' => 'ref_' . uniqid(),
        'user_id' => $user->id,
        'subscription_transaction_id' => $subscriptionTransaction->id,
        'status' => 'pending',
        'amount' => 100.00,
        'currency' => 'NGN',
        'request_data' => $requestData
    ]);
    ```
  - Update payment status after verification:
    ```php
    $paymentLog->update([
        'status' => 'success',
        'response_data' => $responseData,
        'verified_at' => now()
    ]);
    ```
  - Handle webhook data:
    ```php
    $paymentLog->update([
        'webhook_data' => $webhookPayload,
        'status' => $webhookStatus
    ]);
    ```

- **Frontend Integration:**
  - The wallet system supports UI requirements for:
    - Wallet balance display
    - Transaction history lists
    - Payment method management
    - Auto-renewal toggle for subscriptions
  - The payment gateway integration supports:
    - Multiple payment providers
    - Transaction status tracking
    - Payment verification
    - Error handling and recovery

---
1. _Last updated: 10th July 2024_ 
2. _Last updated: 18th July 2024_ 
3. _Last updated: 20th July 2024_
4. _Last updated: 21st July 2024_
5. _Last updated: 23rd July 2024_
6. _Last updated: 24th July 2024_
7. _Last updated: 25th July 2024_
8. _Last updated: 26th July 2024_

## Guardian Management System (July 2024)

### Overview
A comprehensive guardian management system has been implemented to handle guardian profiles, child registration, and direct messaging. The system supports multiple children per guardian, detailed profiles, and communication features.

### Key Actions Taken
- **Database Structure:**
  - Enhanced `guardian_profiles` table with improved tracking:
    - Added `status` field for account status management (active, suspended, inactive)
    - Added `registration_date` field to track when guardian accounts were created
    - Added `children_count` field for efficient tracking of associated students
    - Removed unused fields (occupation, preferred_contact_method, secondary_phone)
  - Enhanced `student_profiles` table with additional fields:
    - Added `gender` field to store student gender information
    - Added `status` field for account status management
    - Added `registration_date` field to track when student accounts were created
  - Created `guardian_messages` table for direct messaging:
    - Support for sender and recipient tracking
    - Message read status and timestamps
    - Flexible querying for conversations

- **Guardian Profile Management:**
  - Implemented guardian status tracking (active, suspended, inactive)
  - Added automatic children count tracking with database triggers
  - Enhanced relationship with student profiles
  - Improved profile data structure based on UI requirements

- **Child Registration System:**
  - Implemented gender tracking for students
  - Added status management for student accounts
  - Enhanced relationship with guardian accounts
  - Implemented automatic guardian update on child association

- **Messaging System:**
  - Created direct messaging capability between users
  - Implemented read status tracking
  - Added conversation history and threading
  - Built message scopes for efficient querying

### Implementation Details
- **Guardian Profiles Migration Updates:**
  - Added status enum field with values: active, suspended, inactive
  - Added registration_date timestamp field
  - Added children_count integer field with automatic updates
  - Removed unused fields: occupation, preferred_contact_method, secondary_phone
  - Created database triggers to maintain children count accuracy

- **Student Profiles Migration Updates:**
  - Added gender enum field with values: male, female, other
  - Added status enum field with values: active, inactive, suspended
  - Added registration_date timestamp field

- **Guardian Messages Migration:**
  - Created table with fields for sender_id, recipient_id, message content
  - Added is_read boolean and read_at timestamp for status tracking
  - Implemented standard timestamps for creation and updates

- **Model Updates:**
  - Enhanced GuardianProfile model with new fields and relationships
  - Updated StudentProfile model with new fields and methods
  - Created GuardianMessage model with comprehensive messaging features
  - Added message relationship methods to User model

### Developer Guidance
- **Guardian Profiles:**
  - Use the `GuardianProfile` model for managing guardian information
  - Access children through the relationship:
    ```php
    $children = $guardianProfile->students;
    $childrenCount = $guardianProfile->children_count;
    ```
  - Update guardian status:
    ```php
    $guardianProfile->status = 'suspended';
    $guardianProfile->save();
    ```

- **Student Profiles:**
  - Use the `StudentProfile` model for managing student information
  - Access guardian through the relationship:
    ```php
    $guardian = $studentProfile->guardian;
    ```
  - Update student status:
    ```php
    $studentProfile->status = 'inactive';
    $studentProfile->save();
    ```
  - Trigger guardian children count update:
    ```php
    $studentProfile->updateGuardianChildrenCount();
    ```

- **Messaging System:**
  - Send messages between users:
    ```php
    GuardianMessage::create([
        'sender_id' => $teacher->id,
        'recipient_id' => $guardian->id,
        'message' => 'Your child is making excellent progress!'
    ]);
    ```
  - Retrieve messages for a user:
    ```php
    $sentMessages = $user->sentMessages;
    $receivedMessages = $user->receivedMessages;
    $allMessages = $user->allMessages()->latest()->get();
    $unreadCount = $user->unreadMessages()->count();
    ```
  - Get conversation between two users:
    ```php
    $conversation = GuardianMessage::betweenUsers($user1->id, $user2->id)
                                  ->latest()
                                  ->get();
    ```
  - Mark messages as read:
    ```php
    $message->markAsRead();
    ```

- **Frontend Integration:**
  - The guardian management system supports UI requirements for:
    - Guardian listing with status and registration date
    - Child management and registration
    - Direct messaging between guardians and staff
    - Profile editing and status management

---
1. _Last updated: 10th July 2024_ 
2. _Last updated: 18th July 2024_ 
3. _Last updated: 20th July 2024_
4. _Last updated: 21st July 2024_
5. _Last updated: 23rd July 2024_
6. _Last updated: 24th July 2024_
7. _Last updated: 25th July 2024_
8. _Last updated: 26th July 2024_

## Teacher Verification Request System (July 2024)

### Overview
A robust teacher verification system has been implemented to ensure quality and trust across the platform. The system supports document review, live video verification (with Zoom and Google Meet integration), status tracking, audit logging, and admin review workflows.

### Key Actions Taken
- **Database Structure:**
  - Created `verification_requests` table to track each teacher's verification event, including:
    - Status (pending, verified, rejected, live_video)
    - Document and video verification status
    - Scheduled call info (date, platform, meeting link)
    - Admin review and rejection reason
    - Submission and review timestamps
  - Created `verification_calls` table to support scheduling and rescheduling of video calls:
    - Platform (Zoom, Google Meet, other)
    - Meeting link, notes, status, and creator
  - Created `verification_audit_logs` table to maintain a full audit trail of status changes and actions:
    - Status, changed_by, changed_at, notes
  - All tables are linked to the appropriate teacher profile and user accounts for full traceability.

- **Model Relationships:**
  - `TeacherProfile` has many `VerificationRequest`
  - `VerificationRequest` has many `VerificationCall` and `VerificationAuditLog`
  - `VerificationRequest` belongs to `TeacherProfile` and reviewer (User)
  - `VerificationCall` belongs to `VerificationRequest` and creator (User)
  - `VerificationAuditLog` belongs to `VerificationRequest` and changer (User)

- **Zoom & Video Integration:**
  - The system supports both Zoom and Google Meet for live video verification calls
  - If Zoom is selected, the platform leverages the existing Zoom integration for meeting creation and management
  - Meeting links and metadata are stored in the verification call records

- **Document Review:**
  - Each verification request is associated with the teacher's documents (ID, certificates, resume)
  - Document statuses are tracked and can be reviewed, verified, or rejected by admins

- **Audit Trail:**
  - Every status change or significant action on a verification request is logged in the audit table
  - Provides a full history for compliance and transparency

### Developer Guidance
- **Verification Requests:**
  - Use the `VerificationRequest` model to manage the lifecycle of teacher verification
  - Schedule or reschedule calls using the `VerificationCall` model
  - Log status changes and actions using the `VerificationAuditLog` model
  - Link verification requests to teacher profiles and admin reviewers

- **Video Call Integration:**
  - When scheduling a call, set the `platform` to 'zoom' or 'google_meet' and store the meeting link
  - For Zoom, use the platform's Zoom integration service to generate and manage meetings

- **Document Review:**
  - Access and update document statuses via the `Document` model and its relationship to `TeacherProfile`
  - Use the verification request's `docs_status` to reflect the overall document review state

- **Audit Logging:**
  - Log every status change or admin action using the `VerificationAuditLog` model for traceability

- **Frontend Integration:**
  - The verification system supports UI requirements for:
    - Listing and filtering verification requests
    - Reviewing and updating document and video statuses
    - Scheduling and managing video calls
    - Viewing audit history and admin actions

---
1. _Last updated: 10th July 2024_ 
2. _Last updated: 18th July 2024_ 
3. _Last updated: 20th July 2024_
4. _Last updated: 21st July 2024_
5. _Last updated: 23rd July 2024_
6. _Last updated: 24th July 2024_
7. _Last updated: 25th July 2024_
8. _Last updated: 26th July 2024_

## Notification System (July 2024)

### Overview
The IQRAPATH platform includes a comprehensive notification system that supports multi-channel delivery, automated triggers, templates, and analytics. The system enables both manual notifications from administrators and automated notifications based on system events.

### Key Actions Taken
- **Database Structure:**
  - Created `notifications` table for storing notification content:
    - Title and body fields for notification content
    - Type classification (custom, system, payment, class, subscription, feature)
    - Status tracking (draft, scheduled, sent, delivered, read, failed)
    - Sender information and metadata storage
    - Scheduling capabilities for future delivery
  - Implemented `notification_recipients` table to track delivery:
    - User-specific notification tracking
    - Channel-specific status (in-app, email, SMS)
    - Read status and timestamps
    - Delivery status tracking
  - Created `notification_templates` table for reusable content:
    - Standardized templates with placeholders
    - Type categorization for different notification purposes
    - Active status management
  - Designed `notification_triggers` table for event-based notifications:
    - Event-to-notification mapping
    - Audience targeting (all, role-specific, individual)
    - Channel selection (in-app, email, SMS)
    - Timing configuration (immediate, before/after event)

- **Notification Service:**
  - Implemented centralized `NotificationService` for all notification operations
  - Created methods for notification creation, recipient management, and delivery
  - Built template-based notification generation with placeholder substitution
  - Implemented scheduled notification processing via command

- **Event Integration:**
  - Created event listeners for common system events:
    - User registration events
    - Payment processing events
    - Session scheduling events
    - Subscription expiry events
  - Implemented `ProcessNotificationTrigger` listener for automatic notifications
  - Added event data extraction for dynamic notification content

- **Multi-channel Delivery:**
  - Implemented in-app notification delivery and storage
  - Created email notification delivery with custom templates
  - Prepared framework for SMS notification delivery
  - Built channel-specific status tracking

- **User Interface:**
  - Created API endpoints for notification retrieval and management
  - Implemented controllers for user notification center
  - Added admin interface for notification creation and management
  - Built notification template and trigger management

### Implementation Details
- **Notifications Migration (`2025_07_25_000000_create_notifications_table.php`):**
  - Table schema includes:
    - `id`: Auto-incrementing primary key
    - `title`: String for notification title
    - `body`: Text field for notification content
    - `type`: String for notification classification
    - `status`: String for notification status
    - `sender_type`: String for sender classification
    - `sender_id`: Foreign key to users table (nullable)
    - `scheduled_at`: Timestamp for scheduled delivery
    - `sent_at`: Timestamp for actual sending
    - `metadata`: JSON field for additional data
    - Standard timestamps for creation and updates

- **Notification Recipients Migration (`2025_07_25_000001_create_notification_recipients_table.php`):**
  - Table schema includes:
    - `id`: Auto-incrementing primary key
    - `notification_id`: Foreign key to notifications table
    - `user_id`: Foreign key to users table
    - `status`: String for delivery status
    - `channel`: String for delivery channel
    - `delivered_at`: Timestamp for delivery time
    - `read_at`: Timestamp for read time
    - Standard timestamps for creation and updates
    - Unique constraint on notification_id, user_id, and channel

- **Notification Templates Migration (`2025_07_25_000002_create_notification_templates_table.php`):**
  - Table schema includes:
    - `id`: Auto-incrementing primary key
    - `name`: Unique string identifier
    - `title`: String for template title
    - `body`: Text field for template content
    - `type`: String for template classification
    - `placeholders`: JSON array of available placeholders
    - `is_active`: Boolean for active status
    - Standard timestamps for creation and updates

- **Notification Triggers Migration (`2025_07_25_000003_create_notification_triggers_table.php`):**
  - Table schema includes:
    - `id`: Auto-incrementing primary key
    - `name`: String for trigger name
    - `event`: String for event name
    - `template_id`: Foreign key to notification_templates table
    - `audience_type`: String for recipient targeting
    - `audience_filter`: JSON field for filtering criteria
    - `channels`: JSON array of delivery channels
    - `timing_type`: String for delivery timing
    - `timing_value`: Integer for timing value
    - `timing_unit`: String for timing unit
    - `is_enabled`: Boolean for active status
    - Standard timestamps for creation and updates

- **Model Implementation:**
  - Created `Notification` model with comprehensive features:
    - Relationships to sender and recipients
    - Status scopes for filtering
    - Type scopes for categorization
    - Send method for delivery
  - Created `NotificationRecipient` model for delivery tracking:
    - Relationships to notification and user
    - Methods for marking as delivered or read
    - Scopes for filtering by user, channel, and status
  - Created `NotificationTemplate` model for reusable content:
    - Relationship to triggers
    - Active scope for filtering
    - Type scope for categorization
    - Method for creating notifications from template
  - Created `NotificationTrigger` model for event handling:
    - Relationship to template
    - Enabled scope for filtering
    - Event scope for filtering
    - Process method for handling events

- **Service Layer:**
  - Implemented `NotificationService` for centralized notification management:
    - Methods for creating notifications
    - Methods for managing recipients
    - Methods for sending notifications
    - Methods for processing events
    - Methods for sending scheduled notifications
    - Methods for creating notifications from templates
    - Methods for counting and marking notifications

- **Event System:**
  - Created event classes for common system events:
    - `UserRegistered` for new user registration
    - `PaymentProcessed` for payment completion
    - `SessionScheduled` for class scheduling
    - `SubscriptionExpiring` for subscription expiry
  - Implemented listeners for handling events:
    - `SendWelcomeNotification` for new users
    - `SendPaymentConfirmation` for completed payments
    - `SendSessionReminder` for scheduled classes
    - `SendSubscriptionExpiryReminder` for expiring subscriptions
    - `ProcessNotificationTrigger` for generic event handling

- **Command and Scheduling:**
  - Created `SendScheduledNotifications` command for processing scheduled notifications
  - Added command to scheduler for minute-by-minute execution
  - Implemented queue processing for asynchronous notification delivery

- **Controllers and Routes:**
  - Created `NotificationController` for admin notification management:
    - CRUD operations for notifications
    - Template and trigger management
    - Notification sending and scheduling
  - Created `UserNotificationController` for user notification center:
    - Notification listing and viewing
    - Marking notifications as read
    - Deleting notifications
  - Created `Api\NotificationController` for notification API:
    - Retrieving notifications
    - Marking notifications as read
    - Counting unread notifications
  - Added routes for all notification functionality

### Developer Guidance
- **Creating Notifications:**
  - Use the `NotificationService` for all notification operations:
    ```php
    $notificationService->createNotification([
        'title' => 'Important Announcement',
        'body' => 'This is an important system announcement.',
        'type' => 'system',
        'sender_type' => 'admin',
        'sender_id' => auth()->id(),
    ]);
    ```
  - Add recipients to notifications:
    ```php
    $notificationService->addRecipients($notification, [
        'all_users' => true,
        'channels' => ['in-app', 'email'],
    ]);
    
    // Or for specific roles
    $notificationService->addRecipients($notification, [
        'roles' => ['student', 'teacher'],
        'channels' => ['in-app'],
    ]);
    
    // Or for specific users
    $notificationService->addRecipients($notification, [
        'user_ids' => [$user1->id, $user2->id],
        'channels' => ['in-app', 'email'],
    ]);
    ```
  - Send notifications:
    ```php
    $notificationService->sendNotification($notification);
    ```

- **Using Templates:**
  - Create notifications from templates:
    ```php
    $notification = $notificationService->createFromTemplate(
        'welcome_user',
        [
            'User_Name' => $user->name,
            'action_url' => route('dashboard'),
            'action_text' => 'Go to Dashboard',
        ],
        [
            'user_ids' => [$user->id],
            'channels' => ['in-app', 'email'],
        ]
    );
    ```
  - Available templates include:
    - `welcome_user`: For new user registration
    - `payment_confirmation`: For successful payments
    - `session_reminder`: For upcoming classes
    - `subscription_expiry`: For expiring subscriptions
    - `new_feature`: For feature announcements

- **Event-Based Notifications:**
  - Dispatch events to trigger notifications:
    ```php
    event(new UserRegistered($user));
    event(new PaymentProcessed($user, $paymentData));
    event(new SessionScheduled($user, $sessionData));
    event(new SubscriptionExpiring($user, $subscriptionData));
    ```
  - Create custom notification triggers:
    ```php
    NotificationTrigger::create([
        'name' => 'Custom Event Trigger',
        'event' => 'custom.event',
        'template_id' => $template->id,
        'audience_type' => 'role',
        'audience_filter' => ['roles' => ['student']],
        'channels' => ['in-app', 'email'],
        'timing_type' => 'immediate',
        'is_enabled' => true,
    ]);
    ```

- **User Notification Access:**
  - Get user notifications:
    ```php
    $notifications = $user->receivedNotifications()
        ->with('notification')
        ->where('channel', 'in-app')
        ->get();
    ```
  - Count unread notifications:
    ```php
    $unreadCount = $user->unreadNotifications()->count();
    ```
  - Mark notifications as read:
    ```php
    $user->markAllNotificationsAsRead();
    // Or individual notifications
    $recipient->markAsRead();
    ```

- **Frontend Integration:**
  - The notification system supports UI requirements for:
    - Notification center with read/unread status
    - Real-time notification counters
    - Admin notification management
    - Template and trigger configuration
    - Multi-channel delivery preferences

## Feedback and Support System

The IQRAPATH platform includes a comprehensive feedback and support system that allows users to submit feedback, create support tickets, and file disputes. This system helps maintain a high level of user satisfaction and provides administrators with tools to manage user issues effectively.

### Key Features

1. **Feedback Submissions**
   - Users can submit general feedback about the platform, teachers, or classes
   - Feedback can include attachments for additional context
   - Administrators can review and respond to feedback

2. **Support Tickets**
   - Users can create support tickets for technical issues or assistance
   - Support tickets can be assigned to staff members for resolution
   - Responses can be scheduled for later delivery
   - Status tracking (open, resolved, closed)

3. **Disputes/Complaints**
   - Users can file formal complaints against other users (teachers, students)
   - Administrators can mediate disputes and update their status
   - Evidence attachments can be uploaded to support claims

4. **Attachments System**
   - Users can upload evidence and supporting documents
   - Secure file storage and access control
   - Multiple file formats supported

5. **Action Logging**
   - All actions within the support system are logged
   - Provides an audit trail for administrative review

### Technical Implementation

- **Database Structure**: Dedicated tables for feedback, support tickets, disputes, responses, and attachments
- **Polymorphic Relationships**: Attachments and action logs use polymorphic relationships for flexibility
- **Authorization**: Comprehensive policy-based authorization system
- **Scheduled Responses**: Support for scheduling responses to be sent at specific times
- **File Management**: Secure file upload, storage, and retrieval system

### User Roles and Permissions

- **Regular Users**: Can submit feedback, create tickets, and file disputes
- **Staff**: Can be assigned tickets and respond to user inquiries
- **Administrators**: Have full access to manage all aspects of the support system