import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { router } from '@inertiajs/react';
import { toast } from 'sonner';

/**
 * 🎨 FIGMA DESIGN REFERENCE
 * 
 * Component: Commission Settings / Payment Settings
 * Design: Commission settings form with editable fields
 * 
 * 📏 EXACT SPECIFICATIONS:
 * - Title: 24px, weight 600, color #1E293B
 * - Section labels: 14px, weight 500, color #64748B
 * - Input fields: height 48px, rounded-lg, border #E2E8F0
 * - Edit buttons: color #14B8A6, text 14px
 * - Save button: bg #14B8A6, rounded-lg, height 48px
 * - Cancel button: text #EF4444
 * - Textarea: min-height 120px
 */

interface PaymentSettingsProps {
    commission_rate: number;
    commission_type: 'fixed_percentage' | 'tiered';
    auto_payout_threshold: number;
    minimum_withdrawal_amount: number;
    bank_verification_enabled: boolean;
    withdrawal_note: string;
}

interface PaymentSettingsData extends Record<string, any> {
    commission_rate: number;
    commission_type: 'fixed_percentage' | 'tiered';
    auto_payout_threshold: number;
    minimum_withdrawal_amount: number;
    bank_verification_enabled: boolean;
    withdrawal_note: string;
    apply_time: 'now' | 'scheduled';
    scheduled_date?: string;
}

interface Props {
    settings?: PaymentSettingsProps;
}

