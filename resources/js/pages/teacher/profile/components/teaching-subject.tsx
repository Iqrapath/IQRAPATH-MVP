import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Edit, Upload, Eye, Plus } from 'lucide-react';
import { useForm } from '@inertiajs/react';
import { toast } from 'sonner';
import TeachingSubjectModal from '../modals/teaching-subject-modal';
import DocumentDisplayModal from '../modals/document-display-modal';
import DocumentCreateModal from '../modals/document-create-modal';

interface Subject {
    id: number;
    name: string;
    is_selected: boolean;
}

interface TeachingSubjectProps {
    subjects: Subject[];
    experience_years: string;
    documents: {
        certificates: Array<{
            id: number;
            name: string;
            status: string;
            metadata: {
                issuer?: string;
                issue_date?: string;
            };
            created_at: string;
            verified_at?: string;
            rejection_reason?: string;
        }>;
    };
}



export default function TeachingSubject({ 
    subjects, 
    experience_years, 
    documents
}: TeachingSubjectProps) {
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [isDocumentDisplayOpen, setIsDocumentDisplayOpen] = useState(false);
    const [isDocumentCreateOpen, setIsDocumentCreateOpen] = useState(false);
    const [currentSubjects, setCurrentSubjects] = useState(subjects);
    const [currentExperience, setCurrentExperience] = useState(experience_years);

    const { data, setData, put, processing } = useForm({
        subjects: subjects.map(s => s.name),
        experience_years: experience_years || '',
    });

    const handleSave = (formData: {
        subjects: string[];
        experience_years: string;
    }) => {
        setData(formData);
        put(route('teacher.profile.update-subjects'), {
            preserveScroll: true,
            onSuccess: () => {
                setIsModalOpen(false);
                // Update the local state immediately for optimistic UI update
                setCurrentSubjects(formData.subjects.map((name, index) => ({
                    id: index + 1,
                    name: name,
                    is_selected: true
                })));
                setCurrentExperience(formData.experience_years);
                // Show success toast
                toast.success('Teaching subjects updated successfully!', {
                    description: 'Your expertise information has been saved.',
                });
            },
            onError: (errors) => {
                // Show error toast
                toast.error('Failed to update teaching subjects', {
                    description: Object.values(errors).flat().join(', '),
                });
            },
        });
    };

    return (
        <>
            <div className="bg-white rounded-xl shadow-md border">
                <div className="p-6">
                    <h3 className="text-lg font-semibold text-gray-900 mb-6">Teaching Subjects & Expertise</h3>
                    
                    {/* Subjects Section */}
                    <div className="mb-8">
                        <label className="text-sm font-medium text-gray-600 mb-4 block">Subjects</label>
                        <div className="flex flex-wrap gap-6">
                            {currentSubjects.length > 0 ? (
                                currentSubjects.map((subject) => (
                                    <div key={subject.id} className="flex items-center space-x-3">
                                        <div className="w-4 h-4 bg-green-600 rounded flex items-center justify-center">
                                            <div className="w-2 h-2 bg-white rounded-sm"></div>
                                        </div>
                                        <span className="text-sm font-medium text-gray-900">
                                            {subject.name}
                                        </span>
                                    </div>
                                ))
                            ) : (
                                <div className="text-gray-500 text-sm">No subjects added yet</div>
                            )}
                            

                        </div>
                    </div>

                    {/* Experience and Certifications Section */}
                    <div className="grid grid-cols-2 gap-8">
                        {/* Experience */}
                        <div>
                            <label className="text-sm font-medium text-gray-600 block mb-2">Experience:</label>
                            <p className="text-base font-semibold text-gray-900">
                                {currentExperience || 'Not specified'}
                            </p>
                        </div>
                        
                        {/* Certifications */}
                        <div>
                            <div className="flex items-center justify-between mb-2">
                                <label className="text-sm font-medium text-gray-600">Certifications:</label>
                                <div className="flex items-center gap-3">
                                    <button
                                        type="button"
                                        onClick={() => setIsDocumentDisplayOpen(true)}
                                        className="text-blue-600 hover:text-blue-700 text-sm font-medium flex items-center gap-1"
                                    >
                                        <Eye className="h-3 w-3" />
                                        View Documents
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setIsDocumentCreateOpen(true)}
                                        className="text-green-600 hover:text-green-700 text-sm font-medium flex items-center gap-1"
                                    >
                                        <Plus className="h-3 w-3" />
                                        Upload Certificate
                                    </button>
                                </div>
                            </div>
                            
                            {/* Certifications Display */}
                            <div className="space-y-3">
                                {documents.certificates.length > 0 ? (
                                    documents.certificates.map((cert) => (
                                        <div key={cert.id} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg border">
                                            <div className="flex-1">
                                                <p className="text-sm font-medium text-gray-900">
                                                    {cert.name}
                                                </p>
                                                {cert.metadata?.issuer && (
                                                    <p className="text-xs text-gray-600">
                                                        Issuer: {cert.metadata.issuer}
                                                    </p>
                                                )}
                                                {cert.metadata?.issue_date && (
                                                    <p className="text-xs text-gray-600">
                                                        Date: {cert.metadata.issue_date}
                                                    </p>
                                                )}
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <span className={`text-xs px-2 py-1 rounded-full ${
                                                    cert.status === 'verified' 
                                                        ? 'bg-green-100 text-green-800' 
                                                        : cert.status === 'pending'
                                                        ? 'bg-yellow-100 text-yellow-800'
                                                        : 'bg-red-100 text-red-800'
                                                }`}>
                                                    {cert.status}
                                                </span>
                                                <a
                                                    href={route('teacher.documents.download', cert.id)}
                                                    className="text-blue-600 hover:text-blue-800 text-xs"
                                                    title="Download certificate"
                                                >
                                                    Download
                                                </a>
                                            </div>
                                        </div>
                                    ))
                                ) : (
                                    <div className="text-center py-6 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                                        <Upload className="h-8 w-8 text-gray-400 mx-auto mb-2" />
                                        <p className="text-sm text-gray-600 mb-3">
                                            No certification documents uploaded
                                        </p>
                                        <button
                                            type="button"
                                            onClick={() => setIsDocumentCreateOpen(true)}
                                            className="text-green-600 hover:text-green-700 text-sm font-medium"
                                        >
                                            Upload your first certificate
                                        </button>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Edit Button */}
                    <div className="flex justify-end mt-6 pt-4 border-t border-gray-100">
                        <Button
                            variant="outline"
                            size="sm"
                            className="text-green-600 border-green-600 hover:bg-green-50"
                            onClick={() => setIsModalOpen(true)}
                        >
                            <Edit className="h-4 w-4 mr-2" />
                            Edit
                        </Button>
                    </div>
                </div>
            </div>

            {/* Teaching Subject Modal */}
            <TeachingSubjectModal
                isOpen={isModalOpen}
                onClose={() => setIsModalOpen(false)}
                subjects={subjects}
                experience_years={experience_years}
                onSave={handleSave}
                processing={processing}
            />

            {/* Document Display Modal */}
            <DocumentDisplayModal
                isOpen={isDocumentDisplayOpen}
                onClose={() => setIsDocumentDisplayOpen(false)}
                documents={documents.certificates}
                documentType="certificate"
            />

            {/* Document Create Modal */}
            <DocumentCreateModal
                isOpen={isDocumentCreateOpen}
                onClose={() => setIsDocumentCreateOpen(false)}
                documentType="certificate"
            />
        </>
    );
}
