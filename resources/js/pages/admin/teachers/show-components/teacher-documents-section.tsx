import React, { useState, useRef } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { VerifiedIcon } from '@/components/icons/verified-icon';
import { IdCardIcon } from '@/components/icons/id-card-icon';
import { Upload, Eye, Download, X, FileText, CheckCircle, AlertCircle } from 'lucide-react';
import { toast } from 'sonner';

interface Document {
  id: number;
  name: string;
  status: 'pending' | 'verified' | 'rejected';
  metadata?: any;
  documentUrl?: string;
}

interface Documents {
  id_verifications: Document[];
  certificates: Document[];
  resume: Document | null;
}

interface Props {
  documents: Documents;
  teacherId: number;
}

type UploadType = 'id_verification' | 'certificate' | 'resume' | null;
type DocumentSide = 'front' | 'back' | null;

export default function TeacherDocumentsSection({ documents, teacherId }: Props) {
  const [uploadModalOpen, setUploadModalOpen] = useState(false);
  const [uploadType, setUploadType] = useState<UploadType>(null);
  const [documentSide, setDocumentSide] = useState<DocumentSide>(null);
  const [selectedFile, setSelectedFile] = useState<File | null>(null);
  const [uploading, setUploading] = useState(false);
  const [certificateType, setCertificateType] = useState('');
  const fileInputRef = useRef<HTMLInputElement>(null);
  
  // Verification modal state
  const [verificationModalOpen, setVerificationModalOpen] = useState(false);
  const [selectedDocument, setSelectedDocument] = useState<Document | null>(null);
  const [verificationAction, setVerificationAction] = useState<'approve' | 'reject' | null>(null);
  const [rejectionReason, setRejectionReason] = useState('');
  const [verifying, setVerifying] = useState(false);

  const getStatusIcon = (status: string) => {
    if (status === 'verified') {
      return <VerifiedIcon className="h-4 w-4 text-green-500" />;
    } else if (status === 'pending') {
      return <div className="h-4 w-4 rounded-full bg-yellow-400"></div>;
    } else if (status === 'rejected') {
      return <div className="h-4 w-4 rounded-full bg-red-400"></div>;
    }
    return null;
  };

  const getStatusText = (status: string, type: string) => {
    switch (status) {
      case 'verified':
        return type === 'id_verification' ? 'Uploaded (NIN Card)' : 'Uploaded';
      case 'pending':
        return 'Pending Verification';
      case 'rejected':
        return 'Rejected';
      default:
        return 'Not Uploaded';
    }
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'verified':
        return 'text-green-600';
      case 'pending':
        return 'text-yellow-600';
      case 'rejected':
        return 'text-red-600';
      default:
        return 'text-gray-500';
    }
  };

  const handleFileSelect = (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    if (file) {
      // Validate file type
      const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
      if (!allowedTypes.includes(file.type)) {
        toast.error('Please select a valid file type (JPEG, PNG, or PDF)');
        return;
      }
      
      // Validate file size (5MB limit)
      if (file.size > 5 * 1024 * 1024) {
        toast.error('File size must be less than 5MB');
        return;
      }
      
      setSelectedFile(file);
    }
  };

  const handleUpload = async () => {
    if (!selectedFile || !uploadType) {
      toast.error('Please select a file and document type');
      return;
    }

    setUploading(true);

    try {
      const formData = new FormData();
      formData.append('document', selectedFile);
      formData.append('type', uploadType);
      formData.append('teacher_id', teacherId.toString());
      
      if (uploadType === 'id_verification' && documentSide) {
        formData.append('side', documentSide);
      }
      
      if (uploadType === 'certificate' && certificateType) {
        formData.append('certificate_type', certificateType);
      }

      console.log('Uploading to:', '/admin/documents/upload');
      console.log('FormData contents:', Object.fromEntries(formData.entries()));
      
      const response = await fetch('/admin/documents/upload', {
        method: 'POST',
        body: formData,
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
      });

      console.log('Response status:', response.status);
      console.log('Response headers:', Object.fromEntries(response.headers.entries()));

      // Check if response is JSON
      const contentType = response.headers.get('content-type');
      if (!contentType || !contentType.includes('application/json')) {
        throw new Error('Server returned non-JSON response. Please check authentication.');
      }

      const result = await response.json();
      
      if (!response.ok) {
        throw new Error(result.message || `Upload failed with status ${response.status}`);
      }
      
      if (result.success) {
        toast.success('Document uploaded successfully');
        setUploadModalOpen(false);
        resetUploadState();
        // Refresh the page to show updated documents
        window.location.reload();
      } else {
        throw new Error(result.message || 'Upload failed');
      }
    } catch (error) {
      console.error('Upload error:', error);
      const errorMessage = error instanceof Error ? error.message : 'Unknown error occurred';
      if (errorMessage.includes('non-JSON response')) {
        toast.error('Authentication error. Please refresh the page and try again.');
      } else {
        toast.error(errorMessage || 'Failed to upload document. Please try again.');
      }
    } finally {
      setUploading(false);
    }
  };

  const resetUploadState = () => {
    setSelectedFile(null);
    setUploadType(null);
    setDocumentSide(null);
    setCertificateType('');
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
  };

  const openUploadModal = (type: UploadType, side?: DocumentSide) => {
    setUploadType(type);
    setDocumentSide(side || null);
    setUploadModalOpen(true);
  };

  const handleViewDocument = (documentUrl: string) => {
    if (documentUrl) {
      window.open(documentUrl, '_blank');
    }
  };

  const handleDownloadDocument = async (documentUrl: string, fileName: string) => {
    try {
      const response = await fetch(documentUrl);
      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = fileName;
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      document.body.removeChild(a);
    } catch (error) {
      toast.error('Failed to download document');
    }
  };

  const openVerificationModal = (document: Document) => {
    setSelectedDocument(document);
    setVerificationAction(null);
    setRejectionReason('');
    setVerificationModalOpen(true);
  };

  const handleVerification = async () => {
    if (!selectedDocument || !verificationAction) {
      toast.error('Please select an action');
      return;
    }

    if (verificationAction === 'reject' && !rejectionReason.trim()) {
      toast.error('Please provide a rejection reason');
      return;
    }

    setVerifying(true);

    try {
      // Use the correct route - both approve and reject use the same endpoint with different actions
      const endpoint = verificationAction === 'approve' 
        ? `/admin/documents/${selectedDocument.id}/verify`
        : `/admin/documents/${selectedDocument.id}/reject`;
        
      const response = await fetch(endpoint, {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({
          rejection_reason: verificationAction === 'reject' ? rejectionReason : null,
        }),
      });

      const result = await response.json();

      if (!response.ok) {
        throw new Error(result.message || `Verification failed with status ${response.status}`);
      }

      if (result.success) {
        toast.success(`Document ${verificationAction === 'approve' ? 'approved' : 'rejected'} successfully`);
        setVerificationModalOpen(false);
        // Refresh the page to show updated status
        window.location.reload();
      } else {
        throw new Error(result.message || 'Verification failed');
      }
    } catch (error) {
      console.error('Verification error:', error);
      const errorMessage = error instanceof Error ? error.message : 'Unknown error occurred';
      toast.error(errorMessage || 'Failed to verify document. Please try again.');
    } finally {
      setVerifying(false);
    }
  };

  const resetVerificationState = () => {
    setSelectedDocument(null);
    setVerificationAction(null);
    setRejectionReason('');
  };

  const hasIdVerifications = documents.id_verifications.length > 0;
  const hasCertificates = documents.certificates.length > 0;
  const hasResume = documents.resume !== null;

  return (
    <>
      <Card className='mb-8 shadow-sm'>
        <CardContent className=''>
          <div className='flex flex-col gap-4 mb-6'>
            <h2 className='text-lg font-bold text-gray-800'>Documents Section</h2>
          </div>
          <div className="space-y-6">
            {/* ID Verification Section */}
            <Card className="shadow-sm">
              <CardContent className="p-6">
                <div className="flex items-center justify-between mb-4">
                                     <div className="flex items-center gap-2">
                     <h3 className="text-lg font-bold text-gray-800">ID Verification:</h3>
                     {(() => {
                       const frontDoc = documents.id_verifications.find(doc => doc.metadata?.side === 'front');
                       const backDoc = documents.id_verifications.find(doc => doc.metadata?.side === 'back');
                       
                       if (frontDoc && backDoc) {
                         // Both documents uploaded
                         const frontStatus = frontDoc.status;
                         const backStatus = backDoc.status;
                         
                         if (frontStatus === 'verified' && backStatus === 'verified') {
                           return (
                             <>
                               {getStatusIcon('verified')}
                               <span className={`text-sm ${getStatusColor('verified')}`}>
                                 Uploaded (NIN Card)
                               </span>
                             </>
                           );
                         } else if (frontStatus === 'rejected' || backStatus === 'rejected') {
                           return (
                             <>
                               {getStatusIcon('rejected')}
                               <span className={`text-sm ${getStatusColor('rejected')}`}>
                                 Rejected
                               </span>
                             </>
                           );
                         } else {
                           return (
                             <>
                               {getStatusIcon('pending')}
                               <span className={`text-sm ${getStatusColor('pending')}`}>
                                 Pending Verification
                               </span>
                             </>
                           );
                         }
                       } else if (frontDoc || backDoc) {
                         // Only one document uploaded
                         const uploadedDoc = frontDoc || backDoc;
                         return (
                           <>
                             {getStatusIcon('pending')}
                             <span className={`text-sm ${getStatusColor('pending')}`}>
                               Partial Upload
                             </span>
                           </>
                         );
                       } else {
                         // No documents uploaded
                         return (
                           <span className="text-sm text-gray-500">Not Uploaded</span>
                         );
                       }
                     })()}
                   </div>
                </div>

                <div className="grid grid-cols-2 gap-4 mb-4">
                  {/* Front Side */}
                  <div className="text-center">
                    {(() => {
                      const frontDoc = documents.id_verifications.find(doc => doc.metadata?.side === 'front');
                      return frontDoc ? (
                        <>
                          <div className="bg-gray-100 rounded-lg p-4 mb-2">
                            <IdCardIcon className="w-16 h-12 mx-auto text-gray-600" />
                          </div>
                          <p className="text-sm text-gray-600">Document Front</p>
                                                     <div className="flex gap-2 justify-center text-xs mt-2">
                             <Button 
                               variant="link" 
                               className="p-0 h-auto text-gray-600 hover:text-gray-800"
                               onClick={() => handleViewDocument(frontDoc.documentUrl || '')}
                             >
                               <Eye className="w-3 h-3 mr-1" />
                               View
                             </Button>
                             <Button 
                               variant="link" 
                               className="p-0 h-auto text-gray-600 hover:text-gray-800"
                               onClick={() => openUploadModal('id_verification', 'front')}
                             >
                               Re-upload
                             </Button>
                             {frontDoc.status === 'pending' && (
                               <Button 
                                 variant="link" 
                                 className="p-0 h-auto text-blue-600 hover:text-blue-700"
                                 onClick={() => openVerificationModal(frontDoc)}
                               >
                                 Verify
                               </Button>
                             )}
                           </div>
                        </>
                      ) : (
                        <>
                          <div className="bg-gray-100 rounded-lg p-4 mb-2 border-2 border-dashed border-gray-300">
                            <Upload className="w-8 h-8 mx-auto text-gray-400" />
                          </div>
                          <p className="text-sm text-gray-500">Document Front</p>
                          <Button 
                            variant="link" 
                            className="p-0 h-auto text-blue-600 hover:text-blue-700 text-xs"
                            onClick={() => openUploadModal('id_verification', 'front')}
                          >
                            Upload Front
                          </Button>
                        </>
                      );
                    })()}
                  </div>

                  {/* Back Side */}
                  <div className="text-center">
                    {(() => {
                      const backDoc = documents.id_verifications.find(doc => doc.metadata?.side === 'back');
                      return backDoc ? (
                        <>
                          <div className="bg-gray-100 rounded-lg p-4 mb-2">
                            <IdCardIcon className="w-16 h-12 mx-auto text-gray-600" />
                          </div>
                          <p className="text-sm text-gray-600">Document Back</p>
                                                     <div className="flex gap-2 justify-center text-xs mt-2">
                             <Button 
                               variant="link" 
                               className="p-0 h-auto text-gray-600 hover:text-gray-800"
                               onClick={() => handleViewDocument(backDoc.documentUrl || '')}
                             >
                               <Eye className="w-3 h-3 mr-1" />
                               View
                             </Button>
                             <Button 
                               variant="link" 
                               className="p-0 h-auto text-gray-600 hover:text-gray-800"
                               onClick={() => openUploadModal('id_verification', 'back')}
                             >
                               Re-upload
                             </Button>
                             {backDoc.status === 'pending' && (
                               <Button 
                                 variant="link" 
                                 className="p-0 h-auto text-blue-600 hover:text-blue-700"
                                 onClick={() => openVerificationModal(backDoc)}
                               >
                                 Verify
                               </Button>
                             )}
                           </div>
                        </>
                      ) : (
                        <>
                          <div className="bg-gray-100 rounded-lg p-4 mb-2 border-2 border-dashed border-gray-300">
                            <Upload className="w-8 h-8 mx-auto text-gray-400" />
                          </div>
                          <p className="text-sm text-gray-500">Document Back</p>
                          <Button 
                            variant="link" 
                            className="p-0 h-auto text-blue-600 hover:text-blue-700 text-xs"
                            onClick={() => openUploadModal('id_verification', 'back')}
                          >
                            Upload Back
                          </Button>
                        </>
                      );
                    })()}
                  </div>
                </div>

                <div className="flex gap-4 text-sm">
                  {!hasIdVerifications && (
                    <Button 
                      variant="link" 
                      className="p-0 h-auto text-blue-600 hover:text-blue-700"
                      onClick={() => openUploadModal('id_verification')}
                    >
                      Upload Documents
                    </Button>
                  )}
                </div>
              </CardContent>
            </Card>

            {/* Certificates Section */}
            <Card className="shadow-sm">
              <CardContent className="p-6">
                <div className="flex items-center justify-between mb-4">
                  <div className="flex items-center gap-2">
                    <h3 className="text-lg font-bold text-gray-800">Certificates:</h3>
                    {hasCertificates ? (
                      <>
                        {getStatusIcon(documents.certificates[0].status)}
                        <span className={`text-sm ${getStatusColor(documents.certificates[0].status)}`}>
                          {getStatusText(documents.certificates[0].status, 'certificates')}
                        </span>
                      </>
                    ) : (
                      <span className="text-sm text-gray-500">Not Uploaded</span>
                    )}
                  </div>
                </div>

                {hasCertificates ? (
                  <div className="grid grid-cols-2 gap-4 mb-4">
                    {documents.certificates.map((cert) => (
                      <div key={cert.id} className="text-center">
                        <div className="bg-gray-100 rounded-lg p-4 mb-2">
                          <IdCardIcon className="w-16 h-12 mx-auto text-gray-600" />
                        </div>
                        <p className={`text-sm mb-2 leading-tight ${cert.name.includes('Quran') ? 'text-green-600' : 'text-gray-700'}`}>
                          {cert.name}
                        </p>
                                                 <div className="flex gap-2 justify-center text-xs">
                           <Button 
                             variant="link" 
                             className="p-0 h-auto text-gray-600 hover:text-gray-800"
                             onClick={() => handleViewDocument(cert.documentUrl || '')}
                           >
                             <Eye className="w-3 h-3 mr-1" />
                             View
                           </Button>
                           <Button 
                             variant="link" 
                             className="p-0 h-auto text-gray-600 hover:text-gray-800"
                             onClick={() => openUploadModal('certificate')}
                           >
                             Re-Upload
                           </Button>
                           {cert.status === 'pending' && (
                             <Button 
                               variant="link" 
                               className="p-0 h-auto text-blue-600 hover:text-blue-700"
                               onClick={() => openVerificationModal(cert)}
                             >
                               Verify
                             </Button>
                           )}
                         </div>
                      </div>
                    ))}
                  </div>
                ) : (
                  <div className="text-center py-8">
                    <div className="bg-gray-100 rounded-lg p-4 mb-2 w-16 h-12 mx-auto border-2 border-dashed border-gray-300">
                      <Upload className="w-8 h-8 mx-auto text-gray-400" />
                    </div>
                    <p className="text-sm text-gray-500">No certificates uploaded</p>
                    <Button 
                      variant="link" 
                      className="p-0 h-auto text-blue-600 hover:text-blue-700 mt-2"
                      onClick={() => openUploadModal('certificate')}
                    >
                      Upload Certificate
                    </Button>
                  </div>
                )}
              </CardContent>
            </Card>

            {/* CV/Resume Section */}
            <Card className="shadow-sm">
              <CardContent className="p-6">
                <div className="flex items-center justify-between mb-4">
                  <div className="flex items-center gap-2">
                    <h3 className="text-lg font-bold text-gray-800">CV/Resume:</h3>
                    {hasResume && documents.resume ? (
                      <>
                        {getStatusIcon(documents.resume.status)}
                        <span className={`text-sm ${getStatusColor(documents.resume.status)}`}>
                          {getStatusText(documents.resume.status, 'resume')}
                        </span>
                      </>
                    ) : (
                      <span className="text-sm text-gray-500">Not Uploaded</span>
                    )}
                  </div>
                  {hasResume && documents.resume && (
                    <Button 
                      variant="link" 
                      className="p-0 h-auto text-green-600 hover:text-green-700"
                      onClick={() => handleDownloadDocument(documents.resume!.documentUrl || '', documents.resume!.name)}
                    >
                      <Download className="w-4 h-4 mr-1" />
                      Download {documents.resume.name}
                    </Button>
                  )}
                </div>

                {hasResume && documents.resume ? (
                  <div className="text-center">
                    <div className="bg-gray-100 rounded-lg p-4 mb-2 w-16 h-12 mx-auto">
                      <IdCardIcon className="w-8 h-8 mx-auto text-gray-600" />
                    </div>
                    <p className="text-sm text-gray-700">{documents.resume.name}</p>
                                         <div className="flex gap-2 justify-center text-xs mt-2">
                       <Button 
                         variant="link" 
                         className="p-0 h-auto text-gray-600 hover:text-gray-800"
                         onClick={() => handleViewDocument(documents.resume!.documentUrl || '')}
                       >
                         <Eye className="w-3 h-3 mr-1" />
                         View
                       </Button>
                       <Button 
                         variant="link" 
                         className="p-0 h-auto text-gray-600 hover:text-gray-800"
                         onClick={() => openUploadModal('resume')}
                       >
                         Re-upload
                       </Button>
                       {documents.resume!.status === 'pending' && (
                         <Button 
                           variant="link" 
                           className="p-0 h-auto text-blue-600 hover:text-blue-700"
                           onClick={() => openVerificationModal(documents.resume!)}
                         >
                           Verify
                         </Button>
                       )}
                     </div>
                  </div>
                ) : (
                  <div className="text-center py-8">
                    <div className="bg-gray-100 rounded-lg p-4 mb-2 w-16 h-12 mx-auto border-2 border-dashed border-gray-300">
                      <Upload className="w-8 h-8 mx-auto text-gray-400" />
                    </div>
                    <p className="text-sm text-gray-500">No resume uploaded</p>
                    <Button 
                      variant="link" 
                      className="p-0 h-auto text-blue-600 hover:text-blue-700 mt-2"
                      onClick={() => openUploadModal('resume')}
                    >
                      Upload Resume
                    </Button>
                  </div>
                )}
              </CardContent>
            </Card>
          </div>
        </CardContent>
      </Card>

      {/* Upload Modal */}
      <Dialog open={uploadModalOpen} onOpenChange={setUploadModalOpen}>
        <DialogContent className="sm:max-w-md" aria-describedby="upload-dialog-description">
          <div id="upload-dialog-description" className="sr-only">
            Upload document modal for teacher verification
          </div>
          <DialogHeader>
            <DialogTitle>
              Upload {uploadType === 'id_verification' ? 'ID Verification' : 
                      uploadType === 'certificate' ? 'Certificate' : 'Resume'}
            </DialogTitle>
          </DialogHeader>
          
          <div className="space-y-4">
            {uploadType === 'id_verification' && !documentSide && (
              <div className="space-y-2">
                <Label>Document Side</Label>
                                 <Select onValueChange={(value) => setDocumentSide(value as DocumentSide)}>
                   <SelectTrigger>
                     <SelectValue placeholder="Select document side" />
                   </SelectTrigger>
                   <SelectContent aria-describedby="document-side-description">
                     <SelectItem value="front">Front</SelectItem>
                     <SelectItem value="back">Back</SelectItem>
                   </SelectContent>
                 </Select>
                 <div id="document-side-description" className="sr-only">
                   Select the side of the document to upload
                 </div>
              </div>
            )}

            {uploadType === 'certificate' && (
              <div className="space-y-2">
                <Label>Certificate Type</Label>
                                 <Select onValueChange={setCertificateType}>
                   <SelectTrigger>
                     <SelectValue placeholder="Select certificate type" />
                   </SelectTrigger>
                   <SelectContent aria-describedby="certificate-type-description">
                     <SelectItem value="quran_memorization">Quran Memorization</SelectItem>
                     <SelectItem value="teaching_certificate">Teaching Certificate</SelectItem>
                     <SelectItem value="academic_degree">Academic Degree</SelectItem>
                     <SelectItem value="other">Other</SelectItem>
                   </SelectContent>
                 </Select>
                 <div id="certificate-type-description" className="sr-only">
                   Select the type of certificate to upload
                 </div>
              </div>
            )}

            <div className="space-y-2">
              <Label>Select File</Label>
              <Input
                ref={fileInputRef}
                type="file"
                accept=".jpg,.jpeg,.png,.pdf"
                onChange={handleFileSelect}
                className="cursor-pointer"
              />
              <p className="text-xs text-gray-500">
                Accepted formats: JPEG, PNG, PDF (Max 5MB)
              </p>
            </div>

            {selectedFile && (
              <div className="flex items-center gap-2 p-3 bg-green-50 rounded-lg">
                <CheckCircle className="h-4 w-4 text-green-500" />
                <span className="text-sm text-green-700">{selectedFile.name}</span>
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={() => setSelectedFile(null)}
                  className="ml-auto h-6 w-6 p-0"
                >
                  <X className="h-3 w-3" />
                </Button>
              </div>
            )}
          </div>

          <DialogFooter>
            <Button variant="outline" onClick={() => setUploadModalOpen(false)}>
              Cancel
            </Button>
            <Button 
              onClick={handleUpload} 
              disabled={!selectedFile || uploading || (uploadType === 'id_verification' && !documentSide) || (uploadType === 'certificate' && !certificateType)}
            >
              {uploading ? 'Uploading...' : 'Upload'}
            </Button>
                     </DialogFooter>
         </DialogContent>
       </Dialog>

       {/* Verification Modal */}
       <Dialog open={verificationModalOpen} onOpenChange={(open) => {
         setVerificationModalOpen(open);
         if (!open) resetVerificationState();
       }}>
         <DialogContent className="sm:max-w-md" aria-describedby="verification-dialog-description">
           <div id="verification-dialog-description" className="sr-only">
             Document verification modal for admin approval or rejection
           </div>
           <DialogHeader>
             <DialogTitle>
               Verify Document: {selectedDocument?.name}
             </DialogTitle>
           </DialogHeader>
           
           <div className="space-y-4">
             <div className="space-y-2">
               <Label>Verification Action</Label>
               <div className="flex gap-2">
                 <Button
                   variant={verificationAction === 'approve' ? 'default' : 'outline'}
                   onClick={() => setVerificationAction('approve')}
                   className="flex-1"
                 >
                   <CheckCircle className="w-4 h-4 mr-2" />
                   Approve
                 </Button>
                 <Button
                   variant={verificationAction === 'reject' ? 'destructive' : 'outline'}
                   onClick={() => setVerificationAction('reject')}
                   className="flex-1"
                 >
                   <AlertCircle className="w-4 h-4 mr-2" />
                   Reject
                 </Button>
               </div>
             </div>

             {verificationAction === 'reject' && (
               <div className="space-y-2">
                 <Label htmlFor="rejection-reason">Rejection Reason</Label>
                 <textarea
                   id="rejection-reason"
                   value={rejectionReason}
                   onChange={(e) => setRejectionReason(e.target.value)}
                   placeholder="Please provide a reason for rejection..."
                   className="w-full p-2 border border-gray-300 rounded-md resize-none"
                   rows={3}
                 />
               </div>
             )}

             {verificationAction === 'approve' && (
               <div className="p-3 bg-green-50 rounded-lg">
                 <p className="text-sm text-green-700">
                   This document will be marked as verified and the teacher will be notified.
                 </p>
               </div>
             )}

             {verificationAction === 'reject' && (
               <div className="p-3 bg-red-50 rounded-lg">
                 <p className="text-sm text-red-700">
                   This document will be marked as rejected and the teacher will be notified with the reason.
                 </p>
               </div>
             )}
           </div>

           <DialogFooter>
             <Button variant="outline" onClick={() => setVerificationModalOpen(false)}>
               Cancel
             </Button>
             <Button 
               onClick={handleVerification} 
               disabled={!verificationAction || verifying || (verificationAction === 'reject' && !rejectionReason.trim())}
               variant={verificationAction === 'reject' ? 'destructive' : 'default'}
             >
               {verifying ? 'Processing...' : verificationAction === 'approve' ? 'Approve Document' : 'Reject Document'}
             </Button>
           </DialogFooter>
         </DialogContent>
       </Dialog>
     </>
   );
 }
