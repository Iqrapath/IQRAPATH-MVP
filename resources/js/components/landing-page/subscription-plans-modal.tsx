import React, { useState } from 'react';
import { Link } from '@inertiajs/react';
import AppModal from '@/components/common/AppModal';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Check, Star, Clock, Users, Award } from 'lucide-react';
import { toast } from 'sonner';

interface SubscriptionPlan {
  id: number;
  name: string;
  description?: string;
  price_naira: number;
  price_dollar: number;
  billing_cycle: 'monthly' | 'quarterly' | 'biannually' | 'annually';
  duration_months: number;
  features?: string[];
  tags?: string[];
  image_path?: string;
  is_active: boolean;
}

interface SubscriptionPlansModalProps {
  isOpen: boolean;
  onClose: () => void;
  plans: SubscriptionPlan[];
}

export default function SubscriptionPlansModal({ isOpen, onClose, plans }: SubscriptionPlansModalProps) {
  const [selectedPlan, setSelectedPlan] = useState<number | null>(null);

  const formatPrice = (plan: SubscriptionPlan) => {
    return `₦${plan.price_naira.toLocaleString()}`;
  };

  const formatBillingCycle = (cycle: string) => {
    return cycle.charAt(0).toUpperCase() + cycle.slice(1);
  };

  const getPlanIcon = (planName: string) => {
    const name = planName.toLowerCase();
    if (name.includes('basic') || name.includes('starter')) {
      return <Users className="w-6 h-6 text-blue-500" />;
    } else if (name.includes('premium') || name.includes('advanced')) {
      return <Star className="w-6 h-6 text-yellow-500" />;
    } else if (name.includes('pro') || name.includes('complete')) {
      return <Award className="w-6 h-6 text-purple-500" />;
    }
    return <Clock className="w-6 h-6 text-green-500" />;
  };

  const getPlanBadgeColor = (planName: string) => {
    const name = planName.toLowerCase();
    if (name.includes('basic') || name.includes('starter')) {
      return 'bg-blue-100 text-blue-800';
    } else if (name.includes('premium') || name.includes('advanced')) {
      return 'bg-yellow-100 text-yellow-800';
    } else if (name.includes('pro') || name.includes('complete')) {
      return 'bg-purple-100 text-purple-800';
    }
    return 'bg-green-100 text-green-800';
  };

  const handleSelectPlan = (planId: number) => {
    setSelectedPlan(planId);
  };

  const handleSubscribe = (plan: SubscriptionPlan) => {
    // For now, just show a toast notification that this feature is coming soon
    toast.info(`The ${plan.name} plan will be available for subscription soon!`, {
      description: "Please check back later for updates.",
      duration: 4000,
    });
  };

  return (
    <AppModal
      open={Boolean(isOpen)}
      onOpenChange={(open) => {
        if (!open) {
          onClose();
        }
      }}
      title="Choose Your Quran Memorization Plan"
      description="Select the perfect plan for your child's Quran learning journey"
      size="xl"
    >
      <div className="space-y-6">
        {/* Plans Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {plans.map((plan) => (
            <div
              key={plan.id}
              className={`relative bg-white rounded-xl border-2 p-6 transition-all duration-200 hover:shadow-lg ${
                selectedPlan === plan.id
                  ? 'border-[#2F8D8C] shadow-lg'
                  : 'border-gray-200 hover:border-[#2F8D8C]/50'
              }`}
            >
              {/* Plan Header */}
              <div className="text-center mb-6">
                <div className="flex justify-center mb-3">
                  {getPlanIcon(plan.name)}
                </div>
                <h3 className="text-xl font-bold text-gray-900 mb-2">{plan.name}</h3>
                <Badge className={`${getPlanBadgeColor(plan.name)} text-xs`}>
                  {formatBillingCycle(plan.billing_cycle)}
                </Badge>
              </div>

              {/* Price */}
              <div className="text-center mb-6">
                <div className="text-3xl font-bold text-[#2F8D8C] mb-1">
                  {formatPrice(plan)}
                </div>
                <div className="text-sm text-gray-500">
                  per {plan.billing_cycle === 'monthly' ? 'month' : plan.billing_cycle}
                </div>
              </div>

              {/* Description */}
              {plan.description && (
                <p className="text-gray-600 text-sm mb-6 text-center">
                  {plan.description}
                </p>
              )}

              {/* Features */}
              {plan.features && plan.features.length > 0 && (
                <div className="mb-6">
                  <ul className="space-y-2">
                    {plan.features.map((feature, index) => (
                      <li key={index} className="flex items-start text-sm text-gray-600">
                        <Check className="w-4 h-4 text-green-500 mr-2 mt-0.5 flex-shrink-0" />
                        <span>{feature}</span>
                      </li>
                    ))}
                  </ul>
                </div>
              )}

              {/* Tags */}
              {plan.tags && plan.tags.length > 0 && (
                <div className="mb-6">
                  <div className="flex flex-wrap gap-1">
                    {plan.tags.map((tag, index) => (
                      <Badge key={index} variant="secondary" className="text-xs">
                        {tag}
                      </Badge>
                    ))}
                  </div>
                </div>
              )}

              {/* Select Button */}
              <Button
                onClick={() => handleSelectPlan(plan.id)}
                className={`w-full ${
                  selectedPlan === plan.id
                    ? 'bg-[#2F8D8C] hover:bg-[#267373] text-white'
                    : 'bg-gray-100 hover:bg-gray-200 text-gray-700'
                }`}
                variant={selectedPlan === plan.id ? 'default' : 'outline'}
              >
                {selectedPlan === plan.id ? 'Selected' : 'Select Plan'}
              </Button>
            </div>
          ))}
        </div>

        {/* Action Buttons */}
        <div className="flex flex-col sm:flex-row gap-4 pt-6 border-t">
          <Button
            onClick={onClose}
            variant="outline"
            className="flex-1"
          >
            Cancel
          </Button>
          <Button
            onClick={() => {
              if (selectedPlan) {
                const plan = plans.find(p => p.id === selectedPlan);
                if (plan) {
                  handleSubscribe(plan);
                }
              }
            }}
            disabled={!selectedPlan}
            className="flex-1 bg-[#2F8D8C] hover:bg-[#267373] text-white"
          >
            {selectedPlan ? 'Coming Soon' : 'Select a Plan First'}
          </Button>
        </div>

        {/* Help Text */}
        <div className="text-center text-sm text-gray-500">
          <p>
            Subscription plans are coming soon! Contact our support team for more information.
          </p>
        </div>
      </div>
    </AppModal>
  );
}
