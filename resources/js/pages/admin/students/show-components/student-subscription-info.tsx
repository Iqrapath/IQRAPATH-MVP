import React from 'react';
import { Button } from '@/components/ui/button';
import { CheckCircle, ArrowRight, Edit } from 'lucide-react';

interface Subscription {
  plan_name: string;
  start_date: string;
  end_date: string;
  amount_paid: number;
  currency: string;
  status: string;
  auto_renew: boolean;
  student_name?: string; // For guardian views to show which child
}

interface Props {
  subscription?: Subscription | null;
  isGuardian?: boolean;
  childrenSubscriptions?: Subscription[];
}

export default function StudentSubscriptionInfo({ subscription, isGuardian = false, childrenSubscriptions = [] }: Props) {
  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-NG', {
      style: 'currency',
      currency: 'NGN',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(amount);
  };

  // If no subscription data available, show message
  if (!subscription && !isGuardian) {
    return (
      <div className="bg-white rounded-xl shadow-sm p-6">
        <div className="flex-1">
          <h3 className="text-lg font-bold text-gray-800 mb-4">Subscription Information</h3>
          <div className="text-center text-gray-500 py-8">
            No subscription data available
          </div>
        </div>
      </div>
    );
  }

  // For guardians with no children subscriptions
  if (isGuardian && (!childrenSubscriptions || childrenSubscriptions.length === 0)) {
    return (
      <div className="bg-white rounded-xl shadow-sm p-6">
        <div className="flex-1">
          <h3 className="text-lg font-bold text-gray-800 mb-4">Subscription Information</h3>
          <div className="text-center text-gray-500 py-8">
            No subscription data available for children
          </div>
        </div>
      </div>
    );
  }

  const getPlanName = () => {
    return subscription?.plan_name || 'No plan name';
  };

  const getAmount = () => {
    return subscription?.amount_paid ? formatCurrency(subscription.amount_paid) : 'No amount';
  };

  const getStartDate = () => {
    return subscription?.start_date || 'No start date';
  };

  const getEndDate = () => {
    return subscription?.end_date || 'No end date';
  };

  const getStatus = () => {
    return subscription?.status === 'active' ? 'Active' : 'Inactive';
  };

  const isActive = subscription?.status === 'active';

  // Render subscription information
  if (isGuardian && childrenSubscriptions && childrenSubscriptions.length > 0) {
    // For guardians, show subscriptions for each child
    return (
      <div className="bg-white rounded-xl shadow-sm p-6">
        <div className="flex-1">
          <h3 className="text-lg font-bold text-gray-800 mb-4">Subscription Information</h3>
          
          <div className="space-y-4">
            {childrenSubscriptions.map((childSub, index) => (
              <div key={index} className="border border-gray-200 rounded-lg p-4">
                <h4 className="font-medium text-gray-800 mb-3">
                  {childSub.student_name || 'Child'} - Subscription
                </h4>
                
                <div className="space-y-3 text-base">
                  {/* Active Plan */}
                  <div className="flex">
                    <span className="font-medium text-gray-700 w-40">Active Plan:</span>
                    <span className="text-gray-600">
                      {childSub.plan_name || 'No plan name'} - {childSub.amount_paid ? formatCurrency(childSub.amount_paid) : 'No amount'}/month
                    </span>
                  </div>

                  {/* Start Date */}
                  <div className="flex">
                    <span className="font-medium text-gray-700 w-40">Start Date:</span>
                    <span className="text-gray-600">{childSub.start_date || 'No start date'}</span>
                  </div>

                  {/* End Date */}
                  <div className="flex">
                    <span className="font-medium text-gray-700 w-40">End Date:</span>
                    <span className="text-gray-600">{childSub.end_date || 'No end date'}</span>
                  </div>

                  {/* Status */}
                  <div className="flex">
                    <span className="font-medium text-gray-700 w-40">Status:</span>
                    <div className="flex items-center gap-2">
                      <CheckCircle className={`h-4 w-4 ${childSub.status === 'active' ? 'text-green-500' : 'text-gray-400'}`} />
                      <span className={`font-medium ${childSub.status === 'active' ? 'text-green-600' : 'text-gray-500'}`}>
                        {childSub.status === 'active' ? 'Active' : 'Inactive'}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    );
  }

  // For individual students
  return (
    <div className="bg-white rounded-xl shadow-sm p-6">
      <div className="flex-1">
        {/* Title */}
        <h3 className="text-lg font-bold text-gray-800 mb-4">Subscription Information</h3>
        
        <div className="space-y-3 text-base">
          {/* Active Plan */}
          <div className="flex">
            <span className="font-medium text-gray-700 w-40">Active Plan:</span>
            <span className="text-gray-600">{getPlanName()} - {getAmount()}/month</span>
          </div>

          {/* Start Date */}
          <div className="flex">
            <span className="font-medium text-gray-700 w-40">Start Date:</span>
            <span className="text-gray-600">{getStartDate()}</span>
          </div>

          {/* End Date */}
          <div className="flex">
            <span className="font-medium text-gray-700 w-40">End Date:</span>
            <span className="text-gray-600">{getEndDate()}</span>
          </div>

          {/* Payment History */}
          <div className="flex">
            <span className="font-medium text-gray-700 w-40">Payment History:</span>
            <span className="text-teal-600 hover:underline cursor-pointer flex items-center gap-1">
              View All Transactions <ArrowRight className="h-4 w-4" />
            </span>
          </div>

          {/* Status */}
          <div className="flex">
            <span className="font-medium text-gray-700 w-40">Status:</span>
            <div className="flex items-center gap-2">
              <CheckCircle className={`h-4 w-4 ${isActive ? 'text-green-500' : 'text-gray-400'}`} />
              <span className={`font-medium ${isActive ? 'text-green-600' : 'text-gray-500'}`}>
                {getStatus()}
              </span>
            </div>
          </div>
        </div>

        {/* Action Buttons */}
        <div className="flex justify-end gap-3 mt-6">
          <Button className="bg-teal-700 hover:bg-teal-800 text-white px-4 py-2 text-sm">
            Upgrade Plan
          </Button>
          <Button variant="outline" className="border-gray-300 text-teal-600 hover:bg-gray-50 px-4 py-2 text-sm">
            Renew Plan
          </Button>
          <Button variant="ghost" className="text-red-600 hover:text-red-700 hover:bg-red-50 px-4 py-2 text-sm">
            Cancel Subscription
          </Button>
        </div>

        {/* Edit Link */}
        <div className="flex justify-end mt-4">
          <Button 
            variant="link" 
            className="text-sm p-0 h-auto text-teal-600 hover:text-teal-700 cursor-pointer"
          >
            Edit
          </Button>
        </div>
      </div>
    </div>
  );
}
