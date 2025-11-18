import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { router } from '@inertiajs/react';
import { toast } from 'sonner';
import { Pencil } from 'lucide-react';

interface CurrencySettingsProps {
    platform_currency: string;
    multi_currency_mode: boolean;
}

interface CurrencySettingsData extends Record<string, any> {
    platform_currency: string;
    multi_currency_mode: boolean;
}

interface Props {
    settings?: CurrencySettingsProps;
}

export default function CurrencySettings({ settings }: Props) {
    const [isEditing, setIsEditing] = useState<Record<string, boolean>>({});
    const [formData, setFormData] = useState<CurrencySettingsData>({
        platform_currency: settings?.platform_currency || 'NGN',
        multi_currency_mode: settings?.multi_currency_mode ?? true,
    });

    const toggleEdit = (field: string) => {
        setIsEditing({ ...isEditing, [field]: !isEditing[field] });
    };

    const handleSave = () => {
        router.post(route('admin.financial.settings.currency.update'), formData, {
            onSuccess: () => {
                toast.success('Currency settings updated successfully!');
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
                platform_currency: settings.platform_currency,
                multi_currency_mode: settings.multi_currency_mode,
            });
        }
        setIsEditing({});
    };

    const currencies = [
        { code: 'NGN', name: 'Nigerian Naira (₦)', symbol: '₦' },
        { code: 'USD', name: 'US Dollar ($)', symbol: '$' },
        { code: 'EUR', name: 'Euro (€)', symbol: '€' },
        { code: 'GBP', name: 'British Pound (£)', symbol: '£' },
    ];

    return (
        <div className="mt-[28px] bg-white rounded-lg border border-gray-200 p-8">
            <h2 className="text-[24px] font-semibold text-[#1E293B] mb-6">Currency Settings</h2>

            <div className="space-y-6">
                {/* Platform Currency */}
                <div className="flex items-center justify-between py-4 border-b border-gray-100">
                    <div className="flex-1">
                        <Label className="text-[14px] font-medium text-[#64748B]">Platform Currency</Label>
                        <p className="text-[12px] text-[#94A3B8] mt-1">
                            Base currency for all transactions and calculations
                        </p>
                        {isEditing.platform_currency ? (
                            <Select
                                value={formData.platform_currency}
                                onValueChange={(value) => setFormData({ ...formData, platform_currency: value })}
                            >
                                <SelectTrigger className="mt-2 max-w-xs">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {currencies.map((currency) => (
                                        <SelectItem key={currency.code} value={currency.code}>
                                            {currency.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        ) : (
                            <p className="text-[16px] text-[#1E293B] mt-2">
                                {currencies.find(c => c.code === formData.platform_currency)?.name || formData.platform_currency}
                            </p>
                        )}
                    </div>
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => toggleEdit('platform_currency')}
                        className="text-[#14B8A6] hover:text-[#129c8e]"
                    >
                        <Pencil className="w-4 h-4 mr-1" />
                        Edit
                    </Button>
                </div>

                {/* Multi-Currency Mode */}
                <div className="flex items-center justify-between py-4">
                    <div className="flex-1">
                        <Label className="text-[14px] font-medium text-[#64748B]">Multi-Currency Mode</Label>
                        <p className="text-[12px] text-[#94A3B8] mt-1">
                            Allow users to transact in multiple currencies with automatic conversion
                        </p>
                    </div>
                    <Switch
                        checked={formData.multi_currency_mode}
                        onCheckedChange={(checked) => setFormData({ ...formData, multi_currency_mode: checked })}
                    />
                </div>

                {/* Info Box */}
                <div className="bg-[#F0FDFA] border border-[#14B8A6] rounded-lg p-4 mt-6">
                    <h4 className="text-[14px] font-medium text-[#1E293B] mb-2">💡 Currency Information</h4>
                    <ul className="text-[12px] text-[#64748B] space-y-1">
                        <li>• Platform currency is used for all internal calculations</li>
                        <li>• Multi-currency mode allows users to pay in their preferred currency</li>
                        <li>• Exchange rates are fetched automatically and updated daily</li>
                        <li>• All amounts are converted to platform currency for storage</li>
                    </ul>
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
