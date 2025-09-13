import React, { useState } from 'react';
import PaymentInfo from '../teacher/earnings/components/PaymentInfo';

export default function PaymentInfoTest() {
    const [hasPaymentMethods, setHasPaymentMethods] = useState(false);

    // Mock payment methods data
    const mockPaymentMethods = [
        {
            id: 1,
            type: 'bank_transfer',
            name: 'My Bank Account',
            details: {
                bank_name: 'First City Monument Bank',
                account_holder: 'Alayande Nurudeen Bamidele',
                account_number: '4773719012'
            },
            is_default: true,
            is_active: true,
            created_at: '2024-01-01T00:00:00Z',
            updated_at: '2024-01-01T00:00:00Z'
        }
    ];

    // Mock page props
    const mockPageProps = {
        paymentMethods: hasPaymentMethods ? mockPaymentMethods : []
    };

    return (
        <div className="min-h-screen bg-gray-50 p-8">
            <div className="max-w-4xl mx-auto">
                <div className="bg-white rounded-lg shadow-lg p-8">
                    <div className="flex justify-between items-center mb-6">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900">
                                Payment Info Component Test
                            </h1>
                            <p className="text-gray-600 mt-1">
                                Test the PaymentInfo component with different states
                            </p>
                        </div>
                        
                        <div className="flex gap-4">
                            <button
                                onClick={() => setHasPaymentMethods(!hasPaymentMethods)}
                                className={`px-4 py-2 rounded-lg font-medium transition-colors ${
                                    hasPaymentMethods 
                                        ? 'bg-red-100 text-red-700 hover:bg-red-200' 
                                        : 'bg-green-100 text-green-700 hover:bg-green-200'
                                }`}
                            >
                                {hasPaymentMethods ? 'Show Empty State' : 'Show With Payment Methods'}
                            </button>
                        </div>
                    </div>

                    {/* Current State Indicator */}
                    <div className="mb-6 p-4 bg-blue-50 rounded-lg">
                        <h3 className="font-semibold text-blue-900 mb-2">Current State:</h3>
                        <p className="text-blue-800">
                            {hasPaymentMethods 
                                ? 'Showing payment methods list with bank transfer details' 
                                : 'Showing empty state with "Add Withdrawal Info" button'
                            }
                        </p>
                    </div>

                    {/* Test Instructions */}
                    <div className="mb-6 p-4 bg-yellow-50 rounded-lg">
                        <h3 className="font-semibold text-yellow-900 mb-2">Test Instructions:</h3>
                        <ul className="text-sm text-yellow-800 space-y-1">
                            <li>• <strong>Empty State:</strong> Click "Add Withdrawal Info" to test the modal</li>
                            <li>• <strong>With Data:</strong> Click "Change" button to test payment method editing</li>
                            <li>• <strong>Modal Testing:</strong> Test both "Add Bank Transfer" and "Add Mobile Wallet" options</li>
                            <li>• <strong>Close Modal:</strong> Test the X button and clicking outside the modal</li>
                        </ul>
                    </div>

                    {/* PaymentInfo Component */}
                    <div className="border border-gray-200 rounded-lg p-6">
                        <PaymentInfo />
                    </div>
                </div>
            </div>
        </div>
    );
}
