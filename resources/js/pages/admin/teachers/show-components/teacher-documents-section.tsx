import React, { useRef, useState } from 'react';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { IdCardIcon } from '@/components/icons/id-card-icon';
import { CertificateIcon } from '@/components/icons/Certificate-icon';
import { ResumeIcon } from '@/components/icons/Resume-icon';
import { VerifiedIcon } from '@/components/icons/verified-icon';

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
    documents: DocumentsGrouped;
    teacherId: number;
}

export default function TeacherDocumentsSection({ documents = { id_verifications: [], certificates: [], resume: null }, teacherId }: Props) {
    const getStatusChip = (status?: 'pending' | 'verified' | 'rejected') => {
        if (!status) return null;
        if (status === 'verified') return <span className="inline-flex items-center gap-1 text-xs text-green-700"><VerifiedIcon className="h-3 w-3 text-green-600" /> Verified</span>;
        if (status === 'rejected') return <span className="inline-flex items-center gap-1 text-xs text-red-700">Rejected</span>;
        return <span className="inline-flex items-center gap-1 text-xs text-yellow-700">Pending</span>;
    };

    // Ensure documents and its properties exist
    const safeDocuments = documents || { id_verifications: [], certificates: [], resume: null };
    const idVerifications = safeDocuments.id_verifications || [];
    
    const getIdFront = idVerifications.find(d => d.metadata?.side === 'front');
    const getIdBack = idVerifications.find(d => d.metadata?.side === 'back');

    const idHeaderStatus = (() => {
        const docs = [getIdFront?.status, getIdBack?.status].filter(Boolean) as string[];
        if (docs.length === 0) return 'Not Uploaded';
        if (docs.every(s => s === 'verified')) return 'Verified';
        if (docs.some(s => s === 'rejected')) return 'Rejected';
        return 'Pending Verification';
    })();

    const fileInputRef = useRef<HTMLInputElement>(null);
    const [pendingUpload, setPendingUpload] = useState<{ type: 'id_verification' | 'certificate' | 'resume'; side?: 'front' | 'back' | undefined; documentId?: number | string } | null>(null);

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
            if (!response.ok) throw new Error('Upload failed');
            window.location.reload();
        } catch (err) {
            console.error(err);
            alert(err instanceof Error ? err.message : 'Upload failed');
        } finally {
            setPendingUpload(null);
            if (fileInputRef.current) fileInputRef.current.value = '';
        }
    };

    const verifyDocument = async (documentId?: number | string) => {
        if (!documentId) return;
        const response = await fetch(`/admin/documents/${documentId}/verify`, {
            method: 'PATCH',
            headers: adminHeaders,
        });
        if (!response.ok) {
            const t = await response.text();
            console.error('Verify failed', t);
            alert('Verification failed');
            return;
        }
        window.location.reload();
    };

    const viewByIdOrUrl = (url?: string, id?: number | string) => {
        if (url) window.open(url, '_blank');
        else if (id) window.open(`/admin/documents/${id}/download`, '_blank');
    };

    const verifyAllCertificates = async () => {
        const ids = (safeDocuments.certificates || []).filter(c => c.status !== 'verified').map(c => c.id);
        for (const id of ids) {
            await verifyDocument(id);
        }
    };

    return (
        <div className="space-y-6">
            <Card>
                <CardContent>
                    <div>
                        <div className="flex items-center justify-between mb-3">
                            <h3 className="text-lg font-semibold text-gray-900">Document Section</h3>
                        </div>

                        <Card className="shadow-sm rounded-2xl">
                            <CardContent className="p-4">
                                <div className="flex items-center justify-between mb-4">
                                    <div className="text-gray-900 font-semibold">ID Verification:</div>
                                    <div className="text-xs text-gray-600">{idHeaderStatus}</div>
                                </div>
                                <div className="grid grid-cols-2 gap-6">
                                    <div className="rounded-xl border border-gray-200 p-4 bg-white">
                                        <div className="h-28 rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 flex items-center justify-center">
                                            <IdCardIcon className="text-gray-400" />
                                        </div>
                                        <div className="mt-2 text-sm text-gray-600 flex items-center justify-between">
                                            <span>Document Front</span>
                                            {getStatusChip(getIdFront?.status)}
                                        </div>
                                        <div className="mt-1 text-xs text-gray-700 truncate">{getIdFront?.name || 'No file uploaded'}</div>
                                        <div className="mt-2 flex items-center justify-center gap-3 text-xs">
                                            <Button variant="link" className="p-0 h-auto text-gray-600" onClick={() => openUpload('id_verification', 'front', getIdFront?.id)} disabled={getIdFront?.status === 'verified'}>Re-Upload</Button>
                                            {!getIdFront && (
                                                <Button variant="link" className="p-0 h-auto text-blue-600" onClick={() => openUpload('id_verification', 'front')}>Upload</Button>
                                            )}
                                            {getIdFront?.id && (
                                                <Button variant="link" className="p-0 h-auto text-blue-600" onClick={() => verifyDocument(getIdFront.id)} disabled={getIdFront?.status === 'verified'}>Verify</Button>
                                            )}
                                        </div>
                                    </div>
                                    <div className="rounded-xl border border-gray-200 p-4 bg-white">
                                        <div className="h-28 rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 flex items-center justify-center">
                                            <IdCardIcon className="text-gray-400" />
                                        </div>
                                        <div className="mt-2 text-sm text-gray-600 flex items-center justify-between">
                                            <span>Document Back</span>
                                            {getStatusChip(getIdBack?.status)}
                                        </div>
                                        <div className="mt-1 text-xs text-gray-700 truncate">{getIdBack?.name || 'No file uploaded'}</div>
                                        <div className="mt-2 flex items-center justify-center gap-3 text-xs">
                                            <Button variant="link" className="p-0 h-auto text-gray-600" onClick={() => openUpload('id_verification', 'back', getIdBack?.id)} disabled={getIdBack?.status === 'verified'}>Re-Upload</Button>
                                            {!getIdBack && (
                                                <Button variant="link" className="p-0 h-auto text-blue-600" onClick={() => openUpload('id_verification', 'back')}>Upload</Button>
                                            )}
                                            {getIdBack?.id && (
                                                <Button variant="link" className="p-0 h-auto text-blue-600" onClick={() => verifyDocument(getIdBack.id)} disabled={getIdBack?.status === 'verified'}>Verify</Button>
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

                        <Card className="shadow-sm rounded-2xl mt-6">
                            <CardContent className="p-4">
                                <div className="flex items-center justify-between mb-4">
                                    <div className="text-gray-900 font-semibold">Certificates:</div>
                                    <div className="flex items-center gap-4">
                                        <span className="text-xs text-gray-600">{(safeDocuments.certificates || []).length > 0 ? 'Uploaded' : 'Not Uploaded'}</span>
                                        {(safeDocuments.certificates || []).some(c => c.status !== 'verified') && (
                                            <Button variant="link" className="p-0 h-auto text-teal-600" onClick={verifyAllCertificates}>Verify certificates</Button>
                                        )}
                                    </div>
                                </div>
                                <div className="grid grid-cols-2 gap-6">
                                    {(safeDocuments.certificates || []).map((cert) => (
                                        <div key={cert.id} className="rounded-xl border border-gray-200 p-4 bg-white text-center">
                                            <div className="h-28 rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 flex items-center justify-center">
                                                <CertificateIcon className="text-gray-400" />
                                            </div>
                                            <div className="mt-2 text-sm flex items-center justify-center gap-2">
                                                <span className={`${cert.name.toLowerCase().includes('quran') ? 'text-green-600' : 'text-gray-700'}`}>{cert.name}</span>
                                                {getStatusChip(cert.status)}
                                            </div>
                                            <div className="flex items-center justify-center gap-4 text-xs mt-2">
                                                <Button variant="link" className="p-0 h-auto text-gray-600" onClick={() => viewByIdOrUrl(cert.documentUrl, cert.id)}>View</Button>
                                                <Button variant="link" className="p-0 h-auto text-gray-600" onClick={() => openUpload('certificate', undefined, cert.id)} disabled={cert.status === 'verified'}>Re-Upload</Button>
                                                <Button variant="link" className="p-0 h-auto text-blue-600" onClick={() => verifyDocument(cert.id)} disabled={cert.status === 'verified'}>Verify</Button>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                                <div className="flex items-center justify-end mt-3">
                                    <Button variant="link" className="p-0 h-auto text-teal-600" onClick={() => openUpload('certificate')}>Add Certificate</Button>
                                </div>
                            </CardContent>
                        </Card>

                        <div className="mt-6 rounded-xl bg-gray-50 border border-gray-200 p-4 flex items-center justify-between">
                            <div className="flex items-center">
                                <ResumeIcon className="text-gray-500 mr-2" />
                                <span className="text-gray-900 font-medium">CV/Resume:</span>
                                <span className="ml-2 text-sm text-gray-500">{safeDocuments.resume ? 'Uploaded' : 'Not Uploaded'}</span>
                            </div>
                            {safeDocuments.resume ? (
                                <div className="flex items-center gap-4">
                                    <Button variant="link" className="p-0 h-auto text-teal-600" onClick={() => safeDocuments.resume?.documentUrl && window.open(safeDocuments.resume.documentUrl, '_blank')}>Download {safeDocuments.resume.name}</Button>
                                    <Button variant="link" className="p-0 h-auto text-blue-600" onClick={() => verifyDocument(safeDocuments.resume?.id)} disabled={safeDocuments.resume?.status === 'verified'}>Verify</Button>
                                    <Button variant="link" className="p-0 h-auto text-gray-600" onClick={() => openUpload('resume', undefined, safeDocuments.resume?.id)} disabled={safeDocuments.resume?.status === 'verified'}>Re-Upload</Button>
                                </div>
                            ) : (
                                <Button variant="link" className="p-0 h-auto text-gray-600" onClick={() => openUpload('resume')}>Upload Resume</Button>
                            )}
                        </div>
                    </div>
                </CardContent>
            </Card>
            <input ref={fileInputRef} type="file" className="hidden" onChange={onFileSelected} accept=".jpg,.jpeg,.png,.pdf" />
        </div>
    );
}
