import { cn } from '@/lib/utils';
import { ReactNode, useState } from 'react';
import { Button } from '@/components/ui/button';
import { X, Wallet, ChevronDown, Copy, Bell, User } from 'lucide-react';
import { router, usePage } from '@inertiajs/react';
import { PageProps } from '@/types';
import FundAccountModal from '@/components/student/FundAccountModal';
import { toast } from 'sonner';
import MessageUserIcon from '@/components/icons/message-user-icon';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useCurrency } from '@/contexts/CurrencyContext';

interface Notification {
    id: string;
    sender: string;
    message: string;
    timestamp: string;
    avatar?: string | null;
    type: string;
    is_read: boolean;
}

interface GuardianRightSidebarProps {
    children?: ReactNode;
    className?: string;
    isMobile?: boolean;
    onClose?: () => void;
    notifications?: Notification[];
}

export default function GuardianRightSidebar({
    children,
    className,
    isMobile = false,
    onClose,
    notifications = []
}: GuardianRightSidebarProps) {
    const { auth } = usePage<PageProps>().props;
    const [showFundModal, setShowFundModal] = useState(false);
    const { selectedCurrency, currencyRates, currencySymbols, setSelectedCurrency, formatBalance } = useCurrency();
    
    // Get wallet balance from guardian wallet relationship (preferred) or fallback to wallet_balance
    const walletBalanceNGN = (auth.user as any)?.guardianWallet?.balance || auth.user?.wallet_balance || 0;
    
    // Generate stable payment ID for guardian (based on user ID, doesn't change on re-renders)
    const paymentId = (auth.user as any)?.guardianWallet ? `IQR-GUA-${String(auth.user.id).padStart(4, '0')}-${String(auth.user.id * 7).slice(-6)}` : "IQR-GUA-LOADING...";

    const handleCopyPaymentId = () => {
        navigator.clipboard.writeText(paymentId);
        toast.dismiss();
        toast.success('Payment ID copied to clipboard', {
            duration: 3000,
            description: `ID: ${paymentId}`,
            action: {
                label: 'Copy Again',
                onClick: () => navigator.clipboard.writeText(paymentId)
            }
        });
    };

    const handleTopUpBalance = () => {
        setShowFundModal(true);
    };

    const handleCloseFundModal = () => {
        setShowFundModal(false);
    };

    const handlePayment = (paymentData: any) => {
        // Handle payment processing
        console.log('Payment data:', paymentData);

        // Show loading toast first
        const loadingToast = toast.loading('Processing payment...', {
            description: 'Please wait while we process your payment'
        });

        // Simulate payment processing (replace with actual payment logic)
        setTimeout(() => {
            toast.dismiss(loadingToast);

            toast.success('Payment successful!', {
                duration: 5000,
                description: `${formatBalance(paymentData.amount || 0)} added to your wallet`,
                action: {
                    label: 'View Balance',
                    onClick: () => {
                        // You could navigate to wallet page or refresh balance
                        window.location.reload();
                    }
                }
            });

            setShowFundModal(false);
        }, 2000);
    };

    // Use notifications from database (passed as props)
    // If no notifications, show empty state
    const hasNotifications = notifications && notifications.length > 0;

    const defaultContent = (
        <div className="space-y-6 bg-[#E8F5F3] rounded-2xl shadow-lg">
            {/* Top Up Card */}
            <div className="bg-[#E8F5F3] p-4 rounded-2xl mx-auto w-full">
                <div className="bg-white rounded-2xl p-4 shadow-lg mx-auto w-full">
                    {/* Card Header */}
                    <div className="flex items-center justify-between mb-4">
                        <div className="flex items-center gap-3">
                            <div className="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center">
                                <Wallet className="w-6 h-6 text-gray-600" />
                            </div>
                            <h3 className="text-lg font-semibold text-gray-900">Your Balance</h3>
                        </div>
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="ghost" className="flex items-center gap-1 text-[#338078] hover:text-[#236158] p-1 h-auto">
                                    <span className="text-sm font-medium">{selectedCurrency}</span>
                                    <ChevronDown className="w-4 h-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" className="w-32">
                                {Object.keys(currencyRates).map((currency) => (
                                    <DropdownMenuItem
                                        key={currency}
                                        onClick={() => setSelectedCurrency(currency)}
                                        className={`cursor-pointer ${
                                            selectedCurrency === currency ? 'bg-[#E8F5F3] text-[#338078]' : ''
                                        }`}
                                    >
                                        <div className="flex items-center justify-between w-full">
                                            <span className="font-medium">{currency}</span>
                                            <span className="text-sm text-gray-500">{currencySymbols[currency as keyof typeof currencySymbols]}</span>
                                        </div>
                                    </DropdownMenuItem>
                                ))}
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>

                    {/* Payment ID */}
                    <div className="flex items-center justify-between mb-4">
                        <span className="text-sm text-gray-600">Payment ID: {paymentId}</span>
                        <button
                            onClick={handleCopyPaymentId}
                            className="p-1 hover:bg-gray-100 rounded"
                        >
                            <Copy className="w-4 h-4 text-gray-400" />
                        </button>
                    </div>

                    {/* Balance Amount */}
                    <div className="mb-6 pt-2">
                        <span className="text-3xl font-bold text-gray-900">
                            {formatBalance(walletBalanceNGN)}
                        </span>
                    </div>

                    {/* Top Up Button */}
                    <div className="text-center">
                        <button
                            onClick={handleTopUpBalance}
                            className="text-[#338078] hover:text-[#236158] font-medium text-sm transition-colors"
                        >
                            Top Up Balance
                        </button>
                    </div>
                </div>
            </div>

            {/* Notifications Section */}
            <div className="bg-[#E8F5F3] p-4">
                {/* Notifications Header */}
                <div className="flex items-center gap-3 mb-4 pl-4">
                    <MessageUserIcon className="w-5 h-5 text-gray-600" />
                    <h3 className="text-lg font-semibold text-gray-900">Notifications</h3>
                </div>

                {/* Notifications List */}
                <div className="space-y-0">
                    {hasNotifications ? (
                        notifications.map((notification, index) => (
                            <div key={notification.id}>
                                <div className="flex items-start gap-3 py-4">
                                    {/* Avatar */}
                                    <div className="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center flex-shrink-0">
                                        {notification.avatar ? (
                                            <img
                                                src={notification.avatar}
                                                alt={notification.sender}
                                                className="w-10 h-10 rounded-full object-cover"
                                            />
                                        ) : (
                                            <User className="w-5 h-5 text-gray-500" />
                                        )}
                                    </div>

                                    {/* Notification Content */}
                                    <div className="flex-1 min-w-0">
                                        <div className="flex items-center justify-between mb-1">
                                            <h4 className="text-sm font-semibold text-gray-900 truncate">
                                                {notification.sender}
                                            </h4>
                                            <span className="text-xs text-gray-500 ml-2 flex-shrink-0">
                                                {notification.timestamp}
                                            </span>
                                        </div>
                                        <p className="text-sm text-gray-600 leading-relaxed">
                                            {notification.message}
                                        </p>
                                    </div>
                                </div>

                                {/* Separator line */}
                                {index < notifications.length - 1 && (
                                    <div className="border-t border-gray-100"></div>
                                )}
                            </div>
                        ))
                    ) : (
                        <div className="py-8 text-center">
                            <div className="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                <Bell className="w-6 h-6 text-gray-400" />
                            </div>
                            <p className="text-sm text-gray-500">No notifications yet</p>
                            <p className="text-xs text-gray-400 mt-1">You'll see updates about your children's progress here</p>
                        </div>
                    )}
                </div>

                {/* View All Notifications Link */}
                <div className="text-center mt-4 pt-4 border-t border-gray-100">
                    <button
                        onClick={() => router.visit('/guardian/notifications')}
                        className="text-[#14B8A6] hover:text-[#0D9488] font-medium text-sm transition-colors">
                        View All Notifications
                    </button>
                </div>
            </div>

            {/* Quran Memorization Plans Section */}
            <div className="bg-gradient-to-b from-[#E8F5F3] to-[#EEFFE6] rounded-2xl p-4 shadow-lg mx-auto w-ful">
                <div className="text-center mb-4">
                    <img
                        src="/assets/images/quran-boy.png"
                        alt="Boy reading Quran"
                        className="w-50 h-50 mx-auto rounded-full"
                    />
                </div>

                <h3 className="text-sm font-semibold text-gray-900 text-center mb-2">
                    Enroll in Our Quran Memorization Plans Today!
                </h3>

                <p className="text-xs text-gray-600 text-center mb-4">
                    Full Quran, Half Quran, or Juz' Amma – Tailored Learning for Every Student.
                </p>

                <div className="space-y-1">

                    <div className="text-center justify-center">
                        <button
                            onClick={() => router.visit('/guardian/memorization-plans')}
                            className="w-full bg-[#14B8A6] hover:bg-[#0D9488] text-white text-xs py-2 px-3 rounded-full"
                        >
                            View Memorization Plans
                        </button>
                        <span className="text-xs text-gray-500 mx-2">Not sure? </span>
                        <button
                            onClick={() => router.visit('/guardian/plan-matcher')}
                            className="text-xs text-[#14B8A6] hover:text-[#0D9488] underline"
                        >
                            Match Me
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );

    return (
        <>
            <div className={cn(
                "w-72 p-4",
                isMobile && "bg-white shadow-xl h-full",
                className
            )}>
                {isMobile && (
                    <div className="flex justify-between items-center mb-4">
                        <h3 className="text-lg font-medium">Details</h3>
                        <Button variant="ghost" size="sm" className="p-1 h-auto" onClick={onClose}>
                            <X className="h-4 w-4" />
                        </Button>
                    </div>
                )}
                {children || defaultContent}
            </div>

            {/* Fund Account Modal */}
            <FundAccountModal
                isOpen={showFundModal}
                onClose={handleCloseFundModal}
                onPayment={handlePayment}
                user={auth.user ? {
                    id: auth.user.id,
                    name: auth.user.name,
                    email: auth.user.email || '',
                    country: 'NG' // ISO 3166-1 alpha-2 country code for Nigeria
                } : undefined}
            />
        </>
    );
}