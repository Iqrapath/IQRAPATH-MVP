import { useState, useEffect } from 'react';
import { StudentCard } from './StudentCard';
import { StudentProfileModal } from '@/components/StudentProfileModal';
import { Button } from '@/components/ui/button';
import { BookOpen } from 'lucide-react';
import axios from 'axios';

interface Student {
    id: number;
    name: string;
    avatar?: string;
    level: string;
    sessionsCompleted: number;
    progress: number;
    rating: number;
    isOnline?: boolean;
    lastActive?: string;
    // Additional fields for StudentProfileModal
    age?: number;
    gender?: string;
    location?: string;
    joinedDate?: string;
    preferredLearningTime?: string;
    subjects?: string[];
    learningGoal?: string;
    availableDays?: string[];
    upcomingSessions?: {
        time: string;
        endTime: string;
        day: string;
        lesson: string;
        status: string;
    }[];
}

interface ActiveStudentTabProps {
    students: Student[];
    onViewProfile: (student: Student) => void | Promise<void>;
    onChat: (student: Student) => void | Promise<void>;
    onVideoCall: (student: Student) => void | Promise<void>;
}

export function ActiveStudentTab({ students, onViewProfile, onChat, onVideoCall }: ActiveStudentTabProps) {
    const [selectedStudent, setSelectedStudent] = useState<Student | null>(null);
    const [isProfileModalOpen, setIsProfileModalOpen] = useState(false);
    const [detailedStudentData, setDetailedStudentData] = useState<Student | null>(null);
    const [isLoadingProfile, setIsLoadingProfile] = useState(false);
    const [searchTerm, setSearchTerm] = useState('');
    const [filteredStudents, setFilteredStudents] = useState<Student[]>(students);

    // Filter students based on search term
    useEffect(() => {
        if (!searchTerm.trim()) {
            setFilteredStudents(students);
        } else {
            const filtered = students.filter(student => 
                student.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                student.level.toLowerCase().includes(searchTerm.toLowerCase()) ||
                (student.subjects && student.subjects.some(subject => 
                    subject.toLowerCase().includes(searchTerm.toLowerCase())
                ))
            );
            setFilteredStudents(filtered);
        }
    }, [searchTerm, students]);

    const handleSearchChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        setSearchTerm(e.target.value);
    };

    const handleViewProfile = async (student: Student) => {
        setSelectedStudent(student);
        setIsProfileModalOpen(true);
        setIsLoadingProfile(true);
        onViewProfile(student);

        try {
            const response = await axios.get(`/teacher/sessions/student/${student.id}/profile`);
            if (response.data.success) {
                setDetailedStudentData(response.data.data);
            }
        } catch (error) {
            console.error('Failed to fetch student profile:', error);
            // Fallback to basic student data if API fails
            setDetailedStudentData(student);
        } finally {
            setIsLoadingProfile(false);
        }
    };

    const handleCloseProfile = () => {
        setIsProfileModalOpen(false);
        setSelectedStudent(null);
        setDetailedStudentData(null);
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
                    placeholder="Search student by name, level, or subject..."
                    value={searchTerm}
                    onChange={handleSearchChange}
                    className="w-full pl-10 pr-12 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                />
                {searchTerm && (
                    <div className="absolute inset-y-0 right-0 pr-3 flex items-center">
                        <Button 
                            variant="ghost" 
                            size="sm" 
                            className="p-2 hover:bg-gray-100"
                            onClick={() => setSearchTerm('')}
                        >
                            <svg className="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </Button>
                    </div>
                )}
            </div>

            {/* Search Results Count */}
            {searchTerm && (
                <div className="text-sm text-gray-600">
                    {filteredStudents.length === 0 
                        ? 'No students found matching your search.'
                        : `Found ${filteredStudents.length} student${filteredStudents.length === 1 ? '' : 's'} matching "${searchTerm}"`
                    }
                </div>
            )}

            {/* Student Cards Grid */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-6">
                {filteredStudents.map((student) => (
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
            {filteredStudents.length === 0 && (
                <div className="text-center py-12">
                    <div className="w-24 h-24 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                        <BookOpen className="w-12 h-12 text-gray-400" />
                    </div>
                    {searchTerm ? (
                        <>
                            <h3 className="text-lg font-semibold text-gray-900 mb-2">No Students Found</h3>
                            <p className="text-gray-600">No students match your search criteria. Try adjusting your search terms.</p>
                        </>
                    ) : (
                        <>
                            <h3 className="text-lg font-semibold text-gray-900 mb-2">No Active Students</h3>
                            <p className="text-gray-600">You don't have any active students at the moment.</p>
                        </>
                    )}
                </div>
            )}

            {/* Student Profile Modal */}
            {selectedStudent && (
                <StudentProfileModal
                    isOpen={isProfileModalOpen}
                    onClose={handleCloseProfile}
                    student={detailedStudentData || {
                        id: selectedStudent.id,
                        name: selectedStudent.name,
                        avatar: selectedStudent.avatar,
                        specialization: selectedStudent.level,
                        isOnline: selectedStudent.isOnline,
                        age: selectedStudent.age || 18,
                        gender: selectedStudent.gender || 'Not specified',
                        location: selectedStudent.location || 'Location not specified',
                        joinedDate: selectedStudent.joinedDate || new Date().toISOString(),
                        preferredLearningTime: selectedStudent.preferredLearningTime || 'Morning (9 AM - 12 PM)',
                        subjects: selectedStudent.subjects || [selectedStudent.level],
                        learningGoal: selectedStudent.learningGoal || `Master ${selectedStudent.level} level concepts`,
                        availableDays: selectedStudent.availableDays || ['Monday', 'Wednesday', 'Friday'],
                        upcomingSessions: selectedStudent.upcomingSessions || []
                    }}
                    onChat={() => onChat(selectedStudent)}
                    onStartClass={() => onVideoCall(selectedStudent)}
                />
            )}
        </div>
    );
}
