/**
 * 🎨 FIGMA REFERENCE
 * URL: https://www.figma.com/design/jmWnnfdCipxqiQF39Tdb0S/IQRAQUEST?node-id=542-68353&t=O1w7ozri9pYud8IO-0
 * Add Payment Method Modal
 * 
 * EXACT SPECS FROM FIGMA:
 * - Add Payment Method title
 * - Payment method selection (Bank Transfer, Mobile Money)
 * - Form fields for bank details
 * - Save Payment Method button
 */

import React, { useState } from 'react';
import { X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

interface AddPaymentMethodModalProps {
    isOpen: boolean;
    onClose: () => void;
    onSuccess: (paymentMethod: any) => void;
}

export default function AddPaymentMethodModal({
    isOpen,
    onClose,
    onSuccess
}: AddPaymentMethodModalProps) {
    const [selectedType, setSelectedType] = useState<'bank_transfer' | 'mobile_money'>('bank_transfer');
    const [isLoading, setIsLoading] = useState(false);
    const [formData, setFormData] = useState({
        name: '',
        bank_name: '',
        account_holder: '',
        account_number: '',
        swift_code: '',
        phone_number: '',
        provider: '',
        notes: ''
    });

    if (!isOpen) return null;

    const handleInputChange = (field: string, value: string) => {
        setFormData(prev => ({
            ...prev,
            [field]: value
        }));
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setIsLoading(true);

        try {
            let details: any = {};
            
            if (selectedType === 'bank_transfer') {
                details = {
                    bank_name: formData.bank_name,
                    account_holder: formData.account_holder,
                    account_number: formData.account_number,
                    swift_code: formData.swift_code,
                    notes: formData.notes
                };
            } else if (selectedType === 'mobile_money') {
                details = {
                    provider: formData.provider,
                    phone_number: formData.phone_number,
                    notes: formData.notes
                };
            }

            const response = await window.axios.post('/student/payment-methods', {
                type: selectedType,
                name: formData.name,
                details: details,
                is_default: false
            });

            if (response.data.success) {
                // Reset form
                setFormData({
                    name: '',
                    bank_name: '',
                    account_holder: '',
                    account_number: '',
                    swift_code: '',
                    phone_number: '',
                    provider: '',
                    notes: ''
                });
                onClose();
                onSuccess(response.data.payment_method);
            } else {
                throw new Error(response.data.message || 'Failed to add payment method');
            }
        } catch (error: any) {
            console.error('Error:', error);
            
            // Handle validation errors
            if (error.response?.data?.errors) {
                const errorMessages = Object.values(error.response.data.errors).flat().join('\n');
                alert('Validation errors:\n' + errorMessages);
            } else {
                alert(error.response?.data?.message || 'Failed to add payment method. Please try again.');
            }
        } finally {
            setIsLoading(false);
        }
    };

    return (
        <div className="fixed inset-0 bg-black/30 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div className="bg-white rounded-3xl p-6 max-w-lg w-full shadow-2xl max-h-[90vh] overflow-y-auto">
                {/* Header */}
                <div className="flex items-center justify-between mb-6">
                    <h2 className="text-xl font-semibold text-gray-900">
                        Add Payment Method
                    </h2>
                    <button
                        onClick={onClose}
                        className="p-1 hover:bg-gray-100 rounded-lg transition-colors"
                    >
                        <X className="w-5 h-5 text-gray-500" />
                    </button>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Payment Method Name */}
                    <div className="space-y-2">
                        <Label htmlFor="name">Payment Method Name</Label>
                        <Input
                            id="name"
                            value={formData.name}
                            onChange={(e) => handleInputChange('name', e.target.value)}
                            placeholder="e.g., My Primary Bank"
                            required
                        />
                    </div>

                    {/* Payment Method Type Selection */}
                    <div className="space-y-3">
                        <Label>Payment Method Type</Label>
                        <div className="grid grid-cols-2 gap-3">
                            <button
                                type="button"
                                onClick={() => setSelectedType('bank_transfer')}
                                className={`p-4 rounded-lg border-2 transition-colors ${
                                    selectedType === 'bank_transfer'
                                        ? 'border-teal-600 bg-teal-50'
                                        : 'border-gray-200 hover:border-gray-300'
                                }`}
                            >
                                <div className="text-center">
                                    <div className="w-8 h-8 mx-auto mb-2 bg-teal-600 rounded-full flex items-center justify-center">
                                        <div className="w-2 h-2 bg-white rounded-full"></div>
                                    </div>
                                    <p className="font-medium text-gray-900">Bank Transfer</p>
                                </div>
                            </button>

                            <button
                                type="button"
                                onClick={() => setSelectedType('mobile_money')}
                                className={`p-4 rounded-lg border-2 transition-colors ${
                                    selectedType === 'mobile_money'
                                        ? 'border-teal-600 bg-teal-50'
                                        : 'border-gray-200 hover:border-gray-300'
                                }`}
                            >
                                <div className="text-center">
                                    <div className="w-8 h-8 mx-auto mb-2 bg-orange-500 rounded-full flex items-center justify-center">
                                        <div className="w-2 h-2 bg-white rounded-full"></div>
                                    </div>
                                    <p className="font-medium text-gray-900">Mobile Money</p>
                                </div>
                            </button>
                        </div>
                    </div>

                    {/* Bank Transfer Fields */}
                    {selectedType === 'bank_transfer' && (
                        <div className="space-y-4">
                            <div className="grid grid-cols-1 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="bank_name">Bank Name</Label>
                                    <Input
                                        id="bank_name"
                                        value={formData.bank_name}
                                        onChange={(e) => handleInputChange('bank_name', e.target.value)}
                                        placeholder="e.g., First City Monument Bank"
                                        required
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="account_holder">Account Holder Name</Label>
                                    <Input
                                        id="account_holder"
                                        value={formData.account_holder}
                                        onChange={(e) => handleInputChange('account_holder', e.target.value)}
                                        placeholder="Full name as on bank account"
                                        required
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="account_number">Account Number</Label>
                                    <Input
                                        id="account_number"
                                        value={formData.account_number}
                                        onChange={(e) => handleInputChange('account_number', e.target.value)}
                                        placeholder="10-digit account number"
                                        required
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="swift_code">SWIFT/Sort Code (Optional)</Label>
                                    <Input
                                        id="swift_code"
                                        value={formData.swift_code}
                                        onChange={(e) => handleInputChange('swift_code', e.target.value)}
                                        placeholder="Bank code for international transfers"
                                    />
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Mobile Money Fields */}
                    {selectedType === 'mobile_money' && (
                        <div className="space-y-4">
                            <div className="grid grid-cols-1 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="provider">Provider</Label>
                                    <Input
                                        id="provider"
                                        value={formData.provider}
                                        onChange={(e) => handleInputChange('provider', e.target.value)}
                                        placeholder="e.g., MTN, Airtel, 9mobile"
                                        required
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="phone_number">Phone Number</Label>
                                    <Input
                                        id="phone_number"
                                        value={formData.phone_number}
                                        onChange={(e) => handleInputChange('phone_number', e.target.value)}
                                        placeholder="e.g., +234 802 123 4567"
                                        required
                                    />
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Notes */}
                    <div className="space-y-2">
                        <Label htmlFor="notes">Notes (Optional)</Label>
                        <Textarea
                            id="notes"
                            value={formData.notes}
                            onChange={(e) => handleInputChange('notes', e.target.value)}
                            placeholder="Any additional information about this payment method"
                            rows={3}
                        />
                    </div>

                    {/* Submit Button */}
                    <div className="flex justify-end space-x-3">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={onClose}
                            disabled={isLoading}
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            disabled={isLoading}
                            className="bg-teal-600 hover:bg-teal-700 text-white"
                        >
                            {isLoading ? 'Adding...' : 'Add Payment Method'}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    );
}
