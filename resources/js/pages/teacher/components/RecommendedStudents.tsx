import { useState, useEffect } from 'react';
import { StudentSessionRequestCard } from '@/components/student-cards';
import { Button } from '@/components/ui/button';
import { ChevronLeft, ChevronRight, Users } from 'lucide-react';

interface RecommendedStudent {
    id: number;
    student: {
        id: number;
        name: string;
        avatar?: string;
        specialization?: string;
        isOnline?: boolean;
    };
    request: {
        description: string;
        dateToStart: string;
        time: string;
        subjects: string[];
        price: string;
        priceNaira: string;
    };
    compatibilityScore: number;
    matchReasons: string[];
}

interface RecommendedStudentsProps {
    teacherId: number;
    teacherSubjects?: string[];
    teacherSpecializations?: string[];
}

export function RecommendedStudents({ 
    teacherId, 
    teacherSubjects = [], 
    teacherSpecializations = [] 
}: RecommendedStudentsProps) {
    const [recommendedStudents, setRecommendedStudents] = useState<RecommendedStudent[]>([]);
    const [loading, setLoading] = useState(true);
    const [scrollPosition, setScrollPosition] = useState(0);

    // Fetch recommended students
    useEffect(() => {
        const fetchRecommendedStudents = async () => {
            try {
                setLoading(true);
                const response = await fetch('/teacher/recommended-students');
                const data = await response.json();
                
                if (data.students && data.students.length > 0) {
                    setRecommendedStudents(data.students);
                } else {
                    setRecommendedStudents([]);
                }
            } catch (error) {
                console.error('Failed to fetch recommended students:', error);
                setRecommendedStudents([]);
            } finally {
                setLoading(false);
            }
        };

        fetchRecommendedStudents();
    }, [teacherId]);


    // Scroll functions
    const scrollLeft = () => {
        const container = document.getElementById('recommended-students-scroll');
        if (container) {
            const newPosition = Math.max(0, scrollPosition - 400);
            container.scrollTo({ left: newPosition, behavior: 'smooth' });
            setScrollPosition(newPosition);
        }
    };

    const scrollRight = () => {
        const container = document.getElementById('recommended-students-scroll');
        if (container) {
            const newPosition = Math.min(
                container.scrollWidth - container.clientWidth,
                scrollPosition + 400
            );
            container.scrollTo({ left: newPosition, behavior: 'smooth' });
            setScrollPosition(newPosition);
        }
    };

    // Handle scroll events
    const handleScroll = (e: React.UIEvent<HTMLDivElement>) => {
        setScrollPosition(e.currentTarget.scrollLeft);
    };

    if (loading) {
        return (
            <div className="space-y-4">
                <h2 className="text-2xl font-bold text-gray-800">Recommended Students For You</h2>
                <div className="flex space-x-4">
                    {[1, 2, 3].map((i) => (
                        <div key={i} className="bg-gray-200 rounded-2xl p-6 w-80 h-64 animate-pulse" />
                    ))}
                </div>
            </div>
        );
    }

    if (recommendedStudents.length === 0) {
        return (
            <div className="space-y-4">
                <h2 className="text-2xl font-bold text-gray-800">Recommended Students For You</h2>
                <div className="bg-white rounded-2xl p-12 text-center shadow-sm border border-gray-100">
                    <Users className="h-12 w-12 text-gray-300 mx-auto mb-4" />
                    <h3 className="text-lg font-medium text-gray-800 mb-2">No Recommendations Yet</h3>
                    <p className="text-gray-600">We're working on finding the perfect students for you!</p>
                </div>
            </div>
        );
    }

    return (
        <div className="space-y-4">
            {/* Header */}
            <div className="flex items-center justify-between">
                <h2 className="text-2xl font-bold text-gray-800">Recommended Students For You</h2>
                <div className="flex items-center space-x-2">
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={scrollLeft}
                        disabled={scrollPosition === 0}
                        className="w-8 h-8 p-0"
                    >
                        <ChevronLeft className="h-4 w-4" />
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={scrollRight}
                        className="w-8 h-8 p-0"
                    >
                        <ChevronRight className="h-4 w-4" />
                    </Button>
                </div>
            </div>

            {/* Horizontal Scroll Container */}
            <div className="relative">
                {recommendedStudents.length > 0 ? (
                    <div
                        id="recommended-students-scroll"
                        className="flex space-x-4 overflow-x-auto scrollbar-hide pb-4"
                        onScroll={handleScroll}
                    >
                        {recommendedStudents.map((recommendation) => (
                            <div key={recommendation.id} className="flex-shrink-0 w-80">
                                <StudentSessionRequestCard
                                    student={recommendation.student}
                                    request={recommendation.request}
                                    onChat={() => {
                                        console.log('Chat with:', recommendation.student.id);
                                        // Open chat with student
                                    }}
                                    onVideoCall={() => {
                                        console.log('Video call with:', recommendation.student.id);
                                        // Initiate video call
                                    }}
                                    className="h-full"
                                />
                            </div>
                        ))}
                    </div>
                ) : (
                    <div className="text-center py-12">
                        <div className="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <Users className="w-8 h-8 text-gray-400" />
                        </div>
                        <h3 className="text-lg font-medium text-gray-900 mb-2">No Students Found</h3>
                        <p className="text-gray-500 mb-4">
                            No students are currently requesting sessions that match your teaching subjects.
                        </p>
                        <p className="text-sm text-gray-400">
                            Check back later or update your teaching subjects to see more recommendations.
                        </p>
                    </div>
                )}
            </div>

            {/* Compatibility Info (Optional) */}
            {recommendedStudents.length > 0 && (
                <div className="text-sm text-gray-600">
                    <p>
                        Showing {recommendedStudents.length} students matched based on your teaching subjects, 
                        availability, and experience level.
                    </p>
                </div>
            )}
        </div>
    );
}
