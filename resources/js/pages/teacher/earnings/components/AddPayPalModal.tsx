import React, { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { X, Loader2 } from 'lucide-react';
import { PaypalIcon } from '@/components/icons/paypal-icon';
import { toast } from 'sonner';
import axios from 'axios';

interface AddPayPalModalProps {
    isOpen: boolean;
    onClose: () => void;
    onSuccess: () => void;
}

export default function AddPayPalModal({
    isOpen,
    onClose,
    onSuccess
}: AddPayPalModalProps) {
    const [step, setStep] = useState<'select' | 'form'>('select');
    const [loading, setLoading] = useState(false);
    const [formData, setFormData] = useState({
        name: '',
        paypal_email: ''
    });
    const [errors, setErrors] = useState({
        name: '',
        paypal_email: ''
    });

    if (!isOpen) return null;

    const validateEmail = (email: string): boolean => {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    };

    const handlePayPalSelect = () => {
        setStep('form');
    };

    const handleInputChange = (field: 'name' | 'paypal_email', value: string) => {
        setFormData(prev => ({ ...prev, [field]: value }));
        // Clear error when user types
        setErrors(prev => ({ ...prev, [field]: '' }));
    };

    const validateForm = (): boolean => {
        const newErrors = { name: '', paypal_email: '' };
        let isValid = true;

        if (!formData.name.trim()) {
            newErrors.name = 'Account holder name is required';
            isValid = false;
        }

        if (!formData.paypal_email.trim()) {
            newErrors.paypal_email = 'PayPal email is required';
            isValid = false;
        } else if (!validateEmail(formData.paypal_email)) {
            newErrors.paypal_email = 'Please enter a valid email address';
            isValid = false;
        }

        setErrors(newErrors);
        return isValid;
    };

    const handleSubmit = async () => {
        if (!validateForm()) {
            return;
        }

        setLoading(true);
        try {
            const response = await axios.post('/teacher/payment-methods', {
                type: 'paypal',
                name: formData.name,
                metadata: {
                    paypal_email: formData.paypal_email
                }
            }, {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                }
            });

            if (response.data.success) {
                toast.success('PayPal account added successfully!', {
                    description: 'Your PayPal account is pending verification',
                    duration: 4000,
                });

                // Reset form
                setFormData({ name: '', paypal_email: '' });
                setStep('select');
                onSuccess();
                onClose();
            }
        } catch (error: any) {
            console.error('Error adding PayPal account:', error);
            const errorMessage = error.response?.data?.message || 'Failed to add PayPal account';
            toast.error('Failed to add PayPal account', {
                description: errorMessage,
                duration: 5000,
            });
        } finally {
            setLoading(false);
        }
    };

    const handleCancel = () => {
        setFormData({ name: '', paypal_email: '' });
        setErrors({ name: '', paypal_email: '' });
        setStep('select');
        onClose();
    };

    return (
        <div className="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50">
            <div className="bg-white rounded-2xl p-6 w-full max-w-md mx-4 border-2">
                {/* Header */}
                <div className="flex justify-between items-start mb-6">
                    <div>
                        <h2 className="text-xl font-bold text-gray-900 mb-1">
                            {step === 'select' ? 'Add Payment Method' : 'Add PayPal Account'}
                        </h2>
                        {step === 'select' && (
                            <p className="text-gray-500 text-sm">
                                Easily withdraw your earnings to your PayPal account
                            </p>
                        )}
                    </div>
                    <button
                        onClick={handleCancel}
                        className="text-gray-500 hover:text-gray-700 transition-colors"
                        disabled={loading}
                    >
                        <X className="h-5 w-5" />
                    </button>
                </div>

                {/* Step 1: PayPal Selection */}
                {step === 'select' && (
                    <>
                        <div className="space-y-4">
                            <div
                                onClick={handlePayPalSelect}
                                className="border border-gray-200 rounded-lg p-4 hover:border-gray-300 transition-colors cursor-pointer"
                            >
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center space-x-4">
                                        <PaypalIcon className="h-6 w-6 text-[#0070ba]" />
                                        <span className="text-[#0070ba] font-semibold text-lg">PayPal</span>
                                    </div>

                                    <div className="w-5 h-5 border-2 rounded-full flex items-center justify-center border-gray-300">
                                        <div className="w-2 h-2 bg-gray-300 rounded-full"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="flex justify-end space-x-3 mt-6">
                            <Button
                                onClick={handleCancel}
                                variant="outline"
                                className="px-6 py-2"
                            >
                                Cancel
                            </Button>
                            <Button
                                onClick={handlePayPalSelect}
                                className="bg-[#338078] hover:bg-[#338078]/80 text-white px-6 py-2"
                            >
                                Select PayPal
                            </Button>
                        </div>
                    </>
                )}

                {/* Step 2: PayPal Form */}
                {step === 'form' && (
                    <>
                        <div className="space-y-4">
                            {/* PayPal Icon */}
                            <div className="flex items-center justify-center mb-4">
                                <PaypalIcon className="h-12 w-12 text-[#0070ba]" />
                            </div>

                            <p className="text-gray-500 text-sm text-center mb-6">
                                Enter your PayPal account details to receive payments
                            </p>

                            {/* Account Holder Name */}
                            <div className="space-y-2">
                                <Label htmlFor="name">Account Holder Name</Label>
                                <Input
                                    id="name"
                                    type="text"
                                    placeholder="John Doe"
                                    value={formData.name}
                                    onChange={(e) => handleInputChange('name', e.target.value)}
                                    className={errors.name ? 'border-red-500' : ''}
                                    disabled={loading}
                                />
                                {errors.name && (
                                    <p className="text-sm text-red-500">{errors.name}</p>
                                )}
                            </div>

                            {/* PayPal Email */}
                            <div className="space-y-2">
                                <Label htmlFor="paypal_email">PayPal Email Address</Label>
                                <Input
                                    id="paypal_email"
                                    type="email"
                                    placeholder="john@example.com"
                                    value={formData.paypal_email}
                                    onChange={(e) => handleInputChange('paypal_email', e.target.value)}
                                    className={errors.paypal_email ? 'border-red-500' : ''}
                                    disabled={loading}
                                />
                                {errors.paypal_email && (
                                    <p className="text-sm text-red-500">{errors.paypal_email}</p>
                                )}
                                <p className="text-xs text-gray-500">
                                    This must be the email address associated with your PayPal account
                                </p>
                            </div>
                        </div>

                        <div className="flex justify-between items-center mt-6">
                            <Button
                                onClick={handleCancel}
                                variant="outline"
                                disabled={loading}
                            >
                                Cancel
                            </Button>
                            <Button
                                onClick={handleSubmit}
                                className="bg-[#338078] hover:bg-[#338078]/80 text-white px-6 py-2"
                                disabled={loading}
                            >
                                {loading ? (
                                    <>
                                        <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                                        Adding...
                                    </>
                                ) : (
                                    'Add PayPal Account'
                                )}
                            </Button>
                        </div>
                    </>
                )}
            </div>
        </div>
    );
}
