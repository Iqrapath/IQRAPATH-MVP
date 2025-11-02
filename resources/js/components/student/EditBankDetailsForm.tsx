import { useState, useEffect } from 'react';
import { Building2, Hash, ArrowLeft } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { router } from '@inertiajs/react';
import { toast } from 'sonner';

interface PaymentMethod {
    id: number;
    type: string;
    name: string;
    bank_code?: string;
    bank_name?: string;
    account_name?: string;
    last_four?: string;
    details?: {
        bank_name?: string;
        account_holder?: string;
        account_number?: string;
    } | null;
}

interface EditBankDetailsFormProps {
    paymentMethod: PaymentMethod;
    onCancel: () => void;
    onSuccess: () => void;
    onAddNew?: () => void;
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

export default function EditBankDetailsForm({ paymentMethod, onCancel, onSuccess, onAddNew }: EditBankDetailsFormProps) {
    const [isEditMode, setIsEditMode] = useState(false);
    const [formData, setFormData] = useState<BankDetails>({
        accountName: paymentMethod.account_name || paymentMethod.details?.account_holder || '',
        bankName: paymentMethod.bank_name || paymentMethod.details?.bank_name || '',
        accountNumber: ''
    });
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [errors, setErrors] = useState<Partial<BankDetails>>({});
    const [showBankSuggestions, setShowBankSuggestions] = useState(false);
    const [banks, setBanks] = useState<Bank[]>([]);
    const [filteredBanks, setFilteredBanks] = useState<Bank[]>([]);
    const [isLoadingBanks, setIsLoadingBanks] = useState(false);

    // Get display value for account number (masked or full)
    const getAccountNumberDisplay = () => {
        if (isEditMode) {
            return formData.accountNumber;
        }
        // Show only last 4 digits when not in edit mode
        const lastFour = paymentMethod.last_four || paymentMethod.details?.account_number?.slice(-4) || '';
        return lastFour ? `******${lastFour}` : '';
    };

    // Update form data when paymentMethod prop changes
    useEffect(() => {
        setFormData({
            accountName: paymentMethod.account_name || paymentMethod.details?.account_holder || '',
            bankName: paymentMethod.bank_name || paymentMethod.details?.bank_name || '',
            accountNumber: ''
        });
        setIsEditMode(false); // Reset edit mode when payment method changes
    }, [paymentMethod]);

    // Fetch banks from API
    useEffect(() => {
        fetchBanks();
    }, []);

    const fetchBanks = async () => {
        try {
            setIsLoadingBanks(true);
            const response = await fetch('/student/banks', {
                headers: {
                    'Accept': 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error('Failed to fetch banks');
            }

            const data = await response.json();
            setBanks(data);
        } catch (error) {
            console.error('Error fetching banks:', error);
            toast.error('Failed to load banks. Please try again.');
        } finally {
            setIsLoadingBanks(false);
        }
    };

    const handleInputChange = (field: keyof BankDetails, value: string) => {
        setFormData(prev => ({ ...prev, [field]: value }));
        if (errors[field]) {
            setErrors(prev => ({ ...prev, [field]: undefined }));
        }

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
        const loadingToast = toast.loading('Updating bank account...');

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

            router.put(`/student/payment-methods/${paymentMethod.id}`, paymentMethodData, {
                preserveScroll: true,
                onSuccess: () => {
                    toast.dismiss(loadingToast);
                    toast.success('Bank account updated successfully');
                    // Call parent's onSuccess which will refresh data and update the prop
                    onSuccess();
                    // Edit mode and form data will be reset by useEffect when prop updates
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
                        toast.error(errors.error as string || 'Failed to update bank account. Please try again.');
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
            console.error('Error updating bank details:', error);
            toast.error('An unexpected error occurred. Please try again.');
            setIsSubmitting(false);
        }
    };

    return (
        <div>
            <div className="bg-white rounded-3xl p-10 max-w-4xl mx-auto shadow-xl">
                {/* Form */}
                <form onSubmit={handleSubmit} className="space-y-10">
                    {/* Account Name Section */}
                    <div>
                        <div className="flex items-center justify-between mb-4">
                            <Label htmlFor="accountName" className="text-lg font-medium text-gray-600">
                                Account Name
                            </Label>
                            <button
                                type="button"
                                onClick={() => setIsEditMode(!isEditMode)}
                                disabled={isSubmitting}
                                className="text-[#2C7870] hover:text-[#236158] font-medium text-base transition-colors disabled:opacity-50"
                            >
                                {isEditMode ? 'Cancel' : 'Edit'}
                            </button>
                        </div>
                        <Input
                            id="accountName"
                            type="text"
                            value={formData.accountName}
                            onChange={(e) => handleInputChange('accountName', e.target.value)}
                            placeholder="Enter Account Name"
                            readOnly={!isEditMode}
                            className="w-full bg-gray-100 border border-gray-200 rounded-2xl h-16 px-6 text-gray-700 text-base placeholder:text-gray-400"
                        />
                        {errors.accountName && (
                            <p className="text-red-500 text-sm mt-2">{errors.accountName}</p>
                        )}
                    </div>

                    {/* Bank Name Section */}
                    <div className="relative">
                        <Label htmlFor="bankName" className="text-lg font-medium text-gray-600 mb-4 block">
                            Bank Name
                        </Label>
                        <div className="relative">
                            <Building2 className="absolute left-5 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400 z-10" />
                            <Input
                                id="bankName"
                                type="text"
                                value={formData.bankName}
                                onChange={(e) => handleInputChange('bankName', e.target.value)}
                                placeholder="Select bank"
                                readOnly={!isEditMode}
                                className="w-full bg-gray-100 border border-gray-200 rounded-2xl h-16 pl-14 pr-12 text-gray-700 text-base placeholder:text-gray-400"
                                onFocus={handleBankFocus}
                                onBlur={handleBankBlur}
                            />
                            <div className="absolute right-5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none z-10">
                                <svg className="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                    <polyline points="6 9 12 15 18 9" />
                                </svg>
                            </div>
                        </div>

                        {/* Bank Suggestions Dropdown */}
                        {showBankSuggestions && filteredBanks.length > 0 && isEditMode && (
                            <div className="absolute z-50 left-0 right-0 mt-2 bg-white border border-gray-200 rounded-xl shadow-lg max-h-48 overflow-y-auto">
                                {filteredBanks.map((bank, index) => (
                                    <button
                                        key={`${bank.code}-${index}`}
                                        type="button"
                                        onMouseDown={(e) => {
                                            e.preventDefault();
                                            handleBankSelect(bank);
                                        }}
                                        className="w-full px-6 py-3 text-left hover:bg-gray-50 text-sm text-gray-700"
                                    >
                                        {bank.name}
                                    </button>
                                ))}
                            </div>
                        )}

                        {isLoadingBanks && (
                            <p className="text-gray-400 text-sm mt-2">Loading banks...</p>
                        )}

                        {errors.bankName && (
                            <p className="text-red-500 text-sm mt-2">{errors.bankName}</p>
                        )}
                    </div>

                    {/* Account Number Section */}
                    <div>
                        <Label htmlFor="accountNumber" className="text-lg font-medium text-gray-600 mb-4 block">
                            Account Number
                        </Label>
                        <div className="relative">
                            <Hash className="absolute left-5 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                            <Input
                                id="accountNumber"
                                type="text"
                                value={isEditMode ? formData.accountNumber : getAccountNumberDisplay()}
                                onChange={(e) => handleInputChange('accountNumber', e.target.value.replace(/\D/g, ''))}
                                placeholder={isEditMode ? "Enter Account Number" : ""}
                                maxLength={10}
                                readOnly={!isEditMode}
                                className="w-full bg-gray-100 border border-gray-200 rounded-2xl h-16 pl-14 text-gray-700 text-base placeholder:text-gray-400"
                            />
                        </div>
                        <p className="text-sm text-gray-400 mt-3">
                            Please enter valid account number with the right number of digits.
                        </p>
                        {errors.accountNumber && (
                            <p className="text-red-500 text-sm mt-2">{errors.accountNumber}</p>
                        )}
                    </div>

                    {/* Submit Button - Only show in edit mode */}
                    {isEditMode && (
                        <div className="pt-6">
                            <Button
                                type="submit"
                                disabled={isSubmitting}
                                className="bg-[#2C7870] hover:bg-[#236158] text-white px-12 py-4 rounded-2xl font-medium text-base h-auto"
                            >
                                {isSubmitting ? (
                                    <div className="flex items-center justify-center space-x-2">
                                        <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                                        <span>Saving...</span>
                                    </div>
                                ) : (
                                    'Save Changes'
                                )}
                            </Button>
                        </div>
                    )}
                </form>
            </div>

            {/* Add New Payment Button */}
            <div className="mt-8 text-center bg-white rounded-3xl p-4 max-w-full mx-auto shadow-xl mb-8">
                <button
                    type="button"
                    onClick={onAddNew || onCancel}
                    className="text-[#2C7870] hover:text-[#236158] font-medium text-base transition-colors cursor-pointer"
                >
                    Add New Payment
                </button>
            </div>
        </div>
    );
}
