import { Head, useForm, router } from '@inertiajs/react';
import { FormEventHandler, useState, useEffect } from 'react';
import { type User } from '@/types';
import { toast } from 'sonner';
import { parsePhoneNumber, isValidPhoneNumber, getCountryCallingCode } from 'libphonenumber-js';
import ReactCountryFlag from 'react-country-flag';

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
import { Upload, Check, Clock, X } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
// We'll create our own country data to avoid dependency issues

interface TeacherOnboardingProps {
    user: User;
    subjects: Array<{ id: number; name: string }>;
    availableCurrencies?: Array<{
        value: string;
        label: string;
        symbol: string;
        is_default: boolean;
    }>;
    onboardingCompleted?: boolean;
    teacherData?: any;
    verificationRequest?: {
        id: number;
        status: string;
        docs_status: string;
        video_status: string;
        submitted_at: string;
        reviewed_at?: string;
        rejection_reason?: string;
    };
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
    preferred_currency: string;
    hourly_rate_usd: string;
    hourly_rate_ngn: string;
    payment_method: string;
    
    // Wallet & Earnings Setup
    withdrawal_method: string;
    bank_name?: string;
    custom_bank_name?: string;
    account_number?: string;
    account_name?: string;

        // Mobile Money
        mobile_provider?: string;
        mobile_number?: string;
        
