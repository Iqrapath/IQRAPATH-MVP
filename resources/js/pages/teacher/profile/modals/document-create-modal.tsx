import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { X, Upload, FileText } from 'lucide-react';
import { useForm } from '@inertiajs/react';
import { toast } from 'sonner';

interface DocumentCreateModalProps {
    isOpen: boolean;
    onClose: () => void;
    documentType: 'certificate' | 'id_verification' | 'resume';
}

export default function DocumentCreateModal({ 
    isOpen, 
    onClose, 
    documentType 
}: DocumentCreateModalProps) {
    const { data, setData, post, processing, errors } = useForm({
        type: documentType,
        name: '',
        document: null as File | null,
        issuer: '',
        issue_date: '',
        side: 'front' as 'front' | 'back',
    });

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            setData('document', file);
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        
        post(route('teacher.documents.store'), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Document uploaded successfully!', {
                    description: 'Your document is pending verification.',
                });
                onClose();
            },
            onError: (errors) => {
                toast.error('Failed to upload document', {
                    description: Object.values(errors).flat().join(', '),
                });
            },
        });
    };

    const getDocumentTypeLabel = (type: string) => {
        switch (type) {
            case 'certificate':
                return 'Certificate';
            case 'id_verification':
                return 'ID Verification';
            case 'resume':
                return 'Resume';
            default:
                return 'Document';
        }
    };

    const getAllowedFileTypes = (type: string) => {
        switch (type) {
            case 'certificate':
                return '.jpg,.jpeg,.png,.pdf';
            case 'id_verification':
                return '.jpg,.jpeg,.png,.pdf';
            case 'resume':
                return '.pdf,.doc,.docx';
            default:
                return '.pdf';
        }
    };

    const getMaxFileSize = (type: string) => {
        switch (type) {
            case 'certificate':
            case 'id_verification':
                return 5; // 5MB
            case 'resume':
                return 10; // 10MB
            default:
                return 5;
        }
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black/20 backdrop-blur-sm flex items-center justify-center z-50">
            <div className="bg-white rounded-xl p-6 w-full max-w-md mx-4 max-h-[90vh] overflow-y-auto">
                {/* Header */}
                <div className="flex justify-between items-start mb-6">
                    <div>
                        <h2 className="text-xl font-semibold text-gray-900">Upload {getDocumentTypeLabel(documentType)}</h2>
                        <p className="text-sm text-gray-600 mt-1">Upload your {documentType.replace('_', ' ')} document</p>
                    </div>
                    <button
                        onClick={onClose}
                        className="text-gray-500 hover:text-gray-700"
                    >
                        <X className="h-5 w-5" />
                    </button>
                </div>

                {/* Form */}
                <form onSubmit={handleSubmit} className="space-y-4">
                    {/* Document Name */}
                    <div>
                        <Label htmlFor="name" className="text-sm font-medium text-gray-700">
                            Document Name
                        </Label>
                        <Input
                            id="name"
                            type="text"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            placeholder={`Enter ${documentType.replace('_', ' ')} name`}
                            className="mt-1"
                        />
                        {errors.name && (
                            <p className="text-red-500 text-xs mt-1">{errors.name}</p>
                        )}
                    </div>

                    {/* File Upload */}
                    <div>
                        <Label className="text-sm font-medium text-gray-700">
                            Document File
                        </Label>
                        <div className="mt-1">
                            <div className="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center">
                                <Upload className="h-8 w-8 text-gray-400 mx-auto mb-2" />
                                <p className="text-sm text-gray-600 mb-2">
                                    Drag and drop your file here, or click to browse
                                </p>
                                <input
                                    type="file"
                                    accept={getAllowedFileTypes(documentType)}
                                    onChange={handleFileChange}
                                    className="hidden"
                                    id="document"
                                />
                                <label
                                    htmlFor="document"
                                    className="inline-block bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 text-sm cursor-pointer"
                                >
                                    Choose File
                                </label>
                                {data.document && (
                                    <div className="mt-2 flex items-center justify-center gap-2">
                                        <FileText className="h-4 w-4 text-green-600" />
                                        <span className="text-sm text-green-600">{data.document.name}</span>
                                    </div>
                                )}
                            </div>
                        </div>
                        {errors.document && (
                            <p className="text-red-500 text-xs mt-1">{errors.document}</p>
                        )}
                        <p className="text-xs text-gray-500 mt-1">
                            Max file size: {getMaxFileSize(documentType)}MB. Allowed formats: {getAllowedFileTypes(documentType)}
                        </p>
                    </div>

                    {/* Certificate-specific fields */}
                    {documentType === 'certificate' && (
                        <>
                            <div>
                                <Label htmlFor="issuer" className="text-sm font-medium text-gray-700">
                                    Issuing Institution
                                </Label>
                                <Input
                                    id="issuer"
                                    type="text"
                                    value={data.issuer}
                                    onChange={(e) => setData('issuer', e.target.value)}
                                    placeholder="e.g., Al-Azhar University"
                                    className="mt-1"
                                />
                                {errors.issuer && (
                                    <p className="text-red-500 text-xs mt-1">{errors.issuer}</p>
                                )}
                            </div>

                            <div>
                                <Label htmlFor="issue_date" className="text-sm font-medium text-gray-700">
                                    Issue Date
                                </Label>
                                <Input
                                    id="issue_date"
                                    type="date"
                                    value={data.issue_date}
                                    onChange={(e) => setData('issue_date', e.target.value)}
                                    className="mt-1"
                                />
                                {errors.issue_date && (
                                    <p className="text-red-500 text-xs mt-1">{errors.issue_date}</p>
                                )}
                            </div>
                        </>
                    )}

                    {/* ID Verification-specific fields */}
                    {documentType === 'id_verification' && (
                        <div>
                            <Label className="text-sm font-medium text-gray-700">
                                ID Side
                            </Label>
                            <div className="mt-1 flex gap-4">
                                <label className="flex items-center">
                                    <input
                                        type="radio"
                                        name="side"
                                        value="front"
                                        checked={data.side === 'front'}
                                        onChange={(e) => setData('side', e.target.value as 'front' | 'back')}
                                        className="mr-2"
                                    />
                                    Front
                                </label>
                                <label className="flex items-center">
                                    <input
                                        type="radio"
                                        name="side"
                                        value="back"
                                        checked={data.side === 'back'}
                                        onChange={(e) => setData('side', e.target.value as 'front' | 'back')}
                                        className="mr-2"
                                    />
                                    Back
                                </label>
                            </div>
                            {errors.side && (
                                <p className="text-red-500 text-xs mt-1">{errors.side}</p>
                            )}
                        </div>
                    )}

                    {/* Buttons */}
                    <div className="flex justify-end gap-3 pt-4">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={onClose}
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            disabled={processing || !data.name || !data.document}
                            className="bg-green-600 text-white hover:bg-green-700"
                        >
                            {processing ? 'Uploading...' : 'Upload Document'}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    );
}
