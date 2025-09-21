/**
 * 🎨 FIGMA REFERENCE
 * URL: https://www.figma.com/design/jmWnnfdCipxqiQF39Tdb0S/IQRAQUEST?node-id=405-22320&t=O1w7ozri9pYud8IO-0
 * Export: Browse teachers page with search, filters, and teacher cards
 * 
 * EXACT SPECS FROM FIGMA:
 * - Search and filter bar at the top
 * - Teacher cards grid layout with profiles
 * - Subject tags and rating displays
 * - Booking buttons and contact options
 * - Pagination and sorting functionality
 */
import { Head, Link, router } from '@inertiajs/react';
import { Search, Filter, MapPin, Star, Clock, Video, Heart } from 'lucide-react';
import StudentLayout from '@/layouts/student/student-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { 
    Breadcrumb,
    BreadcrumbList,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { 
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useState } from 'react';

// Header components
import SearchBar from './teachers/components/SearchBar';
import FiltersBar from './teachers/components/FiltersBar';
import TeacherCard from './teachers/components/TeacherCard';

interface Teacher {
    id: number;
    name: string;
    avatar: string;
    bio: string;
    subjects: string[];
    rating: number;
    reviews_count: number;
    hourly_rate_ngn: number;
    experience_years: string;
    location: string;
    verified: boolean;
    teaching_mode: string;
    languages: string[];
    available_slots: number;
    response_time: string;
    availability?: string; // Added missing property
    is_on_holiday?: boolean; // Added holiday status
}

interface BrowseTeachersProps {
    teachers: {
        data: Teacher[];
        links: any[];
        current_page: number;
        last_page: number;
        total: number;
    };
    subjects: string[];
    filters: {
        search?: string;
        subject?: string;
        min_rating?: number;
        max_price?: number;
        experience?: string;
        language?: string;
        availability?: string;
    };
}

export default function BrowseTeachers({ teachers, subjects, filters }: BrowseTeachersProps) {
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [selectedSubject, setSelectedSubject] = useState(filters.subject || 'all');
    const [minRating, setMinRating] = useState(filters.min_rating || 0);
    const [sortBy, setSortBy] = useState('rating');
    const [maxPrice, setMaxPrice] = useState<number | undefined>(filters.max_price);
    const [language, setLanguage] = useState<string | undefined>(filters.language);
    const [timePreference, setTimePreference] = useState<string | undefined>(filters.availability);

    const handleSearch = () => {
        router.get('/student/browse-teachers', {
            search: searchTerm,
            subject: selectedSubject !== 'all' ? selectedSubject : undefined,
            min_rating: minRating > 0 ? minRating : undefined,
            max_price: maxPrice,
            language,
            availability: timePreference,
            sort: sortBy,
        }, { preserveState: true });
    };

    const handleFilterChange = (key: string, value: any) => {
        router.get('/student/browse-teachers', {
            ...filters,
            [key]: value,
        }, { preserveState: true });
    };

    const renderStars = (rating: number) => {
        return Array.from({ length: 5 }, (_, i) => (
            <Star 
                key={i} 
                className={`w-4 h-4 ${i < rating ? 'text-yellow-400 fill-current' : 'text-gray-300'}`}
            />
        ));
    };

    return (
        <StudentLayout pageTitle="Browse Teachers" showRightSidebar={false}>
            <Head title="Browse Teachers" />

            <div className="min-h-screen bg-gray-50 p-4">
                {/* Breadcrumb */}
                <div className="mb-6">
                    <Breadcrumb>
                        <BreadcrumbList>
                            <BreadcrumbItem>
                                <BreadcrumbLink asChild>
                                    <Link href="/student/dashboard">Dashboard</Link>
                                </BreadcrumbLink>
                            </BreadcrumbItem>
                            <BreadcrumbSeparator />
                            <BreadcrumbItem>
                                <BreadcrumbPage>Browse Teachers</BreadcrumbPage>
                            </BreadcrumbItem>
                        </BreadcrumbList>
                    </Breadcrumb>
                </div>

                {/* Page Header */}
                <div className="mb-6">
                    <h1 className="text-[28px] leading-[36px] font-bold text-gray-900 mb-4">Browse Teachers</h1>
                    <SearchBar
                        value={searchTerm}
                        onChange={setSearchTerm}
                        onSubmit={handleSearch}
                    />
                </div>

                {/* Filters Bar */}
                <FiltersBar
                    subjects={subjects}
                    selectedSubject={selectedSubject}
                    onChangeSubject={setSelectedSubject}
                    language={language}
                    onChangeLanguage={setLanguage}
                    maxPrice={maxPrice}
                    onChangeMaxPrice={setMaxPrice}
                    timePreference={timePreference}
                    onChangeTimePreference={setTimePreference}
                    onApply={handleSearch}
                    totalCount={teachers.total}
                    minRating={minRating}
                    onToggleFourPlus={() => setMinRating(minRating >= 4 ? 0 : 4)}
                />

                {/* Teachers Grid */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-4 mb-8">
                    {teachers.data.map((teacher) => (
                        <TeacherCard key={teacher.id} teacher={teacher} />
                    ))}
                </div>

                {/* Empty State */}
                {teachers.data.length === 0 && (
                    <div className="text-center py-12 bg-white rounded-2xl shadow-sm border border-gray-100">
                        <div className="w-24 h-24 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                            <Search className="w-12 h-12 text-gray-400" />
                        </div>
                        <h3 className="text-lg font-medium text-gray-900 mb-2">No teachers found</h3>
                        <p className="text-gray-600 mb-6">
                            Try adjusting your search criteria or filters to find more teachers.
                        </p>
                        <Button 
                            onClick={() => router.get('/student/browse-teachers')}
                            className="bg-[#2C7870] hover:bg-[#236158]"
                        >
                            Clear Filters
                        </Button>
                    </div>
                )}

                {/* Pagination */}
                {teachers.last_page > 1 && (
                    <div className="flex justify-center mt-8">
                        <div className="flex items-center space-x-2">
                            {teachers.links.map((link, index) => (
                                link.url ? (
                                    <Link
                                        key={index}
                                        href={link.url}
                                        className={`px-3 py-2 text-sm rounded-md ${
                                            link.active 
                                                ? 'bg-[#2C7870] text-white' 
                                                : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'
                                        }`}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ) : (
                                    <span
                                        key={index}
                                        className="px-3 py-2 text-sm text-gray-400"
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                )
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </StudentLayout>
    );
}