        // Digital Wallets
        paypal_email?: string;
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
    { name: 'Afghanistan', code: 'AF', flag: '🇦🇫', callingCode: '+93' },
    { name: 'Albania', code: 'AL', flag: '🇦🇱', callingCode: '+355' },
    { name: 'Algeria', code: 'DZ', flag: '🇩🇿', callingCode: '+213' },
    { name: 'American Samoa', code: 'AS', flag: '🇦🇸', callingCode: '+1' },
    { name: 'Andorra', code: 'AD', flag: '🇦🇩', callingCode: '+376' },
    { name: 'Angola', code: 'AO', flag: '🇦🇴', callingCode: '+244' },
    { name: 'Anguilla', code: 'AI', flag: '🇦🇮', callingCode: '+1' },
    { name: 'Antarctica', code: 'AQ', flag: '🇦🇶', callingCode: '+672' },
    { name: 'Antigua and Barbuda', code: 'AG', flag: '🇦🇬', callingCode: '+1' },
    { name: 'Argentina', code: 'AR', flag: '🇦🇷', callingCode: '+54' },
    { name: 'Armenia', code: 'AM', flag: '🇦🇲', callingCode: '+374' },
    { name: 'Aruba', code: 'AW', flag: '🇦🇼', callingCode: '+297' },
    { name: 'Australia', code: 'AU', flag: '🇦🇺', callingCode: '+61' },
    { name: 'Austria', code: 'AT', flag: '🇦🇹', callingCode: '+43' },
    { name: 'Azerbaijan', code: 'AZ', flag: '🇦🇿', callingCode: '+994' },
    { name: 'Bahamas', code: 'BS', flag: '🇧🇸', callingCode: '+1' },
    { name: 'Bahrain', code: 'BH', flag: '🇧🇭', callingCode: '+973' },
    { name: 'Bangladesh', code: 'BD', flag: '🇧🇩', callingCode: '+880' },
    { name: 'Barbados', code: 'BB', flag: '🇧🇧', callingCode: '+1' },
    { name: 'Belarus', code: 'BY', flag: '🇧🇾', callingCode: '+375' },
    { name: 'Belgium', code: 'BE', flag: '🇧🇪', callingCode: '+32' },
    { name: 'Belize', code: 'BZ', flag: '🇧🇿', callingCode: '+501' },
    { name: 'Benin', code: 'BJ', flag: '🇧🇯', callingCode: '+229' },
    { name: 'Bermuda', code: 'BM', flag: '🇧🇲', callingCode: '+1' },
    { name: 'Bhutan', code: 'BT', flag: '🇧🇹', callingCode: '+975' },
    { name: 'Bolivia', code: 'BO', flag: '🇧🇴', callingCode: '+591' },
    { name: 'Bosnia and Herzegovina', code: 'BA', flag: '🇧🇦', callingCode: '+387' },
    { name: 'Botswana', code: 'BW', flag: '🇧🇼', callingCode: '+267' },
    { name: 'Brazil', code: 'BR', flag: '🇧🇷', callingCode: '+55' },
    { name: 'British Virgin Islands', code: 'VG', flag: '🇻🇬', callingCode: '+1' },
    { name: 'Brunei', code: 'BN', flag: '🇧🇳', callingCode: '+673' },
    { name: 'Bulgaria', code: 'BG', flag: '🇧🇬', callingCode: '+359' },
    { name: 'Burkina Faso', code: 'BF', flag: '🇧🇫', callingCode: '+226' },
    { name: 'Burundi', code: 'BI', flag: '🇧🇮', callingCode: '+257' },
    { name: 'Cambodia', code: 'KH', flag: '🇰🇭', callingCode: '+855' },
    { name: 'Cameroon', code: 'CM', flag: '🇨🇲', callingCode: '+237' },
    { name: 'Canada', code: 'CA', flag: '🇨🇦', callingCode: '+1' },
    { name: 'Cape Verde', code: 'CV', flag: '🇨🇻', callingCode: '+238' },
    { name: 'Cayman Islands', code: 'KY', flag: '🇰🇾', callingCode: '+1' },
    { name: 'Central African Republic', code: 'CF', flag: '🇨🇫', callingCode: '+236' },
    { name: 'Chad', code: 'TD', flag: '🇹🇩', callingCode: '+235' },
    { name: 'Chile', code: 'CL', flag: '🇨🇱', callingCode: '+56' },
    { name: 'China', code: 'CN', flag: '🇨🇳', callingCode: '+86' },
    { name: 'Christmas Island', code: 'CX', flag: '🇨🇽', callingCode: '+61' },
    { name: 'Cocos Islands', code: 'CC', flag: '🇨🇨', callingCode: '+61' },
    { name: 'Colombia', code: 'CO', flag: '🇨🇴', callingCode: '+57' },
    { name: 'Comoros', code: 'KM', flag: '🇰🇲', callingCode: '+269' },
    { name: 'Cook Islands', code: 'CK', flag: '🇨🇰', callingCode: '+682' },
    { name: 'Costa Rica', code: 'CR', flag: '🇨🇷', callingCode: '+506' },
    { name: 'Croatia', code: 'HR', flag: '🇭🇷', callingCode: '+385' },
    { name: 'Cuba', code: 'CU', flag: '🇨🇺', callingCode: '+53' },
    { name: 'Cyprus', code: 'CY', flag: '🇨🇾', callingCode: '+357' },
    { name: 'Czech Republic', code: 'CZ', flag: '🇨🇿', callingCode: '+420' },
    { name: 'Democratic Republic of the Congo', code: 'CD', flag: '🇨🇩', callingCode: '+243' },
    { name: 'Denmark', code: 'DK', flag: '🇩🇰', callingCode: '+45' },
    { name: 'Djibouti', code: 'DJ', flag: '🇩🇯', callingCode: '+253' },
    { name: 'Dominica', code: 'DM', flag: '🇩🇲', callingCode: '+1' },
    { name: 'Dominican Republic', code: 'DO', flag: '🇩🇴', callingCode: '+1' },
    { name: 'East Timor', code: 'TL', flag: '🇹🇱', callingCode: '+670' },
    { name: 'Ecuador', code: 'EC', flag: '🇪🇨', callingCode: '+593' },
    { name: 'Egypt', code: 'EG', flag: '🇪🇬', callingCode: '+20' },
    { name: 'El Salvador', code: 'SV', flag: '🇸🇻', callingCode: '+503' },
    { name: 'Equatorial Guinea', code: 'GQ', flag: '🇬🇶', callingCode: '+240' },
    { name: 'Eritrea', code: 'ER', flag: '🇪🇷', callingCode: '+291' },
    { name: 'Estonia', code: 'EE', flag: '🇪🇪', callingCode: '+372' },
    { name: 'Ethiopia', code: 'ET', flag: '🇪🇹', callingCode: '+251' },
    { name: 'Falkland Islands', code: 'FK', flag: '🇫🇰', callingCode: '+500' },
    { name: 'Faroe Islands', code: 'FO', flag: '🇫🇴', callingCode: '+298' },
    { name: 'Fiji', code: 'FJ', flag: '🇫🇯', callingCode: '+679' },
    { name: 'Finland', code: 'FI', flag: '🇫🇮', callingCode: '+358' },
    { name: 'France', code: 'FR', flag: '🇫🇷', callingCode: '+33' },
    { name: 'French Polynesia', code: 'PF', flag: '🇵🇫', callingCode: '+689' },
    { name: 'Gabon', code: 'GA', flag: '🇬🇦', callingCode: '+241' },
    { name: 'Gambia', code: 'GM', flag: '🇬🇲', callingCode: '+220' },
    { name: 'Georgia', code: 'GE', flag: '🇬🇪', callingCode: '+995' },
    { name: 'Germany', code: 'DE', flag: '🇩🇪', callingCode: '+49' },
    { name: 'Ghana', code: 'GH', flag: '🇬🇭', callingCode: '+233' },
    { name: 'Gibraltar', code: 'GI', flag: '🇬🇮', callingCode: '+350' },
    { name: 'Greece', code: 'GR', flag: '🇬🇷', callingCode: '+30' },
    { name: 'Greenland', code: 'GL', flag: '🇬🇱', callingCode: '+299' },
    { name: 'Grenada', code: 'GD', flag: '🇬🇩', callingCode: '+1' },
    { name: 'Guam', code: 'GU', flag: '🇬🇺', callingCode: '+1' },
    { name: 'Guatemala', code: 'GT', flag: '🇬🇹', callingCode: '+502' },
    { name: 'Guernsey', code: 'GG', flag: '🇬🇬', callingCode: '+44' },
    { name: 'Guinea', code: 'GN', flag: '🇬🇳', callingCode: '+224' },
    { name: 'Guinea-Bissau', code: 'GW', flag: '🇬🇼', callingCode: '+245' },
    { name: 'Guyana', code: 'GY', flag: '🇬🇾', callingCode: '+592' },
    { name: 'Haiti', code: 'HT', flag: '🇭🇹', callingCode: '+509' },
    { name: 'Honduras', code: 'HN', flag: '🇭🇳', callingCode: '+504' },
    { name: 'Hong Kong', code: 'HK', flag: '🇭🇰', callingCode: '+852' },
    { name: 'Hungary', code: 'HU', flag: '🇭🇺', callingCode: '+36' },
    { name: 'Iceland', code: 'IS', flag: '🇮🇸', callingCode: '+354' },
    { name: 'India', code: 'IN', flag: '🇮🇳', callingCode: '+91' },
    { name: 'Indonesia', code: 'ID', flag: '🇮🇩', callingCode: '+62' },
    { name: 'Iran', code: 'IR', flag: '🇮🇷', callingCode: '+98' },
    { name: 'Iraq', code: 'IQ', flag: '🇮🇶', callingCode: '+964' },
    { name: 'Ireland', code: 'IE', flag: '🇮🇪', callingCode: '+353' },
    { name: 'Isle of Man', code: 'IM', flag: '🇮🇲', callingCode: '+44' },
    { name: 'Israel', code: 'IL', flag: '🇮🇱', callingCode: '+972' },
    { name: 'Italy', code: 'IT', flag: '🇮🇹', callingCode: '+39' },
    { name: 'Ivory Coast', code: 'CI', flag: '🇨🇮', callingCode: '+225' },
    { name: 'Jamaica', code: 'JM', flag: '🇯🇲', callingCode: '+1' },
    { name: 'Japan', code: 'JP', flag: '🇯🇵', callingCode: '+81' },
    { name: 'Jersey', code: 'JE', flag: '🇯🇪', callingCode: '+44' },
    { name: 'Jordan', code: 'JO', flag: '🇯🇴', callingCode: '+962' },
    { name: 'Kazakhstan', code: 'KZ', flag: '🇰🇿', callingCode: '+7' },
    { name: 'Kenya', code: 'KE', flag: '🇰🇪', callingCode: '+254' },
    { name: 'Kiribati', code: 'KI', flag: '🇰🇮', callingCode: '+686' },
    { name: 'Kosovo', code: 'XK', flag: '🇽🇰', callingCode: '+383' },
    { name: 'Kuwait', code: 'KW', flag: '🇰🇼', callingCode: '+965' },
    { name: 'Kyrgyzstan', code: 'KG', flag: '🇰🇬', callingCode: '+996' },
    { name: 'Laos', code: 'LA', flag: '🇱🇦', callingCode: '+856' },
    { name: 'Latvia', code: 'LV', flag: '🇱🇻', callingCode: '+371' },
    { name: 'Lebanon', code: 'LB', flag: '🇱🇧', callingCode: '+961' },
    { name: 'Lesotho', code: 'LS', flag: '🇱🇸', callingCode: '+266' },
    { name: 'Liberia', code: 'LR', flag: '🇱🇷', callingCode: '+231' },
    { name: 'Libya', code: 'LY', flag: '🇱🇾', callingCode: '+218' },
    { name: 'Liechtenstein', code: 'LI', flag: '🇱🇮', callingCode: '+423' },
    { name: 'Lithuania', code: 'LT', flag: '🇱🇹', callingCode: '+370' },
    { name: 'Luxembourg', code: 'LU', flag: '🇱🇺', callingCode: '+352' },
    { name: 'Macau', code: 'MO', flag: '🇲🇴', callingCode: '+853' },
    { name: 'Macedonia', code: 'MK', flag: '🇲🇰', callingCode: '+389' },
    { name: 'Madagascar', code: 'MG', flag: '🇲🇬', callingCode: '+261' },
    { name: 'Malawi', code: 'MW', flag: '🇲🇼', callingCode: '+265' },
    { name: 'Malaysia', code: 'MY', flag: '🇲🇾', callingCode: '+60' },
    { name: 'Maldives', code: 'MV', flag: '🇲🇻', callingCode: '+960' },
    { name: 'Mali', code: 'ML', flag: '🇲🇱', callingCode: '+223' },
    { name: 'Malta', code: 'MT', flag: '🇲🇹', callingCode: '+356' },
    { name: 'Marshall Islands', code: 'MH', flag: '🇲🇭', callingCode: '+692' },
    { name: 'Mauritania', code: 'MR', flag: '🇲🇷', callingCode: '+222' },
    { name: 'Mauritius', code: 'MU', flag: '🇲🇺', callingCode: '+230' },
    { name: 'Mayotte', code: 'YT', flag: '🇾🇹', callingCode: '+262' },
    { name: 'Mexico', code: 'MX', flag: '🇲🇽', callingCode: '+52' },
    { name: 'Micronesia', code: 'FM', flag: '🇫🇲', callingCode: '+691' },
    { name: 'Moldova', code: 'MD', flag: '🇲🇩', callingCode: '+373' },
    { name: 'Monaco', code: 'MC', flag: '🇲🇨', callingCode: '+377' },
    { name: 'Mongolia', code: 'MN', flag: '🇲🇳', callingCode: '+976' },
    { name: 'Montenegro', code: 'ME', flag: '🇲🇪', callingCode: '+382' },
    { name: 'Montserrat', code: 'MS', flag: '🇲🇸', callingCode: '+1' },
    { name: 'Morocco', code: 'MA', flag: '🇲🇦', callingCode: '+212' },
    { name: 'Mozambique', code: 'MZ', flag: '🇲🇿', callingCode: '+258' },
    { name: 'Myanmar', code: 'MM', flag: '🇲🇲', callingCode: '+95' },
    { name: 'Namibia', code: 'NA', flag: '🇳🇦', callingCode: '+264' },
    { name: 'Nauru', code: 'NR', flag: '🇳🇷', callingCode: '+674' },
    { name: 'Nepal', code: 'NP', flag: '🇳🇵', callingCode: '+977' },
    { name: 'Netherlands', code: 'NL', flag: '🇳🇱', callingCode: '+31' },
    { name: 'New Caledonia', code: 'NC', flag: '🇳🇨', callingCode: '+687' },
    { name: 'New Zealand', code: 'NZ', flag: '🇳🇿', callingCode: '+64' },
    { name: 'Nicaragua', code: 'NI', flag: '🇳🇮', callingCode: '+505' },
    { name: 'Niger', code: 'NE', flag: '🇳🇪', callingCode: '+227' },
    { name: 'Nigeria', code: 'NG', flag: '🇳🇬', callingCode: '+234' },
    { name: 'Niue', code: 'NU', flag: '🇳🇺', callingCode: '+683' },
    { name: 'Norfolk Island', code: 'NF', flag: '🇳🇫', callingCode: '+672' },
    { name: 'North Korea', code: 'KP', flag: '🇰🇵', callingCode: '+850' },
    { name: 'Northern Mariana Islands', code: 'MP', flag: '🇲🇵', callingCode: '+1' },
    { name: 'Norway', code: 'NO', flag: '🇳🇴', callingCode: '+47' },
    { name: 'Oman', code: 'OM', flag: '🇴🇲', callingCode: '+968' },
    { name: 'Pakistan', code: 'PK', flag: '🇵🇰', callingCode: '+92' },
    { name: 'Palau', code: 'PW', flag: '🇵🇼', callingCode: '+680' },
    { name: 'Palestine', code: 'PS', flag: '🇵🇸', callingCode: '+970' },
    { name: 'Panama', code: 'PA', flag: '🇵🇦', callingCode: '+507' },
    { name: 'Papua New Guinea', code: 'PG', flag: '🇵🇬', callingCode: '+675' },
    { name: 'Paraguay', code: 'PY', flag: '🇵🇾', callingCode: '+595' },
    { name: 'Peru', code: 'PE', flag: '🇵🇪', callingCode: '+51' },
    { name: 'Philippines', code: 'PH', flag: '🇵🇭', callingCode: '+63' },
    { name: 'Pitcairn Islands', code: 'PN', flag: '🇵🇳', callingCode: '+64' },
    { name: 'Poland', code: 'PL', flag: '🇵🇱', callingCode: '+48' },
    { name: 'Portugal', code: 'PT', flag: '🇵🇹', callingCode: '+351' },
    { name: 'Puerto Rico', code: 'PR', flag: '🇵🇷', callingCode: '+1' },
    { name: 'Qatar', code: 'QA', flag: '🇶🇦', callingCode: '+974' },
    { name: 'Republic of the Congo', code: 'CG', flag: '🇨🇬', callingCode: '+242' },
    { name: 'Reunion', code: 'RE', flag: '🇷🇪', callingCode: '+262' },
    { name: 'Romania', code: 'RO', flag: '🇷🇴', callingCode: '+40' },
    { name: 'Russia', code: 'RU', flag: '🇷🇺', callingCode: '+7' },
    { name: 'Rwanda', code: 'RW', flag: '🇷🇼', callingCode: '+250' },
    { name: 'Saint Helena', code: 'SH', flag: '🇸🇭', callingCode: '+290' },
    { name: 'Saint Kitts and Nevis', code: 'KN', flag: '🇰🇳', callingCode: '+1' },
    { name: 'Saint Lucia', code: 'LC', flag: '🇱🇨', callingCode: '+1' },
    { name: 'Saint Pierre and Miquelon', code: 'PM', flag: '🇵🇲', callingCode: '+508' },
    { name: 'Saint Vincent and the Grenadines', code: 'VC', flag: '🇻🇨', callingCode: '+1' },
    { name: 'Samoa', code: 'WS', flag: '🇼🇸', callingCode: '+685' },
    { name: 'San Marino', code: 'SM', flag: '🇸🇲', callingCode: '+378' },
    { name: 'Sao Tome and Principe', code: 'ST', flag: '🇸🇹', callingCode: '+239' },
    { name: 'Saudi Arabia', code: 'SA', flag: '🇸🇦', callingCode: '+966' },
    { name: 'Senegal', code: 'SN', flag: '🇸🇳', callingCode: '+221' },
    { name: 'Serbia', code: 'RS', flag: '🇷🇸', callingCode: '+381' },
    { name: 'Seychelles', code: 'SC', flag: '🇸🇨', callingCode: '+248' },
    { name: 'Sierra Leone', code: 'SL', flag: '🇸🇱', callingCode: '+232' },
    { name: 'Singapore', code: 'SG', flag: '🇸🇬', callingCode: '+65' },
    { name: 'Sint Maarten', code: 'SX', flag: '🇸🇽', callingCode: '+1' },
    { name: 'Slovakia', code: 'SK', flag: '🇸🇰', callingCode: '+421' },
    { name: 'Slovenia', code: 'SI', flag: '🇸🇮', callingCode: '+386' },
    { name: 'Solomon Islands', code: 'SB', flag: '🇸🇧', callingCode: '+677' },
    { name: 'Somalia', code: 'SO', flag: '🇸🇴', callingCode: '+252' },
    { name: 'South Africa', code: 'ZA', flag: '🇿🇦', callingCode: '+27' },
    { name: 'South Korea', code: 'KR', flag: '🇰🇷', callingCode: '+82' },
    { name: 'South Sudan', code: 'SS', flag: '🇸🇸', callingCode: '+211' },
    { name: 'Spain', code: 'ES', flag: '🇪🇸', callingCode: '+34' },
    { name: 'Sri Lanka', code: 'LK', flag: '🇱🇰', callingCode: '+94' },
    { name: 'Sudan', code: 'SD', flag: '🇸🇩', callingCode: '+249' },
    { name: 'Suriname', code: 'SR', flag: '🇸🇷', callingCode: '+597' },
    { name: 'Svalbard and Jan Mayen', code: 'SJ', flag: '🇸🇯', callingCode: '+47' },
    { name: 'Swaziland', code: 'SZ', flag: '🇸🇿', callingCode: '+268' },
    { name: 'Sweden', code: 'SE', flag: '🇸🇪', callingCode: '+46' },
    { name: 'Switzerland', code: 'CH', flag: '🇨🇭', callingCode: '+41' },
    { name: 'Syria', code: 'SY', flag: '🇸🇾', callingCode: '+963' },
    { name: 'Taiwan', code: 'TW', flag: '🇹🇼', callingCode: '+886' },
    { name: 'Tajikistan', code: 'TJ', flag: '🇹🇯', callingCode: '+992' },
    { name: 'Tanzania', code: 'TZ', flag: '🇹🇿', callingCode: '+255' },
    { name: 'Thailand', code: 'TH', flag: '🇹🇭', callingCode: '+66' },
    { name: 'Togo', code: 'TG', flag: '🇹🇬', callingCode: '+228' },
    { name: 'Tokelau', code: 'TK', flag: '🇹🇰', callingCode: '+690' },
    { name: 'Tonga', code: 'TO', flag: '🇹🇴', callingCode: '+676' },
    { name: 'Trinidad and Tobago', code: 'TT', flag: '🇹🇹', callingCode: '+1' },
    { name: 'Tunisia', code: 'TN', flag: '🇹🇳', callingCode: '+216' },
    { name: 'Turkey', code: 'TR', flag: '🇹🇷', callingCode: '+90' },
    { name: 'Turkmenistan', code: 'TM', flag: '🇹🇲', callingCode: '+993' },
    { name: 'Turks and Caicos Islands', code: 'TC', flag: '🇹🇨', callingCode: '+1' },
    { name: 'Tuvalu', code: 'TV', flag: '🇹🇻', callingCode: '+688' },
    { name: 'Uganda', code: 'UG', flag: '🇺🇬', callingCode: '+256' },
    { name: 'Ukraine', code: 'UA', flag: '🇺🇦', callingCode: '+380' },
    { name: 'United Arab Emirates', code: 'AE', flag: '🇦🇪', callingCode: '+971' },
    { name: 'United Kingdom', code: 'GB', flag: '🇬🇧', callingCode: '+44' },
    { name: 'United States', code: 'US', flag: '🇺🇸', callingCode: '+1' },
    { name: 'United States Virgin Islands', code: 'VI', flag: '🇻🇮', callingCode: '+1' },
    { name: 'Uruguay', code: 'UY', flag: '🇺🇾', callingCode: '+598' },
    { name: 'Uzbekistan', code: 'UZ', flag: '🇺🇿', callingCode: '+998' },
    { name: 'Vanuatu', code: 'VU', flag: '🇻🇺', callingCode: '+678' },
    { name: 'Vatican City', code: 'VA', flag: '🇻🇦', callingCode: '+379' },
    { name: 'Venezuela', code: 'VE', flag: '🇻🇪', callingCode: '+58' },
    { name: 'Vietnam', code: 'VN', flag: '🇻🇳', callingCode: '+84' },
    { name: 'Wallis and Futuna', code: 'WF', flag: '🇼🇫', callingCode: '+681' },
    { name: 'Western Sahara', code: 'EH', flag: '🇪🇭', callingCode: '+212' },
    { name: 'Yemen', code: 'YE', flag: '🇾🇪', callingCode: '+967' },
    { name: 'Zambia', code: 'ZM', flag: '🇿🇲', callingCode: '+260' },
    { name: 'Zimbabwe', code: 'ZW', flag: '🇿🇼', callingCode: '+263' },
].sort((a, b) => a.name.localeCompare(b.name));

