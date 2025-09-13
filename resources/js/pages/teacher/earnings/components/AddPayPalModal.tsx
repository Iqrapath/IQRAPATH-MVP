import React, { useState } from 'react';
import { Button } from '@/components/ui/button';
import { X } from 'lucide-react';
import { PaypalIcon } from '@/components/icons/paypal-icon';

interface AddPayPalModalProps {
    isOpen: boolean;
    onClose: () => void;
    onConfirm: () => void;
}

export default function AddPayPalModal({
    isOpen,
    onClose,
    onConfirm
}: AddPayPalModalProps) {
    const [isPayPalSelected, setIsPayPalSelected] = useState(false);

    if (!isOpen) return null;

    const handlePayPalSelect = () => {
        setIsPayPalSelected(true);
    };

    const handleOKTransfer = () => {
        onConfirm();
    };

    const handleCancel = () => {
        setIsPayPalSelected(false);
        onClose();
    };

    return (
        <div className="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50">
            <div className="bg-white rounded-2xl p-6 w-full max-w-md mx-4">
                {/* Header */}
                <div className="flex justify-between items-start mb-6">
                    <div>
                        <h2 className="text-xl font-bold text-gray-900 mb-1">
                            Add Payment Method
                        </h2>
                        {!isPayPalSelected && (
                            <p className="text-gray-500 text-sm">
                                Easily withdraw your earnings to your Paypal account
                            </p>
                        )}
                        
                    </div>
                    <button
                        onClick={onClose}
                        className="text-gray-500 hover:text-gray-700 transition-colors"
                    >
                        <X className="h-5 w-5" />
                    </button>
                </div>

                {/* PayPal Option */}
                <div className="space-y-4">
                    <div 
                        onClick={handlePayPalSelect}
                        className="border border-gray-200 rounded-lg p-4 hover:border-gray-300 transition-colors cursor-pointer"
                    >
                        <div className="flex items-center justify-between">
                            <div className="flex items-center space-x-4">
                                {/* PayPal Logo */}
                                <div className="flex items-center space-x-2">
                                    <PaypalIcon className="h-6 w-6 text-[#0070ba]" />
                                    <span className="text-[#0070ba] font-semibold text-lg">PayPal</span>
                                </div>
                                
                            </div>
                            
                            {/* Radio Button */}
                            <div className={`w-5 h-5 border-2 rounded-full flex items-center justify-center ${
                                isPayPalSelected 
                                    ? 'border-[#338078] bg-[#338078]' 
                                    : 'border-gray-300'
                            }`}>
                                {isPayPalSelected && (
                                    <div className="w-2 h-2 bg-white rounded-full"></div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>

                {isPayPalSelected && (
                            <p className="text-gray-500 text-sm">
                                In order to complete your PayPal registration, we will transfer you over to PayPal's secure servers.
                            </p>
                        )}

                {/* Action Buttons */}
                {!isPayPalSelected ? (
                    <div className="flex justify-end space-x-3 mt-6">
                        <Button
                            onClick={onClose}
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
                ) : (
                    <div className="flex justify-between items-center mt-6">
                        <button
                            onClick={handleCancel}
                            className="text-gray-500 hover:text-gray-700 text-sm"
                        >
                            No Cancel
                        </button>
                        <Button
                            onClick={handleOKTransfer}
                            className="bg-[#338078] hover:bg-[#338078]/80 text-white px-6 py-2 rounded-lg"
                        >
                            OK Transfer Me
                        </Button>
                    </div>
                )}
            </div>
        </div>
    );
}
