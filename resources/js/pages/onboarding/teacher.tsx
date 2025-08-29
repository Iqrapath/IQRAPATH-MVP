import { Head, useForm, router } from '@inertiajs/react';
import { FormEventHandler, useState, useEffect } from 'react';
import { type User } from '@/types';
import { toast } from 'sonner';

import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { ChevronsUpDown } from 'lucide-react';
import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';
import { Upload, Check, Clock } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
// We'll create our own country data to avoid dependency issues

interface TeacherOnboardingProps {
    user: User;
    subjects: Array<{id: number; name: string}>;
}

type TeacherFormData = {
    [key: string]: any;
    // Step 1: Personal Information
    name: string;
    phone: string;
    country: string;
    city: string;
    profile_photo?: File;
    
    // Step 2: Teaching Details
    subjects: string[];
    experience_years: string;
    qualification: string;
    bio: string;
    
    // Step 3: Availability & Schedule
    timezone: string;
    teaching_mode: string;
    availability: {
        monday: { enabled: boolean; from: string; to: string };
        tuesday: { enabled: boolean; from: string; to: string };
        wednesday: { enabled: boolean; from: string; to: string };
        thursday: { enabled: boolean; from: string; to: string };
        friday: { enabled: boolean; from: string; to: string };
        saturday: { enabled: boolean; from: string; to: string };
        sunday: { enabled: boolean; from: string; to: string };
    };
    
    // Step 4: Payment & Earnings
    currency: string;
    hourly_rate: string;
    payment_method: string;
    
    // Wallet & Earnings Setup
    withdrawal_method: string;
    bank_name?: string;
    custom_bank_name?: string;
    account_number?: string;
    account_name?: string;
};

const EXPERIENCE_OPTIONS = [
    '1-2 years', '3-5 years', '6-10 years', '10+ years'
];

// Country data with calling codes and flags
interface Country {
    name: string;
    code: string;
    flag: string;
    callingCode: string;
}

const WORLD_COUNTRIES: Country[] = [
    { name: 'Nigeria', code: 'NG', flag: '🇳🇬', callingCode: '+234' },
    { name: 'United States', code: 'US', flag: '🇺🇸', callingCode: '+1' },
    { name: 'United Kingdom', code: 'GB', flag: '🇬🇧', callingCode: '+44' },
    { name: 'Canada', code: 'CA', flag: '🇨🇦', callingCode: '+1' },
    { name: 'Saudi Arabia', code: 'SA', flag: '🇸🇦', callingCode: '+966' },
    { name: 'Egypt', code: 'EG', flag: '🇪🇬', callingCode: '+20' },
    { name: 'Pakistan', code: 'PK', flag: '🇵🇰', callingCode: '+92' },
    { name: 'India', code: 'IN', flag: '🇮🇳', callingCode: '+91' },
    { name: 'United Arab Emirates', code: 'AE', flag: '🇦🇪', callingCode: '+971' },
    { name: 'Afghanistan', code: 'AF', flag: '🇦🇫', callingCode: '+93' },
    { name: 'Albania', code: 'AL', flag: '🇦🇱', callingCode: '+355' },
    { name: 'Algeria', code: 'DZ', flag: '🇩🇿', callingCode: '+213' },
    { name: 'Argentina', code: 'AR', flag: '🇦🇷', callingCode: '+54' },
    { name: 'Australia', code: 'AU', flag: '🇦🇺', callingCode: '+61' },
    { name: 'Austria', code: 'AT', flag: '🇦🇹', callingCode: '+43' },
    { name: 'Bangladesh', code: 'BD', flag: '🇧🇩', callingCode: '+880' },
    { name: 'Belgium', code: 'BE', flag: '🇧🇪', callingCode: '+32' },
    { name: 'Brazil', code: 'BR', flag: '🇧🇷', callingCode: '+55' },
    { name: 'China', code: 'CN', flag: '🇨🇳', callingCode: '+86' },
    { name: 'Denmark', code: 'DK', flag: '🇩🇰', callingCode: '+45' },
    { name: 'Finland', code: 'FI', flag: '🇫🇮', callingCode: '+358' },
    { name: 'France', code: 'FR', flag: '🇫🇷', callingCode: '+33' },
    { name: 'Germany', code: 'DE', flag: '🇩🇪', callingCode: '+49' },
    { name: 'Ghana', code: 'GH', flag: '🇬🇭', callingCode: '+233' },
    { name: 'Greece', code: 'GR', flag: '🇬🇷', callingCode: '+30' },
    { name: 'Indonesia', code: 'ID', flag: '🇮🇩', callingCode: '+62' },
    { name: 'Iran', code: 'IR', flag: '🇮🇷', callingCode: '+98' },
    { name: 'Iraq', code: 'IQ', flag: '🇮🇶', callingCode: '+964' },
    { name: 'Ireland', code: 'IE', flag: '🇮🇪', callingCode: '+353' },
    { name: 'Israel', code: 'IL', flag: '🇮🇱', callingCode: '+972' },
    { name: 'Italy', code: 'IT', flag: '🇮🇹', callingCode: '+39' },
    { name: 'Japan', code: 'JP', flag: '🇯🇵', callingCode: '+81' },
    { name: 'Jordan', code: 'JO', flag: '🇯🇴', callingCode: '+962' },
    { name: 'Kenya', code: 'KE', flag: '🇰🇪', callingCode: '+254' },
    { name: 'Kuwait', code: 'KW', flag: '🇰🇼', callingCode: '+965' },
    { name: 'Lebanon', code: 'LB', flag: '🇱🇧', callingCode: '+961' },
    { name: 'Malaysia', code: 'MY', flag: '🇲🇾', callingCode: '+60' },
    { name: 'Morocco', code: 'MA', flag: '🇲🇦', callingCode: '+212' },
    { name: 'Netherlands', code: 'NL', flag: '🇳🇱', callingCode: '+31' },
    { name: 'New Zealand', code: 'NZ', flag: '🇳🇿', callingCode: '+64' },
    { name: 'Norway', code: 'NO', flag: '🇳🇴', callingCode: '+47' },
    { name: 'Oman', code: 'OM', flag: '🇴🇲', callingCode: '+968' },
    { name: 'Philippines', code: 'PH', flag: '🇵🇭', callingCode: '+63' },
    { name: 'Poland', code: 'PL', flag: '🇵🇱', callingCode: '+48' },
    { name: 'Portugal', code: 'PT', flag: '🇵🇹', callingCode: '+351' },
    { name: 'Qatar', code: 'QA', flag: '🇶🇦', callingCode: '+974' },
    { name: 'Russia', code: 'RU', flag: '🇷🇺', callingCode: '+7' },
    { name: 'South Africa', code: 'ZA', flag: '🇿🇦', callingCode: '+27' },
    { name: 'South Korea', code: 'KR', flag: '🇰🇷', callingCode: '+82' },
    { name: 'Spain', code: 'ES', flag: '🇪🇸', callingCode: '+34' },
    { name: 'Sweden', code: 'SE', flag: '🇸🇪', callingCode: '+46' },
    { name: 'Switzerland', code: 'CH', flag: '🇨🇭', callingCode: '+41' },
    { name: 'Syria', code: 'SY', flag: '🇸🇾', callingCode: '+963' },
    { name: 'Thailand', code: 'TH', flag: '🇹🇭', callingCode: '+66' },
    { name: 'Turkey', code: 'TR', flag: '🇹🇷', callingCode: '+90' },
    { name: 'Ukraine', code: 'UA', flag: '🇺🇦', callingCode: '+380' },
    { name: 'Vietnam', code: 'VN', flag: '🇻🇳', callingCode: '+84' },
    { name: 'Yemen', code: 'YE', flag: '🇾🇪', callingCode: '+967' },
].sort((a, b) => a.name.localeCompare(b.name));

