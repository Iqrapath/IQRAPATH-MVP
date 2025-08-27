import React from 'react';
import { Button } from '@/components/ui/button';

interface Student {
    id: number;
    role: string;
    status: string;
}

interface Props {
    student: Student;
    onApprove?: (studentId: number) => void;
    onSendMessage?: (studentId: number) => void;
    onReject?: (studentId: number) => void;
    onDeleteAccount?: (studentId: number) => void;
}

export default function StudentActionButtons({ 
    student, 
    onApprove, 
    onSendMessage, 
    onReject, 
    onDeleteAccount 
}: Props) {
    const handleApprove = () => {
        if (onApprove) {
            onApprove(student.id);
        }
    };

    const handleSendMessage = () => {
        if (onSendMessage) {
            onSendMessage(student.id);
        }
    };

    const handleReject = () => {
        if (onReject) {
            onReject(student.id);
        }
    };

    const handleDeleteAccount = () => {
        if (onDeleteAccount) {
            onDeleteAccount(student.id);
        }
    };

    return (
        <div className="mb-4 p-4">
            <div className="flex items-center space-x-4">
                {/* Approve Button - Primary teal button */}
                <Button 
                    onClick={handleApprove}
                    className="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded-full font-medium"
                >
                    Approve
                </Button>

                {/* Send Message Button - Secondary outlined button */}
                <Button 
                    onClick={handleSendMessage}
                    variant="outline"
                    className="border-teal-600 text-teal-600 hover:bg-teal-50 px-4 py-2 rounded-full font-medium"
                >
                    Send Message
                </Button>

                {/* Reject - Text link */}
                <span 
                    onClick={handleReject}
                    className="text-gray-800 hover:text-gray-600 cursor-pointer font-medium ml-4"
                >
                    Reject
                </span>

                {/* Delete Account - Red text link */}
                <span 
                    onClick={handleDeleteAccount}
                    className="text-red-600 hover:text-red-700 cursor-pointer font-medium ml-4"
                >
                    Delete Account
                </span>
            </div>
        </div>
    );
}
