import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { X, User, Phone, Mail, MapPin } from 'lucide-react';

interface BioModalProps {
    isOpen: boolean;
    onClose: () => void;
    user: {
        name: string;
        email: string;
        phone: string;
        location: string;
    };
    onSave: (data: { name: string; email: string; phone: string; location: string }) => void;
    processing?: boolean;
}

export default function BioModal({ isOpen, onClose, user, onSave, processing = false }: BioModalProps) {
    const [formData, setFormData] = useState({
        name: user.name || '',
        email: user.email || '',
        phone: user.phone || '',
        location: user.location || '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        onSave(formData);
        onClose();
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black/20 backdrop-blur-sm flex items-center justify-center z-50">
            <div className="bg-white rounded-xl p-6 w-full max-w-md mx-4">
                {/* Header */}
                <div className="flex justify-between items-start mb-6">
                    <div>
                        <h2 className="text-xl font-semibold text-gray-900">Personal Information</h2>
                        <p className="text-sm text-gray-600 mt-1">Tell us about yourself</p>
                    </div>
                    <button
                        onClick={onClose}
                        className="text-gray-500 hover:text-gray-700"
                    >
                        <X className="h-5 w-5" />
                    </button>
                </div>

                {/* Form */}
                <form onSubmit={handleSubmit} className="space-y-4">
                    {/* Username and Phone Number - Two columns */}
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <Label htmlFor="name" className="text-sm font-medium text-gray-700">
                                Username
                            </Label>
                            <div className="relative mt-1">
                                <User className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-500" />
                                <Input
                                    id="name"
                                    type="text"
                                    value={formData.name}
                                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                    placeholder="Enter your username"
                                    className="pl-10 bg-gray-50 border-gray-200 rounded-lg"
                                />
                            </div>
                        </div>
                        <div>
                            <Label htmlFor="phone" className="text-sm font-medium text-gray-700">
                                Phone Number
                            </Label>
                            <div className="relative mt-1">
                                <Phone className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-500" />
                                <Input
                                    id="phone"
                                    type="tel"
                                    value={formData.phone}
                                    onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                                    placeholder="Enter your Phone Number"
                                    className="pl-10 bg-gray-50 border-gray-200 rounded-lg"
                                />
                            </div>
                        </div>
                    </div>

                    {/* Email Address - Single column */}
                    <div>
                        <Label htmlFor="email" className="text-sm font-medium text-gray-700">
                            Email Address
                        </Label>
                        <div className="relative mt-1">
                            <Mail className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-500" />
                            <Input
                                id="email"
                                type="email"
                                value={formData.email}
                                onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                                placeholder="Enter your Delivery Address"
                                className="pl-10 bg-gray-50 border-gray-200 rounded-lg"
                            />
                        </div>
                    </div>

                    {/* Location - Single column */}
                    <div>
                        <Label htmlFor="location" className="text-sm font-medium text-gray-700">
                            Location
                        </Label>
                        <div className="relative mt-1">
                            <MapPin className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-500" />
                            <Input
                                id="location"
                                type="text"
                                value={formData.location}
                                onChange={(e) => setFormData({ ...formData, location: e.target.value })}
                                placeholder="Select your location"
                                className="pl-10 bg-gray-50 border-gray-200 rounded-lg"
                            />
                        </div>
                    </div>

                    {/* Save Button */}
                    <div className="flex justify-end pt-4">
                        <Button
                            type="submit"
                            disabled={processing}
                            className="bg-green-600 text-white hover:bg-green-700 rounded-lg px-6 py-2"
                        >
                            {processing ? 'Saving...' : 'Save and Continue'}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    );
}
