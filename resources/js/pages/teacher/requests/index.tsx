import { Head } from '@inertiajs/react';
import { useState } from 'react';
import TeacherLayout from '@/layouts/teacher/teacher-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Input } from '@/components/ui/input';
import { 
    Calendar, 
    Clock, 
    User, 
    BookOpen, 
    MessageCircle,
    Video,
    CheckCircle,
    XCircle,
    MoreVertical,
    Search,
    Filter,
    SortAsc,
    SortDesc
} from 'lucide-react';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { RequestCard, FilterBar } from './components';
import { toast } from 'sonner';

const breadcrumbs = [
    { title: 'Dashboard', href: '/teacher/dashboard' },
    { title: 'Requests', href: '/teacher/requests' }
];

interface Request {
    id: number;
    student: {
        id: number;
        name: string;
        avatar?: string | null;
        level: string;
    };
    subject: string;
    requestedDays: string;
    requestedTime: string;
    subjects: string[];
    note: string;
    status: 'pending' | 'accepted' | 'declined' | 'expired';
    price?: number;
    priceUSD?: number;
}


interface FilterState {
    subject: string;
    timePreference: string;
    budget: {
        currency: 'USD' | 'NGN';
        min: number;
        max: number;
    };
    language: string;
}

interface BudgetRange {
    label: string;
    value: number;
}

interface TeacherRequestsProps {
    requests?: Request[];
    subjects?: string[];
    languages?: string[];
    timePreferences?: string[];
    budgetRanges?: BudgetRange[];
}

