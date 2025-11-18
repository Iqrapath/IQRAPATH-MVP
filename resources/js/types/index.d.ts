import { LucideIcon } from 'lucide-react';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
    className?: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
    flash?: {
        success?: string;
        error?: string;
        message?: string;
    };
    [key: string]: unknown;
}

export type StatusType = 'online' | 'away' | 'busy' | 'offline';

export interface Wallet {
    id: number;
    user_id: number;
    payment_id: string;
    balance: number;
    total_spent: number;
    total_refunded: number;
    default_payment_method_id?: number | null;
    auto_renew_enabled: boolean;
    created_at: string;
    updated_at: string;
}

export interface User {
    id: number;
    name: string;
    email?: string | null;
    phone?: string | null;
    avatar?: string | null;
    location?: string | null;
    role?: 'super-admin' | 'teacher' | 'student' | 'guardian' | null;
    status_type?: StatusType | string;
    status_message?: string | null;
    last_active_at?: string | null;
    email_verified_at?: string | null;
    provider?: 'google' | 'facebook' | null;
    provider_id?: string | null;
    teacherProfile?: TeacherProfile;
    wallet_balance?: number | null;
    wallet?: Wallet;
    guardianWallet?: GuardianWallet;
}

export interface Notification {
    id: string;
    type: string;
    notifiable_type: string;
    notifiable_id: number;
    data: {
        title: string;
        message: string;
        action_text?: string;
        action_url?: string;
        image_url?: string;
        sender_id?: number;
        sender_name?: string;
        sender_avatar?: string;
        // New user registration specific fields
        new_user_id?: number;
        new_user_name?: string;
        new_user_email?: string;
        new_user_phone?: string;
        registration_time?: string;
        // Rejection notification specific fields
        rejection_reason?: string;
        resubmission_instructions?: string;
        remaining_attempts?: number;
        support_contact?: string;
    };
    read_at: string | null;
    created_at: string;
    level?: 'info' | 'success' | 'warning' | 'error';
    channel?: 'database' | 'mail' | 'broadcast';
}

export interface Message {
    id: number;
    sender_id: number;
    recipient_id: number;
    content: string;
    read_at: string | null;
    created_at: string;
    updated_at: string;
    sender?: User;
    recipient?: User;
    attachments?: any[];
}

export interface AdminProfile {
    id: number;
    user_id: number;
    department?: string | null;
    admin_level?: string | null;
    permissions?: Record<string, any> | null;
    bio?: string | null;
}

export interface TeacherProfile {
    id: number;
    user_id: number;
    specialization?: string | null;
    qualifications?: string | null;
    bio?: string | null;
    teaching_level?: string | null;
    subjects?: string[] | null;
    experience_years?: string | null;
    availability?: string | null;
    hourly_rate?: number | null;
    verified?: boolean;
    qualification?: string | null;
    languages?: string | null;
    teaching_type?: string | null;
    teaching_mode?: string | null;
    hourly_rate_usd?: number | null;
    hourly_rate_ngn?: number | null;
    join_date?: string | null;
}

export interface StudentProfile {
    id: number;
    user_id: number;
    date_of_birth?: string | null;
    grade_level?: string | null;
    school_name?: string | null;
    guardian_id?: number | null;
    learning_goals?: string | null;
    subjects_of_interest?: string[] | null;
    special_needs?: string | null;
}

export interface StudentStats {
    totalSessions: number;
    completedSessions: number;
    upcomingSessions: number;
}

export interface UpcomingSession {
    id: number;
    session_uuid: string;
    title: string;
    teacher: string;
    teacher_avatar?: string;
    subject: string;
    date: string;
    time: string;
    duration?: number;
    status: 'Confirmed' | 'Pending' | 'Completed' | 'Scheduled' | 'Approved' | 'Upcoming';
    meeting_link?: string;
    meetingUrl?: string; // Legacy field for backward compatibility
    completion_date?: string;
    progress?: number;
    rating?: number;
    imageUrl?: string;
}

