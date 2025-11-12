import { useState, useEffect } from 'react';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { CheckCircle, Bell, XCircle, Loader2 } from 'lucide-react';
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
}

interface SendNotificationModalProps {
    isOpen: boolean;
    onClose: () => void;
    onSuccess?: () => void;
    payout: PayoutRequest | null;
}

export default function SendNotificationModal({ isOpen, onClose, onSuccess, payout }: SendNotificationModalProps) {
    const [notificationType, setNotificationType] = useState<'payout_success' | 'reminder' | 'rejected'>('payout_success');
    const [notificationMessage, setNotificationMessage] = useState('');
    const [sendChannels, setSendChannels] = useState({
        inApp: true,
        email: false,
        sms: false,
        all: false
    });
    const [deliveryTime, setDeliveryTime] = useState<'now' | 'later'>('now');
    const [isSendingNotification, setIsSendingNotification] = useState(false);

    // Reset state when modal closes and ensure body scroll is restored
    useEffect(() => {
        if (!isOpen) {
            // Restore body scroll immediately
            restoreBodyScroll();
            
            // Use setTimeout to ensure state resets after modal animation completes
            const timer = setTimeout(() => {
                setNotificationMessage('');
                setSendChannels({ inApp: true, email: false, sms: false, all: false });
                setDeliveryTime('now');
                setNotificationType('payout_success');
                setIsSendingNotification(false);
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

    // Don't render anything if modal is closed and no payout
    if (!isOpen || !payout) return null;

    const teacherName = payout.teacher?.name || payout.teacher_name || 'N/A';

    const handleSendNotification = async () => {
        if (!notificationMessage.trim()) {
            toast.error('Please enter a notification message');
            return;
        }

        const hasChannel = sendChannels.inApp || sendChannels.email || sendChannels.sms || sendChannels.all;
        if (!hasChannel) {
            toast.error('Please select at least one notification channel');
            return;
        }

        setIsSendingNotification(true);
        try {
            const response = await axios.post(`/admin/financial/payout-requests/${payout.id}/send-notification`, {
                type: notificationType,
                message: notificationMessage,
                channels: sendChannels,
                delivery_time: deliveryTime
            });

            if (response.data.success) {
                toast.success('Notification sent successfully!');
                if (onSuccess) onSuccess();
                handleClose();
            } else {
                toast.error(response.data.message || 'Failed to send notification');
            }
        } catch (error: any) {
            console.error('Error sending notification:', error);
            const errorMessage = error.response?.data?.message || 'An error occurred while sending the notification';
            toast.error(errorMessage);
        } finally {
            setIsSendingNotification(false);
        }
    };

    const handleChannelToggle = (channel: 'inApp' | 'email' | 'sms' | 'all') => {
        if (channel === 'all') {
            const newValue = !sendChannels.all;
            setSendChannels({
                inApp: newValue,
                email: newValue,
                sms: false, // SMS is disabled
                all: newValue
            });
        } else if (channel === 'sms') {
            // SMS is disabled - show toast message
            toast.info('SMS Coming Soon', {
                description: 'SMS notifications will be available soon. Use Email for now.',
                duration: 3000,
            });
            return;
        } else {
            const newChannels = {
                ...sendChannels,
                [channel]: !sendChannels[channel]
            };
            // "All" only includes In-App and Email (SMS is disabled)
            newChannels.all = newChannels.inApp && newChannels.email;
            newChannels.sms = false; // Keep SMS disabled
            setSendChannels(newChannels);
        }
    };

    const handleClose = () => {
        // Restore body scroll first
        restoreBodyScroll();
        
        // Reset state
        setNotificationMessage('');
        setSendChannels({ inApp: true, email: false, sms: false, all: false });
        setDeliveryTime('now');
        setNotificationType('payout_success');
        setIsSendingNotification(false);
        
        // Then close modal
        onClose();
    };

    return (
        <Dialog 
            open={isOpen} 
            onOpenChange={(open) => { 
                if (!open && !isSendingNotification) {
                    handleClose();
                }
            }}
            modal={true}
        >
            <DialogContent 
                className="sm:max-w-[900px]"
                onPointerDownOutside={(e) => {
                    // Prevent closing while sending
                    if (isSendingNotification) {
                        e.preventDefault();
                    }
                }}
                onInteractOutside={(e) => {
                    // Prevent closing while sending
                    if (isSendingNotification) {
                        e.preventDefault();
                    }
                }}
                onEscapeKeyDown={(e) => {
                    // Prevent closing with Escape while sending
                    if (isSendingNotification) {
                        e.preventDefault();
                    }
                }}
            >
                <DialogHeader>
                    <DialogTitle className="text-2xl font-semibold text-[#1E293B]">
                        Send Notification to: {teacherName}
                    </DialogTitle>
                </DialogHeader>

                <div 
                    className="py-6 space-y-6 bg-[#F8FAFC] rounded-lg p-6"
                    onClick={(e) => e.stopPropagation()}
                >
                    {/* Choose Type Section */}
                    <div className="space-y-4">
                        <Label className="text-lg font-semibold text-[#1E293B]">Choose Type:</Label>

                        <div className="space-y-3">
                            {/* Payout Success */}
                            <div
                                className="flex items-center space-x-3 cursor-pointer"
                                onClick={() => setNotificationType('payout_success')}
                            >
                                <div className={`w-6 h-6 rounded border-2 flex items-center justify-center ${notificationType === 'payout_success'
                                    ? 'border-[#14B8A6] bg-[#14B8A6]'
                                    : 'border-[#CBD5E1] bg-white'
                                    }`}>
                                    {notificationType === 'payout_success' && (
                                        <CheckCircle className="w-4 h-4 text-white" />
                                    )}
                                </div>
                                <CheckCircle className="w-5 h-5 text-[#10B981]" />
                                <span className="text-[#64748B] text-base">Payout Success</span>
                            </div>

                            {/* Reminder to Update Account */}
                            <div
                                className="flex items-center space-x-3 cursor-pointer"
                                onClick={() => setNotificationType('reminder')}
                            >
                                <div className={`w-6 h-6 rounded border-2 flex items-center justify-center ${notificationType === 'reminder'
                                    ? 'border-[#14B8A6] bg-[#14B8A6]'
                                    : 'border-[#CBD5E1] bg-white'
                                    }`}>
                                    {notificationType === 'reminder' && (
                                        <CheckCircle className="w-4 h-4 text-white" />
                                    )}
                                </div>
                                <Bell className="w-5 h-5 text-[#F59E0B]" />
                                <span className="text-[#64748B] text-base">Reminder to Update Account</span>
                            </div>

                            {/* Rejected Reason Explanation */}
                            <div
                                className="flex items-center space-x-3 cursor-pointer"
                                onClick={() => setNotificationType('rejected')}
                            >
                                <div className={`w-6 h-6 rounded border-2 flex items-center justify-center ${notificationType === 'rejected'
                                    ? 'border-[#14B8A6] bg-[#14B8A6]'
                                    : 'border-[#CBD5E1] bg-white'
                                    }`}>
                                    {notificationType === 'rejected' && (
                                        <CheckCircle className="w-4 h-4 text-white" />
                                    )}
                                </div>
                                <XCircle className="w-5 h-5 text-[#EF4444]" />
                                <span className="text-[#64748B] text-base">Rejected Reason Explanation</span>
                            </div>
                        </div>
                    </div>

                    {/* Message Body */}
                    <div className="space-y-3">
                        <Label htmlFor="notification-message" className="text-lg font-semibold text-[#1E293B]">
                            Message Body
                        </Label>
                        <Textarea
                            id="notification-message"
                            placeholder="Hello Amina, your payout request of ₦45,000 has been approved and will be credited shortly."
                            value={notificationMessage}
                            onChange={(e) => setNotificationMessage(e.target.value)}
                            className="min-h-[160px] resize-none bg-white border-[#E2E8F0] text-[#64748B] text-base"
                            disabled={isSendingNotification}
                        />
                    </div>

                    {/* Send As Section */}
                    <div className="space-y-3">
                        <div className="flex items-center gap-8">
                            <Label className="text-base font-medium text-[#1E293B] min-w-[100px]">Send As:</Label>
                            <div className="flex items-center gap-8">
                                <div className="flex items-center gap-3">
                                    <span className="text-base text-[#64748B]">In-App</span>
                                    <Switch
                                        checked={sendChannels.inApp}
                                        onCheckedChange={() => handleChannelToggle('inApp')}
                                        disabled={isSendingNotification}
                                    />
                                </div>
                                <div className="flex items-center gap-3">
                                    <span className="text-base text-[#64748B]">Email</span>
                                    <Switch
                                        checked={sendChannels.email}
                                        onCheckedChange={() => handleChannelToggle('email')}
                                        disabled={isSendingNotification}
                                    />
                                </div>
                                <div className="flex items-center gap-3 relative">
                                    <span className="text-base text-[#94A3B8]">SMS</span>
                                    <Switch
                                        checked={false}
                                        disabled={true}
                                        className="opacity-50"
                                    />
                                    <span className="absolute -bottom-3 left-0 text-xs text-[#F59E0B] whitespace-nowrap">
                                        Coming Soon
                                    </span>
                                </div>
                                <div className="flex items-center gap-3">
                                    <span className="text-base text-[#64748B]">All</span>
                                    <Switch
                                        checked={sendChannels.all}
                                        onCheckedChange={() => handleChannelToggle('all')}
                                        disabled={isSendingNotification}
                                    />
                                </div>
                            </div>
                        </div>
                        <p className="text-xs text-[#64748B] ml-[108px]">
                            Note: "All" includes In-App and Email only. SMS will be available soon.
                        </p>
                    </div>

                    {/* Schedule Delivery Time */}
                    <div className="space-y-3">
                        <div className="flex items-center gap-8">
                            <Label className="text-base font-medium text-[#1E293B] min-w-[180px]">Schedule Delivery Time:</Label>
                            <div className="flex items-center gap-8">
                                <div className="flex items-center gap-3">
                                    <span className="text-base text-[#64748B]">Send Now</span>
                                    <Switch
                                        checked={deliveryTime === 'now'}
                                        onCheckedChange={() => setDeliveryTime('now')}
                                        disabled={isSendingNotification}
                                    />
                                </div>
                                <div className="flex items-center gap-3">
                                    <span className="text-base text-[#64748B]">Schedule for Later</span>
                                    <Switch
                                        checked={deliveryTime === 'later'}
                                        onCheckedChange={() => setDeliveryTime('later')}
                                        disabled={isSendingNotification}
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <DialogFooter className="gap-3 mt-4">
                    <Button
                        variant="ghost"
                        onClick={handleClose}
                        disabled={isSendingNotification}
                        className="text-[#EF4444] hover:text-[#DC2626] hover:bg-transparent font-medium"
                    >
                        <XCircle className="w-5 h-5 mr-2" />
                        Cancel
                    </Button>
                    <Button
                        onClick={handleSendNotification}
                        disabled={isSendingNotification || !notificationMessage.trim()}
                        className="rounded-full px-8 py-6 bg-[#14B8A6] hover:bg-[#0F9688] text-white font-medium text-base"
                    >
                        {isSendingNotification ? (
                            <>
                                <Loader2 className="w-5 h-5 mr-2 animate-spin" />
                                Sending...
                            </>
                        ) : (
                            'Send Notification'
                        )}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
