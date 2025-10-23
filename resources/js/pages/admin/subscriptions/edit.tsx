import React, { useState, useEffect } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminLayout from '@/layouts/admin/admin-layout';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Label } from '@/components/ui/label';
import { X, Plus, RefreshCw } from 'lucide-react';
import { toast } from 'sonner';

interface SubscriptionPlan {
    id: number;
    name: string;
    description?: string;
    price_naira: number;
    price_dollar: number;
    billing_cycle: 'monthly' | 'annual';
    duration_months: number;
    features?: string[];
    tags?: string[];
    image_path?: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

interface Props {
    plan: SubscriptionPlan;
    errors?: Record<string, string>;
}

export default function EditSubscriptionPlan({ plan, errors }: Props) {
    const [formData, setFormData] = useState({
        name: plan.name || '',
        description: plan.description || '',
        price_naira: plan.price_naira?.toString() || '',
        price_dollar: plan.price_dollar?.toString() || '',
        billing_cycle: plan.billing_cycle || 'monthly',
        duration_months: plan.duration_months?.toString() || '1',
        features: plan.features || [],
        tags: plan.tags || [],
        is_active: plan.is_active ?? true,
    });

    const [newFeature, setNewFeature] = useState('');
    const [newTag, setNewTag] = useState('');
    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    const [primaryCurrency, setPrimaryCurrency] = useState<'NGN' | 'USD'>('NGN');
    const [isConverting, setIsConverting] = useState(false);
    const [exchangeRate, setExchangeRate] = useState<number | null>(null);

    // Billing cycles - only monthly and annual
    const billingCycles = [
        { value: 'monthly', label: 'Monthly', duration: 1 },
        { value: 'annual', label: 'Annual', duration: 12 },
    ];

    // Auto-set duration based on billing cycle
    useEffect(() => {
        const selectedCycle = billingCycles.find(cycle => cycle.value === formData.billing_cycle);
        if (selectedCycle) {
            setFormData(prev => ({
                ...prev,
                duration_months: selectedCycle.duration.toString()
            }));
        }
    }, [formData.billing_cycle]);

    // Currency conversion function
    const convertCurrency = async (amount: string, fromCurrency: 'NGN' | 'USD', toCurrency: 'NGN' | 'USD') => {
        if (!amount || parseFloat(amount) <= 0) return;
        
        setIsConverting(true);
        try {
            const response = await fetch('/api/currency/convert', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    amount: parseFloat(amount),
                    from: fromCurrency,
                    to: toCurrency,
                }),
            });

            const data = await response.json();
            