export default function TeacherRequests({ 
    requests: initialRequests, 
    subjects, 
    languages, 
    timePreferences, 
    budgetRanges 
}: TeacherRequestsProps) {
    const [requests, setRequests] = useState<Request[]>(initialRequests || []);
    const [searchTerm, setSearchTerm] = useState('');
    const [filterStatus, setFilterStatus] = useState<'all' | 'pending' | 'accepted' | 'declined' | 'expired'>('all');
    const [sortBy, setSortBy] = useState<'date' | 'priority' | 'student'>('date');
    const [sortOrder, setSortOrder] = useState<'asc' | 'desc'>('desc');
    const [loadingRequests, setLoadingRequests] = useState<Set<number>>(new Set());
    const [filters, setFilters] = useState<FilterState>({
        subject: 'all',
        timePreference: 'Select time',
        budget: {
            currency: 'NGN',
            min: 0,
            max: 999999 // Set to very high number to show all by default
        },
        language: 'Choose Language'
    });

    // Filter and sort requests
    const filteredAndSortedRequests = (requests || [])
        .filter(request => {
            const matchesSearch = request.student.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                                request.subject.toLowerCase().includes(searchTerm.toLowerCase()) ||
                                request.note.toLowerCase().includes(searchTerm.toLowerCase());
            const matchesStatus = filterStatus === 'all' || request.status === filterStatus;
            
            // Apply advanced filters
            const matchesSubject = filters.subject === 'All Subject' || filters.subject === 'all' || request.subject === filters.subject;
            const matchesTime = filters.timePreference === 'Select time' || true; // Time filtering can be added later
            
            // Budget filtering - check if request price is within budget range
            const matchesBudget = !filters.budget.max || !request.price || request.price <= filters.budget.max;
            
            const matchesLanguage = filters.language === 'Choose Language' || true; // Language filtering can be added later
            
            return matchesSearch && matchesStatus && matchesSubject && matchesTime && matchesBudget && matchesLanguage;
        })
        .sort((a, b) => {
            let comparison = 0;
            switch (sortBy) {
                case 'date':
                    // Sort by ID as a proxy for creation date
                    comparison = a.id - b.id;
                    break;
                case 'priority':
                    // Sort by price as a proxy for priority (higher price = higher priority)
                    comparison = (a.price || 0) - (b.price || 0);
                    break;
                case 'student':
                    comparison = a.student.name.localeCompare(b.student.name);
                    break;
            }
            return sortOrder === 'asc' ? comparison : -comparison;
        });

    const handleAccept = async (request: Request) => {
        setLoadingRequests(prev => new Set(prev).add(request.id));
        
        try {
            // Simulate API call
            await new Promise(resolve => setTimeout(resolve, 1000));
            
            setRequests(prev => prev.map(r => 
                r.id === request.id ? { ...r, status: 'accepted' as const } : r
            ));
            
            toast.success('Request accepted successfully!');
        } catch (error) {
            toast.error('Failed to accept request');
        } finally {
            setLoadingRequests(prev => {
                const newSet = new Set(prev);
                newSet.delete(request.id);
                return newSet;
            });
        }
    };

    const handleDecline = async (request: Request) => {
        setLoadingRequests(prev => new Set(prev).add(request.id));
        
        try {
            // Simulate API call
            await new Promise(resolve => setTimeout(resolve, 1000));
            
            setRequests(prev => prev.map(r => 
                r.id === request.id ? { ...r, status: 'declined' as const } : r
            ));
            
            toast.success('Request declined successfully!');
        } catch (error) {
            toast.error('Failed to decline request');
        } finally {
            setLoadingRequests(prev => {
                const newSet = new Set(prev);
                newSet.delete(request.id);
                return newSet;
            });
        }
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'pending':
                return 'bg-yellow-100 text-yellow-800';
            case 'accepted':
                return 'bg-green-100 text-green-800';
            case 'declined':
                return 'bg-red-100 text-red-800';
            case 'expired':
                return 'bg-gray-100 text-gray-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    };

    const getPriorityColor = (priority: string) => {
        switch (priority) {
            case 'high':
                return 'bg-red-100 text-red-800';
            case 'medium':
                return 'bg-yellow-100 text-yellow-800';
            case 'low':
                return 'bg-green-100 text-green-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    };

    const getInitials = (name: string) => {
        return name
            .split(' ')
            .map(word => word.charAt(0))
            .join('')
            .toUpperCase()
            .slice(0, 2);
    };

    const formatDate = (dateString: string) => {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { 
            month: 'short', 
            day: 'numeric',
            year: 'numeric'
        });
    };

    const formatTime = (timeString: string) => {
        return timeString;
    };

    const stats = {
        total: (requests || []).length,
        pending: (requests || []).filter(r => r.status === 'pending').length,
        accepted: (requests || []).filter(r => r.status === 'accepted').length,
        declined: (requests || []).filter(r => r.status === 'declined').length
    };


    return (
        <TeacherLayout pageTitle="Requests" showRightSidebar={false}>
            <Head title="Requests" />
            
            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <Breadcrumbs breadcrumbs={breadcrumbs} />
                </div>

                {/* Advanced Filter Bar */}
                <FilterBar 
                    subjects={subjects || []}
                    selectedSubject={filters.subject}
                    onChangeSubject={(subject) => setFilters(prev => ({ ...prev, subject }))}
                    languages={languages || []}
                    language={filters.language}
                    onChangeLanguage={(language) => setFilters(prev => ({ ...prev, language: language || 'Choose Language' }))}
                    budgetRanges={budgetRanges || []}
                    maxPrice={filters.budget.max === 999999 ? undefined : filters.budget.max}
                    onChangeMaxPrice={(maxPrice) => setFilters(prev => ({ ...prev, budget: { ...prev.budget, max: maxPrice || 999999 } }))}
                    timePreferences={timePreferences || []}
                    timePreference={filters.timePreference}
                    onChangeTimePreference={(timePreference) => setFilters(prev => ({ ...prev, timePreference: timePreference || 'Select time' }))}
                    totalCount={filteredAndSortedRequests.length}
                    onApply={() => console.log('Apply filters')}
                    minRating={0}
                    onToggleFourPlus={() => console.log('Toggle 4+ stars')}
                />

                {/* Status Tabs */}
                <Card>
                    <CardContent className="p-6">
                        <div className="flex items-center justify-between gap-4 flex-wrap">
                            {/* Status Filter Tabs */}
                            <div className="flex items-center gap-2 flex-wrap">
                                <Button
                                    variant={filterStatus === 'all' ? 'default' : 'outline'}
                                    size="sm"
                                    onClick={() => setFilterStatus('all')}
                                    className={filterStatus === 'all' ? 'bg-teal-600 hover:bg-teal-700' : ''}
                                >
                                    All ({stats.total})
                                </Button>
                                <Button
                                    variant={filterStatus === 'pending' ? 'default' : 'outline'}
                                    size="sm"
                                    onClick={() => setFilterStatus('pending')}
                                    className={filterStatus === 'pending' ? 'bg-yellow-600 hover:bg-yellow-700' : ''}
                                >
                                    Pending ({stats.pending})
                                </Button>
                                <Button
                                    variant={filterStatus === 'accepted' ? 'default' : 'outline'}
                                    size="sm"
                                    onClick={() => setFilterStatus('accepted')}
                                    className={filterStatus === 'accepted' ? 'bg-green-600 hover:bg-green-700' : ''}
                                >
                                    Accepted ({stats.accepted})
                                </Button>
                                <Button
                                    variant={filterStatus === 'declined' ? 'default' : 'outline'}
                                    size="sm"
                                    onClick={() => setFilterStatus('declined')}
                                    className={filterStatus === 'declined' ? 'bg-red-600 hover:bg-red-700' : ''}
                                >
                                    Declined ({stats.declined})
                                </Button>
                            </div>

                            {/* Sort Controls */}
                            <div className="flex items-center gap-2">
                                <select
                                    value={sortBy}
                                    onChange={(e) => setSortBy(e.target.value as 'date' | 'priority' | 'student')}
                                    className="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-teal-500"
                                >
                                    <option value="date">Sort by Date</option>
                                    <option value="priority">Sort by Priority</option>
                                    <option value="student">Sort by Student</option>
                                </select>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setSortOrder(prev => prev === 'asc' ? 'desc' : 'asc')}
                                >
                                    {sortOrder === 'asc' ? <SortAsc className="h-4 w-4" /> : <SortDesc className="h-4 w-4" />}
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Requests List */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {filteredAndSortedRequests.length > 0 ? (
                        filteredAndSortedRequests.map((request) => (
                            <RequestCard
                                key={request.id}
                                request={request}
                                onAccept={handleAccept}
                                onDecline={handleDecline}
                                onFavorite={(request) => console.log('Favorite:', request)}
                                isLoading={loadingRequests.has(request.id)}
                            />
                        ))
                    ) : (
                        <Card>
                            <CardContent className="p-12 text-center">
                                <div className="w-24 h-24 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                                    <BookOpen className="w-12 h-12 text-gray-400" />
                                </div>
                                <h3 className="text-lg font-semibold text-gray-900 mb-2">No Requests Found</h3>
                                <p className="text-gray-600">
                                    {searchTerm || filterStatus !== 'all' 
                                        ? 'No requests match your current filters. Try adjusting your search criteria.'
                                        : 'You don\'t have any requests at the moment.'
                                    }
                                </p>
                            </CardContent>
                        </Card>
                    )}
                </div>
            </div>
        </TeacherLayout>
    );
}
