import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminLayout from '@/layouts/admin/admin-layout';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Label } from '@/components/ui/label';
import { X, Plus } from 'lucide-react';
import { toast } from 'sonner';

interface Props {
    errors?: Record<string, string>;
}

export default function CreateSubscriptionPlan({ errors }: Props) {
    const [formData, setFormData] = useState({
        name: '',
        description: '',
        price_naira: '',
        price_dollar: '',
        billing_cycle: 'monthly',
        duration_months: '12',
        features: [] as string[],
        tags: [] as string[],
        is_active: true,
        currency_naira: true,
        currency_dollar: false,
    });

    const [newFeature, setNewFeature] = useState('');
    const [newTag, setNewTag] = useState('');
    const [selectedFile, setSelectedFile] = useState<File | null>(null);

    const billingCycles = [
        { value: 'monthly', label: 'Monthly' },
        { value: 'quarterly', label: 'Quarterly' },
        { value: 'biannually', label: 'Biannually' },
        { value: 'annually', label: 'Annually' },
    ];

    const durationOptions = [
        { value: '1', label: '1 Month' },
        { value: '3', label: '3 Months' },
        { value: '6', label: '6 Months' },
        { value: '12', label: '12 Months' },
        { value: '24', label: '24 Months' },
    ];

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
        
        if (selectedFile) {
            submitData.append('image', selectedFile);
        }

        router.post(route('admin.subscription-plans.store'), submitData, {
            onSuccess: () => {
                toast.success('Subscription plan created successfully!');
            },
            onError: (errors) => {
                console.error('Create subscription plan errors:', errors);
                if (errors && typeof errors === 'object') {
                    const errorMessages = Object.values(errors).flat();
                    toast.error(`Validation failed: ${errorMessages.join(', ')}`);
                } else {
                    toast.error('Failed to create subscription plan. Please try again.');
                }
            }
        });
    };

    return (
        <AdminLayout pageTitle="Create / Edit Plan">
            <Head title="Create / Edit Plan" />
            
            <div className="space-y-6 p-6">
                {/* Breadcrumbs */}
                <Breadcrumbs
                    breadcrumbs={[
                        { title: 'Dashboard', href: route('admin.dashboard') },
                        { title: 'Subscription & Plans Management', href: route('admin.subscription-plans.index') },
                        { title: 'Create / Edit Plan', href: route('admin.subscription-plans.create') }
                    ]}
                />

                {/* Header */}
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Create / Edit Plan</h1>
                </div>

                {/* Form */}
                <form onSubmit={handleSubmit} className="space-y-8">
                    {/* Subscription Information */}
                    <div className="bg-white rounded-lg border border-gray-200 p-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">Subscription Information</h2>
                        
                        <div className="space-y-4">
                            <div>
                                <Label htmlFor="name" className="text-sm font-medium text-gray-700">
                                    Plan Name
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
                                    placeholder="Dedicated Quran teacher with 10+ years of experience in Hifz and Tajweed. A comprehensive memorization program for students aiming to memorize the entire Quran."
                                    className="mt-1 min-h-[100px]"
                                    required
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
                        
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <Label htmlFor="price_naira" className="text-sm font-medium text-gray-700">
                                    Price (Naira)
                                </Label>
                                <Input
                                    id="price_naira"
                                    type="number"
                                    value={formData.price_naira}
                                    onChange={(e) => handleInputChange('price_naira', e.target.value)}
                                    placeholder="50000"
                                    className="mt-1"
                                    required
                                />
                                {errors?.price_naira && (
                                    <p className="text-red-500 text-sm mt-1">{errors.price_naira}</p>
                                )}
                            </div>

                            <div>
                                <Label htmlFor="price_dollar" className="text-sm font-medium text-gray-700">
                                    Price (Dollar)
                                </Label>
                                <Input
                                    id="price_dollar"
                                    type="number"
                                    value={formData.price_dollar}
                                    onChange={(e) => handleInputChange('price_dollar', e.target.value)}
                                    placeholder="80"
                                    className="mt-1"
                                    required
                                />
                                {errors?.price_dollar && (
                                    <p className="text-red-500 text-sm mt-1">{errors.price_dollar}</p>
                                )}
                            </div>
                        </div>

                        <div className="mt-4">
                            <Label htmlFor="billing_cycle" className="text-sm font-medium text-gray-700">
                                Billing Cycle
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
                                            {cycle.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors?.billing_cycle && (
                                <p className="text-red-500 text-sm mt-1">{errors.billing_cycle}</p>
                            )}
                        </div>
                    </div>

                    {/* Currency Option */}
                    <div className="bg-white rounded-lg border border-gray-200 p-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">Currency Option</h2>
                        
                        <div className="space-y-3">
                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="currency_naira"
                                    checked={formData.currency_naira}
                                    onCheckedChange={(checked) => {
                                        handleInputChange('currency_naira', checked);
                                        if (checked) {
                                            handleInputChange('currency_dollar', false);
                                        }
                                    }}
                                />
                                <Label htmlFor="currency_naira" className="text-sm font-medium text-gray-700">
                                    Naira
                                </Label>
                            </div>

                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="currency_dollar"
                                    checked={formData.currency_dollar}
                                    onCheckedChange={(checked) => {
                                        handleInputChange('currency_dollar', checked);
                                        if (checked) {
                                            handleInputChange('currency_naira', false);
                                        }
                                    }}
                                />
                                <Label htmlFor="currency_dollar" className="text-sm font-medium text-gray-700">
                                    Dollar
                                </Label>
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
                                    placeholder="Enter tag name"
                                    className="flex-1"
                                />
                                <Button
                                    type="button"
                                    onClick={handleAddTag}
                                    variant="outline"
                                    size="sm"
                                >
                                    Add New
                                </Button>
                            </div>
                        </div>
                    </div>

                    {/* Plan Features */}
                    <div className="bg-white rounded-lg border border-gray-200 p-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">Plan Feature</h2>
                        
                        <div className="space-y-4">
                            <div className="space-y-3">
                                {formData.features.map((feature, index) => (
                                    <div key={index} className="flex items-center space-x-2">
                                        <Checkbox id={`feature-${index}`} />
                                        <Label htmlFor={`feature-${index}`} className="text-sm text-gray-700">
                                            {feature}
                                        </Label>
                                        <button
                                            type="button"
                                            onClick={() => handleRemoveFeature(index)}
                                            className="ml-auto hover:text-red-500"
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
                                    placeholder="Enter feature"
                                    className="flex-1"
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

                    {/* Plan Image */}
                    <div className="bg-white rounded-lg border border-gray-200 p-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">Plan Image (Optional)</h2>
                        
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
                                Choose
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                disabled
                            >
                                {selectedFile ? selectedFile.name : 'No file chosen'}
                            </Button>
                        </div>
                        {errors?.image && (
                            <p className="text-red-500 text-sm mt-1">{errors.image}</p>
                        )}
                    </div>

                    {/* Estimated Duration */}
                    <div className="bg-white rounded-lg border border-gray-200 p-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">Estimated Duration</h2>
                        
                        <Select
                            value={formData.duration_months}
                            onValueChange={(value) => handleInputChange('duration_months', value)}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Select duration" />
                            </SelectTrigger>
                            <SelectContent>
                                {durationOptions.map((option) => (
                                    <SelectItem key={option.value} value={option.value}>
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors?.duration_months && (
                            <p className="text-red-500 text-sm mt-1">{errors.duration_months}</p>
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
                                    Active
                                </Label>
                            </div>
                            <div className="flex items-center space-x-2">
                                <RadioGroupItem value="inactive" id="inactive" />
                                <Label htmlFor="inactive" className="text-sm font-medium text-gray-700">
                                    Inactive
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
                            className="text-red-600 hover:text-red-700"
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            className="bg-green-600 hover:bg-green-700 text-white"
                        >
                            Save Plan
                        </Button>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}
