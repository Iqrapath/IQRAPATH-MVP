import React from 'react';
import { Check } from 'lucide-react';

function formatCurrency(value?: number | string): string {
  const amount = Number(value ?? 0);
  if (Number.isNaN(amount)) return '0';
  return amount.toLocaleString();
}

interface TeacherTabPricingProps {
  usd?: number | string;
  ngn?: number | string;
  teacher?: {
    hourly_rate_usd?: number | string;
    hourly_rate_ngn?: number | string;
    wallet_data?: {
      payment_methods?: string[];
      default_payment_method?: string;
      balance?: number;
      total_earned?: number;
    };
    [key: string]: any;
  };
}

export default function TeacherTabPricing({ usd, ngn, teacher }: TeacherTabPricingProps) {
  // Use props or fallback to teacher object values
  const usdRate = usd || teacher?.hourly_rate_usd || 30;
  const ngnRate = ngn || teacher?.hourly_rate_ngn || 15000;
  
  // Get payment methods from wallet data
  const walletData = teacher?.wallet_data;
  const paymentMethods = walletData?.payment_methods || ['PayPal', 'Credit/Debit Card', 'Bank Transfer'];
  const defaultPaymentMethod = walletData?.default_payment_method;

  return (
    <div className="bg-white border border-gray-100 rounded-2xl p-6">
      {/* Session Rate */}
      <div className="mb-6">
        <h4 className="text-sm text-gray-500 mb-3">Session Rate:</h4>
        <div className="flex items-baseline gap-2 mb-2">
          <span className="text-2xl font-bold text-gray-900">${formatCurrency(usdRate)}/</span>
          <span className="text-lg font-medium text-gray-600">₦{formatCurrency(ngnRate)}</span>
        </div>
        <p className="text-sm text-gray-500">per session</p>
      </div>

      {/* Currency */}
      <div className="mb-6">
        <h4 className="text-sm text-gray-500 mb-3">Currency:</h4>
        <div className="flex items-center gap-4">
          <span className="text-lg font-medium text-gray-900">USD</span>
          <span className="text-gray-400">&</span>
          <span className="text-lg font-medium text-gray-900">NGN</span>
        </div>
      </div>

      {/* Payment Methods */}
      <div className="mb-6">
        <h4 className="text-sm text-gray-500 mb-4">Payment Methods:</h4>
        <div className="grid grid-cols-1 gap-3">
          {paymentMethods.map((method, index) => {
            const isDefault = method === defaultPaymentMethod;
            return (
              <div 
                key={index} 
                className={`flex items-center gap-3 p-3 rounded-lg ${
                  isDefault ? 'bg-[#2C7870]/10 border border-[#2C7870]/20' : 'bg-gray-50'
                }`}
              >
                <div className="flex items-center justify-center w-6 h-6 bg-[#2C7870] rounded-full">
                  <Check className="w-4 h-4 text-white" />
                </div>
                <div className="flex-1">
                  <span className="text-sm font-medium text-gray-900">{method}</span>
                  {isDefault && (
                    <span className="ml-2 text-xs bg-[#2C7870] text-white px-2 py-1 rounded-full">
                      Default
                    </span>
                  )}
                </div>
              </div>
            );
          })}
        </div>
      </div>
    </div>
  );
}