export interface BookingData {
    id: number;
    booking_uuid: string;
    title: string;
    teacher: string | {
        id: number;
        name: string;
    };
    teacher_id: number;
    teacher_avatar: string;
    subject: string | {
        name: string;
    };
    date: string;
    time: string;
    status: 'Pending' | 'Approved' | 'Confirmed' | 'Completed' | 'Cancelled' | 'upcoming' | 'ongoing';
    imageUrl: string;
    meetingUrl?: string;
    session_uuid?: string;
    can_join: boolean;
    can_reschedule: boolean;
    can_cancel: boolean;
    // Additional properties for completed classes
    user_rating?: number;
    materials_url?: string;
    has_materials?: boolean;
    teacher_rated?: boolean;
    // Database fields
    booking_date?: string;
    start_time?: string;
    end_time?: string;
    duration_minutes?: number;
    teacher_notes?: string;
    student_notes?: string;
    user_feedback?: string;
    // TeachingSession relationship
    teachingSession?: {
        id: number;
        teacher_notes?: string;
        student_notes?: string;
        student_rating?: number;
        teacher_rating?: number;
        meeting_platform?: string;
        recording_url?: string;
        completion_date?: string;
        zoom_join_url?: string;
        google_meet_link?: string;
        // Additional data from relationships
        student_review?: string;
        booking_notes?: {
            teacher_note?: string;
            student_note?: string;
            student_review?: string;
        };
    };
}

export interface BookingsPageProps {
    bookings: {
        upcoming: BookingData[];
        ongoing: BookingData[];
        completed: BookingData[];
    };
    stats: {
        total: number;
        upcoming: number;
        ongoing: number;
        completed: number;
    };
}

export interface ClassDetailsBookingData extends BookingData {
    duration: number;
    notes?: string;
}

export interface TeacherDetailsData {
    id: number;
    name: string;
    avatar: string;
    specialization: string;
    location: string;
    rating: number;
    availability: string;
    bio: string;
    experience_years: number;
    subjects: string[];
    reviews_count: number;
    hourly_rate_ngn?: number;
    hourly_rate_usd?: number;
    verified?: boolean;
}

export interface ClassDetailsPageProps {
    booking: ClassDetailsBookingData;
    teacher: TeacherDetailsData;
}

export interface RecommendedTeacher {
    id: number;
    name: string;
    subjects: string;
    location: string;
    rating: number;
    price: string;
    avatarUrl: string;
}

export interface GuardianProfile {
    id: number;
    user_id: number;
    relationship?: string | null;
    occupation?: string | null;
    emergency_contact?: string | null;
    secondary_phone?: string | null;
    preferred_contact_method?: string | null;
}

export interface GuardianWallet {
    id: number;
    user_id: number;
    balance: number;
    total_spent_on_children: number;
    total_refunded: number;
    default_payment_method_id?: number | null;
    auto_fund_children: boolean;
    auto_fund_threshold: number;
    family_spending_limits?: any;
    child_allowances?: any;
    created_at: string;
    updated_at: string;
}

export interface SubscriptionPlan {
    id: number;
    name: string;
    description: string;
    price_naira: number;
    price_dollar: number;
    billing_cycle: 'monthly' | 'annual';
    duration_months: number;
    features: string[];
    tags: string[];
    image_path?: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface Subscription {
    id: number;
    subscription_uuid: string;
    user_id: number;
    subscription_plan_id: number;
    start_date: string;
    end_date: string;
    amount_paid: number;
    currency: 'USD' | 'NGN';
    status: 'pending' | 'active' | 'expired' | 'cancelled';
    next_billing_date?: string | null;
    auto_renew: boolean;
    payment_method?: string | null;
    payment_reference?: string | null;
    created_at: string;
    updated_at: string;
    plan?: SubscriptionPlan;
    days_remaining?: number;
}

export type PageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
  auth: {
    user: User;
  };
  appearance?: {
    theme: string;
    radius: number;
  };
  sidebar_state?: {
    open: boolean;
  };
  flash?: {
    message?: string;
    error?: string;
    success?: string;
  };
  errors?: Record<string, string>;
  ziggy?: {
    location: string;
    url: string;
    port: number | null;
    defaults: Record<string, string>;
    routes: Record<string, any>;
  };
};