            if (data.success) {
                setExchangeRate(data.data.exchange_rate);
                return data.data.converted_amount.toString();
            } else {
                toast.error('Currency conversion failed');
                return null;
            }
        } catch (error) {
            console.error('Currency conversion error:', error);
            toast.error('Currency conversion failed');
            return null;
        } finally {
            setIsConverting(false);
        }
    };

    // Handle primary currency price change
    const handlePrimaryCurrencyChange = async (value: string) => {
        const field = primaryCurrency === 'NGN' ? 'price_naira' : 'price_dollar';
        const otherField = primaryCurrency === 'NGN' ? 'price_dollar' : 'price_naira';
        const otherCurrency = primaryCurrency === 'NGN' ? 'USD' : 'NGN';

        setFormData(prev => ({ ...prev, [field]: value }));

        // Auto-convert to other currency
        if (value && parseFloat(value) > 0) {
            const convertedValue = await convertCurrency(value, primaryCurrency, otherCurrency);
            if (convertedValue) {
                setFormData(prev => ({ ...prev, [otherField]: convertedValue }));
            }
        } else {
            setFormData(prev => ({ ...prev, [otherField]: '' }));
        }
    };

    const handleInputChange = (field: string, value: any) => {
        setFormData(prev => ({
            ...prev,
            [field]: value
        }));
    };

    const handleAddFeature = () => {
        if (newFeature.trim()) {
            setFormData(prev => ({
                ...prev,
                features: [...prev.features, newFeature.trim()]
            }));
            setNewFeature('');
        }
    };

    const handleRemoveFeature = (index: number) => {
        setFormData(prev => ({
            ...prev,
            features: prev.features.filter((_, i) => i !== index)
        }));
    };

    const handleAddTag = () => {
        if (newTag.trim()) {
            setFormData(prev => ({
                ...prev,
                tags: [...prev.tags, newTag.trim()]
            }));
            setNewTag('');
        }
    };

    const handleRemoveTag = (index: number) => {
        setFormData(prev => ({
            ...prev,
            tags: prev.tags.filter((_, i) => i !== index)
        }));
    };

    const handleFileChange = (event: React.ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0];
        if (file) {
            setSelectedFile(file);
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        
        const submitData = new FormData();
        submitData.append('name', formData.name);
        submitData.append('description', formData.description);
        submitData.append('price_naira', formData.price_naira);
        submitData.append('price_dollar', formData.price_dollar);
        submitData.append('billing_cycle', formData.billing_cycle);
        submitData.append('duration_months', formData.duration_months);
        submitData.append('features', JSON.stringify(formData.features.length > 0 ? formData.features : []));
        submitData.append('tags', JSON.stringify(formData.tags.length > 0 ? formData.tags : []));
        submitData.append('is_active', formData.is_active ? '1' : '0');
        submitData.append('_method', 'PUT');
        
        if (selectedFile) {
            submitData.append('image', selectedFile);
        }

        router.post(route('admin.subscription-plans.update', plan.id), submitData, {
            onSuccess: () => {
                toast.success('Subscription plan updated successfully!');
            },
            onError: (errors) => {
                console.error('Update subscription plan errors:', errors);
                if (errors && typeof errors === 'object') {
                    const errorMessages = Object.values(errors).flat();
                    toast.error(`Validation failed: ${errorMessages.join(', ')}`);
                } else {
                    toast.error('Failed to update subscription plan. Please try again.');
                }
            }
        });
    };

    return (
        <AdminLayout pageTitle="Edit Subscription Plan">
            <Head title={`Edit ${plan.name}`} />
            
            <div className="space-y-6 p-6">
                {/* Breadcrumbs */}
                <Breadcrumbs
                    breadcrumbs={[
                        { title: 'Dashboard', href: route('admin.dashboard') },
                        { title: 'Subscription Plans', href: route('admin.subscription-plans.index') },
                        { title: `Edit ${plan.name}`, href: route('admin.subscription-plans.edit', plan.id) }
                    ]}
                />

                {/* Header */}
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Edit Subscription Plan</h1>
                    <p className="text-gray-600 mt-1">Update the subscription plan details</p>
                </div>

                {/* Form */}
                <form onSubmit={handleSubmit} className="space-y-8">
                    {/* Plan Information */}
                    <div className="bg-white rounded-lg border border-gray-200 p-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">Plan Information</h2>
                        
                        <div className="space-y-4">
                            <div>
                                <Label htmlFor="name" className="text-sm font-medium text-gray-700">
                                    Plan Name *
                                </Label>
                                <Input
                                    id="name"
                                    value={formData.name}
                                    onChange={(e) => handleInputChange('name', e.target.value)}
                                    placeholder="Full Quran Memorization"
                                    className="mt-1"
                                    required
                                />
                                {errors?.name && (
                                    <p className="text-red-500 text-sm mt-1">{errors.name}</p>
                                )}
                            </div>

                            <div>
                                <Label htmlFor="description" className="text-sm font-medium text-gray-700">
                                    Description
                                </Label>
                                <Textarea
                                    id="description"
                                    value={formData.description}
                                    onChange={(e) => handleInputChange('description', e.target.value)}
                                    placeholder="A comprehensive memorization program for students aiming to memorize the entire Quran with certified teachers."
                                    className="mt-1 min-h-[100px]"
                                />
                                {errors?.description && (
                                    <p className="text-red-500 text-sm mt-1">{errors.description}</p>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Pricing & Billing */}
                    <div className="bg-white rounded-lg border border-gray-200 p-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">Pricing & Billing</h2>
                        
                        {/* Primary Currency Selector */}
                        <div className="mb-4">
                            <Label className="text-sm font-medium text-gray-700 mb-2 block">
                                Primary Currency (Enter price in this currency)
                            </Label>
                            <RadioGroup
                                value={primaryCurrency}
                                onValueChange={(value) => setPrimaryCurrency(value as 'NGN' | 'USD')}
                                className="flex gap-6"
                            >
                                <div className="flex items-center space-x-2">
                                    <RadioGroupItem value="NGN" id="ngn-primary" />
                                    <Label htmlFor="ngn-primary">Nigerian Naira (₦)</Label>
                                </div>
                                <div className="flex items-center space-x-2">
                                    <RadioGroupItem value="USD" id="usd-primary" />
                                    <Label htmlFor="usd-primary">US Dollar ($)</Label>
                                </div>
                            </RadioGroup>
                        </div>

                        {/* Exchange Rate Display */}
                        {exchangeRate && (
                            <div className="mb-4 p-3 bg-blue-50 rounded-lg">
                                <p className="text-sm text-blue-700">
                                    Current Exchange Rate: 1 {primaryCurrency} = {exchangeRate} {primaryCurrency === 'NGN' ? 'USD' : 'NGN'}
                                </p>
                            </div>
                        )}
                        
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <Label htmlFor="price_naira" className="text-sm font-medium text-gray-700 flex items-center gap-2">
                                    Price (Nigerian Naira) *
                                    {primaryCurrency !== 'NGN' && (
                                        <span className="text-xs text-gray-500">(Auto-converted)</span>
                                    )}
                                </Label>
                                <div className="relative">
                                    <Input
                                        id="price_naira"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value={formData.price_naira}
                                        onChange={(e) => {
                                            if (primaryCurrency === 'NGN') {
                                                handlePrimaryCurrencyChange(e.target.value);
                                            } else {
                                                handleInputChange('price_naira', e.target.value);
                                            }
                                        }}
                                        placeholder="50000.00"
                                        className="mt-1 pr-8"
                                        required
                                        disabled={primaryCurrency !== 'NGN' && isConverting}
                                    />
                                    {primaryCurrency !== 'NGN' && isConverting && (
                                        <RefreshCw className="absolute right-2 top-1/2 transform -translate-y-1/2 h-4 w-4 animate-spin text-gray-400" />
                                    )}
                                </div>
                                {errors?.price_naira && (
                                    <p className="text-red-500 text-sm mt-1">{errors.price_naira}</p>
                                )}
                            </div>

                            <div>
                                <Label htmlFor="price_dollar" className="text-sm font-medium text-gray-700 flex items-center gap-2">
                                    Price (US Dollar) *
                                    {primaryCurrency !== 'USD' && (
                                        <span className="text-xs text-gray-500">(Auto-converted)</span>
                                    )}
                                </Label>
                                <div className="relative">
                                    <Input
                                        id="price_dollar"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value={formData.price_dollar}
                                        onChange={(e) => {
                                            if (primaryCurrency === 'USD') {
                                                handlePrimaryCurrencyChange(e.target.value);
                                            } else {
                                                handleInputChange('price_dollar', e.target.value);
                                            }
                                        }}
                                        placeholder="80.00"
                                        className="mt-1 pr-8"
                                        required
                                        disabled={primaryCurrency !== 'USD' && isConverting}
                                    />
                                    {primaryCurrency !== 'USD' && isConverting && (
                                        <RefreshCw className="absolute right-2 top-1/2 transform -translate-y-1/2 h-4 w-4 animate-spin text-gray-400" />
                                    )}
                                </div>
                                {errors?.price_dollar && (
                                    <p className="text-red-500 text-sm mt-1">{errors.price_dollar}</p>
                                )}
                            </div>
                        </div>

                        <div className="mt-4">
                            <Label htmlFor="billing_cycle" className="text-sm font-medium text-gray-700">
                                Billing Cycle *
                            </Label>
                            <Select
                                value={formData.billing_cycle}
                                onValueChange={(value) => handleInputChange('billing_cycle', value)}
                            >
                                <SelectTrigger className="mt-1">
                                    <SelectValue placeholder="Select billing cycle" />
                                </SelectTrigger>
                                <SelectContent>
                                    {billingCycles.map((cycle) => (
                                        <SelectItem key={cycle.value} value={cycle.value}>
                                            {cycle.label} ({cycle.duration} month{cycle.duration > 1 ? 's' : ''})
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors?.billing_cycle && (
                                <p className="text-red-500 text-sm mt-1">{errors.billing_cycle}</p>
                            )}
                        </div>

                        {/* Duration (Auto-set, read-only) */}
                        <div className="mt-4">
                            <Label className="text-sm font-medium text-gray-700">
                                Duration (Auto-set based on billing cycle)
                            </Label>
                            <Input
                                value={`${formData.duration_months} month${formData.duration_months !== '1' ? 's' : ''}`}
                                className="mt-1 bg-gray-50"
                                disabled
                            />
                        </div>
                    </div>

                    {/* Plan Features */}
                    <div className="bg-white rounded-lg border border-gray-200 p-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">Plan Features</h2>
                        
                        <div className="space-y-4">
                            <div className="space-y-3">
                                {formData.features.map((feature, index) => (
                                    <div key={index} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                        <span className="text-sm text-gray-700">{feature}</span>
                                        <button
                                            type="button"
                                            onClick={() => handleRemoveFeature(index)}
                                            className="text-red-500 hover:text-red-700"
                                        >
                                            <X className="h-4 w-4" />
                                        </button>
                                    </div>
                                ))}
                            </div>

                            <div className="flex gap-2">
                                <Input
                                    value={newFeature}
                                    onChange={(e) => setNewFeature(e.target.value)}
                                    placeholder="Enter feature (e.g., Daily live sessions with certified teacher)"
                                    className="flex-1"
                                    onKeyPress={(e) => e.key === 'Enter' && (e.preventDefault(), handleAddFeature())}
                                />
                                <Button
                                    type="button"
                                    onClick={handleAddFeature}
                                    variant="outline"
                                    size="sm"
                                >
                                    <Plus className="h-4 w-4 mr-1" />
                                    Add
                                </Button>
                            </div>
                        </div>
                    </div>

                    {/* Tags */}
                    <div className="bg-white rounded-lg border border-gray-200 p-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">Tags</h2>
                        
                        <div className="space-y-4">
                            <div className="flex flex-wrap gap-2">
                                {formData.tags.map((tag, index) => (
                                    <Badge key={index} variant="secondary" className="px-3 py-1">
                                        #{tag}
                                        <button
                                            type="button"
                                            onClick={() => handleRemoveTag(index)}
                                            className="ml-2 hover:text-red-500"
                                        >
                                            <X className="h-3 w-3" />
                                        </button>
                                    </Badge>
                                ))}
                            </div>

                            <div className="flex gap-2">
                                <Input
                                    value={newTag}
                                    onChange={(e) => setNewTag(e.target.value)}
                                    placeholder="Enter tag (e.g., popular, recommended)"
                                    className="flex-1"
                                    onKeyPress={(e) => e.key === 'Enter' && (e.preventDefault(), handleAddTag())}
                                />
                                <Button
                                    type="button"
                                    onClick={handleAddTag}
                                    variant="outline"
                                    size="sm"
                                >
                                    Add Tag
                                </Button>
                            </div>
                        </div>
                    </div>

                    {/* Plan Image */}
                    <div className="bg-white rounded-lg border border-gray-200 p-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">Plan Image</h2>
                        
                        {plan.image_path && (
                            <div className="mb-4">
                                <p className="text-sm text-gray-600 mb-2">Current Image:</p>
                                <img 
                                    src={`/storage/${plan.image_path}`} 
                                    alt={plan.name}
                                    className="w-32 h-32 object-cover rounded-lg border"
                                />
                            </div>
                        )}
                        
                        <div className="flex gap-2">
                            <input
                                type="file"
                                id="image"
                                onChange={handleFileChange}
                                accept="image/*"
                                className="hidden"
                            />
                            <Button
                                type="button"
                                onClick={() => document.getElementById('image')?.click()}
                                variant="outline"
                            >
                                {plan.image_path ? 'Change Image' : 'Choose Image'}
                            </Button>
                            <span className="flex items-center text-sm text-gray-500">
                                {selectedFile ? selectedFile.name : 'No new file chosen'}
                            </span>
                        </div>
                        {errors?.image && (
                            <p className="text-red-500 text-sm mt-1">{errors.image}</p>
                        )}
                    </div>

                    {/* Status */}
                    <div className="bg-white rounded-lg border border-gray-200 p-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">Status</h2>
                        
                        <RadioGroup
                            value={formData.is_active ? 'active' : 'inactive'}
                            onValueChange={(value) => handleInputChange('is_active', value === 'active')}
                            className="space-y-3"
                        >
                            <div className="flex items-center space-x-2">
                                <RadioGroupItem value="active" id="active" />
                                <Label htmlFor="active" className="text-sm font-medium text-gray-700">
                                    Active (Plan will be visible to students)
                                </Label>
                            </div>
                            <div className="flex items-center space-x-2">
                                <RadioGroupItem value="inactive" id="inactive" />
                                <Label htmlFor="inactive" className="text-sm font-medium text-gray-700">
                                    Inactive (Plan will be hidden from students)
                                </Label>
                            </div>
                        </RadioGroup>
                    </div>

                    {/* Action Buttons */}
                    <div className="flex justify-end gap-4">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => router.visit(route('admin.subscription-plans.index'))}
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            className="bg-blue-600 hover:bg-blue-700 text-white"
                            disabled={isConverting}
                        >
                            {isConverting ? 'Converting...' : 'Update Plan'}
                        </Button>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}