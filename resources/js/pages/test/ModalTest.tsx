import React, { useState } from 'react';
import { Button } from '@/components/ui/button';
import AddWithdrawalModal from '../teacher/earnings/components/AddWithdrawalModal';
import AddBankTransferModal from '../teacher/earnings/components/AddBankTransferModal';

export default function ModalTest() {
    const [showModal, setShowModal] = useState(false);
    const [showBankModal, setShowBankModal] = useState(false);

    const handleOpenModal = () => {
        setShowModal(true);
    };

    const handleCloseModal = () => {
        setShowModal(false);
    };

    const handleOpenBankModal = () => {
        setShowBankModal(true);
    };

    const handleCloseBankModal = () => {
        setShowBankModal(false);
    };

    const handleAddBankTransfer = () => {
        alert('Bank Transfer option selected!');
        setShowModal(false);
    };

    const handleAddMobileWallet = () => {
        alert('Mobile Wallet option selected!');
        setShowModal(false);
    };

    const handleBankTransferSuccess = () => {
        alert('Bank transfer added successfully!');
        setShowBankModal(false);
    };

    return (
        <div className="min-h-screen bg-gray-50 flex items-center justify-center p-8">
            <div className="bg-white rounded-lg shadow-lg p-8 max-w-md w-full">
                <div className="text-center">
                    <h1 className="text-2xl font-bold text-gray-900 mb-4">
                        Modal Test Page
                    </h1>
                    <p className="text-gray-600 mb-6">
                        Click the button below to test the Add Withdrawal Info modal.
                    </p>
                    
                    <div className="space-y-3">
                        <Button
                            onClick={handleOpenModal}
                            className="bg-[#338078] hover:bg-[#338078]/80 text-white px-6 py-3 text-lg w-full"
                        >
                            Test Add Withdrawal Modal
                        </Button>
                        
                        <Button
                            onClick={handleOpenBankModal}
                            variant="outline"
                            className="w-full"
                        >
                            Test Bank Transfer Modal
                        </Button>
                    </div>
                </div>

                {/* Instructions */}
                <div className="mt-8 p-4 bg-blue-50 rounded-lg">
                    <h3 className="font-semibold text-blue-900 mb-2">Test Instructions:</h3>
                    <ul className="text-sm text-blue-800 space-y-1">
                        <li>• Test "Add Withdrawal Modal" for main selection</li>
                        <li>• Test "Bank Transfer Modal" for form details</li>
                        <li>• Test form validation and submission</li>
                        <li>• Test close buttons and overlays</li>
                        <li>• Check modal positioning and styling</li>
                    </ul>
                </div>
            </div>

            {/* Add Withdrawal Modal */}
            <AddWithdrawalModal
                isOpen={showModal}
                onClose={handleCloseModal}
                onAddBankTransfer={handleAddBankTransfer}
                onAddMobileWallet={handleAddMobileWallet}
            />

            {/* Bank Transfer Modal */}
            <AddBankTransferModal
                isOpen={showBankModal}
                onClose={handleCloseBankModal}
                onSuccess={handleBankTransferSuccess}
            />
        </div>
    );
}
