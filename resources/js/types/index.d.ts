import { LucideIcon } from 'lucide-react';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
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
    [key: string]: unknown;
}

export type StatusType = 'online' | 'away' | 'busy' | 'offline';

export interface User {
    id: number;
    name: string;
    email?: string | null;
    phone?: string | null;
    avatar?: string | null;
    location?: string | null;
    role?: 'super-admin' | 'teacher' | 'student' | 'guardian' | null;
    status_type?: string;
    status_message?: string | null;
    last_active_at?: string | null;
    email_verified_at?: string | null;
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

export interface GuardianProfile {
    id: number;
    user_id: number;
    relationship?: string | null;
    occupation?: string | null;
    emergency_contact?: string | null;
    secondary_phone?: string | null;
    preferred_contact_method?: string | null;
}

export type PageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    auth: {
        user: User;
    };
    appearance: {
        theme: string;
        radius: number;
    };
    sidebar_state: {
        open: boolean;
    };
};
