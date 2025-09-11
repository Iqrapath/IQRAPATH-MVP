import { useState } from 'react';
import { StudentCard } from './StudentCard';
import { StudentProfileCard } from '@/components/StudentProfileCard';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { BookOpen } from 'lucide-react';

interface Student {
    id: number;
    name: string;
    avatar?: string;
    level: string;
    sessionsCompleted: number;
    progress: number;
    rating: number;
    isOnline?: boolean;
}

interface ActiveStudentTabProps {
    students: Student[];
    onViewProfile: (student: Student) => void;
    onChat: (student: Student) => void;
    onVideoCall: (student: Student) => void;
}

export function ActiveStudentTab({ students, onViewProfile, onChat, onVideoCall }: ActiveStudentTabProps) {
    const [selectedStudent, setSelectedStudent] = useState<Student | null>(null);
    const [isProfileModalOpen, setIsProfileModalOpen] = useState(false);

    const handleViewProfile = (student: Student) => {
        setSelectedStudent(student);
        setIsProfileModalOpen(true);
        onViewProfile(student);
    };

    const handleCloseProfile = () => {
        setIsProfileModalOpen(false);
        setSelectedStudent(null);
    };

    return (
        <div className="space-y-6">
            {/* Search Bar */}
            <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg className="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input
                    type="text"
                    placeholder="Search student by name or email..."
                    className="w-full pl-10 pr-12 py-3 border border-gray-300 rounded-lg"
                />
                <div className="absolute inset-y-0 right-0 pr-3 flex items-center">
                    <Button variant="ghost" size="sm" className="p-2">
                        <svg className="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                    </Button>
                </div>
            </div>

            {/* Student Cards Grid */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {students.map((student) => (
                    <StudentCard
                        key={student.id}
                        student={student}
                        onViewProfile={handleViewProfile}
                        onChat={onChat}
                        onVideoCall={onVideoCall}
                    />
                ))}
            </div>

            {/* Empty State */}
            {students.length === 0 && (
                <div className="text-center py-12">
                    <div className="w-24 h-24 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                        <BookOpen className="w-12 h-12 text-gray-400" />
                    </div>
                    <h3 className="text-lg font-semibold text-gray-900 mb-2">No Active Students</h3>
                    <p className="text-gray-600">You don't have any active students at the moment.</p>
                </div>
            )}

            {/* Student Profile Modal */}
            {selectedStudent && (
                <Dialog open={isProfileModalOpen} onOpenChange={setIsProfileModalOpen}>
                    <DialogContent className="max-w-2xl">
                        <DialogHeader>
                            <DialogTitle>Student Profile</DialogTitle>
                        </DialogHeader>
                        <StudentProfileCard
                            student={{
                                id: selectedStudent.id,
                                name: selectedStudent.name,
                                avatar: selectedStudent.avatar,
                                specialization: selectedStudent.level,
                                isOnline: selectedStudent.isOnline,
                                subjects: [selectedStudent.level] // Using level as subject for now
                            }}
                            showActions={true}
                            onViewProfile={handleCloseProfile}
                            onChat={() => onChat(selectedStudent)}
                            onVideoCall={() => onVideoCall(selectedStudent)}
                        />
                    </DialogContent>
                </Dialog>
            )}
        </div>
    );
}
