/**
 * 🎨 FIGMA REFERENCE
 * URL: https://www.figma.com/design/jmWnnfdCipxqiQF39Tdb0S/IQRAPATH?node-id=405-22320&t=O1w7ozri9pYud8IO-0
 * Export: Browse Teachers - Filters Bar
 */
import React from 'react';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

interface FiltersBarProps {
    subjects: string[];
    selectedSubject: string;
    onChangeSubject: (s: string) => void;
    language?: string;
    onChangeLanguage: (l?: string) => void;
    maxPrice?: number;
    onChangeMaxPrice: (p?: number) => void;
    timePreference?: string;
    onChangeTimePreference: (t?: string) => void;
    totalCount: number;
    onApply: () => void;
    minRating: number;
    onToggleFourPlus: () => void;
}

export default function FiltersBar({
    subjects,
    selectedSubject,
    onChangeSubject,
    language,
    onChangeLanguage,
    maxPrice,
    onChangeMaxPrice,
    timePreference,
    onChangeTimePreference,
    totalCount,
    onApply,
    minRating,
    onToggleFourPlus,
}: FiltersBarProps) {
    return (
        <div className="bg-white rounded-[40px] p-6 shadow-sm border border-gray-200 mb-8">
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
                            <SelectItem value="morning">Morning</SelectItem>
                            <SelectItem value="afternoon">Afternoon</SelectItem>
                            <SelectItem value="evening">Evening</SelectItem>
                            <SelectItem value="weekend">Weekend</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                {/* Budget */}
                <div className="md:col-span-3">
                    <label className="text-xs text-gray-500 block mb-2">Budget: NGN</label>
                    <Select value={String(maxPrice ?? '')} onValueChange={(v) => onChangeMaxPrice(v ? Number(v) : undefined)}>
                        <SelectTrigger className="h-11 rounded-xl">
                            <SelectValue placeholder="Select budget" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="5000">₦5,000</SelectItem>
                            <SelectItem value="10000">₦10,000</SelectItem>
                            <SelectItem value="20000">₦20,000</SelectItem>
                            <SelectItem value="50000">₦50,000</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                {/* Language */}
                <div className="md:col-span-2">
                    <label className="text-xs text-gray-500 block mb-2">Language</label>
                    <Select value={language} onValueChange={onChangeLanguage}>
                        <SelectTrigger className="h-11 rounded-xl">
                            <SelectValue placeholder="Language" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="English">English</SelectItem>
                            <SelectItem value="Arabic">Arabic</SelectItem>
                            <SelectItem value="Hausa">Hausa</SelectItem>
                            <SelectItem value="Yoruba">Yoruba</SelectItem>
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