export default function PaymentSettings({ settings }: Props) {
    const [isEditing, setIsEditing] = useState<Record<string, boolean>>({});
    const [formData, setFormData] = useState<PaymentSettingsData>({
        commission_rate: settings?.commission_rate || 10,
        commission_type: settings?.commission_type || 'fixed_percentage',
        auto_payout_threshold: settings?.auto_payout_threshold || 50000,
        minimum_withdrawal_amount: settings?.minimum_withdrawal_amount || 10000,
        bank_verification_enabled: settings?.bank_verification_enabled ?? true,
        withdrawal_note: settings?.withdrawal_note || 'Teachers cannot request withdrawal unless wallet is ₦10,000+',
        apply_time: 'now',
        scheduled_date: undefined,
    });

    const toggleEdit = (field: string) => {
        setIsEditing({ ...isEditing, [field]: !isEditing[field] });
    };

    const handleSave = () => {
        router.post(route('admin.financial.settings.payment.update'), formData, {
            onSuccess: () => {
                toast.success('Payment settings updated successfully!');
                setIsEditing({});
            },
            onError: () => {
                toast.error('Failed to update settings. Please try again.');
            },
        });
    };

    const handleCancel = () => {
        // Reset to original settings
        if (settings) {
            setFormData({
                commission_rate: settings.commission_rate,
                commission_type: settings.commission_type,
                auto_payout_threshold: settings.auto_payout_threshold,
                minimum_withdrawal_amount: settings.minimum_withdrawal_amount,
                bank_verification_enabled: settings.bank_verification_enabled,
                withdrawal_note: settings.withdrawal_note,
                apply_time: 'now',
                scheduled_date: undefined,
            });
        }
        setIsEditing({});
    };

    return (
        <div className="mt-[28px] max-w-[600px]">
            <h2 className="text-[24px] font-semibold text-[#1E293B] mb-6">Commission Settings</h2>

            <div className="space-y-6">
                {/* Current Commission Rate */}
                <div>
                    <Label className="text-[14px] font-medium text-[#64748B] mb-2 block">
                        Current Commission Rate
                    </Label>
                    <div className="flex items-center gap-3">
                        <Input
                            type="text"
                            value={isEditing.commission_rate ? formData.commission_rate : `${formData.commission_rate}%`}
                            onChange={(e) => setFormData({ ...formData, commission_rate: parseFloat(e.target.value) || 0 })}
                            disabled={!isEditing.commission_rate}
                            className="h-[48px] rounded-lg border-[#E2E8F0]"
                            placeholder="e.g., 10%"
                        />
                        <Button
                            variant="ghost"
                            onClick={() => toggleEdit('commission_rate')}
                            className="text-[#14B8A6] hover:text-[#129c8e] text-[14px]"
                        >
                            {isEditing.commission_rate ? 'Done' : 'Edit Rate'}
                        </Button>
                    </div>
                </div>

                {/* Commission Type */}
                <div>
                    <Label className="text-[14px] font-medium text-[#64748B] mb-2 block">
                        Commission Type
                    </Label>
                    <Select
                        value={formData.commission_type}
                        onValueChange={(value) => setFormData({ ...formData, commission_type: value as 'fixed_percentage' | 'tiered' })}
                    >
                        <SelectTrigger className="h-[48px] rounded-lg border-[#E2E8F0]">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="fixed_percentage">Fixed Percentage</SelectItem>
                            <SelectItem value="tiered">Tiered</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                {/* Auto-Payout Threshold */}
                <div>
                    <Label className="text-[14px] font-medium text-[#64748B] mb-2 block">
                        Auto-Payout Threshold
                    </Label>
                    <div className="flex items-center gap-3">
                        <Input
                            type="text"
                            value={isEditing.auto_payout ? formData.auto_payout_threshold : `₦${formData.auto_payout_threshold.toLocaleString()}`}
                            onChange={(e) => setFormData({ ...formData, auto_payout_threshold: parseFloat(e.target.value.replace(/[^0-9]/g, '')) || 0 })}
                            disabled={!isEditing.auto_payout}
                            className="h-[48px] rounded-lg border-[#E2E8F0]"
                            placeholder="₦50,000"
                        />
                        <Button
                            variant="ghost"
                            onClick={() => toggleEdit('auto_payout')}
                            className="text-[#14B8A6] hover:text-[#129c8e] text-[14px]"
                        >
                            {isEditing.auto_payout ? 'Done' : 'Edit'}
                        </Button>
                    </div>
                </div>

                {/* Minimum Withdrawal Amount */}
                <div>
                    <Label className="text-[14px] font-medium text-[#64748B] mb-2 block">
                        Minimum Withdrawal Amount
                    </Label>
                    <div className="flex items-center gap-3">
                        <Input
                            type="text"
                            value={isEditing.min_withdrawal ? formData.minimum_withdrawal_amount : `₦${formData.minimum_withdrawal_amount.toLocaleString()}`}
                            onChange={(e) => setFormData({ ...formData, minimum_withdrawal_amount: parseFloat(e.target.value.replace(/[^0-9]/g, '')) || 0 })}
                            disabled={!isEditing.min_withdrawal}
                            className="h-[48px] rounded-lg border-[#E2E8F0]"
                            placeholder="₦10,000"
                        />
                        <Button
                            variant="ghost"
                            onClick={() => toggleEdit('min_withdrawal')}
                            className="text-[#14B8A6] hover:text-[#129c8e] text-[14px]"
                        >
                            {isEditing.min_withdrawal ? 'Done' : 'Edit'}
                        </Button>
                    </div>
                </div>

                {/* Bank Verification Check */}
                <div className="flex items-center justify-between">
                    <Label className="text-[14px] font-medium text-[#64748B]">
                        Bank Verification Check
                    </Label>
                    <Switch
                        checked={formData.bank_verification_enabled}
                        onCheckedChange={(checked) => setFormData({ ...formData, bank_verification_enabled: checked })}
                    />
                </div>

                {/* Add withdrawal note for users */}
                <div>
                    <Label className="text-[14px] font-medium text-[#64748B] mb-2 block">
                        Add withdrawal note for users
                    </Label>
                    <Textarea
                        value={formData.withdrawal_note}
                        onChange={(e) => setFormData({ ...formData, withdrawal_note: e.target.value })}
                        className="min-h-[120px] rounded-lg border-[#E2E8F0] resize-none"
                        placeholder="Teachers cannot request withdrawal unless wallet is ₦10,000+"
                    />
                </div>

                {/* Apply Time */}
                <div>
                    <Label className="text-[14px] font-medium text-[#64748B] mb-3 block">
                        Apply Time:
                    </Label>
                    <div className="flex items-center gap-6">
                        <div className="flex items-center gap-2">
                            <Switch
                                checked={formData.apply_time === 'now'}
                                onCheckedChange={(checked) => setFormData({ ...formData, apply_time: checked ? 'now' : 'scheduled' })}
                            />
                            <span className="text-[14px] text-[#64748B]">Set Now</span>
                        </div>
                        <div className="flex items-center gap-2">
                            <Switch
                                checked={formData.apply_time === 'scheduled'}
                                onCheckedChange={(checked) => setFormData({ ...formData, apply_time: checked ? 'scheduled' : 'now' })}
                            />
                            <span className="text-[14px] text-[#64748B]">Schedule for Later</span>
                        </div>
                    </div>
                </div>

                {/* Action Buttons */}
                <div className="flex items-center gap-4 pt-4">
                    <Button
                        onClick={handleSave}
                        className="bg-[#14B8A6] hover:bg-[#129c8e] text-white h-[48px] px-8 rounded-lg"
                    >
                        Save Changes
                    </Button>
                    <Button
                        variant="ghost"
                        onClick={handleCancel}
                        className="text-[#EF4444] hover:text-[#dc2626] hover:bg-red-50"
                    >
                        Cancel
                    </Button>
                </div>
            </div>
        </div>
    );
}
