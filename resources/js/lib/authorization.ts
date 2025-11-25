import { User } from '@/types';
import { AxiosError } from 'axios';

export interface Conversation {
    id: number;
    type: 'direct' | 'group';
    participants: User[];
    context_type?: string | null;
    context_id?: number | null;
}

export interface Booking {
    id: number;
    student_id: number;
    teacher_id: number;
    status: 'pending' | 'approved' | 'completed' | 'cancelled';
}

export interface AuthorizationResult {
    allowed: boolean;
    reason?: string;
}

export interface AuthorizationError {
    type: 'auth' | 'permission' | 'other';
    message: string;
    code?: string;
    details?: Record<string, any>;
}

export class AuthorizationHelper {
    /**
     * Check if a user can message another user based on role-based rules.
     */
    static canMessageUser(
        currentUser: User,
        recipient: User,
        bookings?: Booking[]
    ): AuthorizationResult {
        // Super-admins can message anyone
        if (currentUser.role === 'super-admin') {
            return { allowed: true };
        }

        // Students can message teachers if they have an active or completed booking
        if (currentUser.role === 'student' && recipient.role === 'teacher') {
            const hasBooking = bookings?.some(
                (booking) =>
                    booking.student_id === currentUser.id &&
                    booking.teacher_id === recipient.id &&
                    (booking.status === 'approved' || booking.status === 'completed')
            );

            if (!hasBooking) {
                return {
                    allowed: false,
                    reason: 'no_active_booking',
                };
            }

            return { allowed: true };
        }

        // Teachers can message students if they have an active or completed booking
        if (currentUser.role === 'teacher' && recipient.role === 'student') {
            const hasBooking = bookings?.some(
                (booking) =>
                    booking.teacher_id === currentUser.id &&
                    booking.student_id === recipient.id &&
                    (booking.status === 'approved' || booking.status === 'completed')
            );

            if (!hasBooking) {
                return {
                    allowed: false,
                    reason: 'no_active_booking',
                };
            }

            return { allowed: true };
        }

        // Guardians can message teachers (if teacher teaches their child - checked on backend)
        if (currentUser.role === 'guardian' && recipient.role === 'teacher') {
            // Frontend can't verify this relationship, so we allow it and let backend validate
            return { allowed: true };
        }

        // Teachers can message guardians (if they teach the guardian's child - checked on backend)
        if (currentUser.role === 'teacher' && recipient.role === 'guardian') {
            // Frontend can't verify this relationship, so we allow it and let backend validate
            return { allowed: true };
        }

        // Default: not allowed
        return {
            allowed: false,
            reason: 'role_restriction',
        };
    }

    /**
     * Check if a user can access a conversation.
     */
    static canAccessConversation(
        currentUser: User,
        conversation: Conversation
    ): AuthorizationResult {
        // Check if user is a participant
        const isParticipant = conversation.participants.some(
            (participant) => participant.id === currentUser.id
        );

        if (!isParticipant) {
            return {
                allowed: false,
                reason: 'not_participant',
            };
        }

        return { allowed: true };
    }

    /**
     * Handle API errors and categorize them.
     */
    static handleApiError(error: AxiosError): AuthorizationError {
        if (!error.response) {
            return {
                type: 'other',
                message: 'Network error. Please check your connection.',
            };
        }

        const status = error.response.status;
        const data = error.response.data as any;

        // 401 Unauthorized
        if (status === 401) {
            return {
                type: 'auth',
                message: data?.message || 'You must be logged in to perform this action',
                code: data?.code || 'AUTH_REQUIRED',
            };
        }

        // 403 Forbidden
        if (status === 403) {
            const isRoleRestriction = data?.code === 'ROLE_RESTRICTION';

            return {
                type: 'permission',
                message: data?.message || 'You are not authorized to perform this action',
                code: data?.code || 'AUTHORIZATION_FAILED',
                details: data?.details,
            };
        }

        // Other errors
        return {
            type: 'other',
            message: data?.message || 'An error occurred. Please try again.',
            code: data?.code,
            details: data?.details,
        };
    }

    /**
     * Get a user-friendly error message for role violations.
     */
    static getRoleViolationMessage(
        currentUserRole: string,
        recipientRole: string,
        reason: string
    ): string {
        if (reason === 'no_active_booking') {
            if (currentUserRole === 'student' && recipientRole === 'teacher') {
                return 'You can only message teachers you have booked sessions with.';
            }
            if (currentUserRole === 'teacher' && recipientRole === 'student') {
                return 'You can only message students you have sessions with.';
            }
        }

        if (reason === 'teacher_not_teaching_child') {
            return 'You can only message teachers who teach your children.';
        }

        if (reason === 'not_teaching_guardians_child') {
            return "You can only message guardians of students you teach.";
        }

        return 'You are not authorized to message this user.';
    }
}
