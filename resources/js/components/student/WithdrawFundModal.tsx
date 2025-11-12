/**
 * Component: WithdrawFundModal
 * Purpose: Modal for students to request wallet withdrawals
 * 
 * Design Pattern: Follows FundAccountModal.tsx structure and styling
 * 
 * Features:
 * - Amount input with validation (min: ₦500, max: available balance)
 * - Bank account selection from verified payment methods
 * - Real-time balance display
 * - Processing time information (1-3 business days)
 * - Confirmation step before submission
 * 
 * Requirements: 1.1 from student-withdrawal spec
 */
import { useState, useEffect } from 'react';
import { X, Building2, CheckCircle2 } from 'lucide-react';
import { toast } from 'sonner';
import axios from 'axios';

interface WithdrawFundModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSuccess?: () => void;
  walletBalance: number;
  availableWithdrawalBalance: number;
  currency?: string;
  user: {
    id: number;
    name: string;
    email: string;
  };
}

interface BankAccount {
  id: number;
  type: string;
  name: string;
  bank_code: string;
  bank_name: string;
  account_name: string;
  last_four: string;
  is_default: boolean;
  is_active: boolean;
  is_verified: boolean;
  verification_status: string;
}

export default function WithdrawFundModal({
  isOpen,
  onClose,
  onSuccess,
  walletBalance,
  availableWithdrawalBalance,
  currency = '₦',
  user
}: WithdrawFundModalProps) {
  // State management
  const [withdrawalAmount, setWithdrawalAmount] = useState('');
  const [selectedBankId, setSelectedBankId] = useState<number | null>(null);
  const [validationError, setValidationError] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [bankAccounts, setBankAccounts] = useState<BankAccount[]>([]);
  const [loadingBankAccounts, setLoadingBankAccounts] = useState(false);
  const [showConfirmation, setShowConfirmation] = useState(false);

  // Fetch verified bank accounts when modal opens
  useEffect(() => {
    if (isOpen) {
      fetchBankAccounts();
    }
  }, [isOpen]);

  // Fetch bank accounts from API
  const fetchBankAccounts = async () => {
    setLoadingBankAccounts(true);
    try {
      const response = await axios.get('/student/wallet/payment-methods');
      
      // Filter to show only verified bank transfer accounts
      const verifiedBankAccounts = response.data.payment_methods.filter(
        (method: BankAccount) => 
          method.type === 'bank_transfer' && 
          method.is_verified === true &&
          method.is_active === true
      );
      
      setBankAccounts(verifiedBankAccounts);
      
      // Auto-select default bank account if available
      const defaultBank = verifiedBankAccounts.find((bank: BankAccount) => bank.is_default);
      if (defaultBank) {
        setSelectedBankId(defaultBank.id);
      }
    } catch (error) {
      console.error('Error fetching bank accounts:', error);
      toast.error('Failed to load bank accounts');
    } finally {
      setLoadingBankAccounts(false);
    }
  };

  if (!isOpen) return null;

  // Calculate pending withdrawals amount
  const pendingWithdrawals = walletBalance - availableWithdrawalBalance;

  // Validation with real-time feedback
  const validateAmount = (value: string): { valid: boolean; error?: string } => {
    const amount = parseFloat(value);
    
    if (!value || isNaN(amount)) {
      return { valid: false, error: 'Please enter a valid amount' };
    }
    
    if (amount < 500) {
      return { valid: false, error: 'Minimum withdrawal amount is ₦500' };
    }
    
    if (amount > availableWithdrawalBalance) {
      if (pendingWithdrawals > 0) {
        return { 
          valid: false, 
          error: `Amount exceeds available balance. You have ${currency}${pendingWithdrawals.toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} in pending withdrawals.` 
        };
      }
      return { valid: false, error: 'Amount exceeds available balance' };
    }
    
    return { valid: true };
  };

  // Handle amount change with real-time validation
  const handleAmountChange = (value: string) => {
    // Allow only numbers and decimal point
    const sanitized = value.replace(/[^0-9.]/g, '');
    
    // Prevent multiple decimal points
    const parts = sanitized.split('.');
    const formatted = parts.length > 2 
      ? parts[0] + '.' + parts.slice(1).join('') 
      : sanitized;
    
    setWithdrawalAmount(formatted);
    
    // Clear validation error when user types
    if (validationError) {
      setValidationError('');
    }
  };

  // Handle amount blur for real-time validation
  const handleAmountBlur = () => {
    if (withdrawalAmount) {
      const validation = validateAmount(withdrawalAmount);
      if (!validation.valid) {
        setValidationError(validation.error || '');
      }
    }
  };

  // Handle form submission
  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    // Validate amount
    const validation = validateAmount(withdrawalAmount);
    if (!validation.valid) {
      setValidationError(validation.error || '');
      toast.error(validation.error);
      return;
    }
    
    // Validate bank selection
    if (!selectedBankId) {
      toast.error('Please select a bank account');
      return;
    }
    
    // Submit withdrawal request
    setIsLoading(true);
    
    try {
      const response = await axios.post('/student/wallet/withdraw', {
        amount: parseFloat(withdrawalAmount),
        payment_method_id: selectedBankId,
        notes: '' // Optional notes field
      });
      
      // Success handling
      if (response.data.success) {
        const requestId = response.data.data?.id || response.data.data?.request_id;
        
        toast.success(
          `Withdrawal request submitted successfully! Request ID: ${requestId}`,
          {
            duration: 5000,
            description: 'Your withdrawal will be processed within 1-3 business days.'
          }
        );
        
        // Reset form
        setWithdrawalAmount('');
        setSelectedBankId(null);
        setValidationError('');
        
        // Close modal after short delay to allow user to see success message
        setTimeout(() => {
          // Call onSuccess callback to refresh wallet balance
          if (onSuccess) {
            onSuccess();
          } else {
            // Fallback to page reload if no callback provided
            onClose();
            window.location.reload();
          }
        }, 1500);
      }
    } catch (error: any) {
      console.error('Withdrawal request failed:', error);
      
      // Handle validation errors
      if (error.response?.status === 422) {
        const errors = error.response.data.errors;
        
        if (errors?.amount) {
          setValidationError(errors.amount[0]);
          toast.error(errors.amount[0]);
        } else if (errors?.payment_method_id) {
          toast.error(errors.payment_method_id[0]);
        } else {
          toast.error('Please check your input and try again');
        }
      } 
      // Handle business logic errors
      else if (error.response?.status === 400) {
        const message = error.response.data.message || 'Unable to process withdrawal request';
        toast.error(message, {
          duration: 5000
        });
      }
      // Handle authorization errors
      else if (error.response?.status === 403) {
        toast.error('You are not authorized to make withdrawal requests');
      }
      // Handle server errors
      else if (error.response?.status >= 500) {
        toast.error('Server error. Please try again later');
      }
      // Handle network errors
      else {
        toast.error('Network error. Please check your connection and try again');
      }
    } finally {
      setIsLoading(false);
    }
  };

  // Check if form is valid
  const isFormValid = (): boolean => {
    const validation = validateAmount(withdrawalAmount);
    return validation.valid && selectedBankId !== null;
  };

  return (
    <div className="fixed inset-0 bg-black/30 backdrop-blur-sm flex items-center justify-center z-50 p-4 sm:p-6">
      <div className="bg-white rounded-2xl p-4 sm:p-6 max-w-5xl w-full max-h-[90vh] overflow-y-auto shadow-2xl">
        {/* Header */}
        <div className="flex items-center justify-between mb-4 sm:mb-6">
          <h2 className="text-lg sm:text-xl font-semibold text-gray-900">Withdraw Funds</h2>
          <button
            onClick={onClose}
            className="p-1 hover:bg-gray-100 rounded-lg transition-colors"
            aria-label="Close modal"
          >
            <X className="w-5 h-5 text-gray-500" />
          </button>
        </div>

        {/* Available Balance Display */}
        <div className="mb-6 space-y-3">
          <div className="p-4 bg-teal-50 border border-teal-200 rounded-lg">
            <div className="flex items-center justify-between">
              <span className="text-sm text-gray-600">Wallet Balance</span>
              <span className="text-xl font-bold text-teal-700">
                {currency}{walletBalance.toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
              </span>
            </div>
          </div>
          
          {pendingWithdrawals > 0 && (
            <div className="p-4 bg-amber-50 border border-amber-200 rounded-lg">
              <div className="space-y-2">
                <div className="flex items-center justify-between">
                  <span className="text-sm text-gray-600">Pending Withdrawals</span>
                  <span className="text-lg font-semibold text-amber-700">
                    -{currency}{pendingWithdrawals.toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                  </span>
                </div>
                <div className="flex items-center justify-between border-t border-amber-300 pt-2">
                  <span className="text-sm font-medium text-gray-700">Available for Withdrawal</span>
                  <span className="text-xl font-bold text-teal-700">
                    {currency}{availableWithdrawalBalance.toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                  </span>
                </div>
              </div>
            </div>
          )}
          
          {pendingWithdrawals === 0 && (
            <div className="p-4 bg-green-50 border border-green-200 rounded-lg">
              <div className="flex items-center justify-between">
                <span className="text-sm font-medium text-gray-700">Available for Withdrawal</span>
                <span className="text-xl font-bold text-green-700">
                  {currency}{availableWithdrawalBalance.toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                </span>
              </div>
            </div>
          )}
        </div>

        {/* Two-Column Layout */}
        <form onSubmit={handleSubmit}>
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6 lg:gap-8">
            {/* Left Column - Amount Input */}
            <div>
              <h3 className="text-base sm:text-lg font-bold text-gray-900 mb-3 sm:mb-4">
                Withdrawal Amount
              </h3>
              
              {/* Insufficient Balance Warning */}
              {availableWithdrawalBalance < 500 && (
                <div className="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                  <p className="text-sm text-red-800">
                    <span className="font-medium">Insufficient Balance:</span> You need at least {currency}500.00 to make a withdrawal. Your available balance is {currency}{availableWithdrawalBalance.toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}.
                  </p>
                </div>
              )}
              
              <div className="bg-white border border-gray-200 rounded-lg p-4 sm:p-6 shadow-sm">
                <div className="space-y-4">
                  {/* Amount Input */}
                  <div>
                    <label htmlFor="amount" className="block text-sm font-medium text-gray-700 mb-2">
                      Amount to Withdraw
                    </label>
                    <div className="relative">
                      <span className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-lg">
                        {currency}
                      </span>
                      <input
                        type="text"
                        id="amount"
                        value={withdrawalAmount}
                        onChange={(e) => handleAmountChange(e.target.value)}
                        onBlur={handleAmountBlur}
                        placeholder="0.00"
                        disabled={isLoading || availableWithdrawalBalance < 500}
                        className={`w-full pl-8 pr-4 py-3 border rounded-lg text-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-colors ${
                          validationError ? 'border-red-500 focus:ring-red-500' : 'border-gray-300'
                        } ${isLoading || availableWithdrawalBalance < 500 ? 'bg-gray-50 cursor-not-allowed' : ''}`}
                      />
                    </div>
                    {validationError && (
                      <p className="mt-2 text-sm text-red-600 flex items-start gap-1">
                        <svg className="w-4 h-4 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                          <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
                        </svg>
                        <span>{validationError}</span>
                      </p>
                    )}
                  </div>

                  {/* Quick Amount Buttons */}
                  {availableWithdrawalBalance >= 500 && (
                    <div className="space-y-2">
                      <label className="block text-sm font-medium text-gray-700">Quick Select</label>
                      <div className="grid grid-cols-3 gap-2">
                        {[
                          { label: '50%', value: Math.floor(availableWithdrawalBalance * 0.5) },
                          { label: '75%', value: Math.floor(availableWithdrawalBalance * 0.75) },
                          { label: 'Max', value: availableWithdrawalBalance }
                        ].filter(option => option.value >= 500).map((option) => (
                          <button
                            key={option.label}
                            type="button"
                            onClick={() => handleAmountChange(option.value.toFixed(2))}
                            disabled={isLoading || availableWithdrawalBalance < 500}
                            className="px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-teal-50 hover:border-teal-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                          >
                            {option.label}
                          </button>
                        ))}
                      </div>
                    </div>
                  )}

                  {/* Amount Info */}
                  <div className="space-y-2 text-sm text-gray-600">
                    <div className="flex justify-between">
                      <span>Minimum withdrawal:</span>
                      <span className="font-medium">{currency}500.00</span>
                    </div>
                    <div className="flex justify-between">
                      <span>Maximum withdrawal:</span>
                      <span className="font-medium text-teal-700">
                        {currency}{availableWithdrawalBalance.toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                      </span>
                    </div>
                  </div>

                  {/* Processing Time Info */}
                  <div className="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                    <p className="text-sm text-blue-800">
                      <span className="font-medium">Processing Time:</span> 1-3 business days
                    </p>
                  </div>
                </div>
              </div>
            </div>

            {/* Right Column - Bank Selection */}
            <div>
              <h3 className="text-base sm:text-lg font-bold text-gray-900 mb-3 sm:mb-4">
                Select Bank Account
              </h3>
              <div className="bg-white border border-gray-200 rounded-lg p-4 sm:p-6 shadow-sm">
                {loadingBankAccounts ? (
                  <div className="text-center py-8">
                    <div className="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-teal-600"></div>
                    <p className="mt-2 text-sm text-gray-500">Loading bank accounts...</p>
                  </div>
                ) : bankAccounts.length === 0 ? (
                  <div className="text-center py-8 text-gray-500">
                    <Building2 className="w-12 h-12 mx-auto mb-3 text-gray-400" />
                    <p className="mb-2 font-medium">No verified bank accounts found</p>
                    <p className="text-sm">Please add and verify a bank account to withdraw funds</p>
                  </div>
                ) : (
                  <div className="space-y-3">
                    <p className="text-sm text-gray-600 mb-4">
                      Select the bank account where you want to receive your withdrawal
                    </p>
                    
                    {bankAccounts.map((bank) => (
                      <button
                        key={bank.id}
                        type="button"
                        onClick={() => setSelectedBankId(bank.id)}
                        disabled={isLoading}
                        className={`w-full text-left p-4 border-2 rounded-lg transition-all ${
                          selectedBankId === bank.id
                            ? 'border-teal-500 bg-teal-50'
                            : 'border-gray-200 hover:border-teal-300 hover:bg-gray-50'
                        } ${isLoading ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'}`}
                      >
                        <div className="flex items-start justify-between">
                          <div className="flex items-start gap-3 flex-1">
                            <div className={`mt-1 flex-shrink-0 w-5 h-5 rounded-full border-2 flex items-center justify-center ${
                              selectedBankId === bank.id
                                ? 'border-teal-500 bg-teal-500'
                                : 'border-gray-300'
                            }`}>
                              {selectedBankId === bank.id && (
                                <CheckCircle2 className="w-4 h-4 text-white" />
                              )}
                            </div>
                            
                            <div className="flex-1 min-w-0">
                              <div className="flex items-center gap-2 mb-1">
                                <Building2 className="w-4 h-4 text-gray-500 flex-shrink-0" />
                                <span className="font-semibold text-gray-900 truncate">
                                  {bank.bank_name}
                                </span>
                                {bank.is_default && (
                                  <span className="px-2 py-0.5 text-xs font-medium bg-teal-100 text-teal-700 rounded">
                                    Default
                                  </span>
                                )}
                              </div>
                              
                              <p className="text-sm text-gray-700 mb-1">
                                {bank.account_name}
                              </p>
                              
                              <p className="text-sm text-gray-500 font-mono">
                                ****{bank.last_four}
                              </p>
                              
                              <div className="mt-2 flex items-center gap-1 text-xs text-green-600">
                                <CheckCircle2 className="w-3 h-3" />
                                <span>Verified</span>
                              </div>
                            </div>
                          </div>
                        </div>
                      </button>
                    ))}
                    
                    {/* Info about adding more accounts */}
                    <div className="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                      <p className="text-sm text-blue-800">
                        <span className="font-medium">Note:</span> Only verified bank accounts can be used for withdrawals. You can add more accounts in your payment methods settings.
                      </p>
                    </div>
                  </div>
                )}
              </div>
            </div>
          </div>

          {/* Action Buttons */}
          <div className="mt-6 flex flex-col sm:flex-row gap-3 justify-end">
            <button
              type="button"
              onClick={onClose}
              disabled={isLoading}
              className="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={!isFormValid() || isLoading}
              className="px-6 py-3 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {isLoading ? 'Processing...' : 'Continue'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
