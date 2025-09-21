import React, { useRef, useState } from 'react';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { MoreVertical, Eye, Download, Upload, ShieldCheck, OctagonX } from 'lucide-react';
import { IdCardIcon } from '@/components/icons/id-card-icon';
import { CertificateIcon } from '@/components/icons/Certificate-icon';
import { ResumeIcon } from '@/components/icons/Resume-icon';
import { VerifiedIcon } from '@/components/icons/verified-icon';
import { router } from '@inertiajs/react';
import { toast } from 'sonner';

interface FlatDocument {
    id: number | string;
    type: string; // id_verification | certificate | resume
    name: string;
    status: 'pending' | 'verified' | 'rejected';
    url?: string;
}

interface GroupedDocument {
    id: number | string;
    name: string;
    status: 'pending' | 'verified' | 'rejected';
    metadata?: any;
    documentUrl?: string;
}

interface DocumentsGrouped {
    id_verifications: GroupedDocument[];
    certificates: GroupedDocument[];
    resume: GroupedDocument | null;
}

interface Props {
    documentsFlat: FlatDocument[];
    documentsGrouped: DocumentsGrouped;
    teacherId: number;
}

export default function VerificationDocumentsSection({ documentsFlat, documentsGrouped, teacherId }: Props) {
    // Local state management
    const [localDocumentsFlat, setLocalDocumentsFlat] = useState<FlatDocument[]>(documentsFlat);
    const [localDocumentsGrouped, setLocalDocumentsGrouped] = useState<DocumentsGrouped>(documentsGrouped);
    const [isUploading, setIsUploading] = useState(false);
    const [processingIds, setProcessingIds] = useState<Set<string | number>>(new Set());

    const statusDot = (status: string) => {
        const color = status === 'verified' ? 'bg-green-500' : status === 'pending' ? 'bg-yellow-500' : 'bg-red-500';
        return <span className={`inline-block h-2.5 w-2.5 rounded-full ${color}`}></span>;
    };

    const getStatusChip = (status?: 'pending' | 'verified' | 'rejected') => {
        if (!status) return null;
        if (status === 'verified') return <span className="inline-flex items-center gap-1 text-xs text-green-700"><VerifiedIcon className="h-3 w-3 text-green-600" /> Verified</span>;
        if (status === 'rejected') return <span className="inline-flex items-center gap-1 text-xs text-red-700">Rejected</span>;
        return <span className="inline-flex items-center gap-1 text-xs text-yellow-700">Pending</span>;
    };

    const getIdFront = localDocumentsGrouped.id_verifications.find(d => d.metadata?.side === 'front');
    const getIdBack = localDocumentsGrouped.id_verifications.find(d => d.metadata?.side === 'back');

    const idHeaderStatus = (() => {
        const docs = [getIdFront?.status, getIdBack?.status].filter(Boolean) as string[];
        if (docs.length === 0) return 'Not Uploaded';
        if (docs.every(s => s === 'verified')) return 'Verified';
        if (docs.some(s => s === 'rejected')) return 'Rejected';
        return 'Pending Verification';
    })();

    // Upload + Verify helpers
    const fileInputRef = useRef<HTMLInputElement>(null);
    const [pendingUpload, setPendingUpload] = useState<{ type: 'id_verification' | 'certificate' | 'resume'; side?: 'front' | 'back' | undefined; documentId?: number | string } | null>(null);

    // Helper function to update document status in local state
    const updateDocumentInLocalState = (documentId: string | number, updates: Partial<FlatDocument | GroupedDocument>) => {
        // Update flat documents
        setLocalDocumentsFlat(prev => 
            prev.map(doc => doc.id === documentId ? { ...doc, ...updates } : doc)
        );

        // Update grouped documents
        setLocalDocumentsGrouped(prev => {
            const newGrouped = { ...prev };
            
            // Update ID verifications
            newGrouped.id_verifications = newGrouped.id_verifications.map(doc => 
                doc.id === documentId ? { ...doc, ...updates } : doc
            );
            
            // Update certificates
            newGrouped.certificates = newGrouped.certificates.map(doc => 
                doc.id === documentId ? { ...doc, ...updates } : doc
            );
            
            // Update resume
            if (newGrouped.resume && newGrouped.resume.id === documentId) {
                newGrouped.resume = { ...newGrouped.resume, ...updates };
            }
            
            return newGrouped;
        });
    };

    // Helper function to add new document to local state
    const addDocumentToLocalState = (newDoc: FlatDocument, groupedDoc: GroupedDocument) => {
        // Add to flat documents
        setLocalDocumentsFlat(prev => [...prev, newDoc]);

        // Add to grouped documents
        setLocalDocumentsGrouped(prev => {
            const newGrouped = { ...prev };
            
            if (newDoc.type === 'id_verification') {
                newGrouped.id_verifications = [...newGrouped.id_verifications, groupedDoc];
            } else if (newDoc.type === 'certificate') {
                newGrouped.certificates = [...newGrouped.certificates, groupedDoc];
            } else if (newDoc.type === 'resume') {
                newGrouped.resume = groupedDoc;
            }
            
            return newGrouped;
        });
    };

    const openUpload = (type: 'id_verification' | 'certificate' | 'resume', side?: 'front' | 'back', documentId?: number | string) => {
        setPendingUpload({ type, side, documentId });
        fileInputRef.current?.click();
    };

    const adminHeaders: HeadersInit = {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
    };

    const onFileSelected: React.ChangeEventHandler<HTMLInputElement> = async (e) => {
        const file = e.target.files?.[0];
        if (!file || !pendingUpload) return;

        setIsUploading(true);
        toast.info(`Uploading ${file.name}...`);

        try {
            const formData = new FormData();
            formData.append('document', file);
            formData.append('type', pendingUpload.type);
            formData.append('teacher_id', String(teacherId));
            formData.append('name', file.name);
            if (pendingUpload.type === 'id_verification' && pendingUpload.side) {
                formData.append('side', pendingUpload.side);
            }
            if (pendingUpload.documentId) {
                formData.append('document_id', String(pendingUpload.documentId));
            }

            const response = await fetch('/admin/documents/upload', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });
            
            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(errorText || 'Upload failed');
            }

            const result = await response.json();
            
            // Create document objects for local state
            const newFlatDoc: FlatDocument = {
                id: result.document.id,
                type: pendingUpload.type,
                name: file.name,
                status: 'pending',
                url: result.document.url || ''
            };

            const newGroupedDoc: GroupedDocument = {
                id: result.document.id,
                name: file.name,
                status: 'pending',
                metadata: pendingUpload.type === 'id_verification' ? { side: pendingUpload.side } : undefined,
                documentUrl: result.document.url || ''
            };

            // Update local state based on whether this is a new upload or replacement
            if (pendingUpload.documentId) {
                // Replace existing document
                updateDocumentInLocalState(pendingUpload.documentId, {
                    id: result.document.id,
                    name: file.name,
                    status: 'pending',
                    url: result.document.url || '',
                    documentUrl: result.document.url || ''
                });
            } else {
                // Add new document
                addDocumentToLocalState(newFlatDoc, newGroupedDoc);
            }

            toast.success(`${file.name} uploaded successfully!`);
            
            // Use Inertia router to refresh page data in background
            router.reload({ only: ['documents', 'documents_grouped'] });
            
        } catch (err) {
            console.error(err);
            toast.error(err instanceof Error ? err.message : 'Upload failed');
        } finally {
            setIsUploading(false);
            setPendingUpload(null);
            if (fileInputRef.current) fileInputRef.current.value = '';
        }
    };

    const verifyDocument = async (documentId?: number | string) => {
        if (!documentId) return;
        
        setProcessingIds(prev => new Set(prev.add(documentId)));
        toast.info('Verifying document...');

        try {
            const response = await fetch(`/admin/documents/${documentId}/verify`, {
                method: 'PATCH',
                headers: adminHeaders,
            });
            
            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(errorText || 'Verification failed');
            }

            // Update local state
            updateDocumentInLocalState(documentId, { status: 'verified' });
            
            toast.success('Document verified successfully!');
            
            // Use Inertia router to refresh page data in background
            router.reload({ only: ['documents', 'documents_grouped', 'verification_status'] });
            
        } catch (err) {
            console.error(err);
            toast.error(err instanceof Error ? err.message : 'Verification failed');
        } finally {
            setProcessingIds(prev => {
                const newSet = new Set(prev);
                newSet.delete(documentId);
                return newSet;
            });
        }
    };

    const rejectDocument = async (documentId?: number | string) => {
        if (!documentId) return;
        const reason = prompt('Enter rejection reason:');
        if (!reason) return;
        
        setProcessingIds(prev => new Set(prev.add(documentId)));
        toast.info('Rejecting document...');

        try {
            const response = await fetch(`/admin/documents/${documentId}/reject`, {
                method: 'PATCH',
                headers: { ...adminHeaders, 'Content-Type': 'application/json' },
                body: JSON.stringify({ rejection_reason: reason, resubmission_instructions: 'Please correct and re-upload.' })
            });
            
            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(errorText || 'Rejection failed');
            }

            // Update local state
            updateDocumentInLocalState(documentId, { status: 'rejected' });
            
            toast.error(`Document rejected. Reason: ${reason}`);
            
            // Use Inertia router to refresh page data in background
            router.reload({ only: ['documents', 'documents_grouped', 'verification_status'] });
            
        } catch (err) {
            console.error(err);
            toast.error(err instanceof Error ? err.message : 'Rejection failed');
        } finally {
            setProcessingIds(prev => {
                const newSet = new Set(prev);
                newSet.delete(documentId);
                return newSet;
            });
        }
    };

    const viewDocument = (doc: FlatDocument) => {
        if (doc.url) {
            window.open(doc.url, '_blank');
        } else {
            window.open(`/admin/documents/${doc.id}/download`, '_blank');
        }
    };

    const downloadDocument = (doc: FlatDocument) => {
        window.open(`/admin/documents/${doc.id}/download`, '_blank');
    };

    const findIdSideByFlat = (flat: FlatDocument): 'front' | 'back' | undefined => {
        const match = localDocumentsGrouped.id_verifications.find(d => d.id === flat.id);
        return match?.metadata?.side;
    };

    const verifyAllCertificates = async () => {
        const ids = (localDocumentsGrouped.certificates || []).filter(c => c.status !== 'verified').map(c => c.id);
        toast.info(`Verifying ${ids.length} certificates...`);
        
        for (const id of ids) {
            await verifyDocument(id);
        }
        
        toast.success('All certificates verified!');
    };

    return (
        <div className="space-y-6">
            {/* Documents Review Panel */}
            <Card>
                <CardContent>


                    <div>
                        <h3 className="text-lg font-semibold text-gray-900 mb-3">Documents Review Panel</h3>
                        <Card className="shadow-sm rounded-2xl">
                            <CardContent className="p-0">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="text-gray-600">Type</TableHead>
                                            <TableHead className="text-gray-600">File</TableHead>
                                            <TableHead className="text-gray-600">Status</TableHead>
                                            <TableHead className="text-gray-600">Actions</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {localDocumentsFlat.map(doc => {
                                            const isProcessing = processingIds.has(doc.id);
                                            return (
                                            <TableRow key={doc.id} className={isProcessing ? 'opacity-50' : ''}>
                                                <TableCell className="whitespace-nowrap">{doc.type === 'id_verification' ? 'ID Card' : doc.type === 'certificate' ? 'Teaching Certificate' : 'Resume/CV'}</TableCell>
                                                <TableCell>
                                                    {doc.url ? (
                                                        <a href={doc.url} target="_blank" rel="noopener noreferrer" className="text-gray-700 hover:underline">
                                                            {doc.name}
                                                        </a>
                                                    ) : (
                                                        <span className="text-gray-700">{doc.name}</span>
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex items-center gap-2">
                                                        {doc.status === 'verified' ? (
                                                            <VerifiedIcon className="h-4 w-4 text-green-600" />
                                                        ) : (
                                                            statusDot(doc.status)
                                                        )}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <DropdownMenu>
                                                        <DropdownMenuTrigger asChild>
                                                            <Button variant="ghost" size="icon" className="h-8 w-8 p-0">
                                                                <MoreVertical className="h-4 w-4" />
                                                            </Button>
                                                        </DropdownMenuTrigger>
                                                        <DropdownMenuContent align="end">
                                                            <DropdownMenuItem onClick={() => viewDocument(doc)}>
                                                                <Eye className="mr-2 h-4 w-4" /> View
                                                            </DropdownMenuItem>
                                                            <DropdownMenuItem onClick={() => downloadDocument(doc)}>
                                                                <Download className="mr-2 h-4 w-4" /> Download
                                                            </DropdownMenuItem>
                                                            <DropdownMenuItem onClick={() => openUpload(
                                                                doc.type as any,
                                                                doc.type === 'id_verification' ? findIdSideByFlat(doc) : undefined,
                                                                doc.status !== 'verified' ? doc.id : undefined
                                                            )} disabled={doc.status === 'verified'}>
                                                                <Upload className="mr-2 h-4 w-4" /> Re-Upload
                                                            </DropdownMenuItem>
                                                            <DropdownMenuItem onClick={() => verifyDocument(doc.id)} disabled={doc.status === 'verified'}>
                                                                <ShieldCheck className="mr-2 h-4 w-4" /> Verify
                                                            </DropdownMenuItem>
                                                            <DropdownMenuItem onClick={() => rejectDocument(doc.id)}>
                                                                <OctagonX className="mr-2 h-4 w-4" /> Reject
                                                            </DropdownMenuItem>
                                                        </DropdownMenuContent>
                                                    </DropdownMenu>
                                                </TableCell>
                                            </TableRow>
                                            );
                                        })}
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Document Section */}
                    <div>
                        <div className="flex items-center justify-between mb-3 mt-3">
                            <h3 className="text-lg font-semibold text-gray-900">Document Section</h3>
                        </div>

                        {/* ID Verification */}
                        <Card className="shadow-sm rounded-2xl">
                            <CardContent className="p-4">
                                <div className="flex items-center justify-between mb-4">
                                    <div className="text-gray-900 font-semibold">ID Verification:</div>
                                    <div className="text-xs text-gray-600">{idHeaderStatus}</div>
                                </div>
                                <div className="grid grid-cols-2 gap-6">
                                    {/* Front */}
                                    <div className="rounded-xl border border-gray-200 p-4 bg-white">
                                        <div className="h-28 rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 flex items-center justify-center">
                                            <IdCardIcon className="text-gray-400" />
                                        </div>
                                        <div className="mt-2 text-sm text-gray-600 flex items-center justify-between">
                                            <span>Document Front</span>
                                            {getStatusChip(getIdFront?.status as any)}
                                        </div>
                                        <div className="mt-1 text-xs text-gray-700 truncate">{getIdFront?.name || 'No file uploaded'}</div>
                                        <div className="mt-2 flex items-center justify-center gap-3 text-xs">
                                            <Button variant="link" className="p-0 h-auto text-gray-600" onClick={() => openUpload('id_verification', 'front', getIdFront?.id)} disabled={getIdFront?.status === 'verified' || isUploading}>Re-Upload</Button>
                                            {!getIdFront && (
                                                <Button variant="link" className="p-0 h-auto text-blue-600" onClick={() => openUpload('id_verification', 'front')} disabled={isUploading}>
                                                    {isUploading ? 'Uploading...' : 'Upload'}
                                                </Button>
                                            )}
                                            {getIdFront?.id && (
                                                <Button variant="link" className="p-0 h-auto text-blue-600" onClick={() => verifyDocument(getIdFront.id)} disabled={getIdFront?.status === 'verified' || processingIds.has(getIdFront?.id || '')}>
                                                    {processingIds.has(getIdFront?.id || '') ? 'Processing...' : 'Verify'}
                                                </Button>
                                            )}
                                        </div>
                                    </div>
                                    {/* Back */}
                                    <div className="rounded-xl border border-gray-200 p-4 bg-white">
                                        <div className="h-28 rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 flex items-center justify-center">
                                            <IdCardIcon className="text-gray-400" />
                                        </div>
                                        <div className="mt-2 text-sm text-gray-600 flex items-center justify-between">
                                            <span>Document Back</span>
                                            {getStatusChip(getIdBack?.status as any)}
                                        </div>
                                        <div className="mt-1 text-xs text-gray-700 truncate">{getIdBack?.name || 'No file uploaded'}</div>
                                        <div className="mt-2 flex items-center justify-center gap-3 text-xs">
                                            <Button variant="link" className="p-0 h-auto text-gray-600" onClick={() => openUpload('id_verification', 'back', getIdBack?.id)} disabled={getIdBack?.status === 'verified' || isUploading}>Re-Upload</Button>
                                            {!getIdBack && (
                                                <Button variant="link" className="p-0 h-auto text-blue-600" onClick={() => openUpload('id_verification', 'back')} disabled={isUploading}>
                                                    {isUploading ? 'Uploading...' : 'Upload'}
                                                </Button>
                                            )}
                                            {getIdBack?.id && (
                                                <Button variant="link" className="p-0 h-auto text-blue-600" onClick={() => verifyDocument(getIdBack.id)} disabled={getIdBack?.status === 'verified' || processingIds.has(getIdBack?.id || '')}>
                                                    {processingIds.has(getIdBack?.id || '') ? 'Processing...' : 'Verify'}
                                                </Button>
                                            )}
                                        </div>
                                    </div>
                                </div>
                                <div className="flex items-center justify-end gap-6 mt-4">
                                    {(getIdFront?.id || getIdBack?.id) && (
                                        <Button variant="link" className="p-0 h-auto text-teal-600" onClick={() => { if (getIdFront?.id) verifyDocument(getIdFront.id); if (getIdBack?.id) verifyDocument(getIdBack.id); }} disabled={(getIdFront?.status === 'verified') && (getIdBack?.status === 'verified')}>Verify ID</Button>
                                    )}
                                </div>
                            </CardContent>
                        </Card>

                        {/* Certificates */}
                        <Card className="shadow-sm rounded-2xl mt-6">
                            <CardContent className="p-4">
                                <div className="flex items-center justify-between mb-4">
                                    <div className="text-gray-900 font-semibold">Certificates:</div>
                                    <div className="flex items-center gap-4">
                                        <span className="text-xs text-gray-600">{(localDocumentsGrouped.certificates || []).length > 0 ? 'Uploaded' : 'Not Uploaded'}</span>
                                        {(localDocumentsGrouped.certificates || []).some(c => c.status !== 'verified') && (
                                            <Button variant="link" className="p-0 h-auto text-teal-600" onClick={verifyAllCertificates}>Verify certificates</Button>
                                        )}
                                    </div>
                                </div>
                                <div className="grid grid-cols-2 gap-6">
                                    {(localDocumentsGrouped.certificates || []).map((cert) => {
                                        const isProcessing = processingIds.has(cert.id);
                                        return (
                                        <div key={cert.id} className="rounded-xl border border-gray-200 p-4 bg-white text-center">
                                            <div className="h-28 rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 flex items-center justify-center">
                                                <CertificateIcon className="text-gray-400" />
                                            </div>
                                            <div className="mt-2 text-sm flex items-center justify-center gap-2">
                                                <span className={`${cert.name.toLowerCase().includes('quran') ? 'text-green-600' : 'text-gray-700'}`}>{cert.name}</span>
                                                {getStatusChip(cert.status)}
                                            </div>
                                            <div className={`flex items-center justify-center gap-4 text-xs mt-2 ${isProcessing ? 'opacity-50' : ''}`}>
                                                <Button variant="link" className="p-0 h-auto text-gray-600" onClick={() => cert.documentUrl && window.open(cert.documentUrl, '_blank')}>View</Button>
                                                <Button variant="link" className="p-0 h-auto text-gray-600" onClick={() => openUpload('certificate', undefined, cert.id)} disabled={cert.status === 'verified' || isProcessing || isUploading}>Re-Upload</Button>
                                                <Button variant="link" className="p-0 h-auto text-blue-600" onClick={() => verifyDocument(cert.id)} disabled={cert.status === 'verified' || isProcessing}>
                                                    {isProcessing ? 'Processing...' : 'Verify'}
                                                </Button>
                                            </div>
                                        </div>
                                        );
                                    })}
                                </div>
                                <div className="flex items-center justify-end mt-3">
                                    <Button variant="link" className="p-0 h-auto text-teal-600" onClick={() => openUpload('certificate')}>Add Certificate</Button>
                                </div>
                            </CardContent>
                        </Card>

                        {/* CV/Resume Row */}
                        <div className="mt-6 rounded-xl bg-gray-50 border border-gray-200 p-4 flex items-center justify-between">
                            <div className="flex items-center">
                                <ResumeIcon className="text-gray-500 mr-2" />
                                <span className="text-gray-900 font-medium">CV/Resume:</span>
                                <span className="ml-2 text-sm text-gray-500">{localDocumentsGrouped.resume ? 'Uploaded' : 'Not Uploaded'}</span>
                            </div>
                            {localDocumentsGrouped.resume ? (
                                <div className="flex items-center gap-4">
                                    <Button variant="link" className="p-0 h-auto text-teal-600" onClick={() => localDocumentsGrouped.resume?.documentUrl && window.open(localDocumentsGrouped.resume.documentUrl, '_blank')}>Download {localDocumentsGrouped.resume.name}</Button>
                                    <Button variant="link" className="p-0 h-auto text-blue-600" onClick={() => verifyDocument(localDocumentsGrouped.resume?.id)} disabled={localDocumentsGrouped.resume?.status === 'verified' || processingIds.has(localDocumentsGrouped.resume?.id || '')}>
                                        {processingIds.has(localDocumentsGrouped.resume?.id || '') ? 'Processing...' : 'Verify'}
                                    </Button>
                                    <Button variant="link" className="p-0 h-auto text-gray-600" onClick={() => openUpload('resume', undefined, localDocumentsGrouped.resume?.id)} disabled={localDocumentsGrouped.resume?.status === 'verified' || isUploading}>Re-Upload</Button>
                                </div>
                            ) : (
                                <Button variant="link" className="p-0 h-auto text-gray-600" onClick={() => openUpload('resume')} disabled={isUploading}>
                                    {isUploading ? 'Uploading...' : 'Upload Resume'}
                                </Button>
                            )}
                        </div>
                    </div>
                </CardContent>
            </Card>
            <input ref={fileInputRef} type="file" className="hidden" onChange={onFileSelected} accept=".jpg,.jpeg,.png,.pdf" />
        </div>
    );
}
