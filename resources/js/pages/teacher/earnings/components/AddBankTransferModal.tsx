import React, { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { X, Building2, Hash } from 'lucide-react';
import { router } from '@inertiajs/react';
import { toast } from 'sonner';

interface AddBankTransferModalProps {
    isOpen: boolean;
    onClose: () => void;
    onSuccess: () => void;
}

interface BankDetails {
    accountName: string;
    bankName: string;
    accountNumber: string;
}

interface Bank {
    id: number;
    name: string;
    code: string;
    slug: string | null;
    country: string;
}

export default function AddBankTransferModal({
    isOpen,
    onClose,
    onSuccess
}: AddBankTransferModalProps) {
    const [formData, setFormData] = useState<BankDetails>({
        accountName: '',
        bankName: '',
        accountNumber: ''
    });
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [errors, setErrors] = useState<Partial<BankDetails>>({});
    const [showBankSuggestions, setShowBankSuggestions] = useState(false);
    const [banks, setBanks] = useState<Bank[]>([]);
    const [filteredBanks, setFilteredBanks] = useState<Bank[]>([]);
    const [isLoadingBanks, setIsLoadingBanks] = useState(false);

    // Fetch banks from API
    useEffect(() => {
        if (isOpen && banks.length === 0) {
            fetchBanks();
        }
    }, [isOpen]);

    const fetchBanks = async () => {
        try {
            setIsLoadingBanks(true);
            const response = await fetch('/teacher/banks', {
                headers: {
                    'Accept': 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error('Failed to fetch banks');
            }

            const data = await response.json();
            console.log('Fetched banks:', data.length, 'banks');
            setBanks(data);
        } catch (error) {
            console.error('Error fetching banks:', error);
            toast.error('Failed to load banks. Please try again.');
        } finally {
            setIsLoadingBanks(false);
        }
    };

    if (!isOpen) return null;

    const handleInputChange = (field: keyof BankDetails, value: string) => {
        setFormData(prev => ({ ...prev, [field]: value }));
        // Clear error when user starts typing
        if (errors[field]) {
            setErrors(prev => ({ ...prev, [field]: undefined }));
        }

        // Handle bank name search
        if (field === 'bankName') {
            if (value.trim()) {
                const filtered = banks.filter(bank =>
                    bank.name.toLowerCase().includes(value.toLowerCase())
                );
                setFilteredBanks(filtered);
                setShowBankSuggestions(true);
            } else {
                // Show all banks when input is empty
                setFilteredBanks(banks);
                setShowBankSuggestions(true);
            }
        }
    };

    const handleBankFocus = () => {
        // Show all banks when field is focused
        if (banks.length > 0) {
            if (formData.bankName.trim()) {
                const filtered = banks.filter(bank =>
                    bank.name.toLowerCase().includes(formData.bankName.toLowerCase())
                );
                setFilteredBanks(filtered);
            } else {
                setFilteredBanks(banks);
            }
            setShowBankSuggestions(true);
        }
    };

    const handleBankSelect = (bank: Bank) => {
        console.log('Bank selected:', bank.name);
        setFormData(prev => ({ ...prev, bankName: bank.name }));
        setShowBankSuggestions(false);
        setFilteredBanks([]);
        // Clear any error
        if (errors.bankName) {
            setErrors(prev => ({ ...prev, bankName: undefined }));
        }
    };

    const handleBankBlur = () => {
        // Delay hiding to allow click on dropdown items
        setTimeout(() => {
            setShowBankSuggestions(false);
        }, 300);
    };

    const validateForm = (): boolean => {
        const newErrors: Partial<BankDetails> = {};

        if (!formData.accountName.trim()) {
            newErrors.accountName = 'Account name is required';
        }
        if (!formData.bankName.trim()) {
            newErrors.bankName = 'Bank name is required';
        }
        if (!formData.accountNumber.trim()) {
            newErrors.accountNumber = 'Account number is required';
        } else if (!/^\d{10}$/.test(formData.accountNumber)) {
            newErrors.accountNumber = 'Account number must be 10 digits';
        }

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();

        if (!validateForm()) return;

        // Find the selected bank to get its code
        const selectedBank = banks.find(bank => bank.name === formData.bankName);

        if (!selectedBank) {
            setErrors(prev => ({ ...prev, bankName: 'Please select a valid bank from the list' }));
            return;
        }

        setIsSubmitting(true);
        
        // Show loading toast
        const loadingToast = toast.loading('Verifying bank account...');

        try {
            // Prepare data for API
            const paymentMethodData = {
                type: 'bank_transfer',
                name: `${selectedBank.name} - ${formData.accountNumber.slice(-4)}`,
                bank_code: selectedBank.code,
                bank_name: selectedBank.name,
                account_number: formData.accountNumber,
                account_name: formData.accountName,
                currency: 'NGN'
            };

            // Submit using Inertia
            router.post('/teacher/payment-methods', paymentMethodData, {
                preserveScroll: true,
                onSuccess: () => {
                    toast.dismiss(loadingToast);
                    toast.success('Bank account added successfully');
                    handleClose();
                    onSuccess();
                },
                onError: (errors) => {
                    toast.dismiss(loadingToast);
                    console.error('Validation errors:', errors);

                    // Map backend errors to form errors
                    const formErrors: Partial<BankDetails> = {};
                    if (errors.account_number) {
                        formErrors.accountNumber = errors.account_number as string;
                    }
                    if (errors.bank_code || errors.bank_name) {
                        formErrors.bankName = (errors.bank_code || errors.bank_name) as string;
                    }
                    if (errors.account_name) {
                        formErrors.accountName = errors.account_name as string;
                    }

                    setErrors(formErrors);

                    // Show generic error toast if no specific field errors
                    if (Object.keys(formErrors).length === 0) {
                        toast.error(errors.error as string || 'Failed to add bank account. Please try again.');
                    } else {
                        toast.error('Please check the form for errors');
                    }
                },
                onFinish: () => {
                    toast.dismiss(loadingToast);
                    setIsSubmitting(false);
                }
            });
        } catch (error) {
            toast.dismiss(loadingToast);
            console.error('Error saving bank details:', error);
            toast.error('An unexpected error occurred. Please try again.');
            setIsSubmitting(false);
        }
    };

    const handleClose = () => {
        if (!isSubmitting) {
            setFormData({
                accountName: '',
                bankName: '',
                accountNumber: ''
            });
            setErrors({});
            setShowBankSuggestions(false);
            onClose();
        }
    };

    return (
        <div className="fixed inset-0 bg-transparent backdrop-blur-sm flex items-center justify-center z-50">
            <div className="bg-white rounded-4xl p-6 w-full max-w-xl mx-4">
                {/* Header */}
                <div className="flex justify-between items-start mb-6">
                    <div>
                        <h2 className="text-xl font-bold text-gray-900 mb-1">
                            Add your Bank Deatils
                        </h2>
                        <p className="text-gray-600 text-sm">
                            Easily transfer your Earning balance to your bank account
                        </p>
                    </div>
                    <button
                        onClick={handleClose}
                        disabled={isSubmitting}
                        className="text-gray-500 hover:text-gray-700 transition-colors disabled:opacity-50"
                    >
                        <X className="h-5 w-5" />
                    </button>
                </div>

                {/* Form */}
                <form onSubmit={handleSubmit} className="space-y-4">
                    {/* Account Name */}
                    <div className="relative w-[70%]">
                        <Label htmlFor="accountName" className="text-sm font-medium text-gray-700">
                            Account Name
                        </Label>
                        <Input
                            id="accountName"
                            type="text"
                            value={formData.accountName}
                            onChange={(e) => handleInputChange('accountName', e.target.value)}
                            placeholder="Enter Account Name"
                            className="mt-1 bg-gray-100 border-gray-200"
                        />
                        {errors.accountName && (
                            <p className="text-red-500 text-xs mt-1">{errors.accountName}</p>
                        )}
                    </div>

                    {/* Bank Name */}
                    <div className="relative w-[70%]">
                        <Label htmlFor="bankName" className="text-sm font-medium text-gray-700">
                            Bank Name
                        </Label>
                        <div className="relative mt-1">
                            <Building2 className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                            <Input
                                id="bankName"
                                type="text"
                                value={formData.bankName}
                                onChange={(e) => handleInputChange('bankName', e.target.value)}
                                placeholder="Select bank"
                                className="pl-10 pr-10 bg-gray-100 border-gray-200"
                                onFocus={handleBankFocus}
                                onBlur={handleBankBlur}
                            />
                            <div className="absolute right-3 top-1/2 transform -translate-y-1/2">
                                <svg className="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>

                        {/* Bank Suggestions Dropdown */}
                        {showBankSuggestions && filteredBanks.length > 0 && (
                            <div className="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                                {filteredBanks.map((bank) => (
                                    <button
                                        key={bank.code}
                                        type="button"
                                        onMouseDown={(e) => {
                                            e.preventDefault();
                                            handleBankSelect(bank);
                                        }}
                                        className="w-full px-4 py-2 text-left hover:bg-gray-50 text-sm"
                                    >
                                        {bank.name}
                                    </button>
                                ))}
                            </div>
                        )}

                        {/* Loading banks indicator */}
                        {isLoadingBanks && (
                            <p className="text-gray-500 text-xs mt-1">Loading banks...</p>
                        )}

                        {errors.bankName && (
                            <p className="text-red-500 text-xs mt-1">{errors.bankName}</p>
                        )}
                    </div>

                    {/* Account Number */}
                    <div className="relative">
                        <Label htmlFor="accountNumber" className="text-sm font-medium text-gray-700">
                            Account Number
                        </Label>
                        <div className="relative mt-1">
                            <Hash className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                            <Input
                                id="accountNumber"
                                type="text"
                                value={formData.accountNumber}
                                onChange={(e) => handleInputChange('accountNumber', e.target.value.replace(/\D/g, ''))}
                                placeholder="Enter Account Number"
                                maxLength={10}
                                className="pl-10 bg-gray-100 border-gray-200"
                            />
                        </div>
                        <p className="text-gray-500 text-xs mt-1">
                            Please enter valid account number with the right number of digits.
                        </p>
                        {errors.accountNumber && (
                            <p className="text-red-500 text-xs mt-1">{errors.accountNumber}</p>
                        )}
                    </div>

                    {/* Action Button */}
                    <div className="pt-4">
                        <Button
                            type="submit"
                            disabled={isSubmitting}
                            className=" bg-[#338078] hover:bg-[#338078]/80 text-white rounded-full py-3"
                        >
                            {isSubmitting ? (
                                <div className="flex items-center justify-center space-x-2">
                                    <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                                    <span>Adding...</span>
                                </div>
                            ) : (
                                'Add Your Bank'
                            )}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    );
}
