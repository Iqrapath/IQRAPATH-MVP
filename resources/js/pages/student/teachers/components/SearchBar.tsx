/**
 * 🎨 FIGMA REFERENCE
 * URL: https://www.figma.com/design/jmWnnfdCipxqiQF39Tdb0S/IQRAQUEST?node-id=405-22320&t=O1w7ozri9pYud8IO-0
 * Export: Browse Teachers - Search Bar
 */
import React from 'react';
import { Search } from 'lucide-react';
import { Input } from '@/components/ui/input';

interface SearchBarProps {
    value: string;
    onChange: (val: string) => void;
    onSubmit: () => void;
}

export default function SearchBar({ value, onChange, onSubmit }: SearchBarProps) {
    return (
        <div className="relative">
            <Search className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 w-4 h-4" />
            <Input
                placeholder="Search for competent teacher or subject"
                value={value}
                onChange={(e) => onChange(e.target.value)}
                className="pl-10 h-12 rounded-2xl bg-white border border-gray-200"
                onKeyDown={(e) => e.key === 'Enter' && onSubmit()}
            />
        </div>
    );
}


