import React from 'react';
import { Button } from '@/components/ui/button';
import { X, AlertTriangle } from 'lucide-react';

interface ConfirmationModalProps {
    isOpen: boolean;
    onClose: () => void;
    onConfirm: () => void;
    title: string;
    message: string;
    confirmText?: string;
    cancelText?: string;
    variant?: 'danger' | 'warning' | 'info';
    isLoading?: boolean;
}

export default function ConfirmationModal({
    isOpen,
    onClose,
    onConfirm,
    title,
    message,
    confirmText = 'Confirm',
    cancelText = 'Cancel',
    variant = 'danger',
    isLoading = false
}: ConfirmationModalProps) {
    if (!isOpen) return null;

    const variantStyles = {
        danger: {
            icon: 'bg-red-100',
            iconColor: 'text-red-600',
            button: 'bg-red-600 hover:bg-red-700'
        },
        warning: {
            icon: 'bg-yellow-100',
            iconColor: 'text-yellow-600',
            button: 'bg-yellow-600 hover:bg-yellow-700'
        },
        info: {
            icon: 'bg-blue-100',
            iconColor: 'text-blue-600',
            button: 'bg-blue-600 hover:bg-blue-700'
        }
    };

    const styles = variantStyles[variant];

    return (
        <div className="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50">
            <div className="bg-white rounded-2xl p-6 w-full max-w-md mx-4 shadow-xl">
                {/* Header */}
                <div className="flex justify-between items-start mb-4">
                    <div className="flex items-start gap-3">
                        {/* Icon */}
                        <div className={`${styles.icon} rounded-full p-2`}>
                            <AlertTriangle className={`h-5 w-5 ${styles.iconColor}`} />
                        </div>
                        
                        {/* Title */}
                        <div>
                            <h2 className="text-lg font-bold text-gray-900">
                                {title}
                            </h2>
                        </div>
                    </div>
                    
                    {/* Close button */}
                    <button
                        onClick={onClose}
                        disabled={isLoading}
                        className="text-gray-400 hover:text-gray-600 transition-colors disabled:opacity-50"
                    >
                        <X className="h-5 w-5" />
                    </button>
                </div>

                {/* Message */}
                <div className="mb-6 ml-11">
                    <p className="text-gray-600 text-sm">
                        {message}
                    </p>
                </div>

                {/* Actions */}
                <div className="flex justify-end gap-3">
                    <Button
                        onClick={onClose}
                        disabled={isLoading}
                        variant="outline"
                        className="px-4 py-2"
                    >
                        {cancelText}
                    </Button>
                    
                    <Button
                        onClick={onConfirm}
                        disabled={isLoading}
                        className={`${styles.button} text-white px-4 py-2`}
                    >
                        {isLoading ? (
                            <div className="flex items-center gap-2">
                                <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                                <span>Processing...</span>
                            </div>
                        ) : (
                            confirmText
                        )}
                    </Button>
                </div>
            </div>
        </div>
    );
}
