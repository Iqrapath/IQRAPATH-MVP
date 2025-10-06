import { cn } from '@/lib/utils';
import { ReactNode, useState } from 'react';
import { Button } from '@/components/ui/button';
import { X, Wallet, ChevronDown, Copy } from 'lucide-react';
import { usePage } from '@inertiajs/react';
import { PageProps } from '@/types';
import FundAccountModal from '@/components/student/FundAccountModal';
import { toast } from 'sonner';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useCurrency } from '@/contexts/CurrencyContext';

interface StudentRightSidebarProps {
    children?: ReactNode;
    className?: string;
    isMobile?: boolean;
    onClose?: () => void;
}

export default function StudentRightSidebar({
    children,
    className,
    isMobile = false,
    onClose
}: StudentRightSidebarProps) {
    const { auth } = usePage<PageProps>().props;
    const [showFundModal, setShowFundModal] = useState(false);
    const { selectedCurrency, currencyRates, currencySymbols, setSelectedCurrency, formatBalance } = useCurrency();
    
    // Get wallet balance from wallet relationship (preferred) or fallback to wallet_balance
    const walletBalanceNGN = (auth.user as { wallet?: { balance: number } })?.wallet?.balance || auth.user?.wallet_balance || 0;
    // Payment ID from wallet - now guaranteed to be unique
    const paymentId = (auth.user as { wallet?: { payment_id: string } })?.wallet?.payment_id || "IQR-STU-LOADING...";

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

    const defaultContent = (
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