import { toast } from 'sonner';

/**
 * Show error toast notification
 */
export function showError(message: string) {
    toast.error(message, {
        duration: 5000,
        position: 'top-right'
    });
}

/**
 * Show success toast notification
 */
export function showSuccess(message: string) {
    toast.success(message, {
        duration: 3000,
        position: 'top-right'
    });
}

/**
 * Show info toast notification
 */
export function showInfo(message: string) {
    toast.info(message, {
        duration: 4000,
        position: 'top-right'
    });
}

/**
 * Show warning toast notification
 */
export function showWarning(message: string) {
    toast.warning(message, {
        duration: 4000,
        position: 'top-right'
    });
}

/**
 * Show multiple validation errors
 */
export function showValidationErrors(errors: string[]) {
    if (errors.length === 1) {
        showError(errors[0]);
    } else {
        showError(`${errors.length} validation errors:\n${errors.slice(0, 3).join('\n')}${errors.length > 3 ? '\n...' : ''}`);
    }
}
