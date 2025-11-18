import { useState, useEffect } from 'react';
import { X, Building2, Hash } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { router } from '@inertiajs/react';
import { toast } from 'sonner';

interface AddBankDetailsModalProps {
    isOpen: boolean;
    onClose: () => void;
    onBack: () => void;
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

export default function AddBankDetailsModal({ isOpen, onClose, onBack }: AddBankDetailsModalProps) {
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
            const response = await fetch('/guardian/banks', {
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
                setFilteredBanks(banks);
                setShowBankSuggestions(true);
            }
        }
    };

    const handleBankFocus = () => {
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
        if (errors.bankName) {
            setErrors(prev => ({ ...prev, bankName: undefined }));
        }
    };

    const handleBankBlur = () => {
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

        const selectedBank = banks.find(bank => bank.name === formData.bankName);

        if (!selectedBank) {
            setErrors(prev => ({ ...prev, bankName: 'Please select a valid bank from the list' }));
            return;
        }

        setIsSubmitting(true);

        const loadingToast = toast.loading('Verifying bank account...');

        try {
            const paymentMethodData = {
                type: 'bank_transfer',
                name: `${selectedBank.name} - ${formData.accountNumber.slice(-4)}`,
                bank_code: selectedBank.code,
                bank_name: selectedBank.name,
                account_number: formData.accountNumber,
                account_name: formData.accountName,
                currency: 'NGN'
            };

            router.post('/guardian/payment/methods', paymentMethodData, {
                preserveScroll: true,
                onSuccess: () => {
                    toast.dismiss(loadingToast);
                    toast.success('Bank account added successfully');
                    handleClose();
                },
                onError: (errors) => {
                    toast.dismiss(loadingToast);
                    console.error('Validation errors:', errors);

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
        <div className="fixed inset-0 bg-black/30 backdrop-blur-sm flex items-center justify-center z-50 p-4 sm:p-6">
            <div className="bg-white rounded-2xl w-full max-w-2xl mx-4 p-8">
                {/* Header */}
                <div className="flex items-start justify-between mb-6">
                    <div>
                        <h2 className="text-3xl font-bold text-gray-900 mb-2">
                            Add your Bank Details
                        </h2>
                        <p className="text-gray-500">
                            Easily transfer your Earning balance to your bank account
                        </p>
                    </div>
                    <button
                        onClick={handleClose}
                        disabled={isSubmitting}
                        className="text-gray-400 hover:text-gray-600 transition-colors disabled:opacity-50"
                    >
                        <X className="w-6 h-6" />
                    </button>
                </div>

                {/* Form */}
                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Account Name */}
                    <div>
                        <Label htmlFor="accountName" className="text-gray-700 font-medium">
                            Account Name
                        </Label>
                        <Input
                            id="accountName"
                            type="text"
                            value={formData.accountName}
                            onChange={(e) => handleInputChange('accountName', e.target.value)}
                            placeholder="Enter Account Name"
                            className="mt-2 border-2 border-gray-200 rounded-xl focus:border-[#2C7870] h-12"
                        />
                        {errors.accountName && (
                            <p className="text-red-500 text-xs mt-1">{errors.accountName}</p>
                        )}
                    </div>

                    {/* Bank Name */}
                    <div className="relative">
                        <Label htmlFor="bankName" className="text-gray-700 font-medium">
                            Bank Name
                        </Label>
                        <div className="relative mt-2">
                            <Building2 className="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                            <Input
                                id="bankName"
                                type="text"
                                value={formData.bankName}
                                onChange={(e) => handleInputChange('bankName', e.target.value)}
                                placeholder="Select bank"
                                className="pl-12 pr-10 border-2 border-gray-200 rounded-xl focus:border-[#2C7870] h-12"
                                onFocus={handleBankFocus}
                                onBlur={handleBankBlur}
                            />
                            <div className="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                                <svg className="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                    <polyline points="6 9 12 15 18 9" />
                                </svg>
                            </div>
                        </div>

                        {/* Bank Suggestions Dropdown */}
                        {showBankSuggestions && filteredBanks.length > 0 && (
                            <div className="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                                {filteredBanks.map((bank, index) => (
                                    <button
                                        key={`${bank.code}-${index}`}
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

                        {isLoadingBanks && (
                            <p className="text-gray-500 text-xs mt-1">Loading banks...</p>
                        )}

                        {errors.bankName && (
                            <p className="text-red-500 text-xs mt-1">{errors.bankName}</p>
                        )}
                    </div>

                    {/* Account Number */}
                    <div>
                        <Label htmlFor="accountNumber" className="text-gray-700 font-medium">
                            Account Number
                        </Label>
                        <div className="relative mt-2">
                            <Hash className="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                            <Input
                                id="accountNumber"
                                type="text"
                                value={formData.accountNumber}
                                onChange={(e) => handleInputChange('accountNumber', e.target.value.replace(/\D/g, ''))}
                                placeholder="Enter Account Number"
                                maxLength={10}
                                className="pl-12 border-2 border-gray-200 rounded-xl focus:border-[#2C7870] h-12"
                            />
                        </div>
                        <p className="text-sm text-gray-400 mt-2">
                            Please enter valid account number with the right number of digits.
                        </p>
                        {errors.accountNumber && (
                            <p className="text-red-500 text-xs mt-1">{errors.accountNumber}</p>
                        )}
                    </div>

                    {/* Submit Button */}
                    <div className="pt-4">
                        <Button
                            type="submit"
                            disabled={isSubmitting}
                            className="bg-[#2C7870] hover:bg-[#236158] text-white px-8 py-3 rounded-xl font-medium"
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

