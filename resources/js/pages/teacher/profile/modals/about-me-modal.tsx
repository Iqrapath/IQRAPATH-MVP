import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { X, User, BookOpen, Languages, GraduationCap, Award, Globe } from 'lucide-react';

interface AboutMeModalProps {
    isOpen: boolean;
    onClose: () => void;
    profile: {
        bio: string;
        experience_years: string;
        languages: string[];
        teaching_type: string;
        teaching_mode: string;
        education: string;
        qualification: string;
    } | null;
    onSave: (data: {
        bio: string;
        experience_years: string;
        languages: string[];
        teaching_type: string;
        teaching_mode: string;
        education: string;
        qualification: string;
    }) => void;
    processing?: boolean;
}

export default function AboutMeModal({ isOpen, onClose, profile, onSave, processing = false }: AboutMeModalProps) {
    const [formData, setFormData] = useState({
        bio: profile?.bio || '',
        experience_years: profile?.experience_years || '',
        languages: profile?.languages || [],
        teaching_type: profile?.teaching_type || '',
        teaching_mode: profile?.teaching_mode || '',
        education: profile?.education || '',
        qualification: profile?.qualification || '',
    });

    const [languageInput, setLanguageInput] = useState('');

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        onSave(formData);
    };

    const addLanguage = () => {
        if (languageInput.trim() && !formData.languages.includes(languageInput.trim())) {
            setFormData(prev => ({
                ...prev,
                languages: [...prev.languages, languageInput.trim()]
            }));
            setLanguageInput('');
        }
    };

    const removeLanguage = (languageToRemove: string) => {
        setFormData(prev => ({
            ...prev,
            languages: prev.languages.filter(lang => lang !== languageToRemove)
        }));
    };

    const handleKeyPress = (e: React.KeyboardEvent) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            addLanguage();
        }
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black/20 backdrop-blur-sm flex items-center justify-center z-50">
            <div className="bg-white rounded-xl p-6 w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
                {/* Header */}
                <div className="flex justify-between items-start mb-6">
                    <div>
                        <h2 className="text-xl font-semibold text-gray-900">About Me Information</h2>
                        <p className="text-sm text-gray-600 mt-1">Tell us about your teaching experience and qualifications</p>
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
                    {/* Bio */}
                    <div>
                        <Label htmlFor="bio" className="text-sm font-medium text-gray-700">
                            Bio
                        </Label>
                        <div className="relative mt-1">
                            <User className="absolute left-3 top-3 h-4 w-4 text-gray-500" />
                            <Textarea
                                id="bio"
                                value={formData.bio}
                                onChange={(e) => setFormData({ ...formData, bio: e.target.value })}
                                placeholder="Tell us about yourself and your teaching experience..."
                                className="pl-10 bg-gray-50 border-gray-200 rounded-lg min-h-[100px]"
                                maxLength={1000}
                            />
                        </div>
                        <p className="text-xs text-gray-500 mt-1">{formData.bio.length}/1000 characters</p>
                    </div>

                    {/* Experience and Teaching Type */}
                    <div className="grid grid-cols-2 gap-4">
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
                                    placeholder="e.g., 5+ years"
                                    className="pl-10 bg-gray-50 border-gray-200 rounded-lg"
                                />
                            </div>
                        </div>
                        <div>
                            <Label htmlFor="teaching_type" className="text-sm font-medium text-gray-700">
                                Teaching Type
                            </Label>
                            <div className="relative mt-1">
                                <BookOpen className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-500" />
                                <Input
                                    id="teaching_type"
                                    type="text"
                                    value={formData.teaching_type}
                                    onChange={(e) => setFormData({ ...formData, teaching_type: e.target.value })}
                                    placeholder="e.g., Hifz, Tajweed, Tafseer"
                                    className="pl-10 bg-gray-50 border-gray-200 rounded-lg"
                                />
                            </div>
                        </div>
                    </div>

                    {/* Teaching Mode and Education */}
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <Label htmlFor="teaching_mode" className="text-sm font-medium text-gray-700">
                                Teaching Mode
                            </Label>
                            <div className="relative mt-1">
                                <Globe className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-500" />
                                <Input
                                    id="teaching_mode"
                                    type="text"
                                    value={formData.teaching_mode}
                                    onChange={(e) => setFormData({ ...formData, teaching_mode: e.target.value })}
                                    placeholder="e.g., Online, In-person, Hybrid"
                                    className="pl-10 bg-gray-50 border-gray-200 rounded-lg"
                                />
                            </div>
                        </div>
                        <div>
                            <Label htmlFor="education" className="text-sm font-medium text-gray-700">
                                Education
                            </Label>
                            <div className="relative mt-1">
                                <GraduationCap className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-500" />
                                <Input
                                    id="education"
                                    type="text"
                                    value={formData.education}
                                    onChange={(e) => setFormData({ ...formData, education: e.target.value })}
                                    placeholder="e.g., Islamic Studies, Arabic Literature"
                                    className="pl-10 bg-gray-50 border-gray-200 rounded-lg"
                                />
                            </div>
                        </div>
                    </div>

                    {/* Qualification */}
                    <div>
                        <Label htmlFor="qualification" className="text-sm font-medium text-gray-700">
                            Qualification
                        </Label>
                        <div className="relative mt-1">
                            <Award className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-500" />
                            <Input
                                id="qualification"
                                type="text"
                                value={formData.qualification}
                                onChange={(e) => setFormData({ ...formData, qualification: e.target.value })}
                                placeholder="e.g., Ijazah in Hifz, Certified Tajweed Teacher"
                                className="pl-10 bg-gray-50 border-gray-200 rounded-lg"
                            />
                        </div>
                    </div>

                    {/* Languages */}
                    <div>
                        <Label htmlFor="languages" className="text-sm font-medium text-gray-700">
                            Languages
                        </Label>
                        <div className="relative mt-1">
                            <Languages className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-500" />
                            <Input
                                id="languages"
                                type="text"
                                value={languageInput}
                                onChange={(e) => setLanguageInput(e.target.value)}
                                onKeyPress={handleKeyPress}
                                placeholder="Type a language and press Enter"
                                className="pl-10 bg-gray-50 border-gray-200 rounded-lg"
                            />
                        </div>
                        {formData.languages.length > 0 && (
                            <div className="flex flex-wrap gap-2 mt-2">
                                {formData.languages.map((language, index) => (
                                    <div
                                        key={index}
                                        className="bg-green-100 text-green-800 px-2 py-1 rounded-full text-sm flex items-center gap-1"
                                    >
                                        {language}
                                        <button
                                            type="button"
                                            onClick={() => removeLanguage(language)}
                                            className="text-green-600 hover:text-green-800"
                                        >
                                            <X className="h-3 w-3" />
                                        </button>
                                    </div>
                                ))}
                            </div>
                        )}
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
