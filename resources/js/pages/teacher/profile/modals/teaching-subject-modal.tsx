import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { X, BookOpen, Award, Upload } from 'lucide-react';

interface Subject {
    id: number;
    name: string;
    is_selected: boolean;
}

interface TeachingSubjectModalProps {
    isOpen: boolean;
    onClose: () => void;
    subjects: Subject[];
    experience_years: string;
    onSave: (data: {
        subjects: string[];
        experience_years: string;
    }) => void;
    processing?: boolean;
}



export default function TeachingSubjectModal({ 
    isOpen, 
    onClose, 
    subjects, 
    experience_years, 
    onSave, 
    processing = false 
}: TeachingSubjectModalProps) {
    const [formData, setFormData] = useState({
        subjects: subjects.map(s => s.name),
        experience_years: experience_years || '',
    });

    const [newSubject, setNewSubject] = useState('');

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        onSave(formData);
    };

    const addSubject = () => {
        if (newSubject.trim() && !formData.subjects.includes(newSubject.trim())) {
            setFormData(prev => ({
                ...prev,
                subjects: [...prev.subjects, newSubject.trim()]
            }));
            setNewSubject('');
        }
    };

    const removeSubject = (subjectToRemove: string) => {
        setFormData(prev => ({
            ...prev,
            subjects: prev.subjects.filter(subject => subject !== subjectToRemove)
        }));
    };

    const handleKeyPress = (e: React.KeyboardEvent) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            addSubject();
        }
    };



    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black/20 backdrop-blur-sm flex items-center justify-center z-50">
            <div className="bg-white rounded-xl p-6 w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
                {/* Header */}
                <div className="flex justify-between items-start mb-6">
                    <div>
                        <h2 className="text-xl font-semibold text-gray-900">Teaching Subjects & Expertise</h2>
                        <p className="text-sm text-gray-600 mt-1">Select your teaching subjects and provide experience details</p>
                    </div>
                    <button
                        onClick={onClose}
                        className="text-gray-500 hover:text-gray-700"
                    >
                        <X className="h-5 w-5" />
                    </button>
                </div>

                {/* Form */}
                <form onSubmit={handleSubmit} className="space-y-6">
                                         {/* Subjects */}
                     <div>
                         <Label className="text-sm font-medium text-gray-700 mb-3 block">
                             Teaching Subjects
                         </Label>
                         
                         {/* Add New Subject */}
                         <div className="flex gap-2 mb-4">
                             <Input
                                 type="text"
                                 value={newSubject}
                                 onChange={(e) => setNewSubject(e.target.value)}
                                 onKeyPress={handleKeyPress}
                                 placeholder="Type a subject and press Enter"
                                 className="flex-1"
                             />
                             <Button
                                 type="button"
                                 onClick={addSubject}
                                 variant="outline"
                                 size="sm"
                                 className="text-green-600 border-green-600 hover:bg-green-50"
                             >
                                 Add
                             </Button>
                         </div>
                         
                         {/* Current Subjects */}
                         {formData.subjects.length > 0 && (
                             <div className="flex flex-wrap gap-2 mb-4">
                                 {formData.subjects.map((subject, index) => (
                                     <div
                                         key={index}
                                         className="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm flex items-center gap-2"
                                     >
                                         {subject}
                                         <button
                                             type="button"
                                             onClick={() => removeSubject(subject)}
                                             className="text-green-600 hover:text-green-800"
                                         >
                                             <X className="h-3 w-3" />
                                         </button>
                                     </div>
                                 ))}
                             </div>
                         )}
                     </div>

                    {/* Experience Years */}
                    <div>
                        <Label htmlFor="experience_years" className="text-sm font-medium text-gray-700">
                            Years of Experience
                        </Label>
                        <div className="relative mt-1">
                            <BookOpen className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-500" />
                            <Input
                                id="experience_years"
                                type="text"
                                value={formData.experience_years}
                                onChange={(e) => setFormData({ ...formData, experience_years: e.target.value })}
                                placeholder="e.g., 10+ Years"
                                className="pl-10 bg-gray-50 border-gray-200 rounded-lg"
                            />
                        </div>
                    </div>
                    {/* Save Button */}
                    <div className="flex justify-end pt-4">
                        <Button
                            type="submit"
                            disabled={processing}
                            className="bg-green-600 text-white hover:bg-green-700 rounded-lg px-6 py-2"
                        >
                            {processing ? 'Saving...' : 'Save and Continue'}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    );
}
