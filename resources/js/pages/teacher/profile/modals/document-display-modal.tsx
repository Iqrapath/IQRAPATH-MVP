import { X, Download, Eye, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useForm } from '@inertiajs/react';
import { toast } from 'sonner';

interface Document {
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
}

interface DocumentDisplayModalProps {
    isOpen: boolean;
    onClose: () => void;
    documents: Document[];
    documentType: 'certificate' | 'id_verification' | 'resume';
}

export default function DocumentDisplayModal({ 
    isOpen, 
    onClose, 
    documents, 
    documentType 
}: DocumentDisplayModalProps) {
    const { delete: deleteDocument, processing } = useForm();

    const handleDeleteDocument = (documentId: number) => {
        if (confirm('Are you sure you want to delete this document? This action cannot be undone.')) {
            deleteDocument(route('teacher.documents.destroy', documentId), {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success('Document deleted successfully!', {
                        description: 'The document has been removed from your profile.',
                    });
                },
                onError: (errors) => {
                    toast.error('Failed to delete document', {
                        description: Object.values(errors).flat().join(', '),
                    });
                },
            });
        }
    };

    if (!isOpen) return null;

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'verified':
                return 'bg-green-100 text-green-800';
            case 'pending':
                return 'bg-yellow-100 text-yellow-800';
            case 'rejected':
                return 'bg-red-100 text-red-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    };

    const getDocumentTypeLabel = (type: string) => {
        switch (type) {
            case 'certificate':
                return 'Certificates';
            case 'id_verification':
                return 'ID Verifications';
            case 'resume':
                return 'Resume';
            default:
                return 'Documents';
        }
    };

    return (
        <div className="fixed inset-0 bg-black/20 backdrop-blur-sm flex items-center justify-center z-50">
            <div className="bg-white rounded-xl p-6 w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
                {/* Header */}
                <div className="flex justify-between items-start mb-6">
                    <div>
                        <h2 className="text-xl font-semibold text-gray-900">{getDocumentTypeLabel(documentType)}</h2>
                        <p className="text-sm text-gray-600 mt-1">View and manage your uploaded documents</p>
                    </div>
                    <button
                        onClick={onClose}
                        className="text-gray-500 hover:text-gray-700"
                    >
                        <X className="h-5 w-5" />
                    </button>
                </div>

                {/* Documents List */}
                <div className="space-y-4">
                    {documents.length > 0 ? (
                        documents.map((doc) => (
                            <div key={doc.id} className="border border-gray-200 rounded-lg p-4">
                                <div className="flex items-start justify-between">
                                    <div className="flex-1">
                                        <h3 className="text-lg font-medium text-gray-900 mb-2">
                                            {doc.name}
                                        </h3>
                                        
                                        {/* Metadata */}
                                        {doc.metadata?.issuer && (
                                            <p className="text-sm text-gray-600 mb-1">
                                                <span className="font-medium">Issuer:</span> {doc.metadata.issuer}
                                            </p>
                                        )}
                                        {doc.metadata?.issue_date && (
                                            <p className="text-sm text-gray-600 mb-1">
                                                <span className="font-medium">Issue Date:</span> {doc.metadata.issue_date}
                                            </p>
                                        )}
                                        
                                        {/* Status */}
                                        <div className="flex items-center gap-2 mt-3">
                                            <span className={`text-xs px-2 py-1 rounded-full ${getStatusColor(doc.status)}`}>
                                                {doc.status}
                                            </span>
                                            {doc.rejection_reason && (
                                                <p className="text-xs text-red-600">
                                                    Reason: {doc.rejection_reason}
                                                </p>
                                            )}
                                        </div>
                                        
                                        {/* Dates */}
                                        <div className="text-xs text-gray-500 mt-2">
                                            <p>Uploaded: {new Date(doc.created_at).toLocaleDateString()}</p>
                                            {doc.verified_at && (
                                                <p>Verified: {new Date(doc.verified_at).toLocaleDateString()}</p>
                                            )}
                                        </div>
                                    </div>
                                    
                                    {/* Actions */}
                                    <div className="flex items-center gap-2 ml-4">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => window.open(route('teacher.documents.download', doc.id), '_blank')}
                                            className="text-blue-600 border-blue-600 hover:bg-blue-50"
                                        >
                                            <Download className="h-4 w-4 mr-1" />
                                            Download
                                        </Button>
                                        {doc.status === 'pending' && (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => handleDeleteDocument(doc.id)}
                                                disabled={processing}
                                                className="text-red-600 border-red-600 hover:bg-red-50"
                                            >
                                                <Trash2 className="h-4 w-4 mr-1" />
                                                Delete
                                            </Button>
                                        )}
                                    </div>
                                </div>
                            </div>
                        ))
                    ) : (
                        <div className="text-center py-8">
                            <div className="text-gray-400 mb-4">
                                <Eye className="h-12 w-12 mx-auto" />
                            </div>
                            <h3 className="text-lg font-medium text-gray-900 mb-2">No documents uploaded</h3>
                            <p className="text-sm text-gray-600 mb-4">
                                You haven't uploaded any {documentType.replace('_', ' ')} documents yet.
                            </p>
                        </div>
                    )}
                </div>

                {/* Footer */}
                <div className="flex justify-end gap-3 mt-6 pt-4 border-t">
                    <Button
                        variant="outline"
                        onClick={onClose}
                    >
                        Close
                    </Button>
                </div>
            </div>
        </div>
    );
}
