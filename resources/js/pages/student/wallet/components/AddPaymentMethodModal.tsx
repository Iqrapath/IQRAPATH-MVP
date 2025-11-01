import React, { useState } from 'react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { toast } from 'sonner';
import axios from 'axios';

interface AddPaymentMethodModalProps {
    isOpen: boolean;
    onClose: () => void;
    onSuccess: () => void;
}

export default function AddPaymentMethodModal({
    isOpen,
    onClose,
    onSuccess,
}: AddPaymentMethodModalProps) {
    const [paymentType, setPaymentType] = useState<'bank_transfer' | 'mobile_money'>('bank_transfer');
    const [isSubmitting, setIsSubmitting] = useState(false);

    // Bank Transfer fields
    const [bankName, setBankName] = useState('');
    const [accountHolder, setAccountHolder] = useState('');
    const [accountNumber, setAccountNumber] = useState('');

    // Mobile Money fields
    const [provider, setProvider] = useState('');
    const [phoneNumber, setPhoneNumber] = useState('');

    const nigerianBanks = [
        'Access Bank',
        'Citibank',
        'Ecobank',
        'Fidelity Bank',
        'First Bank',
        'First City Monument Bank (FCMB)',
        'Guaranty Trust Bank (GTBank)',
        'Heritage Bank',
        'Keystone Bank',
        'Polaris Bank',
        'Providus Bank',
        'Stanbic IBTC Bank',
        'Standard Chartered Bank',
        'Sterling Bank',
        'Union Bank',
        'United Bank for Africa (UBA)',
        'Unity Bank',
        'Wema Bank',
        'Zenith Bank',
    ];

    const mobileProviders = ['MTN', 'Airtel', 'Glo', '9mobile'];

    const handleSubmit = async () => {
        // Validation
        if (paymentType === 'bank_transfer') {
            if (!bankName || !accountHolder || !accountNumber) {
                toast.error('Please fill in all bank transfer fields');
                return;
            }
            if (accountNumber.length !== 10) {
                toast.error('Account number must be 10 digits');
                return;
            }
        } else {
            if (!provider || !phoneNumber) {
                toast.error('Please fill in all mobile money fields');
                return;
            }
            if (phoneNumber.length !== 11) {
                toast.error('Phone number must be 11 digits');
                return;
            }
        }

        setIsSubmitting(true);

        try {
            const payload = {
                type: paymentType,
                name: paymentType === 'bank_transfer' 
                    ? `${bankName} - ${accountNumber.slice(-4)}`
                    : `${provider} - ${phoneNumber.slice(-4)}`,
                details: paymentType === 'bank_transfer'
                    ? {
                        bank_name: bankName,
                        account_holder: accountHolder,
                        account_number: accountNumber,
                    }
                    : {
                        provider: provider,
                        phone_number: phoneNumber,
                    },
                is_default: false,
            };

            await axios.post('/student/wallet/payment-methods', payload);

            toast.success('Payment method added successfully!');
            onSuccess();
            onClose();
        } catch (error: any) {
            console.error('Error adding payment method:', error);
            const errorMessage = error.response?.data?.message || 'Failed to add payment method';
            toast.error(errorMessage);
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="sm:max-w-[500px]">
                <DialogHeader>
                    <DialogTitle className="text-xl font-bold">Add Payment Method</DialogTitle>
                    <DialogDescription>
                        Add a new payment method for wallet top-ups
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-4 py-4">
                    {/* Payment Type Selection */}
                    <div className="space-y-2">
                        <Label>Payment Type</Label>
                        <Select value={paymentType} onValueChange={(value: 'bank_transfer' | 'mobile_money') => setPaymentType(value)}>
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="bank_transfer">🏦 Bank Transfer</SelectItem>
                                <SelectItem value="mobile_money">📱 Mobile Money</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    {/* Bank Transfer Fields */}
                    {paymentType === 'bank_transfer' && (
                        <>
                            <div className="space-y-2">
                                <Label htmlFor="bankName">Bank Name</Label>
                                <Select value={bankName} onValueChange={setBankName}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select your bank" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {nigerianBanks.map((bank) => (
                                            <SelectItem key={bank} value={bank}>
                                                {bank}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="accountHolder">Account Holder Name</Label>
                                <Input
                                    id="accountHolder"
                                    placeholder="Enter account holder name"
                                    value={accountHolder}
                                    onChange={(e) => setAccountHolder(e.target.value)}
                                />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="accountNumber">Account Number</Label>
                                <Input
                                    id="accountNumber"
                                    type="text"
                                    placeholder="Enter 10-digit account number"
                                    value={accountNumber}
                                    onChange={(e) => {
                                        const value = e.target.value.replace(/\D/g, '').slice(0, 10);
                                        setAccountNumber(value);
                                    }}
                                    maxLength={10}
                                />
                                <p className="text-xs text-gray-500">Must be 10 digits</p>
                            </div>
                        </>
                    )}

                    {/* Mobile Money Fields */}
                    {paymentType === 'mobile_money' && (
                        <>
                            <div className="space-y-2">
                                <Label htmlFor="provider">Mobile Provider</Label>
                                <Select value={provider} onValueChange={setProvider}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select provider" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {mobileProviders.map((prov) => (
                                            <SelectItem key={prov} value={prov}>
                                                {prov}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="phoneNumber">Phone Number</Label>
                                <Input
                                    id="phoneNumber"
                                    type="tel"
                                    placeholder="Enter 11-digit phone number"
                                    value={phoneNumber}
                                    onChange={(e) => {
                                        const value = e.target.value.replace(/\D/g, '').slice(0, 11);
                                        setPhoneNumber(value);
                                    }}
                                    maxLength={11}
                                />
                                <p className="text-xs text-gray-500">Format: 08012345678</p>
                            </div>
                        </>
                    )}

                    <div className="p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                        <p className="text-sm text-yellow-900">
                            <strong>Note:</strong> Your payment method will be verified before it can be used for transactions.
                        </p>
                    </div>
                </div>

                <DialogFooter>
                    <Button
                        variant="outline"
                        onClick={onClose}
                        disabled={isSubmitting}
                    >
                        Cancel
                    </Button>
                    <Button
                        onClick={handleSubmit}
                        disabled={isSubmitting}
                        className="bg-[#2C7870] hover:bg-[#235f59] text-white"
                    >
                        {isSubmitting ? 'Adding...' : 'Add Payment Method'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
