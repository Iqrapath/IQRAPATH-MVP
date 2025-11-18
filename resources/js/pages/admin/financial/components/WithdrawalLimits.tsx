import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { router } from '@inertiajs/react';
import { toast } from 'sonner';
import { Pencil } from 'lucide-react';

interface WithdrawalLimitsProps {
    daily_withdrawal_limit: number;
    monthly_withdrawal_limit: number;
    instant_payouts_enabled: boolean;
}

interface WithdrawalLimitsData extends Record<string, any> {
    daily_withdrawal_limit: number;
    monthly_withdrawal_limit: number;
    instant_payouts_enabled: boolean;
}

interface Props {
    settings?: WithdrawalLimitsProps;
}

export default function WithdrawalLimits({ settings }: Props) {
    const [isEditing, setIsEditing] = useState<Record<string, boolean>>({});
    const [formData, setFormData] = useState<WithdrawalLimitsData>({
        daily_withdrawal_limit: settings?.daily_withdrawal_limit || 500000,
        monthly_withdrawal_limit: settings?.monthly_withdrawal_limit || 5000000,
        instant_payouts_enabled: settings?.instant_payouts_enabled ?? true,
    });

    const toggleEdit = (field: string) => {
        setIsEditing({ ...isEditing, [field]: !isEditing[field] });
    };

    const handleSave = () => {
        router.post(route('admin.financial.settings.withdrawal-limits.update'), formData, {
            onSuccess: () => {
                toast.success('Withdrawal limits updated successfully!');
                setIsEditing({});
            },
            onError: () => {
                toast.error('Failed to update settings. Please try again.');
            },
        });
    };

    const handleCancel = () => {
        if (settings) {
            setFormData({
                daily_withdrawal_limit: settings.daily_withdrawal_limit,
                monthly_withdrawal_limit: settings.monthly_withdrawal_limit,
                instant_payouts_enabled: settings.instant_payouts_enabled,
            });
        }
        setIsEditing({});
    };

    const formatCurrency = (value: number) => {
        return new Intl.NumberFormat('en-NG', {
            style: 'currency',
            currency: 'NGN',
            minimumFractionDigits: 0,
        }).format(value);
    };

    return (
        <div className="mt-[28px] bg-white rounded-lg border border-gray-200 p-8">
            <h2 className="text-[24px] font-semibold text-[#1E293B] mb-6">Withdrawal Limits</h2>

            <div className="space-y-6">
                {/* Daily Withdrawal Limit */}
                <div className="flex items-center justify-between py-4 border-b border-gray-100">
                    <div className="flex-1">
                        <Label className="text-[14px] font-medium text-[#64748B]">Daily Withdrawal Limit</Label>
                        {isEditing.daily_withdrawal_limit ? (
                            <Input
                                type="number"
                                value={formData.daily_withdrawal_limit}
                                onChange={(e) => setFormData({ ...formData, daily_withdrawal_limit: parseFloat(e.target.value) })}
                                className="mt-2 max-w-xs"
                                placeholder="Enter daily limit"
                            />
                        ) : (
                            <p className="text-[16px] text-[#1E293B] mt-1">{formatCurrency(formData.daily_withdrawal_limit)}</p>
                        )}
                    </div>
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => toggleEdit('daily_withdrawal_limit')}
                        className="text-[#14B8A6] hover:text-[#129c8e]"
                    >
                        <Pencil className="w-4 h-4 mr-1" />
                        Edit
                    </Button>
                </div>

                {/* Monthly Withdrawal Limit */}
                <div className="flex items-center justify-between py-4 border-b border-gray-100">
                    <div className="flex-1">
                        <Label className="text-[14px] font-medium text-[#64748B]">Monthly Withdrawal Limit</Label>
                        {isEditing.monthly_withdrawal_limit ? (
                            <Input
                                type="number"
                                value={formData.monthly_withdrawal_limit}
                                onChange={(e) => setFormData({ ...formData, monthly_withdrawal_limit: parseFloat(e.target.value) })}
                                className="mt-2 max-w-xs"
                                placeholder="Enter monthly limit"
                            />
                        ) : (
                            <p className="text-[16px] text-[#1E293B] mt-1">{formatCurrency(formData.monthly_withdrawal_limit)}</p>
                        )}
                    </div>
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => toggleEdit('monthly_withdrawal_limit')}
                        className="text-[#14B8A6] hover:text-[#129c8e]"
                    >
                        <Pencil className="w-4 h-4 mr-1" />
                        Edit
                    </Button>
                </div>

                {/* Instant Payouts */}
                <div className="flex items-center justify-between py-4">
                    <div className="flex-1">
                        <Label className="text-[14px] font-medium text-[#64748B]">Instant Payouts</Label>
                        <p className="text-[12px] text-[#94A3B8] mt-1">
                            Enable instant payout processing for eligible requests
                        </p>
                    </div>
                    <Switch
                        checked={formData.instant_payouts_enabled}
                        onCheckedChange={(checked) => setFormData({ ...formData, instant_payouts_enabled: checked })}
                    />
                </div>
            </div>

            {/* Action Buttons */}
            {Object.values(isEditing).some(Boolean) && (
                <div className="flex justify-end gap-3 mt-6 pt-6 border-t border-gray-200">
                    <Button
                        variant="outline"
                        onClick={handleCancel}
                        className="text-[#EF4444] border-[#EF4444] hover:bg-[#FEF2F2]"
                    >
                        Cancel
                    </Button>
                    <Button
                        onClick={handleSave}
                        className="bg-[#14B8A6] hover:bg-[#129c8e] text-white"
                    >
                        Save Changes
                    </Button>
                </div>
            )}
        </div>
    );
}
