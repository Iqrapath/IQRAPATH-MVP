/**
 * 🎨 FIGMA REFERENCE
 * URL: https://www.figma.com/design/jmWnnfdCipxqiQF39Tdb0S/IQRAQUEST?node-id=405-22320&t=O1w7ozri9pYud8IO-0
 * Export: Browse Teachers - Filters Bar
 */
import React from 'react';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Slider } from '@/components/ui/slider';

interface BudgetRange {
    label: string;
    value: number;
}

interface FilterBarProps {
    subjects: string[];
    selectedSubject: string;
    onChangeSubject: (s: string) => void;
    languages: string[];
    language?: string;
    onChangeLanguage: (l?: string) => void;
    budgetRanges: BudgetRange[];
    maxPrice?: number;
    onChangeMaxPrice: (p?: number) => void;
    timePreferences: string[];
    timePreference?: string;
    onChangeTimePreference: (t?: string) => void;
    totalCount: number;
    onApply: () => void;
    minRating: number;
    onToggleFourPlus: () => void;
}

export default function FilterBar({
    subjects,
    selectedSubject,
    onChangeSubject,
    languages,
    language,
    onChangeLanguage,
    budgetRanges,
    maxPrice,
    onChangeMaxPrice,
    timePreferences,
    timePreference,
    onChangeTimePreference,
    totalCount,
    onApply,
    minRating,
    onToggleFourPlus,
}: FilterBarProps) {
    return (
        <div className="bg-white rounded-[60px] p-6 shadow-sm border border-gray-200 mb-8">
            <div className="grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
                {/* Subject */}
                <div className="md:col-span-3">
                    <label className="text-xs text-gray-500 block mb-2">Subject</label>
                    <Select value={selectedSubject} onValueChange={onChangeSubject}>
                        <SelectTrigger className="h-11 rounded-xl">
                            <SelectValue placeholder="All Subject" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Subject</SelectItem>
                            {subjects.map((s) => (
                                <SelectItem key={s} value={s}>{s}</SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                {/* Time Preference */}
                <div className="md:col-span-3">
                    <label className="text-xs text-gray-500 block mb-2">Time Preference</label>
                    <Select value={timePreference} onValueChange={onChangeTimePreference}>
                        <SelectTrigger className="h-11 rounded-xl">
                            <SelectValue placeholder="Select time" />
                        </SelectTrigger>
                        <SelectContent>
                            {timePreferences.map((time) => (
                                <SelectItem key={time} value={time.toLowerCase()}>{time}</SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                {/* Budget */}
                <div className="md:col-span-3">
                    <label className="text-xs text-gray-500 block mb-2">
                        Budget: {maxPrice ? `₦${maxPrice.toLocaleString()}` : 'Select budget'}
                    </label>
                    <div className="px-3">
                        <Slider
                            value={[maxPrice || 0]}
                            onValueChange={(values) => onChangeMaxPrice(values[0] || undefined)}
                            max={budgetRanges.length > 0 ? Math.max(...budgetRanges.map(r => r.value)) : 100000}
                            min={budgetRanges.length > 0 ? Math.min(...budgetRanges.map(r => r.value)) : 0}
                            step={1000}
                            className="w-full"
                        />
                        <div className="flex justify-between text-xs text-gray-400 mt-1">
                            <span>₦{budgetRanges.length > 0 ? Math.min(...budgetRanges.map(r => r.value)).toLocaleString() : '0'}</span>
                            <span>₦{budgetRanges.length > 0 ? Math.max(...budgetRanges.map(r => r.value)).toLocaleString() : '100,000'}</span>
                        </div>
                    </div>
                </div>

                {/* Language */}
                <div className="md:col-span-2">
                    <label className="text-xs text-gray-500 block mb-2">Language</label>
                    <Select value={language} onValueChange={onChangeLanguage}>
                        <SelectTrigger className="h-11 rounded-xl">
                            <SelectValue placeholder="Language" />
                        </SelectTrigger>
                        <SelectContent>
                            {languages.map((lang) => (
                                <SelectItem key={lang} value={lang}>{lang}</SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                {/* Apply */}
                <div className="md:col-span-1 flex items-end">
                    <Button onClick={onApply} className="w-full h-11 rounded-full bg-[#2C7870] hover:bg-[#236158]">Apply</Button>
                </div>
            </div>

            {/* Quick row */}
            <div className="flex items-center justify-between mt-4">
                <div className="flex items-center gap-2 text-sm text-gray-600">
                    <span>{totalCount} teachers found</span>
                    <button
                        className={`text-xs px-3 py-1 rounded-full border ${minRating >= 4 ? 'bg-[#2C7870] text-white border-[#2C7870]' : 'border-gray-300 text-gray-700'}`}
                        onClick={onToggleFourPlus}
                    >
                        4+ Stars
                    </button>
                </div>
            </div>
        </div>
    );
}
