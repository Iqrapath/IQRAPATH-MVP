import { Head } from '@inertiajs/react';
import StudentLayout from '@/layouts/student/student-layout';
import PaymentHistory from './components/PaymentHistory';

export default function StudentWalletHistory() {
    return (
        <StudentLayout pageTitle="Payment History" showRightSidebar={true}>
            <Head title="Payment History" />

            <div className="space-y-6">
                <PaymentHistory />
            </div>
        </StudentLayout>
    );
}
