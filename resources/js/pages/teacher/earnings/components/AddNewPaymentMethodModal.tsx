import React from 'react';
import { Button } from '@/components/ui/button';
import { X, CreditCard, ChevronRight } from 'lucide-react';

interface AddNewPaymentMethodModalProps {
    isOpen: boolean;
    onClose: () => void;
    onSelectBankCard: () => void;
    onSelectPayPal: () => void;
}

export default function AddNewPaymentMethodModal({ 
    isOpen, 
    onClose, 
    onSelectBankCard,
    onSelectPayPal 
}: AddNewPaymentMethodModalProps) {
    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-gray-500 bg-opacity-50 flex items-center justify-center z-50">
            <div className="bg-white rounded-lg p-6 w-full max-w-md mx-4">
                {/* Header */}
                <div className="flex justify-between items-start mb-6">
                    <div>
                        <h2 className="text-xl font-bold text-gray-900 mb-1">
                            Add New Payment Method
                        </h2>
                        <p className="text-gray-600 text-sm">
                            Select how you'd like to pay online.
                        </p>
                    </div>
                    <button
                        onClick={onClose}
                        className="text-gray-500 hover:text-gray-700 transition-colors"
                    >
                        <X className="h-5 w-5" />
                    </button>
                </div>

                {/* Payment Method Options */}
                <div className="space-y-4">
                    {/* Bank Card Option */}
                    <div
                        onClick={onSelectBankCard}
                        className="border border-gray-200 rounded-lg p-4 hover:border-gray-300 transition-colors cursor-pointer"
                    >
                        <div className="flex items-center justify-between">
                            <div className="flex items-center space-x-4">
                                {/* Bank Card Icon */}
                                <div className="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                                    <CreditCard className="h-6 w-6 text-gray-600" />
                                </div>
                                
                                <div>
                                    <h3 className="font-semibold text-gray-900 text-base">
                                        Bank Card (Direct Withdrawal)
                                    </h3>
                                    <p className="text-gray-600 text-sm mt-1">
                                        Withdraw your earnings directly to a linked bank account.
                                    </p>
                                </div>
                            </div>
                            
                            <ChevronRight className="h-5 w-5 text-gray-400" />
                        </div>
                    </div>

                    {/* PayPal Option */}
                    <div
                        onClick={onSelectPayPal}
                        className="border border-gray-200 rounded-lg p-4 hover:border-gray-300 transition-colors cursor-pointer"
                    >
                        <div className="flex items-center justify-between">
                            <div className="flex items-center space-x-4">
                                {/* PayPal Icon */}
                                <div className="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                                    <div className="w-8 h-8 bg-[#0070ba] rounded-sm flex items-center justify-center">
                                        <span className="text-white text-sm font-bold">P</span>
                                    </div>
                                </div>
                                
                                <div>
                                    <h3 className="font-semibold text-gray-900 text-base">
                                        PayPal
                                    </h3>
                                    <p className="text-gray-600 text-sm mt-1">
                                        Withdraw your earnings via PayPal, Payoneer, or Flutterwave.
                                    </p>
                                </div>
                            </div>
                            
                            <ChevronRight className="h-5 w-5 text-gray-400" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
