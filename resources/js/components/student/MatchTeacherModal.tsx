/**
 * Match Teacher Modal for Memorization Plans
 * Helps students find the perfect Quran teacher for their memorization journey
 */

import { useState } from 'react';
import { router } from '@inertiajs/react';
import { Dialog, DialogContent, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { X, Star, Clock, DollarSign } from 'lucide-react';
import { toast } from 'sonner';

interface MatchedTeacher {
    id: number;
    name: string;
    image: string | null;
    subjects: string;
    rating: string | number;
    reviews_count: number;
    experience_years: string;
    price_naira: string | number;
    bio: string;
}

interface UserData {
    name: string;
    email?: string | null;
}

interface MatchTeacherModalProps {
    isOpen: boolean;
    onClose: () => void;
    isAuthenticated: boolean;
    user?: UserData;
}

export default function MatchTeacherModal({ isOpen, onClose, isAuthenticated, user }: MatchTeacherModalProps) {
    const [step, setStep] = useState<'form' | 'results'>('form');
    const [isLoading, setIsLoading] = useState(false);
    const [matchedTeachers, setMatchedTeachers] = useState<MatchedTeacher[]>([]);
    
    const [formData, setFormData] = useState({
        name: user?.name || '',
        email: user?.email || '',
        student_age: '',
        preferred_subject: 'Quran Memorization',
        best_time: '',
        memorization_level: '', // New field for plan context
    });

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setIsLoading(true);

        try {
            const response = await fetch('/student/match-teachers', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify(formData),
            });

            const data = await response.json();

            if (data.success) {
                let teachers: MatchedTeacher[] = [];
                if (Array.isArray(data.matched_teachers)) {
                    teachers = data.matched_teachers;
                } else if (data.matched_teachers && typeof data.matched_teachers === 'object') {
                    teachers = Object.values(data.matched_teachers) as MatchedTeacher[];
                }
                
                setMatchedTeachers(teachers);
                setStep('results');
                toast.success(data.message || `Found ${teachers.length} matching teachers!`);
            } else {
                toast.error(data.message || 'No teachers found matching your preferences.');
            }
        } catch (error) {
            console.error('Error matching teachers:', error);
            toast.error('Something went wrong. Please try again.');
        } finally {
            setIsLoading(false);
        }
    };

    const handleBookTeacher = (teacherId: number) => {
        onClose();
        if (isAuthenticated) {
            router.visit(`/student/book-class?teacher_id=${teacherId}`);
        } else {
            router.visit(`/login?redirect=/student/book-class?teacher_id=${teacherId}`);
        }
    };

    const handleBack = () => {
        setStep('form');
        setMatchedTeachers([]);
    };

    const handleClose = () => {
        setStep('form');
        setMatchedTeachers([]);
        // Reset form but keep user data pre-filled
        setFormData({
            name: user?.name || '',
            email: user?.email || '',
            student_age: '',
            preferred_subject: 'Quran Memorization',
            best_time: '',
            memorization_level: '',
        });
        onClose();
    };

    return (
        <Dialog open={isOpen} onOpenChange={handleClose}>
            <DialogContent className={step === 'results' ? 'max-w-[95vw] w-full max-h-[90vh] overflow-y-auto p-0' : 'max-w-5xl max-herflow-y-auto p-0'}>
                <DialogTitle className="sr-only">
                    {step === 'form' ? 'Find Your Perfect Quran Teacher' : 'Your Matched Teachers'}
                </DialogTitle>
                <DialogDescription className="sr-only">
                    {step === 'form' 
                        ? 'Fill out the form to find teachers that match your learning preferences'
                        : `We found ${matchedTeachers.length} teacher(s) perfect for your memorization journey`
                    }
                </DialogDescription>

                {step === 'form' ? (
                    <div className="p-8">
                        <div className="mb-6">
                            <h2 className="text-2xl font-bold text-[#1E293B] mb-2">
                                Find Your Perfect Quran Teacher
                            </h2>
                            <p className="text-[#64748B]">
                                Tell us about your learning preferences and we'll match you with the best teachers for your memorization journey.
                            </p>
                        </div>

                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                {/* Name */}
                                <div>
                                    <Label htmlFor="name">Your Name *</Label>
                                    <Input
                                        id="name"
                                        value={formData.name}
                                        onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                        placeholder="Enter your full name"
                                        required
                                    />
                                </div>

                                {/* Email */}
                                <div>
                                    <Label htmlFor="email">Email Address *</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        value={formData.email}
                                        onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                                        placeholder="your@email.com"
                                        required
                                    />
                                </div>

                                {/* Age */}
                                <div>
                                    <Label htmlFor="age">Student Age *</Label>
                                    <Input
                                        id="age"
                                        type="number"
                                        min="5"
                                        max="100"
                                        value={formData.student_age}
                                        onChange={(e) => setFormData({ ...formData, student_age: e.target.value })}
                                        placeholder="Age"
                                        required
                                    />
                                </div>

                                {/* Memorization Level */}
                                <div>
                                    <Label htmlFor="level">Memorization Goal *</Label>
                                    <Select
                                        value={formData.memorization_level}
                                        onValueChange={(value) => setFormData({ ...formData, memorization_level: value })}
                                        required
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select your goal" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="juz-amma">Juz' Amma (Last Part)</SelectItem>
                                            <SelectItem value="half-quran">Half Quran (15 Juz)</SelectItem>
                                            <SelectItem value="full-quran">Full Quran (30 Juz)</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                {/* Best Time */}
                                <div className="md:col-span-2">
                                    <Label htmlFor="time">Preferred Learning Time *</Label>
                                    <Select
                                        value={formData.best_time}
                                        onValueChange={(value) => setFormData({ ...formData, best_time: value })}
                                        required
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select preferred time" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="morning">Morning (6 AM - 12 PM)</SelectItem>
                                            <SelectItem value="afternoon">Afternoon (12 PM - 5 PM)</SelectItem>
                                            <SelectItem value="evening">Evening (5 PM - 10 PM)</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <div className="flex justify-end gap-3 pt-4">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={handleClose}
                                >
                                    Cancel
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={isLoading}
                                    className="bg-[#14B8A6] hover:bg-[#129c8e] text-white"
                                >
                                    {isLoading ? 'Finding Teachers...' : 'Find My Teacher'}
                                </Button>
                            </div>
                        </form>
                    </div>
                ) : (
                    <div className="p-8">
                        <div className="mb-6">
                            <button
                                onClick={handleBack}
                                className="text-[#14B8A6] hover:text-[#129c8e] font-medium mb-4"
                            >
                                ← Back to form
                            </button>
                            <h2 className="text-2xl font-bold text-[#1E293B] mb-2">
                                Your Matched Teachers
                            </h2>
                            <p className="text-[#64748B]">
                                We found {matchedTeachers.length} teacher(s) perfect for your memorization journey!
                            </p>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-6">
                            {matchedTeachers.map((teacher) => (
                                <div
                                    key={teacher.id}
                                    className="bg-white border border-gray-200 rounded-xl p-6 hover:shadow-lg transition-shadow"
                                >
                                    <div className="flex items-start gap-4 mb-4">
                                        <div className="w-16 h-16 rounded-full bg-gray-200 flex items-center justify-center overflow-hidden flex-shrink-0">
                                            {teacher.image ? (
                                                <img src={teacher.image} alt={teacher.name} className="w-full h-full object-cover" />
                                            ) : (
                                                <span className="text-xl font-bold text-gray-600">
                                                    {teacher.name.split(' ').map(n => n[0]).join('').substring(0, 2)}
                                                </span>
                                            )}
                                        </div>
                                        <div className="flex-1">
                                            <h3 className="font-semibold text-[#1E293B] text-lg">{teacher.name}</h3>
                                            <p className="text-sm text-[#64748B]">{teacher.subjects}</p>
                                        </div>
                                    </div>

                                    <div className="space-y-2 mb-4">
                                        <div className="flex items-center justify-between text-sm">
                                            <div className="flex items-center gap-1 text-[#64748B]">
                                                <Star className="w-4 h-4" />
                                                <span>Rating</span>
                                            </div>
                                            <div className="flex items-center gap-1">
                                                <span className="text-yellow-400">★</span>
                                                <span className="font-medium">{Number(teacher.rating).toFixed(1)}</span>
                                                <span className="text-[#64748B]">({teacher.reviews_count})</span>
                                            </div>
                                        </div>

                                        <div className="flex items-center justify-between text-sm">
                                            <div className="flex items-center gap-1 text-[#64748B]">
                                                <Clock className="w-4 h-4" />
                                                <span>Experience</span>
                                            </div>
                                            <span className="font-medium">{teacher.experience_years}</span>
                                        </div>

                                        <div className="flex items-center justify-between text-sm">
                                            <div className="flex items-center gap-1 text-[#64748B]">
                                                <DollarSign className="w-4 h-4" />
                                                <span>Rate</span>
                                            </div>
                                            <span className="font-medium">₦{Number(teacher.price_naira).toLocaleString()}/hr</span>
                                        </div>
                                    </div>

                                    {teacher.bio && (
                                        <p className="text-sm text-[#64748B] mb-4 line-clamp-2">{teacher.bio}</p>
                                    )}

                                    <Button
                                        onClick={() => handleBookTeacher(teacher.id)}
                                        className="w-full bg-[#14B8A6] hover:bg-[#129c8e] text-white"
                                    >
                                        Book This Teacher
                                    </Button>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </DialogContent>
        </Dialog>
    );
}