// Popular countries to show first
const POPULAR_COUNTRIES = ['NG', 'US', 'GB', 'CA', 'SA', 'EG', 'PK', 'IN', 'AE'];

const sortedCountries = [
    ...WORLD_COUNTRIES.filter(c => POPULAR_COUNTRIES.includes(c.code)),
    ...WORLD_COUNTRIES.filter(c => !POPULAR_COUNTRIES.includes(c.code))
];

export default function TeacherOnboarding({ user, subjects, availableCurrencies = [], onboardingCompleted = false, teacherData = {}, verificationRequest }: TeacherOnboardingProps) {
    // Fallback currency data if not provided
    const currencies = availableCurrencies.length > 0 ? availableCurrencies : [
        { value: 'NGN', label: 'Nigerian Naira (NGN)', symbol: '₦', is_default: true },
        { value: 'USD', label: 'US Dollar (USD)', symbol: '$', is_default: false },
        { value: 'EUR', label: 'Euro (EUR)', symbol: '€', is_default: false },
        { value: 'GBP', label: 'British Pound (GBP)', symbol: '£', is_default: false }
    ];
    const [currentStep, setCurrentStep] = useState(() => {
        // Retrieve step from sessionStorage on component mount with security validation
        try {
        const savedStep = sessionStorage.getItem('teacher_onboarding_step');
            if (savedStep) {
                const step = parseInt(savedStep, 10);
                // Security: Validate step is within valid range
                if (step >= 1 && step <= 4) {
                    return step;
                }
            }
        } catch (error) {
            // Silent fail - use default step
        }
        return 1;
    });

    // Save current step to sessionStorage whenever it changes
    useEffect(() => {
        try {
            // Security: Validate step is a valid number
            if (currentStep >= 1 && currentStep <= 4) {
                sessionStorage.setItem('teacher_onboarding_step', currentStep.toString());
            }
        } catch (error) {
            // Silent fail - step tracking not critical
        }
    }, [currentStep]);
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

    // If onboarding is completed, show success screen
    useEffect(() => {
        if (onboardingCompleted) {
            setIsCompleted(true);
        }
    }, [onboardingCompleted]);

    // Check for new verification request after form submission
    useEffect(() => {
        const checkForNewVerificationRequest = () => {
            // If we have a verification request and it's not rejected, show success screen
            if (verificationRequest && verificationRequest.status !== 'rejected') {
                setIsCompleted(true);
            }
        };
        
        checkForNewVerificationRequest();
    }, [verificationRequest]);
    const [profilePhotoPreview, setProfilePhotoPreview] = useState<string | null>(null);
    const [cities, setCities] = useState<string[]>([]);
    const [loadingCities, setLoadingCities] = useState(false);
    const [selectedCountry, setSelectedCountry] = useState<Country | null>(null);
    const [countryOpen, setCountryOpen] = useState(false);
    const [cityOpen, setCityOpen] = useState(false);
    const [phoneValidationError, setPhoneValidationError] = useState<string | null>(null);
    const [exchangeRate, setExchangeRate] = useState<number>(1500); // Default USD to NGN rate

    // Security: Sanitize data before storing
    const sanitizeData = (data: TeacherFormData): TeacherFormData => {
        const sanitized = { ...data };
        
        // Remove sensitive fields that shouldn't be stored locally
        delete sanitized.profile_photo; // File objects can't be serialized anyway
        
        // Sanitize string fields to prevent XSS
        const stringFields = ['name', 'phone', 'country', 'city', 'qualification', 'bio', 'bank_name', 'custom_bank_name', 'account_number', 'account_name', 'mobile_provider', 'mobile_number', 'paypal_email'];
        
        stringFields.forEach(field => {
            if (typeof sanitized[field] === 'string') {
                // Basic XSS prevention - remove script tags and dangerous characters
                sanitized[field] = sanitized[field]
                    .replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '')
                    .replace(/javascript:/gi, '')
                    .replace(/on\w+\s*=/gi, '')
                    .trim();
            }
        });
        
        return sanitized;
    };

    // Load saved data from sessionStorage with security checks
    const getSavedData = (): Partial<TeacherFormData> => {
        try {
            const saved = sessionStorage.getItem('teacher_onboarding_data');
            if (!saved) return {};
            
            const parsed = JSON.parse(saved);
            
            // Security: Validate that parsed data is an object
            if (typeof parsed !== 'object' || parsed === null) {
                sessionStorage.removeItem('teacher_onboarding_data');
                return {};
            }
            
            // Security: Check for suspicious patterns
            const dataString = JSON.stringify(parsed);
            if (dataString.includes('<script') || dataString.includes('javascript:') || dataString.includes('onload=')) {
                sessionStorage.removeItem('teacher_onboarding_data');
                return {};
            }
            
            return parsed;
        } catch (error) {
            sessionStorage.removeItem('teacher_onboarding_data');
            return {};
        }
    };

    // Save data to sessionStorage with security measures
    const saveDataToStorage = (formData: TeacherFormData) => {
        try {
            // Security: Sanitize data before storing
            const sanitized = sanitizeData(formData);
            
            // Security: Limit data size to prevent storage abuse
            const dataString = JSON.stringify(sanitized);
            if (dataString.length > 100000) { // 100KB limit
                return;
            }
            
            sessionStorage.setItem('teacher_onboarding_data', dataString);
        } catch (error) {
            // Silent fail - data persistence not critical
        }
    };

    // Clear saved data from sessionStorage
    const clearSavedData = () => {
        try {
            sessionStorage.removeItem('teacher_onboarding_data');
            sessionStorage.removeItem('teacher_onboarding_step');
        } catch (error) {
            // Silent fail - cleanup not critical
        }
    };

    const savedData = getSavedData();
    
    const { data, setData, post, processing, errors } = useForm<TeacherFormData>({
        // Step 1 - Use teacherData first, then savedData, then defaults
        name: teacherData.name || savedData.name || user.name || '',
        phone: teacherData.phone || savedData.phone || '',
        country_code: teacherData.country_code || savedData.country_code || '',
        country: teacherData.country || savedData.country || '',
        city: teacherData.city || savedData.city || '',
        calling_code: teacherData.calling_code || savedData.calling_code || '',
        
        // Step 2
        subjects: teacherData.subjects || savedData.subjects || [],
        experience_years: teacherData.experience_years || savedData.experience_years || '',
        qualification: teacherData.qualification || savedData.qualification || '',
        bio: teacherData.bio || savedData.bio || '',
        
        // Step 3
        timezone: teacherData.timezone || savedData.timezone || '',
        teaching_mode: teacherData.teaching_mode || savedData.teaching_mode || '',
        availability: teacherData.availability || savedData.availability || {
            monday: { enabled: false, from: '', to: '' },
            tuesday: { enabled: false, from: '', to: '' },
            wednesday: { enabled: false, from: '', to: '' },
            thursday: { enabled: false, from: '', to: '' },
            friday: { enabled: false, from: '', to: '' },
            saturday: { enabled: false, from: '', to: '' },
            sunday: { enabled: false, from: '', to: '' }
        },
        
        // Step 4
        preferred_currency: teacherData.preferred_currency || savedData.preferred_currency || 'NGN',
        hourly_rate_usd: teacherData.hourly_rate_usd || savedData.hourly_rate_usd || '',
        hourly_rate_ngn: teacherData.hourly_rate_ngn || savedData.hourly_rate_ngn || '',
        payment_method: savedData.payment_method || '',
        
        // Wallet & Earnings Setup
        withdrawal_method: savedData.withdrawal_method || '',
        bank_name: savedData.bank_name || '',
        custom_bank_name: savedData.custom_bank_name || '',
        account_number: savedData.account_number || '',
        account_name: savedData.account_name || '',

        // Mobile Money
        mobile_provider: savedData.mobile_provider || '',
        mobile_number: savedData.mobile_number || '',
        
        // Digital Wallets
        paypal_email: savedData.paypal_email || ''
    });

    // Save data to sessionStorage whenever form data changes
    useEffect(() => {
        saveDataToStorage(data);
    }, [data]);

    // Fetch cities when country changes
    useEffect(() => {
        if (data.country && data.country !== '') {
            fetchCities(data.country);
        }
    }, [data.country]);

    // Fetch exchange rate on component mount
    useEffect(() => {
        const fetchExchangeRate = async () => {
            try {
                const response = await fetch('/api/exchange-rate/USD/NGN');
                if (response.ok) {
                    const data = await response.json();
                    setExchangeRate(data.rate || 1500);
                }
            } catch (error) {
                // Use default exchange rate
            }
        };

        fetchExchangeRate();
    }, []);

    // Check for verification call notifications
    useEffect(() => {
        const checkForVerificationCalls = async () => {
            try {
                const response = await fetch('/api/user/notifications', {
                    credentials: 'include',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                
                if (response.ok) {
                    const data = await response.json();
                    const verificationNotifications = data.data?.filter((notification: any) => 
                        notification.type === 'App\\Notifications\\VerificationCallScheduledNotification' && 
                        !notification.read_at
                    ) || [];
                    
                    // Show toast for unread verification calls
                    verificationNotifications.forEach((notification: any) => {
                        const verificationData = notification.data;
                        const scheduledTime = verificationData.scheduled_at_human || 'the scheduled time';
                        const platform = verificationData.platform_label || 'the video platform';
                        const meetingLink = verificationData.meeting_link;
                        
                        const enhancedMessage = `${verificationData.message}\n\n📅 Scheduled: ${scheduledTime}\n📹 Platform: ${platform}${meetingLink ? '\n🔗 Meeting link available' : ''}\n\n📧 Please check your email for detailed verification instructions.`;
                        
                        toast.success(`📞 ${verificationData.title}`, {
                            description: enhancedMessage,
                            duration: 10000,
                            action: verificationData.action_text ? {
                                label: verificationData.action_text,
                                onClick: () => {
                                    if (verificationData.action_url) {
                                        if (verificationData.action_url.startsWith('/')) {
                                            window.location.href = verificationData.action_url;
                                        } else {
                                            window.open(verificationData.action_url, '_blank');
                                        }
                                    }
                                },
                            } : {
                                label: 'Check Email',
                                onClick: () => {
                                    toast.info('📧 Check your email for verification details and meeting link!');
                                }
                            },
                        });
                    });
                }
            } catch (error) {
                // Silent fail - verification check not critical
            }
        };

        // Check for verification calls after a delay
        const timeoutId = setTimeout(checkForVerificationCalls, 3000);
        
        return () => clearTimeout(timeoutId);
    }, []);

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
            setCities([]);
        }
        setLoadingCities(false);
    };

    const validatePhoneNumber = (phone: string, countryCode: string): boolean => {
        if (!phone || !countryCode) return false;

        try {
            // Remove any non-digit characters except +
            const cleanPhone = phone.replace(/[^\d+]/g, '');

            // Check if it's a valid phone number for the country
            const isValid = isValidPhoneNumber(cleanPhone, countryCode as any);

            if (!isValid) {
                const country = sortedCountries.find(c => c.code === countryCode);
                const callingCode = country?.callingCode || getCountryCallingCode(countryCode as any);
                setPhoneValidationError(`Please enter a valid phone number for ${country?.name || countryCode}. Example: ${callingCode}1234567890`);
                return false;
            }

            setPhoneValidationError(null);
            return true;
        } catch (error) {
            setPhoneValidationError('Invalid phone number format');
            return false;
        }
    };

    // Timezone mapping for countries
    const getTimezoneForCountry = (countryCode: string): string => {
        const timezoneMap: Record<string, string> = {
            // North America
            'US': 'UTC-5', // Eastern Time (most common)
            'CA': 'UTC-5', // Eastern Time
            'MX': 'UTC-6', // Central Time

            // Europe
            'GB': 'UTC+0', // GMT
            'FR': 'UTC+1', // CET
            'DE': 'UTC+1', // CET
            'IT': 'UTC+1', // CET
            'ES': 'UTC+1', // CET
            'NL': 'UTC+1', // CET
            'BE': 'UTC+1', // CET
            'CH': 'UTC+1', // CET
            'AT': 'UTC+1', // CET
            'SE': 'UTC+1', // CET
            'NO': 'UTC+1', // CET
            'DK': 'UTC+1', // CET
            'FI': 'UTC+2', // EET
            'PL': 'UTC+1', // CET
            'CZ': 'UTC+1', // CET
            'HU': 'UTC+1', // CET
            'RO': 'UTC+2', // EET
            'BG': 'UTC+2', // EET
            'GR': 'UTC+2', // EET
            'TR': 'UTC+3', // TRT
            'RU': 'UTC+3', // MSK (Moscow)

            // Middle East
            'SA': 'UTC+3', // AST
            'AE': 'UTC+4', // GST
            'QA': 'UTC+3', // AST
            'KW': 'UTC+3', // AST
            'BH': 'UTC+3', // AST
            'OM': 'UTC+4', // GST
            'JO': 'UTC+2', // EET
            'LB': 'UTC+2', // EET
            'SY': 'UTC+2', // EET
            'IQ': 'UTC+3', // AST
            'IR': 'UTC+3:30', // IRST
            'IL': 'UTC+2', // IST
            'PS': 'UTC+2', // EET
            'EG': 'UTC+2', // EET
            'LY': 'UTC+2', // EET
            'TN': 'UTC+1', // CET
            'DZ': 'UTC+1', // CET
            'MA': 'UTC+0', // WET

            // Asia
            'PK': 'UTC+5', // PKT
            'IN': 'UTC+5:30', // IST
            'BD': 'UTC+6', // BST
            'LK': 'UTC+5:30', // SLST
            'NP': 'UTC+5:45', // NPT
            'BT': 'UTC+6', // BTT
            'MV': 'UTC+5', // MVT
            'AF': 'UTC+4:30', // AFT
            'CN': 'UTC+8', // CST
            'JP': 'UTC+9', // JST
            'KR': 'UTC+9', // KST
            'TH': 'UTC+7', // ICT
            'VN': 'UTC+7', // ICT
            'LA': 'UTC+7', // ICT
            'KH': 'UTC+7', // ICT
            'MM': 'UTC+6:30', // MMT
            'MY': 'UTC+8', // MYT
            'SG': 'UTC+8', // SGT
            'ID': 'UTC+7', // WIB (Western Indonesia)
            'PH': 'UTC+8', // PHT
            'TW': 'UTC+8', // CST
            'HK': 'UTC+8', // HKT
            'MO': 'UTC+8', // MCT
            'MN': 'UTC+8', // ULAT

            // Africa
            'NG': 'UTC+1', // WAT
            'KE': 'UTC+3', // EAT
            'TZ': 'UTC+3', // EAT
            'UG': 'UTC+3', // EAT
            'ET': 'UTC+3', // EAT
            'SO': 'UTC+3', // EAT
            'DJ': 'UTC+3', // EAT
            'ER': 'UTC+3', // EAT
            'SD': 'UTC+2', // CAT
            'SS': 'UTC+2', // CAT
            'TD': 'UTC+1', // WAT
            'CF': 'UTC+1', // WAT
            'CM': 'UTC+1', // WAT
            'GQ': 'UTC+1', // WAT
            'GA': 'UTC+1', // WAT
            'CG': 'UTC+1', // WAT
            'CD': 'UTC+1', // WAT
            'AO': 'UTC+1', // WAT
            'ZM': 'UTC+2', // CAT
            'ZW': 'UTC+2', // CAT
            'BW': 'UTC+2', // CAT
            'NA': 'UTC+2', // CAT
            'ZA': 'UTC+2', // SAST
            'LS': 'UTC+2', // SAST
            'SZ': 'UTC+2', // SAST
            'MW': 'UTC+2', // CAT
            'MZ': 'UTC+2', // CAT
            'MG': 'UTC+3', // EAT
            'MU': 'UTC+4', // MUT
            'SC': 'UTC+4', // SCT
            'KM': 'UTC+3', // EAT
            'YT': 'UTC+3', // EAT
            'RE': 'UTC+4', // RET
            'GH': 'UTC+0', // GMT
            'TG': 'UTC+0', // GMT
            'BJ': 'UTC+1', // WAT
            'BF': 'UTC+0', // GMT
            'ML': 'UTC+0', // GMT
            'NE': 'UTC+1', // WAT
            'SN': 'UTC+0', // GMT
            'GM': 'UTC+0', // GMT
            'GN': 'UTC+0', // GMT
            'SL': 'UTC+0', // GMT
            'LR': 'UTC+0', // GMT
            'CI': 'UTC+0', // GMT
            'MR': 'UTC+0', // GMT
            'CV': 'UTC-1', // CVT
            'ST': 'UTC+0', // GMT

            // Oceania
            'AU': 'UTC+10', // AEST (Eastern)
            'NZ': 'UTC+12', // NZST
            'FJ': 'UTC+12', // FJT
            'PG': 'UTC+10', // PGT
            'SB': 'UTC+11', // SBT
            'VU': 'UTC+11', // VUT
            'NC': 'UTC+11', // NCT
            'PF': 'UTC-10', // TAHT
            'WS': 'UTC+13', // WST
            'TO': 'UTC+13', // TOT
            'KI': 'UTC+12', // GILT
            'TV': 'UTC+12', // TVT
            'NR': 'UTC+12', // NRT
            'MH': 'UTC+12', // MHT
            'FM': 'UTC+11', // PONT
            'PW': 'UTC+9', // PWT
            'GU': 'UTC+10', // ChST
            'MP': 'UTC+10', // ChST
            'AS': 'UTC-11', // SST
            'CK': 'UTC-10', // CKT
            'NU': 'UTC-11', // NUT
            'TK': 'UTC+13', // TKT
            'WF': 'UTC+12', // WFT
            'PN': 'UTC-8', // PST

            // South America
            'BR': 'UTC-3', // BRT (Brasília)
            'AR': 'UTC-3', // ART
            'CL': 'UTC-3', // CLT
            'CO': 'UTC-5', // COT
            'PE': 'UTC-5', // PET
            'EC': 'UTC-5', // ECT
            'VE': 'UTC-4', // VET
            'GY': 'UTC-4', // GYT
            'SR': 'UTC-3', // SRT
            'UY': 'UTC-3', // UYT
            'PY': 'UTC-3', // PYT
            'BO': 'UTC-4', // BOT
            'FK': 'UTC-3', // FKT
            'GF': 'UTC-3', // GFT
            'GS': 'UTC-2', // GST
        };

        return timezoneMap[countryCode] || 'UTC+0'; // Default to GMT if not found
    };

    const handleCountryChange = (countryName: string) => {
        const country = sortedCountries.find(c => c.name === countryName);
        if (country) {
                setSelectedCountry(country);

            // Auto-detect timezone based on country
            const detectedTimezone = getTimezoneForCountry(country.code);

                setData({
                    ...data,
                    country: countryName,
                    country_code: country.code,
                    calling_code: country.callingCode,
                timezone: detectedTimezone, // Auto-set timezone
                    city: '', // Reset city when country changes
                phone: '', // Reset phone when country changes
            });
            setPhoneValidationError(null); // Clear any previous validation errors
        }
    };

    const handlePhoneChange = (phone: string) => {
        setData('phone', phone);

        // Validate phone number if country is selected
        if (data.country_code && phone) {
            validatePhoneNumber(phone, data.country_code);
            } else {
            setPhoneValidationError(null);
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
        const currentSchedule = data.availability[day as keyof typeof data.availability];

        // Business logic validation
        if (field === 'from' && currentSchedule.to) {
            // Ensure 'from' time is before 'to' time
            if (value >= currentSchedule.to) {
                // Auto-adjust 'to' time to be 1 hour after 'from'
                const fromTime = new Date(`2000-01-01T${value}`);
                const toTime = new Date(fromTime.getTime() + 60 * 60 * 1000); // Add 1 hour
                const adjustedToTime = toTime.toTimeString().slice(0, 5);

        setData('availability', {
            ...data.availability,
            [day]: {
                        ...currentSchedule,
                        from: value,
                        to: adjustedToTime
                    }
                });
                return;
            }
        }

        if (field === 'to' && currentSchedule.from) {
            // Ensure 'to' time is after 'from' time
            if (value <= currentSchedule.from) {
                // Auto-adjust 'from' time to be 1 hour before 'to'
                const toTime = new Date(`2000-01-01T${value}`);
                const fromTime = new Date(toTime.getTime() - 60 * 60 * 1000); // Subtract 1 hour
                const adjustedFromTime = fromTime.toTimeString().slice(0, 5);

                setData('availability', {
                    ...data.availability,
                    [day]: {
                        ...currentSchedule,
                        from: adjustedFromTime,
                        to: value
                    }
                });
                return;
            }
        }

        // Normal update
        setData('availability', {
            ...data.availability,
            [day]: {
                ...currentSchedule,
                [field]: value
            }
        });
    };

    // Smart defaults based on teaching mode
    const handleTeachingModeChange = (mode: string) => {
        setData('teaching_mode', mode);

        if (mode === 'full-time') {
            // Suggest professional full-time schedule
            setData('availability', {
                monday: { enabled: true, from: '09:00', to: '17:00' },
                tuesday: { enabled: true, from: '09:00', to: '17:00' },
                wednesday: { enabled: true, from: '09:00', to: '17:00' },
                thursday: { enabled: true, from: '09:00', to: '17:00' },
                friday: { enabled: true, from: '09:00', to: '17:00' },
                saturday: { enabled: false, from: '', to: '' },
                sunday: { enabled: false, from: '', to: '' }
            });
        } else if (mode === 'part-time') {
            // Suggest flexible part-time schedule
            setData('availability', {
                monday: { enabled: true, from: '18:00', to: '21:00' },
                tuesday: { enabled: true, from: '18:00', to: '21:00' },
                wednesday: { enabled: false, from: '', to: '' },
                thursday: { enabled: true, from: '18:00', to: '21:00' },
                friday: { enabled: false, from: '', to: '' },
                saturday: { enabled: true, from: '10:00', to: '16:00' },
                sunday: { enabled: true, from: '10:00', to: '16:00' }
            });
        }
    };

    // Smart validation with suggestions
    const validateAvailability = () => {
        const enabledDays = Object.values(data.availability).filter(day => day.enabled).length;
        const totalHours = Object.values(data.availability)
            .filter(day => day.enabled)
            .reduce((total, day) => {
                if (!day.from || !day.to) return total;
                const from = new Date(`2000-01-01T${day.from}`);
                const to = new Date(`2000-01-01T${day.to}`);
                const hours = (to.getTime() - from.getTime()) / (1000 * 60 * 60);
                return total + Math.max(0, hours); // Ensure positive hours
            }, 0);

        const suggestions = [];

        // Check for invalid time ranges
        const invalidRanges = Object.values(data.availability)
            .filter(day => day.enabled && day.from && day.to && day.from >= day.to);

        if (invalidRanges.length > 0) {
            suggestions.push('End time must be after start time');
        }

        // Check for very short sessions (less than 30 minutes)
        const shortSessions = Object.values(data.availability)
            .filter(day => {
                if (!day.enabled || !day.from || !day.to) return false;
                const from = new Date(`2000-01-01T${day.from}`);
                const to = new Date(`2000-01-01T${day.to}`);
                const hours = (to.getTime() - from.getTime()) / (1000 * 60 * 60);
                return hours < 0.5; // Less than 30 minutes
            });

        if (shortSessions.length > 0) {
            suggestions.push('Sessions should be at least 30 minutes long');
        }

        // Teaching mode suggestions
        if (data.teaching_mode === 'full-time') {
            if (enabledDays < 5) {
                suggestions.push('Full-time teachers typically work 5+ days per week');
            }
            if (totalHours < 30) {
                suggestions.push('Full-time teachers usually have 30+ hours available');
            }
        } else if (data.teaching_mode === 'part-time') {
            if (enabledDays > 4) {
                suggestions.push('Part-time teachers typically work 4 or fewer days');
            }
            if (totalHours > 25) {
                suggestions.push('Part-time teachers usually have 25 or fewer hours available');
            }
        }

        return suggestions;
    };

    // Get rate suggestions based on teaching mode
    const getRateSuggestions = () => {
        if (data.teaching_mode === 'full-time') {
            return {
                usd: { min: 25, max: 50, suggested: 35 },
                ngn: { min: 30000, max: 60000, suggested: 42000 }
            };
        } else {
            return {
                usd: { min: 15, max: 35, suggested: 25 },
                ngn: { min: 18000, max: 42000, suggested: 30000 }
            };
        }
    };

    const saveCurrentStep = async () => {
        try {

            // Validate phone number before saving step 1
            if (currentStep === 1 && data.phone && data.country_code) {
                const isValidPhone = validatePhoneNumber(data.phone, data.country_code);
                if (!isValidPhone) {
                    toast.error('Please enter a valid phone number for the selected country');
                    return false;
                }
            }
            
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
                    formData.append('preferred_currency', data.preferred_currency || 'NGN');
                    formData.append('hourly_rate_usd', data.hourly_rate_usd || '');
                    formData.append('hourly_rate_ngn', data.hourly_rate_ngn || '');
                    formData.append('payment_method', data.payment_method || '');
                    formData.append('withdrawal_method', data.withdrawal_method || '');
                    formData.append('bank_name', data.bank_name || '');
                    formData.append('custom_bank_name', data.custom_bank_name || '');
                    formData.append('account_number', data.account_number || '');
                    formData.append('account_name', data.account_name || '');

        // Mobile Money
        formData.append('mobile_provider', data.mobile_provider || '');
        formData.append('mobile_number', data.mobile_number || '');
        
        // Digital Wallets
        formData.append('paypal_email', data.paypal_email || '');
                    break;
            }

            // Get CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            
            
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
                return false;
            }

            const result = await response.json();
            
            if (result.success) {

                // Clear sessionStorage after successful completion
                if (currentStep === 4) {
                    clearSavedData();
                    sessionStorage.removeItem('teacher_onboarding_step');
                    sessionStorage.setItem('teacher_onboarding_completed', 'true');
                }
                
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
                toast.error(`❌ Failed to save step ${currentStep}: ${result.message}`);
                return false;
            }
        } catch (error) {
            toast.error(`❌ Network error while saving step ${currentStep}. Please try again.`);
            return false;
        }
    };

    const nextStep = async () => {
        
        // Re-enable step saving
        const saved = await saveCurrentStep();
        
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
        
        // Re-enable step saving for final step
        const saved = await saveCurrentStep();
        
        if (saved) {
            // Mark as completed and show success screen
            setIsCompleted(true);
            // Save completion status to sessionStorage
            sessionStorage.setItem('teacher_onboarding_completed', 'true');
            // Clear the step tracking since onboarding is complete
            sessionStorage.removeItem('teacher_onboarding_step');
            
            // Show final success toast
            toast.success('🎉 Teacher onboarding completed successfully! Welcome to IqraQuest!', {
                duration: 5000,
            });
        } else {
            toast.error('❌ Failed to complete onboarding. Please try again.');
        }
    };

    const renderStepIndicator = () => (
        <div className="flex items-center justify-center mb-6 sm:mb-8 px-4">
            <div className="flex items-center space-x-2 sm:space-x-4">
            {[1, 2, 3, 4].map((step) => (
                <div key={step} className="flex items-center">
                        <div className={`w-8 h-8 sm:w-10 sm:h-10 rounded-full flex items-center justify-center text-white font-medium text-sm sm:text-base ${step < currentStep ? 'bg-teal-600' :
                        step === currentStep ? 'bg-teal-600' : 'bg-gray-300'
                    }`}>
                            {step < currentStep ? <Check size={16} className="sm:w-5 sm:h-5" /> : step}
                    </div>
                    {step < 4 && (
                            <div className={`w-12 sm:w-20 h-1 mx-1 sm:mx-2 ${step < currentStep ? 'bg-teal-600' : 'bg-gray-300'
                        }`} />
                    )}
                </div>
            ))}
            </div>
        </div>
    );

    const renderStep2 = () => (
        <div className="space-y-4 sm:space-y-6">
            <div className="text-center mb-4 sm:mb-6">
                <h2 className="text-xl sm:text-2xl font-bold mb-2">Teaching Details</h2>
                <p className="text-gray-600 text-sm sm:text-base">Your Teaching Expertise</p>
            </div>

            <div>
                <Label className="text-sm sm:text-base">Subjects you teach</Label>
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 sm:gap-3 mt-2">
                    {subjects.map((subject) => (
                        <div key={subject.id} className="flex items-center space-x-2">
                            <Checkbox
                                id={subject.name}
                                checked={data.subjects.includes(subject.name)}
                                onCheckedChange={() => handleSubjectToggle(subject.name)}
                            />
                            <Label htmlFor={subject.name} className="text-xs sm:text-sm cursor-pointer">
                                {subject.name}
                            </Label>
                        </div>
                    ))}
                </div>

                {/* Custom Subject Input */}
                <div className="mt-4">
                    <Label htmlFor="custom_subject" className="text-sm sm:text-base">Add Custom Subject</Label>
                    <div className="flex gap-2 mt-1">
                        <Input
                            id="custom_subject"
                            placeholder="e.g., Advanced Tajweed, Quran Translation"
                            className="flex-1"
                            onKeyDown={(e) => {
                                if (e.key === 'Enter') {
                                    e.preventDefault();
                                    const input = e.target as HTMLInputElement;
                                    const customSubject = input.value.trim();
                                    if (customSubject && !data.subjects.includes(customSubject)) {
                                        handleSubjectToggle(customSubject);
                                        input.value = '';
                                    }
                                }
                            }}
                        />
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() => {
                                const input = document.getElementById('custom_subject') as HTMLInputElement;
                                const customSubject = input.value.trim();
                                if (customSubject && !data.subjects.includes(customSubject)) {
                                    handleSubjectToggle(customSubject);
                                    input.value = '';
                                }
                            }}
                        >
                            Add
                        </Button>
                    </div>
                    <p className="text-xs text-gray-500 mt-1">
                        Type a subject name and press Enter or click Add to include it
                    </p>
                </div>

                {/* Selected Subjects Display */}
                {data.subjects.length > 0 && (
                    <div className="mt-4">
                        <Label className="text-sm sm:text-base">Selected Subjects</Label>
                        <div className="flex flex-wrap gap-2 mt-2">
                            {data.subjects.map((subject) => (
                                <div
                                    key={subject}
                                    className="flex items-center space-x-1 bg-blue-100 text-blue-800 px-2 py-1 rounded-md text-xs sm:text-sm"
                                >
                                    <span>{subject}</span>
                                    <button
                                        type="button"
                                        onClick={() => handleSubjectToggle(subject)}
                                        className="text-blue-600 hover:text-blue-800 ml-1"
                                    >
                                        ×
                                    </button>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {errors.subjects && <p className="text-red-500 text-sm mt-1">{errors.subjects}</p>}
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                <div>
                    <Label htmlFor="experience_years" className="text-sm sm:text-base">Years of Experience</Label>
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
                    <Label htmlFor="qualification" className="text-sm sm:text-base">Qualification</Label>
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
                <Label htmlFor="bio" className="text-sm sm:text-base">Introduce Yourself</Label>
                <p className="text-xs sm:text-sm text-gray-600 mb-2">
                    Share your teaching experience and passion for education and briefly mention your interests and hobbies
                </p>
                <Textarea
                    id="bio"
                    value={data.bio}
                    onChange={(e) => setData('bio', e.target.value)}
                    placeholder="Write your bio here..."
                    rows={4}
                    className="mt-1"
                />
                {errors.bio && <p className="text-red-500 text-sm mt-1">{errors.bio}</p>}
            </div>
        </div>
    );

    const renderStep3 = () => (
        <div className="space-y-4 sm:space-y-6">
            <div className="text-center mb-4 sm:mb-6">
                <h2 className="text-xl sm:text-2xl font-bold mb-2">Availability & Schedule</h2>
                <p className="text-gray-600 text-sm sm:text-base">Your Teaching Schedule</p>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                <div>
                    <Label htmlFor="timezone" className="text-sm sm:text-base">Time Zone</Label>
                    <p className="text-xs sm:text-sm text-gray-600 mb-2">
                        Auto-detected based on your country
                    </p>

                    {/* Show detected timezone prominently */}
                    {selectedCountry && data.timezone && (
                        <div className="bg-green-50 border border-green-200 rounded-md p-3 mb-3">
                            <div className="flex items-center space-x-2">
                                <div className="w-2 h-2 bg-green-500 rounded-full"></div>
                                <p className="text-sm text-green-800 font-medium">
                                    Detected: {data.timezone} for {selectedCountry.name}
                                </p>
                            </div>
                        </div>
                    )}

                    <Select value={data.timezone} onValueChange={(value) => setData('timezone', value)}>
                        <SelectTrigger id="timezone" className="mt-1">
                            <SelectValue placeholder={selectedCountry ? "Timezone auto-detected" : "Select country first"} />
                        </SelectTrigger>
                        <SelectContent>
                            {/* Show detected timezone first if available */}
                            {selectedCountry && data.timezone && (
                                <SelectItem value={data.timezone} key={`detected-${data.timezone}`}>
                                    {data.timezone} (Detected for {selectedCountry.name})
                                </SelectItem>
                            )}

                            {/* All timezone options - exclude the detected one to avoid duplicates */}
                            {(!selectedCountry || !data.timezone || data.timezone !== "UTC-12") && (
                                <SelectItem value="UTC-12" key="UTC-12">UTC-12 (Baker Island)</SelectItem>
                            )}
                            {(!selectedCountry || !data.timezone || data.timezone !== "UTC-11") && (
                                <SelectItem value="UTC-11" key="UTC-11">UTC-11 (American Samoa)</SelectItem>
                            )}
                            {(!selectedCountry || !data.timezone || data.timezone !== "UTC-10") && (
                                <SelectItem value="UTC-10" key="UTC-10">UTC-10 (Hawaii)</SelectItem>
                            )}
                            {(!selectedCountry || !data.timezone || data.timezone !== "UTC-9") && (
                                <SelectItem value="UTC-9" key="UTC-9">UTC-9 (Alaska)</SelectItem>
                            )}
                            {(!selectedCountry || !data.timezone || data.timezone !== "UTC-8") && (
                                <SelectItem value="UTC-8" key="UTC-8">UTC-8 (Pacific Time)</SelectItem>
                            )}
                            {(!selectedCountry || !data.timezone || data.timezone !== "UTC-7") && (
                                <SelectItem value="UTC-7" key="UTC-7">UTC-7 (Mountain Time)</SelectItem>
                            )}
                            {(!selectedCountry || !data.timezone || data.timezone !== "UTC-6") && (
                                <SelectItem value="UTC-6" key="UTC-6">UTC-6 (Central Time)</SelectItem>
                            )}
                            {(!selectedCountry || !data.timezone || data.timezone !== "UTC-5") && (
                                <SelectItem value="UTC-5" key="UTC-5">UTC-5 (Eastern Time)</SelectItem>
                            )}
                            {(!selectedCountry || !data.timezone || data.timezone !== "UTC-4") && (
                                <SelectItem value="UTC-4" key="UTC-4">UTC-4 (Atlantic Time)</SelectItem>
                            )}
                            {(!selectedCountry || !data.timezone || data.timezone !== "UTC-3") && (
                                <SelectItem value="UTC-3" key="UTC-3">UTC-3 (Argentina)</SelectItem>
                            )}
                            {(!selectedCountry || !data.timezone || data.timezone !== "UTC-2") && (
                                <SelectItem value="UTC-2" key="UTC-2">UTC-2 (Mid-Atlantic)</SelectItem>
                            )}
                            {(!selectedCountry || !data.timezone || data.timezone !== "UTC-1") && (
                                <SelectItem value="UTC-1" key="UTC-1">UTC-1 (Azores)</SelectItem>
                            )}
                            {(!selectedCountry || !data.timezone || data.timezone !== "UTC+0") && (
                                <SelectItem value="UTC+0" key="UTC+0">UTC+0 (Greenwich Mean Time)</SelectItem>
                            )}
                            {(!selectedCountry || !data.timezone || data.timezone !== "UTC+1") && (
                                <SelectItem value="UTC+1" key="UTC+1">UTC+1 (Central European Time)</SelectItem>
                            )}
                            {(!selectedCountry || !data.timezone || data.timezone !== "UTC+2") && (
                                <SelectItem value="UTC+2" key="UTC+2">UTC+2 (Eastern European Time)</SelectItem>
                            )}
                            {(!selectedCountry || !data.timezone || data.timezone !== "UTC+3") && (
                                <SelectItem value="UTC+3" key="UTC+3">UTC+3 (Moscow Time)</SelectItem>
                            )}
                            {(!selectedCountry || !data.timezone || data.timezone !== "UTC+4") && (
                                <SelectItem value="UTC+4" key="UTC+4">UTC+4 (Gulf Standard Time)</SelectItem>
                            )}
                            {(!selectedCountry || !data.timezone || data.timezone !== "UTC+5") && (
                                <SelectItem value="UTC+5" key="UTC+5">UTC+5 (Pakistan Standard Time)</SelectItem>
                            )}
                            {(!selectedCountry || !data.timezone || data.timezone !== "UTC+5:30") && (
                                <SelectItem value="UTC+5:30" key="UTC+5:30">UTC+5:30 (India Standard Time)</SelectItem>
                            )}
                            {(!selectedCountry || !data.timezone || data.timezone !== "UTC+6") && (
                                <SelectItem value="UTC+6" key="UTC+6">UTC+6 (Bangladesh Standard Time)</SelectItem>
                            )}
                            {(!selectedCountry || !data.timezone || data.timezone !== "UTC+7") && (
                                <SelectItem value="UTC+7" key="UTC+7">UTC+7 (Indochina Time)</SelectItem>
                            )}
                            {(!selectedCountry || !data.timezone || data.timezone !== "UTC+8") && (
                                <SelectItem value="UTC+8" key="UTC+8">UTC+8 (China Standard Time)</SelectItem>
                            )}
                            {(!selectedCountry || !data.timezone || data.timezone !== "UTC+9") && (
                                <SelectItem value="UTC+9" key="UTC+9">UTC+9 (Japan Standard Time)</SelectItem>
                            )}
                            {(!selectedCountry || !data.timezone || data.timezone !== "UTC+10") && (
                                <SelectItem value="UTC+10" key="UTC+10">UTC+10 (Australian Eastern Time)</SelectItem>
                            )}
                            {(!selectedCountry || !data.timezone || data.timezone !== "UTC+11") && (
                                <SelectItem value="UTC+11" key="UTC+11">UTC+11 (Solomon Islands)</SelectItem>
                            )}
                            {(!selectedCountry || !data.timezone || data.timezone !== "UTC+12") && (
                                <SelectItem value="UTC+12" key="UTC+12">UTC+12 (New Zealand)</SelectItem>
                            )}
                        </SelectContent>
                    </Select>


                    {errors.timezone && <p className="text-red-500 text-sm mt-1">{errors.timezone}</p>}
                </div>

                <div>
                    <Label className="text-sm sm:text-base">Teaching Mode</Label>
                    <p className="text-xs sm:text-sm text-gray-600 mb-2">
                        Max 8 hours/day for full-time, 3 hours/day for part-time
                    </p>
                    <div className="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-4 mt-2">
                        <div className="flex items-center space-x-2">
                            <Checkbox
                                id="full-time"
                                checked={data.teaching_mode === 'full-time'}
                                onCheckedChange={(checked) => checked && handleTeachingModeChange('full-time')}
                            />
                            <Label htmlFor="full-time" className="cursor-pointer text-sm sm:text-base">Full-Time</Label>
                        </div>
                        <div className="flex items-center space-x-2">
                            <Checkbox
                                id="part-time"
                                checked={data.teaching_mode === 'part-time'}
                                onCheckedChange={(checked) => checked && handleTeachingModeChange('part-time')}
                            />
                            <Label htmlFor="part-time" className="cursor-pointer text-sm sm:text-base">Part-Time</Label>
                        </div>
                    </div>
                    {errors.teaching_mode && <p className="text-red-500 text-sm mt-1">{errors.teaching_mode}</p>}
                </div>
            </div>

            {/* Progressive Disclosure - Show availability only after timezone and teaching mode are selected */}
            {data.timezone && data.teaching_mode && (
            <div>
                    <Label className="text-sm sm:text-base">Select Your Availability</Label>
                    <p className="text-xs sm:text-sm text-gray-600 mb-2">
                        Set your weekly teaching schedule
                    </p>


                    {/* Smart Validation Suggestions */}
                    {(() => {
                        const suggestions = validateAvailability();
                        return suggestions.length > 0 && (
                            <div className="bg-blue-50 border border-blue-200 rounded-md p-2 mb-3">
                                <p className="text-xs text-blue-700">
                                    💡 {suggestions[0]}
                                </p>
                            </div>
                        );
                    })()}

                    <div className="space-y-3 sm:space-y-4">
                    {Object.entries(data.availability).map(([day, schedule]) => (
                            <div key={day} className="flex flex-col sm:flex-row sm:items-center space-y-2 sm:space-y-0 sm:space-x-4">
                                <div className="flex items-center space-x-2 w-full sm:w-24">
                                <Checkbox
                                    id={day}
                                    checked={schedule.enabled}
                                    onCheckedChange={(checked) => 
                                        handleAvailabilityChange(day, 'enabled', checked)
                                    }
                                />
                                    <Label htmlFor={day} className="capitalize cursor-pointer text-sm sm:text-base">
                                    {day}
                                </Label>
                            </div>
                            
                            {schedule.enabled && (
                                    <div className="flex flex-col sm:flex-row sm:items-center space-y-2 sm:space-y-0 sm:space-x-2">
                                    <div>
                                            <Label htmlFor={`${day}-from`} className="text-xs sm:text-sm">Start Time</Label>
                                            <Input
                                                type="time"
                                            value={schedule.from} 
                                                onChange={(e) => handleAvailabilityChange(day, 'from', e.target.value)}
                                                className="w-full sm:w-32"
                                                step="900" // 15-minute intervals
                                                min="06:00"
                                                max="23:00"
                                            />
                                    </div>
                                    
                                    <div>
                                            <Label htmlFor={`${day}-to`} className="text-xs sm:text-sm">End Time</Label>
                                            <Input
                                                type="time"
                                            value={schedule.to} 
                                                onChange={(e) => handleAvailabilityChange(day, 'to', e.target.value)}
                                                className="w-full sm:w-32"
                                                step="900" // 15-minute intervals
                                                min={schedule.from || "06:00"}
                                                max="23:59"
                                            />
                                    </div>
                                </div>
                            )}
                        </div>
                    ))}
                </div>
            </div>
            )}

            {/* Show message when timezone or teaching mode is not selected */}
            {(!data.timezone || !data.teaching_mode) && (
                <div className="bg-gray-50 border border-gray-200 rounded-md p-3">
                    <p className="text-xs text-gray-600 text-center">
                        Select timezone and teaching mode to continue
                    </p>
                </div>
            )}
        </div>
    );

    const renderStep4 = () => (
        <div className="space-y-4 sm:space-y-6">
            <div className="text-center mb-4 sm:mb-6">
                <h2 className="text-xl sm:text-2xl font-bold mb-2">Payment & Earnings</h2>
                <p className="text-gray-600 text-sm sm:text-base">Set Your Rate & Payment Method</p>
            </div>

            {/* Smart Rate Suggestions */}
            {data.teaching_mode && (
                <div className="bg-green-50 border border-green-200 rounded-md p-2 mb-3">
                    <p className="text-xs text-green-700">
                        💰 Suggested: ${getRateSuggestions().usd.suggested}/hour (USD) or ₦{getRateSuggestions().ngn.suggested.toLocaleString()}/hour (NGN)
                    </p>
                </div>
            )}

            {/* Earning Configuration */}
            <div className="space-y-4 sm:space-y-6">
                <div>
                    <Label className="text-sm sm:text-base">Preferred Currency</Label>
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 mt-2">
                        {currencies.map((currency) => (
                            <div key={currency.value} className="flex items-center space-x-2">
                                <Checkbox
                                    id={currency.value}
                                    checked={data.preferred_currency === currency.value}
                                    onCheckedChange={(checked) => checked && setData('preferred_currency', currency.value)}
                                />
                                <Label htmlFor={currency.value} className="cursor-pointer flex items-center space-x-2 text-xs sm:text-sm">
                                    <span>{currency.symbol}</span>
                                    <span className="truncate">{currency.label}</span>
                                    {currency.is_default && (
                                        <Badge variant="secondary" className="text-xs">Default</Badge>
                                    )}
                                </Label>
                            </div>
                        ))}
                    </div>
                    {errors.preferred_currency && <p className="text-red-500 text-sm mt-1">{errors.preferred_currency}</p>}
                </div>

                <div className="bg-blue-50 p-3 sm:p-4 rounded-lg mb-4 sm:mb-6">
                    <p className="text-xs sm:text-sm text-blue-800">
                        <strong>💡 Tip:</strong> You can set rates in both currencies to attract international students. 
                        At least one rate is required. Consider setting competitive rates based on your experience and subject expertise.
                    </p>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                    <div>
                        <Label htmlFor="hourly_rate_ngn" className="text-sm sm:text-base">Hourly Rate (NGN) - Primary</Label>
                        <Input
                            id="hourly_rate_ngn"
                            type="number"
                            value={data.hourly_rate_ngn}
                            onChange={(e) => {
                                const ngnValue = e.target.value;
                                setData('hourly_rate_ngn', ngnValue);
                                // Auto-convert to USD
                                if (ngnValue && !isNaN(Number(ngnValue))) {
                                    const usdValue = (Number(ngnValue) / exchangeRate).toFixed(2);
                                    setData('hourly_rate_usd', usdValue);
                                }
                            }}
                            placeholder={data.teaching_mode ? `e.g., ${getRateSuggestions().ngn.suggested.toLocaleString()}` : "e.g., 30000"}
                            min="0"
                            max="1000000"
                            className="mt-1"
                        />
                        <p className="text-xs sm:text-sm text-gray-500 mt-1">
                            NGN per hour (max: ₦1,000,000) • Auto-converts to USD: ${data.hourly_rate_usd || '0.00'}
                        </p>
                        {errors.hourly_rate_ngn && <p className="text-red-500 text-sm mt-1">{errors.hourly_rate_ngn}</p>}
                    </div>
                    
                    <div>
                        <Label htmlFor="hourly_rate_usd" className="text-sm sm:text-base">Hourly Rate (USD) - Auto-calculated</Label>
                        <Input
                            id="hourly_rate_usd"
                            type="number"
                            value={data.hourly_rate_usd}
                            onChange={(e) => {
                                const usdValue = e.target.value;
                                setData('hourly_rate_usd', usdValue);
                                // Auto-convert to NGN
                                if (usdValue && !isNaN(Number(usdValue))) {
                                    const ngnValue = Math.round(Number(usdValue) * exchangeRate);
                                    setData('hourly_rate_ngn', ngnValue.toString());
                                }
                            }}
                            placeholder={data.teaching_mode ? `e.g., ${getRateSuggestions().usd.suggested}` : "e.g., 25"}
                            min="0"
                            max="1000"
                            className="mt-1"
                        />
                        <p className="text-xs sm:text-sm text-gray-500 mt-1">
                            USD per hour (max: $1,000) • Auto-converts to NGN: ₦{data.hourly_rate_ngn || '0'}
                        </p>
                        {errors.hourly_rate_usd && <p className="text-red-500 text-sm mt-1">{errors.hourly_rate_usd}</p>}
                    </div>

                    <div className="lg:col-span-2">
                        <Label htmlFor="withdrawal_method" className="text-sm sm:text-base">Withdrawal Method</Label>
                        <Select value={data.withdrawal_method} onValueChange={(value) => setData('withdrawal_method', value)}>
                            <SelectTrigger id="withdrawal_method" className="mt-1">
                                <SelectValue placeholder="Select withdrawal method" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="bank_transfer">Bank Transfer</SelectItem>
                                <SelectItem value="mobile_money">Mobile Money</SelectItem>
                                <SelectItem value="paypal">PayPal</SelectItem>
                            </SelectContent>
                        </Select>
                        {errors.withdrawal_method && <p className="text-red-500 text-sm mt-1">{errors.withdrawal_method}</p>}
                    </div>
                </div>

                {/* Bank Transfer Details */}
                {data.withdrawal_method === 'bank_transfer' && (
                    <div className="space-y-4 p-3 sm:p-4 bg-gray-50 rounded-lg">
                        <h4 className="font-medium text-sm sm:text-base">Bank Account Details</h4>
                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
                            <div>
                                <Label htmlFor="bank_name" className="text-sm sm:text-base">Bank Name</Label>
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
                                        <Label htmlFor="custom_bank_name" className="text-sm sm:text-base">Bank Name</Label>
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
                                <Label htmlFor="account_name" className="text-sm sm:text-base">Account Name</Label>
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
                            <Label htmlFor="account_number" className="text-sm sm:text-base">Account Number</Label>
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

                {/* Mobile Money Details */}
                {data.withdrawal_method === 'mobile_money' && (
                    <div className="space-y-4 p-3 sm:p-4 bg-gray-50 rounded-lg">
                        <h4 className="font-medium text-sm sm:text-base">Mobile Money Details</h4>
                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
                            <div>
                                <Label htmlFor="mobile_provider" className="text-sm sm:text-base">Mobile Provider</Label>
                                <Select value={data.mobile_provider} onValueChange={(value) => setData('mobile_provider', value)}>
                                    <SelectTrigger id="mobile_provider" className="mt-1">
                                        <SelectValue placeholder="Select provider" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="mtn">MTN</SelectItem>
                                        <SelectItem value="airtel">Airtel</SelectItem>
                                        <SelectItem value="9mobile">9mobile</SelectItem>
                                        <SelectItem value="glo">Glo</SelectItem>
                                    </SelectContent>
                                </Select>
                                {errors.mobile_provider && <p className="text-red-500 text-sm mt-1">{errors.mobile_provider}</p>}
                            </div>

                            <div>
                                <Label htmlFor="mobile_number" className="text-sm sm:text-base">Mobile Number</Label>
                                <Input
                                    id="mobile_number"
                                    type="text"
                                    value={data.mobile_number || ''}
                                    onChange={(e) => setData('mobile_number', e.target.value)}
                                    className="mt-1"
                                    placeholder="Enter mobile number"
                                />
                                {errors.mobile_number && <p className="text-red-500 text-sm mt-1">{errors.mobile_number}</p>}
                            </div>
                        </div>
                    </div>
                )}

                {/* PayPal Details */}
                {data.withdrawal_method === 'paypal' && (
                    <div className="space-y-4 p-3 sm:p-4 bg-gray-50 rounded-lg">
                        <h4 className="font-medium text-sm sm:text-base">PayPal Details</h4>
                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
                            <div>
                                <Label htmlFor="paypal_email" className="text-sm sm:text-base">PayPal Email</Label>
                                <Input
                                    id="paypal_email"
                                    type="email"
                                    value={data.paypal_email || ''}
                                    onChange={(e) => setData('paypal_email', e.target.value)}
                                    className="mt-1"
                                    placeholder="Enter PayPal email"
                                />
                                {errors.paypal_email && <p className="text-red-500 text-sm mt-1">{errors.paypal_email}</p>}
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );

    const renderStep1 = () => (
        <div className="space-y-4 sm:space-y-6">
            <div className="text-center mb-4 sm:mb-6">
                <h2 className="text-xl sm:text-2xl font-bold mb-2">Personal Information</h2>
                <p className="text-gray-600 text-sm sm:text-base">Tell us about yourself</p>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                <div>
                    <Label htmlFor="name" className="text-sm sm:text-base">Name</Label>
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
                    <Label htmlFor="phone" className="text-sm sm:text-base">Phone Number</Label>
                    <div className="flex mt-1">
                        <div className="flex items-center px-3 py-2 border border-r-0 border-gray-300 bg-gray-50 rounded-l-md text-xs sm:text-sm text-gray-600 min-w-[80px] sm:min-w-[100px]">
                                    {selectedCountry ? (
                                        <span className="flex items-center space-x-1">
                                    <ReactCountryFlag
                                        countryCode={selectedCountry.code}
                                        svg
                                        style={{
                                            width: '20px',
                                            height: '15px',
                                            marginRight: '4px'
                                        }}
                                    />
                                    <span>{selectedCountry.callingCode}</span>
                                        </span>
                                    ) : (
                                <span>Code</span>
                            )}
                        </div>
                        <Input
                            id="phone"
                            value={data.phone}
                            onChange={(e) => handlePhoneChange(e.target.value)}
                            placeholder={selectedCountry ? "Enter phone number" : "Select country first"}
                            className="rounded-l-none"
                            disabled={!selectedCountry}
                        />
                    </div>
                    {phoneValidationError && (
                        <p className="text-red-500 text-xs sm:text-sm mt-1">{phoneValidationError}</p>
                    )}
                    {errors.phone && <p className="text-red-500 text-sm mt-1">{errors.phone}</p>}
                </div>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                <div>
                    <Label htmlFor="country-select" className="text-sm sm:text-base">Country</Label>
                    <Popover open={countryOpen} onOpenChange={setCountryOpen}>
                        <PopoverTrigger asChild>
                            <Button
                                id="country-select"
                                variant="outline"
                                role="combobox"
                                aria-expanded={countryOpen}
                                className="w-full justify-between mt-1 text-sm sm:text-base"
                            >
                                {data.country ? (
                                    <span className="flex items-center space-x-2">
                                        <ReactCountryFlag
                                            countryCode={sortedCountries.find(c => c.name === data.country)?.code || ''}
                                            svg
                                            style={{
                                                width: '20px',
                                                height: '15px',
                                                marginRight: '4px'
                                            }}
                                        />
                                        <span className="truncate">{data.country}</span>
                                    </span>
                                ) : (
                                    "Select your country..."
                                )}
                                <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent className="w-[320px] sm:w-[400px] p-0">
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
                                                    <ReactCountryFlag
                                                        countryCode={country.code}
                                                        svg
                                                        style={{
                                                            width: '20px',
                                                            height: '15px',
                                                            marginRight: '4px'
                                                        }}
                                                    />
                                                    <span className="truncate">{country.name}</span>
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
                    <Label htmlFor="city-select" className="text-sm sm:text-base">City</Label>
                    <Popover open={cityOpen} onOpenChange={setCityOpen}>
                        <PopoverTrigger asChild>
                            <Button
                                id="city-select"
                                variant="outline"
                                role="combobox"
                                aria-expanded={cityOpen}
                                className="w-full justify-between mt-1 text-sm sm:text-base"
                                disabled={!data.country || loadingCities}
                            >
                                <span className="truncate">
                                {data.city || (
                                    !data.country ? "Select country first..." :
                                    loadingCities ? "Loading cities..." :
                                    "Select your city..."
                                )}
                                </span>
                                <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent className="w-[320px] sm:w-[400px] p-0">
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
                <Label className="text-sm sm:text-base">Profile Photo</Label>
                <p className="text-xs sm:text-sm text-gray-600 mb-4">Choose a photo that will help learners get to know you</p>
                <div className="flex flex-col sm:flex-row items-start sm:items-center space-y-4 sm:space-y-0 sm:space-x-4">
                    <div className="w-16 h-16 sm:w-20 sm:h-20 bg-gray-100 rounded-lg flex items-center justify-center overflow-hidden">
                        {profilePhotoPreview ? (
                            <img src={profilePhotoPreview} alt="Profile" className="w-full h-full object-cover" />
                        ) : (
                            <div className="text-center text-gray-400">
                                <Upload size={20} className="sm:w-6 sm:h-6" />
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
                        <Label htmlFor="profile_photo" className="cursor-pointer inline-flex items-center px-3 sm:px-4 py-2 border border-gray-300 rounded-md shadow-sm text-xs sm:text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            Upload
                        </Label>
                    </div>
                </div>
                {errors.profile_photo && <p className="text-red-500 text-sm mt-1">{errors.profile_photo}</p>}
            </div>
        </div>
    );

    const renderSuccessScreen = () => {
        // Check if teacher was rejected
        if (verificationRequest && verificationRequest.status === 'rejected') {
            return (
                <div className="text-center space-y-4 sm:space-y-6">
                    {/* Rejection Icon */}
                    <div className="relative mx-auto w-20 h-20 sm:w-24 sm:h-24">
                        <div className="absolute inset-0 bg-red-600 rounded-full flex items-center justify-center">
                            <X className="w-6 h-6 sm:w-8 sm:h-8 text-white stroke-[3]" />
                        </div>
                    </div>
                    
                    {/* Main Heading */}
                    <div>
                        <h2 className="text-xl sm:text-2xl font-bold text-gray-900 mb-2">
                            Application Rejected
                        </h2>
                        <p className="text-gray-600 text-sm sm:text-base">
                            Your teacher application has been reviewed and unfortunately rejected.
                        </p>
                    </div>

                    {/* Rejection Reason */}
                    {verificationRequest.rejection_reason && (
                        <div className="bg-red-50 border border-red-200 rounded-lg p-3 sm:p-4 max-w-md mx-auto">
                            <p className="text-red-800 font-medium text-center mb-2 text-xs sm:text-sm">
                                <strong>Reason for rejection:</strong>
                            </p>
                            <p className="text-red-700 text-center text-xs sm:text-sm">
                                {verificationRequest.rejection_reason}
                            </p>
                        </div>
                    )}

                    {/* Action Buttons */}
                    <div className="flex flex-col sm:flex-row gap-3 justify-center max-w-md mx-auto">
                        <Button
                            onClick={() => window.location.href = '/disputes/create'}
                            className="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-full text-sm"
                        >
                            Appeal Decision
                        </Button>
                        <Button
                            variant="outline"
                            onClick={() => {
                                // Clear completion state and reset to step 1
                                // BUT preserve form data for reapplication
                                sessionStorage.removeItem('teacher_onboarding_completed');
                                sessionStorage.setItem('teacher_onboarding_step', '1');
                                setIsCompleted(false);
                                setCurrentStep(1);
                                // Force reload to get fresh data from backend
                                window.location.reload();
                            }}
                            className="px-6 py-2 rounded-full text-sm"
                        >
                            Reapply
                        </Button>
                    </div>

                    {/* Support Contact */}
                    <div className="text-center">
                        <p className="text-gray-600 text-xs sm:text-sm">
                            Need help? Contact us at{' '}
                            <a href="mailto:support@iqraquest.com" className="text-teal-600 hover:underline">
                                support@iqraquest.com
                            </a>
                        </p>
                    </div>
                </div>
            );
        }

        // Default success screen for pending/approved
        return (
            <div className="text-center space-y-4 sm:space-y-6">
                {/* Success Icon with Translucent Square Shapes */}
                <div className="relative mx-auto w-20 h-20 sm:w-24 sm:h-24">
                    {/* Translucent teal square shapes */}
                    <div className="absolute -top-2 -left-2 w-12 h-12 sm:w-16 sm:h-16 bg-teal-200/40 rounded-lg transform rotate-12"></div>
                    <div className="absolute -bottom-2 -right-2 w-12 h-12 sm:w-16 sm:h-16 bg-teal-200/40 rounded-lg transform -rotate-12"></div>
                    {/* Dark teal circle with checkmark */}
                    <div className="absolute inset-0 bg-teal-600 rounded-full flex items-center justify-center">
                        <Check className="w-6 h-6 sm:w-8 sm:h-8 text-white stroke-[3]" />
                    </div>
                </div>
                
                {/* Main Heading */}
                <div>
                    <h2 className="text-xl sm:text-2xl font-bold text-gray-900 mb-2">
                        Thank you for completing<br />registration!
                    </h2>
                    <p className="text-gray-600 text-sm sm:text-base">
                        We've received your application and are currently reviewing it.
                    </p>
                </div>

                {/* Informational Text Block */}
                <div className="bg-teal-50 border border-teal-200 rounded-lg p-3 sm:p-4 max-w-md mx-auto">
                    <p className="text-teal-800 font-medium text-center mb-2 text-xs sm:text-sm">
                        To ensure the quality and authenticity of our teachers, we require a quick live video call before you can proceed to your dashboard.
                    </p>
                    <p className="text-teal-700 text-center text-xs sm:text-sm">
                        You will receive an email with the invitation live video call within 5 business days. Stay tuned!
                    </p>
                </div>

                {/* Important Notes */}
                <div className="max-w-md mx-auto">
                    <h4 className="text-orange-500 font-semibold mb-3 text-sm sm:text-base">Important Notes</h4>
                    <div className="space-y-2 text-center">
                        <div className="flex items-center justify-center gap-2">
                            <span className="text-yellow-500">⚠️</span>
                            <span className="text-gray-600 text-xs sm:text-sm">Make sure to have a stable internet connection.</span>
                        </div>
                        <div className="flex items-center justify-center gap-2">
                            <span className="text-yellow-500">⚠️</span>
                            <span className="text-gray-600 text-xs sm:text-sm">Use a quiet and well-lit environment.</span>
                        </div>
                        <div className="flex items-center justify-center gap-2">
                            <span className="text-yellow-500">⚠️</span>
                            <span className="text-gray-600 text-xs sm:text-sm">Keep your ID and teaching qualifications ready.</span>
                        </div>
                    </div>
                </div>
            </div>
        );
    };

    if (isCompleted) {
        return (
            <AppLayout pageTitle="Registration Complete">
                <Head title="Registration Complete" />
                <div className="py-4 sm:py-8 flex items-center justify-center min-h-screen">
                    <div className="max-w-2xl mx-auto px-4 w-full">
                        <Card className="bg-white shadow-sm">
                            <CardContent className="p-4 sm:p-6 lg:p-8">
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
            
            <div className="py-4 sm:py-8 min-h-screen">
                <div className="max-w-2xl mx-auto px-4 w-full">
                    {renderStepIndicator()}
                    
                    <Card className="bg-white shadow-sm">
                        <CardContent className="p-4 sm:p-6 lg:p-8">
                            <form onSubmit={submit}>
                                {currentStep === 1 && renderStep1()}
                                {currentStep === 2 && renderStep2()}
                                {currentStep === 3 && renderStep3()}
                                {currentStep === 4 && renderStep4()}
                                
                                <div className="flex flex-col sm:flex-row justify-between items-center gap-4 mt-6 sm:mt-8">
                                    {currentStep > 1 && (
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={prevStep}
                                            className="w-full sm:w-auto px-6 order-2 sm:order-1"
                                        >
                                            Back
                                        </Button>
                                    )}
                                    
                                    <div className="w-full sm:w-auto order-1 sm:order-2">
                                        {currentStep < 4 ? (
                                            <Button
                                                type="button"
                                                onClick={nextStep}
                                                className="bg-teal-600 hover:bg-teal-700 text-white w-full sm:w-auto px-6 sm:px-8 py-2 sm:py-3 rounded-full text-sm sm:text-base"
                                            >
                                                Save and Continue
                                            </Button>
                                        ) : (
                                            <Button
                                                type="submit"
                                                disabled={processing}
                                                className="bg-teal-600 hover:bg-teal-700 text-white w-full sm:w-auto px-6 sm:px-8 py-2 sm:py-3 rounded-full text-sm sm:text-base"
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
