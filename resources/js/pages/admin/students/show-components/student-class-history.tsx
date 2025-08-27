import React from 'react';

interface ClassHistoryItem {
    id: number;
    date: string;
    class_type: string;
    teacher: string;
    status: 'completed' | 'missed' | 'cancelled' | 'scheduled';
}

interface Props {
    classHistory: ClassHistoryItem[];
}

export default function StudentClassHistory({ classHistory = [] }: Props) {
    const getStatusDisplay = (status: string) => {
        switch (status) {
            case 'completed':
                return <span className="text-green-600 font-medium">Completed</span>;
            case 'missed':
                return <span className="text-yellow-600 font-medium">Missed</span>;
            case 'cancelled':
                return <span className="text-red-600 font-medium">Cancelled</span>;
            case 'scheduled':
                return <span className="text-blue-600 font-medium">Scheduled</span>;
            default:
                return <span className="text-gray-600 font-medium">{status}</span>;
        }
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    };

    return (
        <div className="bg-white rounded-xl shadow-sm p-6">
            {/* Title */}
            <h3 className="text-lg font-bold text-gray-800 mb-6">Class History</h3>
            
            {classHistory.length > 0 ? (
                <div className="overflow-x-auto">
                    <table className="w-full">
                        {/* Table Header */}
                        <thead>
                            <tr className="bg-gray-50">
                                <th className="px-4 py-3 text-left text-sm font-medium text-gray-700">Date</th>
                                <th className="px-4 py-3 text-left text-sm font-medium text-gray-700">Class Type</th>
                                <th className="px-4 py-3 text-left text-sm font-medium text-gray-700">Teacher</th>
                                <th className="px-4 py-3 text-left text-sm font-medium text-gray-700">Status</th>
                            </tr>
                        </thead>
                        
                        {/* Table Body */}
                        <tbody className="divide-y divide-gray-200">
                            {classHistory.map((classItem) => (
                                <tr key={classItem.id} className="hover:bg-gray-50">
                                    <td className="px-4 py-3 text-sm text-gray-900">
                                        {formatDate(classItem.date)}
                                    </td>
                                    <td className="px-4 py-3 text-sm text-gray-900">
                                        {classItem.class_type}
                                    </td>
                                    <td className="px-4 py-3 text-sm text-gray-900">
                                        {classItem.teacher}
                                    </td>
                                    <td className="px-4 py-3 text-sm">
                                        {getStatusDisplay(classItem.status)}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            ) : (
                <div className="text-center py-8 text-gray-500">
                    No class history available
                </div>
            )}
        </div>
    );
}
