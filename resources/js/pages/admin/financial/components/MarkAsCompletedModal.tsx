import { useState, useEffect } from 'react';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { CheckCircle, XCircle, Loader2, AlertCircle } from 'lucide-react';
import { toast } from 'sonner';
import axios from 'axios';

// Ensure body scroll is restored when modal closes
const restoreBodyScroll = () => {
    document.body.style.overflow = '';
    document.body.style.pointerEvents = '';
};

interface PayoutRequest {
    id: number;
    teacher?: {
        name: string;
        email: string;
    };
    teacher_name?: string;
    amount: number;
    status: string;
    payment_details?: {
        bank_name?: string;
        account_number?: string;
        account_name?: string;
    };
}

interface MarkAsCompletedModalProps {
    isOpen: boolean;
    onClose: () => void;
    onSuccess?: () => void;
    payout: PayoutRequest | null;
}

export default function MarkAsCompletedModal({ isOpen, onClose, onSuccess, payout }: MarkAsCompletedModalProps) {
    const [externalReference, setExternalReference] = useState('');
    const [notes, setNotes] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);

    // Restore body scroll when modal closes
    useEffect(() => {
        if (!isOpen) {
            restoreBodyScroll();
            // Reset form after animation
            const timer = setTimeout(() => {
                setExternalReference('');
                setNotes('');
                setIsSubmitting(false);
            }, 200);

            return () => clearTimeout(timer);
        }
    }, [isOpen]);

    // Cleanup on unmount
    useEffect(() => {
        return () => {
            restoreBodyScroll();
        };
    }, []);

    if (!isOpen || !payout) return null;

    const teacherName = payout.teacher?.name || payout.teacher_name || 'N/A';
    const bankName = payout.payment_details?.bank_name || 'N/A';
    const accountNumber = payout.payment_details?.account_number || 'N/A';
    const accountName = payout.payment_details?.account_name || 'N/A';

    const handleSubmit = async () => {
        if (!externalReference.trim()) {
            toast.error('Please enter the bank transaction reference');
            return;
        }

        setIsSubmitting(true);
        try {
            const response = await axios.post(`/admin/financial/payout-requests/${payout.id}/mark-completed`, {
                external_reference: externalReference.trim(),
                notes: notes.trim() || undefined,
            });

            if (response.data.success) {
                toast.success('Payout marked as completed!', {
                    description: 'Teacher has been notified about the successful transfer.',
                    duration: 4000,
                });
                if (onSuccess) onSuccess();
                handleClose();
            } else {
                toast.error(response.data.message || 'Failed to mark payout as completed');
            }
        } catch (error: any) {
            console.error('Error marking payout as completed:', error);
            const errorMessage = error.response?.data?.message || 'An error occurred while marking the payout as completed';
            toast.error(errorMessage);
        } finally {
            setIsSubmitting(false);
        }
    };

    const handleClose = () => {
        restoreBodyScroll();
        setExternalReference('');
        setNotes('');
        setIsSubmitting(false);
        onClose();
    };

    return (
        <Dialog
            open={isOpen}
            onOpenChange={(open) => {
                if (!open && !isSubmitting) {
                    handleClose();
                }
            }}
            modal={true}
        >
            <DialogContent
                className="sm:max-w-[600px] max-h-[90vh] p-0"
                onPointerDownOutside={(e) => {
                    if (isSubmitting) {
                        e.preventDefault();
                    }
                }}
                onInteractOutside={(e) => {
                    if (isSubmitting) {
                        e.preventDefault();
                    }
                }}
                onEscapeKeyDown={(e) => {
                    if (isSubmitting) {
                        e.preventDefault();
                    }
                }}
            >
                <DialogHeader className="px-6 pt-6 pb-0">
                    <DialogTitle className="text-2xl font-semibold text-[#1E293B]">
                        Mark Payout as Completed
                    </DialogTitle>
                </DialogHeader>

                <ScrollArea className="max-h-[calc(90vh-180px)] px-6">
                    <div className="py-6 space-y-6">
                        {/* Info Alert */}
                        <div className="bg-[#FEF3C7] border border-[#FDE68A] rounded-lg p-4 flex gap-3">
                            <AlertCircle className="w-5 h-5 text-[#D97706] flex-shrink-0 mt-0.5" />
                            <div className="text-sm text-[#92400E]">
                                <p className="font-semibold mb-1">Important: Only mark as completed after you've made the actual bank transfer</p>
                                <p>You must enter the bank's transaction reference number to complete this action.</p>
                            </div>
                        </div>

                        {/* Payout Details */}
                        <div className="bg-[#F8FAFC] rounded-lg p-4 space-y-3">
                            <h3 className="text-sm font-semibold text-[#64748B] uppercase">Transfer Details</h3>

                            <div className="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <p className="text-[#64748B]">Teacher</p>
                                    <p className="font-medium text-[#1E293B]">{teacherName}</p>
                                </div>
                                <div>
                                    <p className="text-[#64748B]">Amount</p>
                                    <p className="font-semibold text-[#14B8A6] text-lg">₦{payout.amount.toLocaleString()}</p>
                                </div>
                                <div>
                                    <p className="text-[#64748B]">Bank</p>
                                    <p className="font-medium text-[#1E293B]">{bankName}</p>
                                </div>
                                <div>
                                    <p className="text-[#64748B]">Account Number</p>
                                    <p className="font-mono font-medium text-[#1E293B]">{accountNumber}</p>
                                </div>
                                <div className="col-span-2">
                                    <p className="text-[#64748B]">Account Name</p>
                                    <p className="font-medium text-[#1E293B]">{accountName}</p>
                                </div>
                            </div>
                        </div>

                        {/* Transaction Reference Input */}
                        <div className="space-y-2">
                            <Label htmlFor="external-reference" className="text-base font-semibold text-[#1E293B]">
                                Bank Transaction Reference <span className="text-[#EF4444]">*</span>
                            </Label>
                            <Input
                                id="external-reference"
                                placeholder="e.g., TRF123456789 or NIP/2024/01/15/001234"
                                value={externalReference}
                                onChange={(e) => setExternalReference(e.target.value)}
                                className="bg-white border-[#E2E8F0] text-[#1E293B] text-base"
                                disabled={isSubmitting}
                                maxLength={255}
                            />
                            <p className="text-xs text-[#64748B]">
                                Enter the transaction reference from your bank's transfer confirmation
                            </p>
                        </div>

                        {/* Notes Input */}
                        <div className="space-y-2">
                            <Label htmlFor="notes" className="text-base font-semibold text-[#1E293B]">
                                Additional Notes <span className="text-[#64748B] font-normal">(Optional)</span>
                            </Label>
                            <Textarea
                                id="notes"
                                placeholder="Add any additional information about this transfer (e.g., transfer method, special circumstances, etc.)"
                                value={notes}
                                onChange={(e) => setNotes(e.target.value)}
                                className="min-h-[100px] resize-none bg-white border-[#E2E8F0] text-[#64748B] text-base"
                                disabled={isSubmitting}
                                maxLength={1000}
                            />
                            <p className="text-xs text-[#64748B]">
                                {notes.length}/1000 characters
                            </p>
                        </div>
                    </div>
                </ScrollArea>

                <DialogFooter className="gap-3 px-6 pb-6 pt-4 border-t border-[#E2E8F0]">
                    <Button
                        variant="ghost"
                        onClick={handleClose}
                        disabled={isSubmitting}
                        className="text-[#64748B] hover:text-[#475569] hover:bg-[#F1F5F9] font-medium"
                    >
                        <XCircle className="w-5 h-5 mr-2" />
                        Cancel
                    </Button>
                    <Button
                        onClick={handleSubmit}
                        disabled={isSubmitting || !externalReference.trim()}
                        className="rounded-full px-8 py-6 bg-[#14B8A6] hover:bg-[#0F9688] text-white font-medium text-base"
                    >
                        {isSubmitting ? (
                            <>
                                <Loader2 className="w-5 h-5 mr-2 animate-spin" />
                                Processing...
                            </>
                        ) : (
                            <>
                                <CheckCircle className="w-5 h-5 mr-2" />
                                Mark as Completed
                            </>
                        )}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
