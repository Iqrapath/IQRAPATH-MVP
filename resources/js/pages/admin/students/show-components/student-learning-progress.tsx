import React from 'react';
import { Progress } from '@/components/ui/progress';
import { BookOpen, Award, Calendar, Users } from 'lucide-react';
import { LearningProgressItem } from '@/types/student';

interface Props {
    student: {
        id: number;
        role: string;
        is_guardian?: boolean;
        name?: string;
    };
    learningProgress?: LearningProgressItem[];
}

export default function StudentLearningProgress({ student, learningProgress = [] }: Props) {
    // If no learning progress data available, show message
    if (!learningProgress || learningProgress.length === 0) {
        return (
            <div className="bg-white rounded-xl shadow-sm p-6">
                <div className="flex items-center justify-between mb-4">
                    <h3 className="text-lg font-medium text-gray-800">Learning Progress</h3>
                    <button
                        className="text-teal-600 hover:text-teal-700 cursor-pointer font-medium text-base"
                        onClick={() => {
                            window.location.href = `/admin/students/${student.id}/learning-progress`;
                        }}
                    >
                        Track Learning Progress
                    </button>
                </div>
                <div className="text-center text-gray-500 py-8">
                    No learning progress data available
                </div>
            </div>
        );
    }

    // Group progress by student for guardians
    const progressByStudent = student.is_guardian 
        ? learningProgress.reduce((acc, item) => {
            const studentName = item.student_name || 'Unknown Student';
            if (!acc[studentName]) {
                acc[studentName] = [];
            }
            acc[studentName].push(item);
            return acc;
        }, {} as Record<string, LearningProgressItem[]>)
        : null;

    return (
        <div className="bg-white rounded-xl shadow-sm p-6">
            <div className="flex items-center justify-between mb-6">
                <h3 className="text-lg font-medium text-gray-800">Learning Progress</h3>
                <button
                    className="text-teal-600 hover:text-teal-700 cursor-pointer font-medium text-base"
                    onClick={() => {
                        window.location.href = `/admin/students/${student.id}/learning-progress`;
                    }}
                >
                    Track Learning Progress
                </button>
            </div>

            {student.is_guardian && progressByStudent ? (
                // For guardians, show progress for each child
                <div className="space-y-6">
                    {Object.entries(progressByStudent).map(([studentName, progressItems]) => (
                        <div key={studentName} className="border border-gray-200 rounded-lg p-4">
                            <h4 className="font-medium text-gray-800 mb-4 flex items-center gap-2">
                                <Users className="h-4 w-4 text-teal-600" />
                                {studentName} - Learning Progress
                            </h4>
                            
                            <div className="space-y-4">
                                {progressItems.map((item, index) => (
                                    <div key={index} className="bg-gray-50 rounded-lg p-4">
                                        <div className="flex items-center justify-between mb-3">
                                            <div className="flex items-center gap-2">
                                                <BookOpen className="h-4 w-4 text-teal-600" />
                                                <span className="font-medium text-gray-700">{item.subject}</span>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <Award className="h-4 w-4 text-yellow-500" />
                                                <span className="text-sm text-gray-600">{item.certificates_earned} certificates</span>
                                            </div>
                                        </div>
                                        
                                        <div className="space-y-2">
                                            <div className="flex justify-between text-sm text-gray-600">
                                                <span>Progress</span>
                                                <span>{item.progress_percentage}%</span>
                                            </div>
                                            <Progress value={item.progress_percentage} className="h-2" />
                                            
                                            <div className="flex items-center gap-4 text-sm text-gray-600">
                                                <div className="flex items-center gap-1">
                                                    <Calendar className="h-3 w-3" />
                                                    <span>{item.completed_sessions}/{item.total_sessions} sessions</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    ))}
                </div>
            ) : (
                // For individual students
                <div className="space-y-4">
                    {learningProgress.map((item, index) => (
                        <div key={index} className="bg-gray-50 rounded-lg p-4">
                            <div className="flex items-center justify-between mb-3">
                                <div className="flex items-center gap-2">
                                    <BookOpen className="h-4 w-4 text-teal-600" />
                                    <span className="font-medium text-gray-700">{item.subject}</span>
                                </div>
                                <div className="flex items-center gap-2">
                                    <Award className="h-4 w-4 text-yellow-500" />
                                    <span className="text-sm text-gray-600">{item.certificates_earned} certificates</span>
                                </div>
                            </div>
                            
                            <div className="space-y-2">
                                <div className="flex justify-between text-sm text-gray-600">
                                    <span>Progress</span>
                                    <span>{item.progress_percentage}%</span>
                                </div>
                                <Progress value={item.progress_percentage} className="h-2" />
                                
                                <div className="flex items-center gap-4 text-sm text-gray-600">
                                    <div className="flex items-center gap-1">
                                        <Calendar className="h-3 w-3" />
                                        <span>{item.completed_sessions}/{item.total_sessions} sessions</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}