// Popular countries to show first
const POPULAR_COUNTRIES = ['NG', 'US', 'GB', 'CA', 'SA', 'EG', 'PK', 'IN', 'AE'];

const sortedCountries = [
    ...WORLD_COUNTRIES.filter(c => POPULAR_COUNTRIES.includes(c.code)),
    ...WORLD_COUNTRIES.filter(c => !POPULAR_COUNTRIES.includes(c.code))
];

export default function TeacherOnboarding({ user, subjects }: TeacherOnboardingProps) {
    const [currentStep, setCurrentStep] = useState(() => {
        // Retrieve step from sessionStorage on component mount
        const savedStep = sessionStorage.getItem('teacher_onboarding_step');
        return savedStep ? parseInt(savedStep, 10) : 1;
    });
    const [isCompleted, setIsCompleted] = useState(() => {
        // Check if onboarding was completed
        const completed = sessionStorage.getItem('teacher_onboarding_completed');
        return completed === 'true';
    });
    
    // Check if user is already verified (for returning verified teachers)
    const [isVerified, setIsVerified] = useState(() => {
        return user.teacherProfile?.verified || false;
    });
    
    // Check if user should be redirected to dashboard (already verified)
    useEffect(() => {
        if (isVerified && user.role === 'teacher') {
            // User is already verified, redirect to dashboard
            router.visit(route('teacher.dashboard'));
        }
    }, [isVerified, user.role]);
    const [profilePhotoPreview, setProfilePhotoPreview] = useState<string | null>(null);
    const [cities, setCities] = useState<string[]>([]);
    const [loadingCities, setLoadingCities] = useState(false);
    const [selectedCountry, setSelectedCountry] = useState<Country | null>(null);
    const [countryOpen, setCountryOpen] = useState(false);
    const [cityOpen, setCityOpen] = useState(false);
    const [phoneCountryOpen, setPhoneCountryOpen] = useState(false);
    
    const { data, setData, post, processing, errors } = useForm<TeacherFormData>({
        // Step 1
        name: user.name || '',
        phone: '',
        country_code: '',
        country: '',
        city: '',
        calling_code: '',
        
        // Step 2
        subjects: [],
        experience_years: '',
        qualification: '',
        bio: '',
        
        // Step 3
        timezone: '',
        teaching_mode: '',
        availability: {
            monday: { enabled: false, from: '', to: '' },
            tuesday: { enabled: false, from: '', to: '' },
            wednesday: { enabled: false, from: '', to: '' },
            thursday: { enabled: false, from: '', to: '' },
            friday: { enabled: false, from: '', to: '' },
            saturday: { enabled: false, from: '', to: '' },
            sunday: { enabled: false, from: '', to: '' }
        },
        
        // Step 4
        currency: '',
        hourly_rate: '',
        payment_method: '',
        
        // Wallet & Earnings Setup
        withdrawal_method: '',
        bank_name: '',
        custom_bank_name: '',
        account_number: '',
        account_name: ''
    });

    // Fetch cities when country changes
    useEffect(() => {
        if (data.country && data.country !== '') {
            fetchCities(data.country);
        }
    }, [data.country]);

    const fetchCities = async (countryName: string) => {
        setLoadingCities(true);
        try {
            // Using a free API for cities - you might want to replace with a more reliable one
            const response = await fetch(`https://countriesnow.space/api/v0.1/countries/cities`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ country: countryName })
            });
            const result = await response.json();
            if (result.error === false && result.data) {
                setCities(result.data.sort());
            } else {
                setCities([]);
            }
        } catch (error) {
            console.error('Error fetching cities:', error);
            setCities([]);
        }
        setLoadingCities(false);
    };

    const handleCountryChange = (countryName: string) => {
        const country = sortedCountries.find(c => c.name === countryName);
        if (country) {
            // If no phone country is selected yet, auto-select it
            if (!selectedCountry) {
                setSelectedCountry(country);
                setData({
                    ...data,
                    country: countryName,
                    country_code: country.code,
                    calling_code: country.callingCode,
                    city: '', // Reset city when country changes
                });
            } else {
                setData({
                    ...data,
                    country: countryName,
                    city: '', // Reset city when country changes
                });
            }
        }
    };

    const formatTime = (time: string) => {
        if (!time) return '';
        const [hours, minutes] = time.split(':');
        const hour = parseInt(hours);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const displayHour = hour % 12 || 12;
        return `${displayHour}:${minutes} ${ampm}`;
    };

    const generateTimeOptions = () => {
        const times = [];
        for (let hour = 0; hour < 24; hour++) {
            for (let minute of [0, 30]) {
                const timeString = `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`;
                times.push({
                    value: timeString,
                    label: formatTime(timeString)
                });
            }
        }
        return times;
    };

    const timeOptions = generateTimeOptions();

    const handlePhotoUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            setData('profile_photo', file);
            const reader = new FileReader();
            reader.onloadend = () => {
                setProfilePhotoPreview(reader.result as string);
            };
            reader.readAsDataURL(file);
        }
    };

    const handleSubjectToggle = (subject: string) => {
        const currentSubjects = data.subjects;
        if (currentSubjects.includes(subject)) {
            setData('subjects', currentSubjects.filter(s => s !== subject));
        } else {
            setData('subjects', [...currentSubjects, subject]);
        }
    };

    const handleAvailabilityChange = (day: string, field: string, value: any) => {
        setData('availability', {
            ...data.availability,
            [day]: {
                ...data.availability[day as keyof typeof data.availability],
                [field]: value
            }
        });
    };

    const saveCurrentStep = async () => {
        try {
            console.log('Saving step:', currentStep);
            console.log('Current data:', data);
            
            const formData = new FormData();
            formData.append('step', currentStep.toString());
            
            // Add current step data
            switch (currentStep) {
                case 1:
                    formData.append('name', data.name || '');
                    formData.append('phone', data.phone || '');
                    formData.append('country', data.country || '');
                    formData.append('country_code', data.country_code || '');
                    formData.append('calling_code', data.calling_code || '');
                    formData.append('city', data.city || '');
                    if (data.profile_photo) {
                        formData.append('profile_photo', data.profile_photo);
                    }
                    break;
                case 2:
                    formData.append('subjects', JSON.stringify(data.subjects || []));
                    formData.append('experience_years', data.experience_years || '');
                    formData.append('qualification', data.qualification || '');
                    formData.append('bio', data.bio || '');
                    break;
                case 3:
                    formData.append('timezone', data.timezone || '');
                    formData.append('teaching_mode', data.teaching_mode || '');
                    formData.append('availability', JSON.stringify(data.availability || {}));
                    break;
                case 4:
                    formData.append('currency', data.currency || '');
                    formData.append('hourly_rate', data.hourly_rate || '');
                    formData.append('payment_method', data.payment_method || '');
                    formData.append('withdrawal_method', data.withdrawal_method || '');
                    formData.append('bank_name', data.bank_name || '');
                    formData.append('custom_bank_name', data.custom_bank_name || '');
                    formData.append('account_number', data.account_number || '');
                    formData.append('account_name', data.account_name || '');
                    break;
            }

            // Get CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            
            // Log form data for debugging
            console.log('Form data being sent:');
            for (let [key, value] of formData.entries()) {
                console.log(`${key}:`, value);
            }
            
            const response = await fetch(route('onboarding.teacher.step'), {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': csrfToken || '',
                    'Accept': 'application/json',
                },
            });

            // Check if response is ok
            if (!response.ok) {
                const errorText = await response.text();
                console.error('HTTP Error:', response.status, response.statusText);
                console.error('Response body:', errorText);
                return false;
            }

            const result = await response.json();
            
            if (result.success) {
                console.log('Step saved successfully:', result.message);
                
                // Show success toast for each step
                const stepMessages: Record<number, string> = {
                    1: '✅ Personal information saved successfully!',
                    2: '✅ Teaching details saved successfully!',
                    3: '✅ Availability & schedule saved successfully!',
                    4: '✅ Payment & earnings setup completed!'
                };
                
                toast.success(stepMessages[currentStep] || '✅ Step completed successfully!');
                return true;
            } else {
                console.error('Error saving step:', result.message);
                console.error('Full response:', result);
                toast.error(`❌ Failed to save step ${currentStep}: ${result.message}`);
                return false;
            }
        } catch (error) {
            console.error('Error saving step:', error);
            toast.error(`❌ Network error while saving step ${currentStep}. Please try again.`);
            return false;
        }
    };

    const nextStep = async () => {
        console.log('Moving to next step, current step:', currentStep);
        
        // Re-enable step saving
        const saved = await saveCurrentStep();
        console.log('Step saved:', saved);
        
        if (saved && currentStep < 4) {
            const nextStepNumber = currentStep + 1;
            setCurrentStep(nextStepNumber);
            // Save step to sessionStorage
            sessionStorage.setItem('teacher_onboarding_step', nextStepNumber.toString());
            
            // Show step progress toast
            const stepNames: Record<number, string> = {
                2: 'Teaching Details',
                3: 'Availability & Schedule', 
                4: 'Payment & Earnings'
            };
            toast.info(`📋 Step ${nextStepNumber}: ${stepNames[nextStepNumber]}`);
        } else if (!saved) {
            console.error('Failed to save step, not proceeding');
            toast.error('❌ Please complete all required fields before proceeding.');
        }
    };

    const prevStep = () => {
        if (currentStep > 1) {
            const prevStepNumber = currentStep - 1;
            setCurrentStep(prevStepNumber);
            // Save step to sessionStorage
            sessionStorage.setItem('teacher_onboarding_step', prevStepNumber.toString());
        }
    };

    const submit: FormEventHandler = async (e) => {
        e.preventDefault();
        
        console.log('Submitting final step...');
        
        // Re-enable step saving for final step
        const saved = await saveCurrentStep();
        console.log('Final step saved:', saved);
        
        if (saved) {
            // Mark as completed and show success screen
            setIsCompleted(true);
            // Save completion status to sessionStorage
            sessionStorage.setItem('teacher_onboarding_completed', 'true');
            // Clear the step tracking since onboarding is complete
            sessionStorage.removeItem('teacher_onboarding_step');
            
            // Show final success toast
            toast.success('🎉 Teacher onboarding completed successfully! Welcome to IqraPath!', {
                duration: 5000,
            });
        } else {
            console.error('Failed to save final step');
            toast.error('❌ Failed to complete onboarding. Please try again.');
        }
    };

    const renderStepIndicator = () => (
        <div className="flex items-center justify-center mb-8">
            {[1, 2, 3, 4].map((step) => (
                <div key={step} className="flex items-center">
                    <div className={`w-10 h-10 rounded-full flex items-center justify-center text-white font-medium ${
                        step < currentStep ? 'bg-teal-600' : 
                        step === currentStep ? 'bg-teal-600' : 'bg-gray-300'
                    }`}>
                        {step < currentStep ? <Check size={20} /> : step}
                    </div>
                    {step < 4 && (
                        <div className={`w-20 h-1 mx-2 ${
                            step < currentStep ? 'bg-teal-600' : 'bg-gray-300'
                        }`} />
                    )}
                </div>
            ))}
        </div>
    );

    const renderStep2 = () => (
        <div className="space-y-6">
            <div className="text-center mb-6">
                <h2 className="text-2xl font-bold mb-2">Teaching Details</h2>
                <p className="text-gray-600">Your Teaching Expertise</p>
            </div>

            <div>
                <Label>Subjects you teach</Label>
                <div className="grid grid-cols-2 md:grid-cols-3 gap-3 mt-2">
                    {subjects.map((subject) => (
                        <div key={subject.id} className="flex items-center space-x-2">
                            <Checkbox
                                id={subject.name}
                                checked={data.subjects.includes(subject.name)}
                                onCheckedChange={() => handleSubjectToggle(subject.name)}
                            />
                            <Label htmlFor={subject.name} className="text-sm cursor-pointer">
                                {subject.name}
                            </Label>
                        </div>
                    ))}
                </div>
                {errors.subjects && <p className="text-red-500 text-sm mt-1">{errors.subjects}</p>}
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <Label htmlFor="experience_years">Years of Experience</Label>
                    <Select value={data.experience_years} onValueChange={(value) => setData('experience_years', value)}>
                        <SelectTrigger id="experience_years" className="mt-1">
                            <SelectValue placeholder="Select one option..." />
                        </SelectTrigger>
                        <SelectContent>
                            {EXPERIENCE_OPTIONS.map((option) => (
                                <SelectItem key={option} value={option}>{option}</SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    {errors.experience_years && <p className="text-red-500 text-sm mt-1">{errors.experience_years}</p>}
                </div>

                <div>
                    <Label htmlFor="qualification">Qualification</Label>
                    <Input
                        id="qualification"
                        value={data.qualification}
                        onChange={(e) => setData('qualification', e.target.value)}
                        placeholder="e.g., Ijazah in Quran, Islamic Studies Degree, Al-Azhar Graduate"
                        className="mt-1"
                    />
                    {errors.qualification && <p className="text-red-500 text-sm mt-1">{errors.qualification}</p>}
                </div>
            </div>

            <div>
                <Label htmlFor="bio">Introduce Yourself</Label>
                <p className="text-sm text-gray-600 mb-2">
                    Share your teaching experience and passion for education and briefly mention your interests and hobbies
                </p>
                <Textarea
                    id="bio"
                    value={data.bio}
                    onChange={(e) => setData('bio', e.target.value)}
                    placeholder="Write your bio here..."
                    rows={5}
                    className="mt-1"
                />
                {errors.bio && <p className="text-red-500 text-sm mt-1">{errors.bio}</p>}
            </div>
        </div>
    );

    const renderStep3 = () => (
        <div className="space-y-6">
            <div className="text-center mb-6">
                <h2 className="text-2xl font-bold mb-2">Availability & Schedule</h2>
                <p className="text-gray-600">Your Teaching Expertise</p>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <Label htmlFor="timezone">Set your Time Zone</Label>
                    <p className="text-sm text-gray-600 mb-2">
                        A correct time zone is essential to coordinate lessons with international students
                    </p>
                    <Select value={data.timezone} onValueChange={(value) => setData('timezone', value)}>
                        <SelectTrigger id="timezone" className="mt-1">
                            <SelectValue placeholder="Select one option..." />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="WAT">West Africa Time (WAT)</SelectItem>
                            <SelectItem value="GMT">Greenwich Mean Time (GMT)</SelectItem>
                            <SelectItem value="EST">Eastern Standard Time (EST)</SelectItem>
                            <SelectItem value="PST">Pacific Standard Time (PST)</SelectItem>
                        </SelectContent>
                    </Select>
                    {errors.timezone && <p className="text-red-500 text-sm mt-1">{errors.timezone}</p>}
                </div>

                <div>
                    <Label>Teaching Mode</Label>
                    <p className="text-sm text-gray-600 mb-2">
                        Max 8 hours/day for full-time, 3 hours/day for part-time
                    </p>
                    <div className="flex space-x-4 mt-2">
                        <div className="flex items-center space-x-2">
                            <Checkbox
                                id="full-time"
                                checked={data.teaching_mode === 'full-time'}
                                onCheckedChange={(checked) => checked && setData('teaching_mode', 'full-time')}
                            />
                            <Label htmlFor="full-time" className="cursor-pointer">Full-Time</Label>
                        </div>
                        <div className="flex items-center space-x-2">
                            <Checkbox
                                id="part-time"
                                checked={data.teaching_mode === 'part-time'}
                                onCheckedChange={(checked) => checked && setData('teaching_mode', 'part-time')}
                            />
                            <Label htmlFor="part-time" className="cursor-pointer">Part-Time</Label>
                        </div>
                    </div>
                    {errors.teaching_mode && <p className="text-red-500 text-sm mt-1">{errors.teaching_mode}</p>}
                </div>
            </div>

            <div>
                <Label>Select Your Availability</Label>
                <p className="text-sm text-gray-600 mb-4">
                    A correct time zone is essential to coordinate lessons with international students
                </p>
                
                <div className="space-y-4">
                    {Object.entries(data.availability).map(([day, schedule]) => (
                        <div key={day} className="flex items-center space-x-4">
                            <div className="flex items-center space-x-2 w-24">
                                <Checkbox
                                    id={day}
                                    checked={schedule.enabled}
                                    onCheckedChange={(checked) => 
                                        handleAvailabilityChange(day, 'enabled', checked)
                                    }
                                />
                                <Label htmlFor={day} className="capitalize cursor-pointer">
                                    {day}
                                </Label>
                            </div>
                            
                            {schedule.enabled && (
                                <div className="flex items-center space-x-2">
                                    <div>
                                        <Label htmlFor={`${day}-from`} className="text-sm">From</Label>
                                        <Select 
                                            value={schedule.from} 
                                            onValueChange={(value) => handleAvailabilityChange(day, 'from', value)}
                                        >
                                            <SelectTrigger id={`${day}-from`} className="w-32">
                                                <SelectValue placeholder="Start time" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {timeOptions.map((time) => (
                                                    <SelectItem key={time.value} value={time.value}>
                                                        {time.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    
                                    <div>
                                        <Label htmlFor={`${day}-to`} className="text-sm">To</Label>
                                        <Select 
                                            value={schedule.to} 
                                            onValueChange={(value) => handleAvailabilityChange(day, 'to', value)}
                                        >
                                            <SelectTrigger id={`${day}-to`} className="w-32">
                                                <SelectValue placeholder="End time" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {timeOptions.map((time) => (
                                                    <SelectItem key={time.value} value={time.value}>
                                                        {time.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>
                            )}
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );

    const renderStep4 = () => (
        <div className="space-y-6">
            <div className="text-center mb-6">
                <h2 className="text-2xl font-bold mb-2">Payment & Earnings</h2>
                <p className="text-gray-600">Set Your Rate & Payment Method</p>
            </div>

            {/* Earning Configuration */}
            <div className="space-y-6">
                <div>
                    <Label>Preferred Currency</Label>
                    <div className="flex space-x-4 mt-2">
                        <div className="flex items-center space-x-2">
                            <Checkbox
                                id="naira"
                                checked={data.currency === 'naira'}
                                onCheckedChange={(checked) => checked && setData('currency', 'naira')}
                            />
                            <Label htmlFor="naira" className="cursor-pointer">Nigerian Naira (₦)</Label>
                        </div>
                        <div className="flex items-center space-x-2">
                            <Checkbox
                                id="dollar"
                                checked={data.currency === 'dollar'}
                                onCheckedChange={(checked) => checked && setData('currency', 'dollar')}
                            />
                            <Label htmlFor="dollar" className="cursor-pointer">US Dollar ($)</Label>
                        </div>
                    </div>
                    {errors.currency && <p className="text-red-500 text-sm mt-1">{errors.currency}</p>}
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <Label htmlFor="hourly_rate">Hourly Rate</Label>
                        <Input
                            id="hourly_rate"
                            type="number"
                            value={data.hourly_rate}
                            onChange={(e) => setData('hourly_rate', e.target.value)}
                            placeholder={data.currency === 'dollar' ? 'e.g., 25' : 'e.g., 5000'}
                            className="mt-1"
                        />
                        <p className="text-sm text-gray-500 mt-1">
                            {data.currency === 'dollar' ? 'USD per hour' : 'NGN per hour'}
                        </p>
                        {errors.hourly_rate && <p className="text-red-500 text-sm mt-1">{errors.hourly_rate}</p>}
                    </div>

                    <div>
                        <Label htmlFor="withdrawal_method">Withdrawal Method</Label>
                        <Select value={data.withdrawal_method} onValueChange={(value) => setData('withdrawal_method', value)}>
                            <SelectTrigger id="withdrawal_method" className="mt-1">
                                <SelectValue placeholder="Select withdrawal method" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="bank_transfer">Bank Transfer</SelectItem>
                                <SelectItem value="paystack">Paystack</SelectItem>
                                <SelectItem value="stripe">Stripe</SelectItem>
                            </SelectContent>
                        </Select>
                        {errors.withdrawal_method && <p className="text-red-500 text-sm mt-1">{errors.withdrawal_method}</p>}
                    </div>
                </div>

                {/* Bank Transfer Details */}
                {data.withdrawal_method === 'bank_transfer' && (
                    <div className="space-y-4 p-4 bg-gray-50 rounded-lg">
                        <h4 className="font-medium">Bank Account Details</h4>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <Label htmlFor="bank_name">Bank Name</Label>
                                <Select value={data.bank_name} onValueChange={(value) => setData('bank_name', value)}>
                                    <SelectTrigger id="bank_name" className="mt-1">
                                        <SelectValue placeholder="Select your bank" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="access_bank">Access Bank</SelectItem>
                                        <SelectItem value="gtbank">GTBank</SelectItem>
                                        <SelectItem value="first_bank">First Bank</SelectItem>
                                        <SelectItem value="uba">UBA</SelectItem>
                                        <SelectItem value="zenith_bank">Zenith Bank</SelectItem>
                                        <SelectItem value="fidelity_bank">Fidelity Bank</SelectItem>
                                        <SelectItem value="sterling_bank">Sterling Bank</SelectItem>
                                        <SelectItem value="union_bank">Union Bank</SelectItem>
                                        <SelectItem value="wema_bank">Wema Bank</SelectItem>
                                        <SelectItem value="fcmb">FCMB</SelectItem>
                                        <SelectItem value="other">Other (Enter Below)</SelectItem>
                                    </SelectContent>
                                </Select>
                                {errors.bank_name && <p className="text-red-500 text-sm mt-1">{errors.bank_name}</p>}
                                
                                {data.bank_name === 'other' && (
                                    <div className="mt-2">
                                        <Label htmlFor="custom_bank_name">Bank Name</Label>
                                        <Input
                                            id="custom_bank_name"
                                            value={data.custom_bank_name}
                                            onChange={(e) => setData('custom_bank_name', e.target.value)}
                                            placeholder="Enter your bank name"
                                            className="mt-1"
                                        />
                                        {errors.custom_bank_name && <p className="text-red-500 text-sm mt-1">{errors.custom_bank_name}</p>}
                                    </div>
                                )}
                            </div>
                            
                            <div>
                                <Label htmlFor="account_name">Account Name</Label>
                                <Input
                                    id="account_name"
                                    value={data.account_name}
                                    onChange={(e) => setData('account_name', e.target.value)}
                                    placeholder="Full name on account"
                                    className="mt-1"
                                />
                                {errors.account_name && <p className="text-red-500 text-sm mt-1">{errors.account_name}</p>}
                            </div>
                        </div>
                        
                        <div>
                            <Label htmlFor="account_number">Account Number</Label>
                            <Input
                                id="account_number"
                                value={data.account_number}
                                onChange={(e) => setData('account_number', e.target.value)}
                                placeholder="Account number"
                                className="mt-1"
                            />
                            {errors.account_number && <p className="text-red-500 text-sm mt-1">{errors.account_number}</p>}
                        </div>
                    </div>
                )}
            </div>
        </div>
    );

    const renderStep1 = () => (
        <div className="space-y-6">
            <div className="text-center mb-6">
                <h2 className="text-2xl font-bold mb-2">Personal Information</h2>
                <p className="text-gray-600">Tell us about yourself</p>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <Label htmlFor="name">Name</Label>
                    <Input
                        id="name"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        placeholder="Enter your name"
                        className="mt-1"
                    />
                    {errors.name && <p className="text-red-500 text-sm mt-1">{errors.name}</p>}
                </div>

                <div>
                    <Label htmlFor="phone">Phone Number</Label>
                    <div className="flex mt-1">
                        <Popover open={phoneCountryOpen} onOpenChange={setPhoneCountryOpen}>
                            <PopoverTrigger asChild>
                                <Button
                                    variant="outline"
                                    role="combobox"
                                    aria-expanded={phoneCountryOpen}
                                    className="w-32 justify-between rounded-r-none border-r-0"
                                >
                                    {selectedCountry ? (
                                        <span className="flex items-center space-x-1">
                                            <span>{selectedCountry.flag}</span>
                                            <span className="text-sm">{selectedCountry.callingCode}</span>
                                        </span>
                                    ) : (
                                        "Code"
                                    )}
                                    <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent className="w-[300px] p-0">
                                <Command>
                                    <CommandInput placeholder="Search country..." />
                                    <CommandEmpty>No country found.</CommandEmpty>
                                    <CommandGroup>
                                        <CommandList>
                                            {sortedCountries.map((country) => (
                                                <CommandItem
                                                    key={country.code}
                                                    value={`${country.name} ${country.callingCode}`}
                                                    onSelect={() => {
                                                        setSelectedCountry(country);
                                                        setData({
                                                            ...data,
                                                            calling_code: country.callingCode,
                                                            country_code: country.code,
                                                        });
                                                        setPhoneCountryOpen(false);
                                                    }}
                                                >
                                                    <span className="flex items-center space-x-2">
                                                        <span>{country.flag}</span>
                                                        <span>{country.callingCode}</span>
                                                        <span className="text-sm text-gray-500">{country.name}</span>
                                                    </span>
                                                </CommandItem>
                                            ))}
                                        </CommandList>
                                    </CommandGroup>
                                </Command>
                            </PopoverContent>
                        </Popover>
                        <Input
                            id="phone"
                            value={data.phone}
                            onChange={(e) => setData('phone', e.target.value)}
                            placeholder="Phone Number"
                            className="rounded-l-none"
                        />
                    </div>
                    {errors.phone && <p className="text-red-500 text-sm mt-1">{errors.phone}</p>}
                </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <Label htmlFor="country-select">Country</Label>
                    <Popover open={countryOpen} onOpenChange={setCountryOpen}>
                        <PopoverTrigger asChild>
                            <Button
                                id="country-select"
                                variant="outline"
                                role="combobox"
                                aria-expanded={countryOpen}
                                className="w-full justify-between mt-1"
                            >
                                {data.country ? (
                                    <span className="flex items-center space-x-2">
                                        <span>{sortedCountries.find(c => c.name === data.country)?.flag}</span>
                                        <span>{data.country}</span>
                                    </span>
                                ) : (
                                    "Select your country..."
                                )}
                                <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent className="w-[400px] p-0">
                            <Command>
                                <CommandInput placeholder="Search country..." />
                                <CommandEmpty>No country found.</CommandEmpty>
                                <CommandGroup>
                                    <CommandList>
                                        {sortedCountries.map((country) => (
                                            <CommandItem
                                                key={country.code}
                                                value={country.name}
                                                onSelect={(currentValue) => {
                                                    handleCountryChange(currentValue);
                                                    setCountryOpen(false);
                                                }}
                                            >
                                                <span className="flex items-center space-x-2">
                                                    <span>{country.flag}</span>
                                                    <span>{country.name}</span>
                                                </span>
                                            </CommandItem>
                                        ))}
                                    </CommandList>
                                </CommandGroup>
                            </Command>
                        </PopoverContent>
                    </Popover>
                    {errors.country && <p className="text-red-500 text-sm mt-1">{errors.country}</p>}
                </div>

                <div>
                    <Label htmlFor="city-select">City</Label>
                    <Popover open={cityOpen} onOpenChange={setCityOpen}>
                        <PopoverTrigger asChild>
                            <Button
                                id="city-select"
                                variant="outline"
                                role="combobox"
                                aria-expanded={cityOpen}
                                className="w-full justify-between mt-1"
                                disabled={!data.country || loadingCities}
                            >
                                {data.city || (
                                    !data.country ? "Select country first..." :
                                    loadingCities ? "Loading cities..." :
                                    "Select your city..."
                                )}
                                <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent className="w-[400px] p-0">
                            <Command>
                                <CommandInput placeholder="Search city..." />
                                <CommandEmpty>No city found.</CommandEmpty>
                                <CommandGroup>
                                    <CommandList>
                                        {cities.length > 0 ? (
                                            cities.map((city) => (
                                                <CommandItem
                                                    key={city}
                                                    value={city}
                                                    onSelect={(currentValue) => {
                                                        setData('city', currentValue);
                                                        setCityOpen(false);
                                                    }}
                                                >
                                                    {city}
                                                </CommandItem>
                                            ))
                                        ) : data.country && !loadingCities ? (
                                            <CommandItem
                                                value="other"
                                                onSelect={(currentValue) => {
                                                    setData('city', currentValue);
                                                    setCityOpen(false);
                                                }}
                                            >
                                                Other / City not listed
                                            </CommandItem>
                                        ) : null}
                                    </CommandList>
                                </CommandGroup>
                            </Command>
                        </PopoverContent>
                    </Popover>
                    {errors.city && <p className="text-red-500 text-sm mt-1">{errors.city}</p>}
                </div>
            </div>

            <div>
                <Label>Profile Photo</Label>
                <p className="text-sm text-gray-600 mb-4">Choose a photo that will help learners get to know you</p>
                <div className="flex items-center space-x-4">
                    <div className="w-20 h-20 bg-gray-100 rounded-lg flex items-center justify-center overflow-hidden">
                        {profilePhotoPreview ? (
                            <img src={profilePhotoPreview} alt="Profile" className="w-full h-full object-cover" />
                        ) : (
                            <div className="text-center text-gray-400">
                                <Upload size={24} />
                                <div className="text-xs mt-1">JPG or PNG<br />Max 5MB</div>
                            </div>
                        )}
                    </div>
                    <div>
                        <input
                            type="file"
                            id="profile_photo"
                            accept="image/*"
                            onChange={handlePhotoUpload}
                            className="hidden"
                        />
                        <Label htmlFor="profile_photo" className="cursor-pointer inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            Upload
                        </Label>
                    </div>
                </div>
                {errors.profile_photo && <p className="text-red-500 text-sm mt-1">{errors.profile_photo}</p>}
            </div>
        </div>
    );

    const renderSuccessScreen = () => (
        <div className="text-center space-y-6">
            {/* Success Icon with Translucent Square Shapes */}
            <div className="relative mx-auto w-24 h-24">
                {/* Translucent teal square shapes */}
                <div className="absolute -top-2 -left-2 w-16 h-16 bg-teal-200/40 rounded-lg transform rotate-12"></div>
                <div className="absolute -bottom-2 -right-2 w-16 h-16 bg-teal-200/40 rounded-lg transform -rotate-12"></div>
                {/* Dark teal circle with checkmark */}
                <div className="absolute inset-0 bg-teal-600 rounded-full flex items-center justify-center">
                    <Check className="w-8 h-8 text-white stroke-[3]" />
                </div>
            </div>
            
            {/* Main Heading */}
            <div>
                <h2 className="text-2xl font-bold text-gray-900 mb-2">
                    Thank you for completing<br />registration!
                </h2>
                <p className="text-gray-600">
                    We've received your application and are currently reviewing it.
                </p>
            </div>

            {/* Informational Text Block */}
            <div className="bg-teal-50 border border-teal-200 rounded-lg p-4 max-w-md mx-auto">
                <p className="text-teal-800 font-medium text-center mb-2">
                    To ensure the quality and authenticity of our teachers, we require a quick live video call before you can proceed to your dashboard.
                </p>
                <p className="text-teal-700 text-center text-sm">
                    You will receive an email with the invitation live video call within 5 business days. Stay tuned!
                </p>
            </div>

            {/* Important Notes */}
            <div className="max-w-md mx-auto">
                <h4 className="text-orange-500 font-semibold mb-3">Important Notes</h4>
                <div className="space-y-2 text-center">
                    <div className="flex items-center justify-center gap-2">
                        <span className="text-yellow-500">⚠️</span>
                        <span className="text-gray-600 text-sm">Make sure to have a stable internet connection.</span>
                    </div>
                    <div className="flex items-center justify-center gap-2">
                        <span className="text-yellow-500">⚠️</span>
                        <span className="text-gray-600 text-sm">Use a quiet and well-lit environment.</span>
                    </div>
                    <div className="flex items-center justify-center gap-2">
                        <span className="text-yellow-500">⚠️</span>
                        <span className="text-gray-600 text-sm">Keep your ID and teaching qualifications ready.</span>
                    </div>
                </div>
            </div>


        </div>
    );

    if (isCompleted) {
        return (
            <AppLayout pageTitle="Registration Complete">
                <Head title="Registration Complete" />
                <div className="py-8 flex items-center justify-center">
                    <div className="max-w-2xl mx-auto px-4">
                        <Card className="bg-white shadow-sm">
                            <CardContent className="p-8">
                                {renderSuccessScreen()}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout pageTitle="Teacher Registration">
            <Head title="Teacher Registration" />
            
            <div className=" py-8">
                <div className="max-w-2xl mx-auto px-4">
                    {renderStepIndicator()}
                    
                    <Card className="bg-white shadow-sm">
                        <CardContent className="p-8">
                            <form onSubmit={submit}>
                                {currentStep === 1 && renderStep1()}
                                {currentStep === 2 && renderStep2()}
                                {currentStep === 3 && renderStep3()}
                                {currentStep === 4 && renderStep4()}
                                
                                <div className="flex justify-between mt-8">
                                    {currentStep > 1 && (
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={prevStep}
                                            className="px-6"
                                        >
                                            Back
                                        </Button>
                                    )}
                                    
                                    <div className="ml-auto">
                                        {currentStep < 4 ? (
                                            <Button
                                                type="button"
                                                onClick={nextStep}
                                                className="bg-teal-600 hover:bg-teal-700 text-white px-8 py-3 rounded-full"
                                            >
                                                Save and Continue
                                            </Button>
                                        ) : (
                                            <Button
                                                type="submit"
                                                disabled={processing}
                                                className="bg-teal-600 hover:bg-teal-700 text-white px-8 py-3 rounded-full"
                                            >
                                                {processing ? 'Completing...' : 'Complete Registration'}
                                            </Button>
                                        )}
                                    </div>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
