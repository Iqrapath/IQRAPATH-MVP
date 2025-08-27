import React from 'react';
import { Button } from '@/components/ui/button';
import { CheckCircle } from 'lucide-react';

interface Subscription {
    plan_name: string;
    amount: number;
    currency: string;
    start_date: string;
    end_date: string;
    billing_cycle: string;
    status: string;
}

interface Props {
    subscription: Subscription | null;
}

export default function StudentPlanOverview({ subscription }: Props) {
    const getPlanDisplay = () => {
        if (!subscription) return 'No Plan Enrolled';
        return `${subscription.plan_name} - ${subscription.currency}${subscription.amount}/${subscription.billing_cycle}`;
    };

    const getStatusDisplay = () => {
        if (!subscription) return 'No Subscription';
        
        if (subscription.status === 'active') {
            return (
                <div className="flex items-center gap-2">
                    <CheckCircle className="h-4 w-4 text-green-500" />
                    <span className="text-green-600">Active</span>
                </div>
            );
        } else if (subscription.status === 'expired') {
            return (
                <div className="flex items-center gap-2">
                    <span className="text-red-600">Expired</span>
                </div>
            );
        } else if (subscription.status === 'cancelled') {
            return (
                <div className="flex items-center gap-2">
                    <span className="text-gray-600">Cancelled</span>
                </div>
            );
        }
        
        return (
            <div className="flex items-center gap-2">
                <span className="text-gray-600">{subscription.status}</span>
            </div>
        );
    };

    const handleUpgradePlan = () => {
        // Handle upgrade plan action
        console.log('Upgrade plan clicked');
    };

    const handleRenewPlan = () => {
        // Handle renew plan action
        console.log('Renew plan clicked');
    };

    const handleCancelSubscription = () => {
        // Handle cancel subscription action
        console.log('Cancel subscription clicked');
    };

    const handleEdit = () => {
        // Handle edit action
        console.log('Edit clicked');
    };

    return (
        <div className="bg-[#F2FFFE] rounded-xl border border-teal-600 shadow-sm p-6">
            {/* Title */}
            <h1 className="text-xl font-bold text-gray-800 mb-6">Plan Overview</h1>
            
            <div className="space-y-4 text-base">
                {/* Enrolled Plan */}
                <div className="flex">
                    <span className="font-medium text-gray-700 w-40">Enrolled Plan:</span>
                    <span className="text-gray-600">{getPlanDisplay()}</span>
                </div>

                {/* Start Date */}
                <div className="flex">
                    <span className="font-medium text-gray-700 w-40">Start Date:</span>
                    <span className="text-gray-600">
                        {subscription?.start_date || 'Not specified'}
                    </span>
                </div>

                {/* End Date */}
                <div className="flex">
                    <span className="font-medium text-gray-700 w-40">End Date:</span>
                    <span className="text-gray-600">
                        {subscription?.end_date || 'Not specified'}
                    </span>
                </div>

                {/* Billing */}
                <div className="flex">
                    <span className="font-medium text-gray-700 w-40">Billing:</span>
                    <span className="text-gray-600">
                        {subscription?.billing_cycle || 'Not specified'}
                    </span>
                </div>

                {/* Status */}
                <div className="flex">
                    <span className="font-medium text-gray-700 w-40">Status:</span>
                    <span className="text-gray-600">
                        {getStatusDisplay()}
                    </span>
                </div>
            </div>

            {/* Action Buttons and Links */}
            {subscription ? (
                <div className="mt-8 flex flex-col items-end">
                    {/* Primary Action Buttons */}
                    <div className="flex items-center gap-4 mb-4">
                        <Button 
                            onClick={handleUpgradePlan}
                            className="bg-teal-700 hover:bg-teal-800 text-white px-4 py-2 rounded-full"
                        >
                            Upgrade Plan
                        </Button>
                        
                        {subscription.status === 'active' && (
                            <button 
                                onClick={handleRenewPlan}
                                className="text-teal-600 hover:text-teal-700 font-medium cursor-pointer"
                            >
                                Renew Plan
                            </button>
                        )}
                        
                        {subscription.status === 'active' && (
                            <button 
                                onClick={handleCancelSubscription}
                                className="text-red-600 hover:text-red-700 font-medium cursor-pointer"
                            >
                                Cancel Subscription
                            </button>
                        )}
                    </div>

                    {/* Edit Link - Positioned below and to the right */}
                    <div className="flex justify-end">
                        <button 
                            onClick={handleEdit}
                            className="text-gray-500 hover:text-gray-700 text-sm cursor-pointer"
                        >
                            Edit
                        </button>
                    </div>
                </div>
            ) : (
                <div className="mt-8 flex flex-col items-end">
                    {/* No Subscription - Show Enroll Button */}
                    <div className="flex items-center gap-4 mb-4">
                        <Button 
                            onClick={handleUpgradePlan}
                            className="bg-teal-700 hover:bg-teal-800 text-white px-4 py-2 rounded-full"
                        >
                            Enroll in a Plan
                        </Button>
                        <button 
                            onClick={handleEdit}
                            className="text-gray-500 hover:text-gray-700 text-sm cursor-pointer"
                        >
                            Edit
                        </button>
                    </div>
                </div>
            )}
        </div>
    );
}
