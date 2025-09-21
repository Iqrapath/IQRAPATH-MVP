import React from 'react';
import { Head } from '@inertiajs/react';
import TeacherLayout from '@/layouts/teacher/teacher-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { 
    Search, 
    Filter, 
    Users, 
    MessageCircle, 
    Video, 
    Star,
    Clock,
    BookOpen
} from 'lucide-react';
import { Breadcrumbs } from '@/components/breadcrumbs';

const breadcrumbs = [
    { title: 'Dashboard', href: '/teacher/dashboard' },
    { title: 'Students', href: '/teacher/students' }
];

interface Student {
    id: number;
    name: string;
    avatar?: string;
    level: string;
    sessionsCompleted: number;
    progress: number;
    rating: number;
    lastActive: string;
    subjects: string[];
    nextSession?: {
        date: string;
        time: string;
        subject: string;
    };
}

interface TeacherStudentsProps {
    students: Student[];
}

export default function TeacherStudents({ students = [] }: TeacherStudentsProps) {
    const getInitials = (name: string) => {
        return name
            .split(' ')
            .map(word => word.charAt(0))
            .join('')
            .toUpperCase()
            .slice(0, 2);
    };

    const formatLastActive = (lastActive: string) => {
        const date = new Date(lastActive);
        const now = new Date();
        const diffInHours = Math.floor((now.getTime() - date.getTime()) / (1000 * 60 * 60));
        
        if (diffInHours < 1) return 'Just now';
        if (diffInHours < 24) return `${diffInHours}h ago`;
        const diffInDays = Math.floor(diffInHours / 24);
        if (diffInDays < 7) return `${diffInDays}d ago`;
        return date.toLocaleDateString();
    };

    return (
        <TeacherLayout pageTitle="My Students">
            <Head title="My Students" />
            
            <div className="container py-6 space-y-6">
                <div className="mb-8">
                    <Breadcrumbs breadcrumbs={breadcrumbs} />
                </div>

                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900 mb-2">
                            My Students
                        </h1>
                        <p className="text-gray-600">
                            Manage and track your students' progress and upcoming sessions.
                        </p>
                    </div>
                    
                    <div className="flex items-center gap-3">
                        <Button
                            variant="outline"
                            size="sm"
                            className="flex items-center gap-2"
                        >
                            <Filter className="h-4 w-4" />
                            Filter
                        </Button>
                        
                        <Button
                            variant="outline"
                            size="sm"
                            className="flex items-center gap-2"
                        >
                            <Search className="h-4 w-4" />
                            Search
                        </Button>
                    </div>
                </div>

                {/* Stats Cards */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-center">
                                <div className="p-2 bg-blue-100 rounded-lg">
                                    <Users className="h-6 w-6 text-blue-600" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Total Students</p>
                                    <p className="text-2xl font-bold text-gray-900">{students.length}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    
                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-center">
                                <div className="p-2 bg-green-100 rounded-lg">
                                    <BookOpen className="h-6 w-6 text-green-600" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Sessions Completed</p>
                                    <p className="text-2xl font-bold text-gray-900">
                                        {students.reduce((sum, student) => sum + student.sessionsCompleted, 0)}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    
                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-center">
                                <div className="p-2 bg-yellow-100 rounded-lg">
                                    <Star className="h-6 w-6 text-yellow-600" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Average Rating</p>
                                    <p className="text-2xl font-bold text-gray-900">
                                        {students.length > 0 
                                            ? (students.reduce((sum, student) => sum + student.rating, 0) / students.length).toFixed(1)
                                            : '0.0'
                                        }
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    
                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-center">
                                <div className="p-2 bg-purple-100 rounded-lg">
                                    <Clock className="h-6 w-6 text-purple-600" />
                                </div>
                                <div className="ml-4">
                                    <p className="text-sm font-medium text-gray-600">Active Students</p>
                                    <p className="text-2xl font-bold text-gray-900">
                                        {students.filter(student => {
                                            const lastActive = new Date(student.lastActive);
                                            const now = new Date();
                                            const diffInDays = Math.floor((now.getTime() - lastActive.getTime()) / (1000 * 60 * 60 * 24));
                                            return diffInDays <= 7;
                                        }).length}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Students List */}
                <Card>
                    <CardHeader>
                        <CardTitle>Student List</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {students.length > 0 ? (
                            <div className="space-y-4">
                                {students.map((student) => (
                                    <div key={student.id} className="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                        <div className="flex items-center space-x-4">
                                            <Avatar className="h-12 w-12">
                                                <AvatarImage src={student.avatar} alt={student.name} />
                                                <AvatarFallback className="bg-teal-100 text-teal-600">
                                                    {getInitials(student.name)}
                                                </AvatarFallback>
                                            </Avatar>
                                            
                                            <div className="flex-1">
                                                <div className="flex items-center space-x-2 mb-1">
                                                    <h3 className="text-lg font-semibold text-gray-900">{student.name}</h3>
                                                    <Badge variant="secondary" className="text-xs">
                                                        {student.level}
                                                    </Badge>
                                                </div>
                                                
                                                <div className="flex items-center space-x-4 text-sm text-gray-600">
                                                    <span className="flex items-center">
                                                        <BookOpen className="h-4 w-4 mr-1" />
                                                        {student.sessionsCompleted} sessions
                                                    </span>
                                                    <span className="flex items-center">
                                                        <Star className="h-4 w-4 mr-1" />
                                                        {student.rating}/5
                                                    </span>
                                                    <span className="flex items-center">
                                                        <Clock className="h-4 w-4 mr-1" />
                                                        {formatLastActive(student.lastActive)}
                                                    </span>
                                                </div>
                                                
                                                <div className="mt-2">
                                                    <div className="flex items-center space-x-2">
                                                        <span className="text-sm text-gray-600">Progress:</span>
                                                        <div className="flex-1 bg-gray-200 rounded-full h-2">
                                                            <div 
                                                                className="bg-teal-600 h-2 rounded-full transition-all duration-300"
                                                                style={{ width: `${student.progress}%` }}
                                                            ></div>
                                                        </div>
                                                        <span className="text-sm text-gray-600">{student.progress}%</span>
                                                    </div>
                                                </div>
                                                
                                                {student.subjects && student.subjects.length > 0 && (
                                                    <div className="mt-2">
                                                        <div className="flex flex-wrap gap-1">
                                                            {student.subjects.slice(0, 3).map((subject, index) => (
                                                                <Badge key={index} variant="outline" className="text-xs">
                                                                    {subject}
                                                                </Badge>
                                                            ))}
                                                            {student.subjects.length > 3 && (
                                                                <Badge variant="outline" className="text-xs">
                                                                    +{student.subjects.length - 3} more
                                                                </Badge>
                                                            )}
                                                        </div>
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                        
                                        <div className="flex items-center space-x-2">
                                            {student.nextSession && (
                                                <div className="text-right mr-4">
                                                    <p className="text-sm font-medium text-gray-900">Next Session</p>
                                                    <p className="text-xs text-gray-600">{student.nextSession.date}</p>
                                                    <p className="text-xs text-gray-600">{student.nextSession.time}</p>
                                                </div>
                                            )}
                                            
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                className="flex items-center space-x-1"
                                            >
                                                <MessageCircle className="h-4 w-4" />
                                                <span>Chat</span>
                                            </Button>
                                            
                                            <Button
                                                size="sm"
                                                className="bg-teal-600 hover:bg-teal-700 text-white flex items-center space-x-1"
                                            >
                                                <Video className="h-4 w-4" />
                                                <span>Call</span>
                                            </Button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="text-center py-12">
                                <Users className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                                <h3 className="text-lg font-medium text-gray-900 mb-2">No students yet</h3>
                                <p className="text-gray-600 mb-4">
                                    You don't have any students yet. Start by accepting booking requests.
                                </p>
                                <Button className="bg-teal-600 hover:bg-teal-700 text-white">
                                    View Requests
                                </Button>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </TeacherLayout>
    );
}
