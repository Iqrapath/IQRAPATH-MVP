import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { router } from '@inertiajs/react';
import { toast } from 'sonner';
import { Pencil } from 'lucide-react';

interface PaymentMethod {
    name: string;
    key: string;
    fee_type: 'flat' | 'percentage';
    fee_amount: number;
    processing_time: string;
}

interface PaymentMethodsData extends Record<string, any> {
    methods: PaymentMethod[];
}

interface Props {
    settings?: {
        bank_transfer_fee_type: string;
        bank_transfer_fee_amount: number;
        bank_transfer_processing_time: string;
        mobile_money_fee_type: string;
        mobile_money_fee_amount: number;
        mobile_money_processing_time: string;
        paypal_fee_type: string;
        paypal_fee_amount: number;
        paypal_processing_time: string;
        flutterwave_fee_type: string;
        flutterwave_fee_amount: number;
        flutterwave_processing_time: string;
        paystack_fee_type: string;
        paystack_fee_amount: number;
        paystack_processing_time: string;
        stripe_fee_type: string;
        stripe_fee_amount: number;
        stripe_processing_time: string;
    };
}

export default function PaymentMethodsSettings({ settings }: Props) {
    const [isEditing, setIsEditing] = useState<Record<string, boolean>>({});
    const [methods, setMethods] = useState<PaymentMethod[]>([
        {
            name: 'Bank Transfer',
            key: 'bank_transfer',
            fee_type: (settings?.bank_transfer_fee_type as 'flat' | 'percentage') || 'flat',
            fee_amount: settings?.bank_transfer_fee_amount || 100,
            processing_time: settings?.bank_transfer_processing_time || '1-3 business days',
        },
        {
            name: 'Mobile Money',
            key: 'mobile_money',
            fee_type: (settings?.mobile_money_fee_type as 'flat' | 'percentage') || 'percentage',
            fee_amount: settings?.mobile_money_fee_amount || 2.5,
            processing_time: settings?.mobile_money_processing_time || 'Instant',
        },
        {
            name: 'PayPal',
            key: 'paypal',
            fee_type: (settings?.paypal_fee_type as 'flat' | 'percentage') || 'percentage',
            fee_amount: settings?.paypal_fee_amount || 3.5,
            processing_time: settings?.paypal_processing_time || 'Instant',
        },
        {
            name: 'Flutterwave',
            key: 'flutterwave',
            fee_type: (settings?.flutterwave_fee_type as 'flat' | 'percentage') || 'flat',
            fee_amount: settings?.flutterwave_fee_amount || 50,
            processing_time: settings?.flutterwave_processing_time || '1-2 business days',
        },
        {
            name: 'Paystack',
            key: 'paystack',
            fee_type: (settings?.paystack_fee_type as 'flat' | 'percentage') || 'flat',
            fee_amount: settings?.paystack_fee_amount || 100,
            processing_time: settings?.paystack_processing_time || '1-2 business days',
        },
        {
            name: 'Stripe',
            key: 'stripe',
            fee_type: (settings?.stripe_fee_type as 'flat' | 'percentage') || 'percentage',
            fee_amount: settings?.stripe_fee_amount || 2.9,
            processing_time: settings?.stripe_processing_time || '1-2 business days',
        },
    ]);

    const toggleEdit = (key: string) => {
        setIsEditing({ ...isEditing, [key]: !isEditing[key] });
    };

    const updateMethod = (key: string, field: keyof PaymentMethod, value: any) => {
        setMethods(methods.map(m => m.key === key ? { ...m, [field]: value } : m));
    };

    const handleSave = () => {
        const formData: Record<string, any> = {};
        methods.forEach(method => {
            formData[`${method.key}_fee_type`] = method.fee_type;
            formData[`${method.key}_fee_amount`] = method.fee_amount;
            formData[`${method.key}_processing_time`] = method.processing_time;
        });

        router.post(route('admin.financial.settings.payment-methods.update'), formData, {
            onSuccess: () => {
                toast.success('Payment methods updated successfully!');
                setIsEditing({});
            },
            onError: () => {
                toast.error('Failed to update settings. Please try again.');
            },
        });
    };

    const handleCancel = () => {
        setIsEditing({});
        // Reset to original values
        if (settings) {
            setMethods([
                {
                    name: 'Bank Transfer',
                    key: 'bank_transfer',
                    fee_type: (settings.bank_transfer_fee_type as 'flat' | 'percentage') || 'flat',
                    fee_amount: settings.bank_transfer_fee_amount || 100,
                    processing_time: settings.bank_transfer_processing_time || '1-3 business days',
                },
                // ... repeat for other methods
            ]);
        }
    };

    return (
        <div className="mt-[28px] bg-white rounded-lg border border-gray-200 p-8">
            <h2 className="text-[24px] font-semibold text-[#1E293B] mb-6">Payment Methods Configuration</h2>

            <div className="space-y-6">
                {methods.map((method) => (
                    <div key={method.key} className="border border-gray-200 rounded-lg p-6">
                        <div className="flex items-center justify-between mb-4">
                            <h3 className="text-[18px] font-medium text-[#1E293B]">{method.name}</h3>
                            <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => toggleEdit(method.key)}
                                className="text-[#14B8A6] hover:text-[#129c8e]"
                            >
                                <Pencil className="w-4 h-4 mr-1" />
                                {isEditing[method.key] ? 'Cancel' : 'Edit'}
                            </Button>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            {/* Fee Type */}
                            <div>
                                <Label className="text-[14px] font-medium text-[#64748B]">Fee Type</Label>
                                {isEditing[method.key] ? (
                                    <Select
                                        value={method.fee_type}
                                        onValueChange={(value) => updateMethod(method.key, 'fee_type', value)}
                                    >
                                        <SelectTrigger className="mt-2">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="flat">Flat Fee</SelectItem>
                                            <SelectItem value="percentage">Percentage</SelectItem>
                                        </SelectContent>
                                    </Select>
                                ) : (
                                    <p className="text-[16px] text-[#1E293B] mt-2 capitalize">{method.fee_type}</p>
                                )}
                            </div>

                            {/* Fee Amount */}
                            <div>
                                <Label className="text-[14px] font-medium text-[#64748B]">Fee Amount</Label>
                                {isEditing[method.key] ? (
                                    <Input
                                        type="number"
                                        step="0.01"
                                        value={method.fee_amount}
                                        onChange={(e) => updateMethod(method.key, 'fee_amount', parseFloat(e.target.value))}
                                        className="mt-2"
                                    />
                                ) : (
                                    <p className="text-[16px] text-[#1E293B] mt-2">
                                        {method.fee_type === 'percentage' ? `${method.fee_amount}%` : `₦${method.fee_amount}`}
                                    </p>
                                )}
                            </div>

                            {/* Processing Time */}
                            <div>
                                <Label className="text-[14px] font-medium text-[#64748B]">Processing Time</Label>
                                {isEditing[method.key] ? (
                                    <Input
                                        type="text"
                                        value={method.processing_time}
                                        onChange={(e) => updateMethod(method.key, 'processing_time', e.target.value)}
                                        className="mt-2"
                                        placeholder="e.g., Instant"
                                    />
                                ) : (
                                    <p className="text-[16px] text-[#1E293B] mt-2">{method.processing_time}</p>
                                )}
                            </div>
                        </div>
                    </div>
                ))}
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
