import { useState } from 'react';
import { Head } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import TeacherVerificationSuccessModal from '@/components/teacher/TeacherVerificationSuccessModal';

export default function TestVerificationModal() {
    const [isModalOpen, setIsModalOpen] = useState(false);

    return (
        <div className="min-h-screen bg-gray-50 flex items-center justify-center">
            <Head title="Test Verification Modal" />
            
            <div className="text-center space-y-4">
                <h1 className="text-2xl font-bold">Test Teacher Verification Success Modal</h1>
                <p className="text-gray-600">Click the button below to test the modal</p>
                
                <Button 
                    onClick={() => setIsModalOpen(true)}
                    className="bg-teal-600 hover:bg-teal-700"
                >
                    Show Verification Success Modal
                </Button>
            </div>

            <TeacherVerificationSuccessModal
                isOpen={isModalOpen}
                onClose={() => setIsModalOpen(false)}
                teacherName="Abdullah"
            />
        </div>
    );
}
