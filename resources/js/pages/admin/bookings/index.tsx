import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminLayout from '@/layouts/admin/admin-layout';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Search, Filter, MoreVertical, CheckCircle, Clock, XCircle, AlertTriangle, Calendar, User, BookOpen, Star, ClipboardList, Pencil, MessageSquare, UserCheck } from 'lucide-react';
import { toast } from 'sonner';
import { format } from 'date-fns';
import { BookingApprovalDialog } from '@/components/booking-approval-dialog';
import { BookingCancelDialog } from '@/components/booking-cancel-dialog';
import { BookingDetailsModal } from '@/components/booking-details-modal';
import { BookingRescheduleModal } from '@/components/booking-reschedule-modal';
import { BookingReassignModal } from '@/components/booking-reassign-modal';

interface Booking {
    id: number;
    booking_uuid: string;
    student: {
        id: number;
        name: string;
        email: string;
    };
    teacher: {
        id: number;
        name: string;
        email: string;
        teacherAvailabilities?: {
            holiday_mode: boolean;
            is_active: boolean;
        }[];
    };
    subject: {
        id: number;
        template: {
            id: number;
            name: string;
        };
    };
    booking_date: string;
    start_time: string;
    end_time: string;
    duration_minutes: number;
    status: 'pending' | 'approved' | 'rejected' | 'upcoming' | 'completed' | 'missed' | 'cancelled';
    notes?: string;
    created_at: string;
    approved_at?: string;
    cancelled_at?: string;
}

interface SubjectTemplate {
    id: number;
    name: string;
}

interface Stats {
    total: number;
    pending: number;
    upcoming: number;
    completed: number;
    cancelled: number;
    missed: number;
}

interface Props {
    bookings: {
        data: Booking[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: any[];
    };
    subjects: SubjectTemplate[];
    statusOptions: Record<string, string>;
    stats: Stats;
    filters: {
        search?: string;
        status?: string;
        subject_id?: string;
        date?: string;
        date_from?: string;
        date_to?: string;
    };
}

const statusConfig = {
    pending: { label: 'Pending', color: 'bg-yellow-100 text-yellow-800', icon: Clock },
    approved: { label: 'Approved', color: 'bg-blue-100 text-blue-800', icon: CheckCircle },
    rejected: { label: 'Rejected', color: 'bg-red-100 text-red-800', icon: XCircle },
    upcoming: { label: 'Upcoming', color: 'bg-blue-100 text-blue-800', icon: Calendar },
    completed: { label: 'Completed', color: 'bg-green-100 text-green-800', icon: CheckCircle },
    missed: { label: 'Missed', color: 'bg-orange-100 text-orange-800', icon: AlertTriangle },
    cancelled: { label: 'Cancelled', color: 'bg-gray-100 text-gray-800', icon: XCircle },
};

export default function BookingIndex({ bookings, subjects, statusOptions, stats, filters }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [statusFilter, setStatusFilter] = useState(filters.status || 'all');
    const [subjectFilter, setSubjectFilter] = useState(filters.subject_id || 'all');
    const [dateFilter, setDateFilter] = useState(filters.date || 'all');
    const [selectedBookings, setSelectedBookings] = useState<number[]>([]);
    const [showBulkActions, setShowBulkActions] = useState(false);
    const [approvalDialog, setApprovalDialog] = useState<{
        isOpen: boolean;
        booking: Booking | null;
    }>({
        isOpen: false,
        booking: null,
    });
    const [cancelDialog, setCancelDialog] = useState<{
        isOpen: boolean;
        booking: Booking | null;
    }>({
        isOpen: false,
        booking: null,
    });
    const [openDropdown, setOpenDropdown] = useState<number | null>(null);
    const [detailsModal, setDetailsModal] = useState<{
        isOpen: boolean;
        booking: Booking | null;
    }>({
        isOpen: false,
        booking: null,
    });
    const [rescheduleModal, setRescheduleModal] = useState<{
        isOpen: boolean;
        booking: Booking | null;
    }>({
        isOpen: false,
        booking: null,
    });
    const [reassignModal, setReassignModal] = useState<{
        isOpen: boolean;
        booking: Booking | null;
    }>({
        isOpen: false,
        booking: null,
    });


    const handleSearch = () => {
        router.get(route('admin.bookings.index'), {
            search,
            status: statusFilter === 'all' ? undefined : statusFilter,
            subject_id: subjectFilter === 'all' ? undefined : subjectFilter,
            date: dateFilter === 'all' ? undefined : dateFilter,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const handleStatusChange = (bookingId: number, newStatus: string) => {
        const reason = prompt('Please provide a reason for this status change (optional):');

        router.patch(route('admin.bookings.update-status', bookingId), {
            status: newStatus,
            notes: reason || undefined,
        }, {
            onSuccess: () => {
                toast.success('Booking status updated successfully');
            },
            onError: () => {
                toast.error('Failed to update booking status');
            },
        });
    };

    const handleBulkStatusChange = (newStatus: string) => {
        if (selectedBookings.length === 0) {
            toast.error('Please select at least one booking');
            return;
        }

        const reason = prompt('Please provide a reason for this bulk status change (optional):');

        router.patch(route('admin.bookings.bulk-update-status'), {
            booking_ids: selectedBookings,
            status: newStatus,
            notes: reason || undefined,
        }, {
            onSuccess: () => {
                toast.success(`Successfully updated ${selectedBookings.length} booking(s)`);
                setSelectedBookings([]);
                setShowBulkActions(false);
            },
            onError: () => {
                toast.error('Failed to update bookings');
            },
        });
    };

    const handleApproveClick = (booking: Booking) => {
        setApprovalDialog({
            isOpen: true,
            booking: booking,
        });
        // Close any open dropdowns
        setOpenDropdown(null);
    };

    const handleApprovalConfirm = () => {
        if (approvalDialog.booking) {
            // Direct approval without asking for reason
            router.patch(route('admin.bookings.update-status', approvalDialog.booking.id), {
                status: 'approved',
                notify_parties: true,
            }, {
                onSuccess: () => {
                    toast.success('Booking approved successfully');
                    router.reload({ only: ['bookings'] });
                },
                onError: () => {
                    toast.error('Failed to approve booking');
                }
            });
            setApprovalDialog({ isOpen: false, booking: null });
        }
    };

    const handleApprovalCancel = () => {
        setApprovalDialog({ isOpen: false, booking: null });
    };

    const handleApprovalDialogClose = (open: boolean) => {
        if (!open) {
            setApprovalDialog({ isOpen: false, booking: null });
        }
    };

    const handleCancelClick = (booking: Booking) => {
        setCancelDialog({
            isOpen: true,
            booking: booking,
        });
        // Close any open dropdowns
        setOpenDropdown(null);
    };

    const handleCancelConfirm = (reason: string, notifyParties: boolean) => {
        if (cancelDialog.booking) {
            // Update the status change to include the reason
            router.patch(route('admin.bookings.update-status', cancelDialog.booking.id), {
                status: 'cancelled',
                notes: reason,
                notify_parties: notifyParties,
            }, {
                onSuccess: () => {
                    toast.success('Booking cancelled successfully');
                    router.reload({ only: ['bookings'] });
                },
                onError: () => {
                    toast.error('Failed to cancel booking');
                }
            });
            setCancelDialog({ isOpen: false, booking: null });
        }
    };

    const handleCancelDialogClose = () => {
        setCancelDialog({ isOpen: false, booking: null });
    };

    const handleCancelDialogStateChange = (open: boolean) => {
        if (!open) {
            setCancelDialog({ isOpen: false, booking: null });
        }
    };

    // Close dropdowns when clicking outside
    const handlePageClick = () => {
        setOpenDropdown(null);
    };

    const handleViewDetails = (booking: Booking) => {
        setDetailsModal({
            isOpen: true,
            booking: booking,
        });
        setOpenDropdown(null);
    };

    const handleDetailsModalClose = () => {
        setDetailsModal({
            isOpen: false,
            booking: null,
        });
    };

    const handleReschedule = () => {
        if (detailsModal.booking) {
            setRescheduleModal({
                isOpen: true,
                booking: detailsModal.booking,
            });
            handleDetailsModalClose();
        }
    };

    const handleRescheduleModalClose = () => {
        setRescheduleModal({
            isOpen: false,
            booking: null,
        });
    };

    const handleRescheduleConfirm = (data: {
        newDate: string;
        newTime: string;
        notifyParties: boolean;
        reason: string;
    }) => {
        if (rescheduleModal.booking) {
            router.patch(route('admin.bookings.reschedule', rescheduleModal.booking.id), {
                new_date: data.newDate,
                new_time: data.newTime,
                notify_parties: data.notifyParties,
                reason: data.reason,
            }, {
                onSuccess: () => {
                    toast.success('Booking rescheduled successfully');
                    handleRescheduleModalClose();
                    router.reload({ only: ['bookings'] });
                },
                onError: (errors) => {
                    toast.error('Failed to reschedule booking. Please try again.');
                    console.error('Reschedule errors:', errors);
                }
            });
        }
    };

    const handleReassign = () => {
        if (detailsModal.booking) {
            setReassignModal({
                isOpen: true,
                booking: detailsModal.booking,
            });
            handleDetailsModalClose();
        }
    };

    const handleReassignModalClose = () => {
        setReassignModal({
            isOpen: false,
            booking: null,
        });
    };

    const handleReassignConfirm = (data: {
        newTeacherId: number;
        notifyParties: boolean;
        adminNote: string;
    }) => {
        if (reassignModal.booking) {
            router.patch(route('admin.bookings.reassign', reassignModal.booking.id), {
                new_teacher_id: data.newTeacherId,
                notify_parties: data.notifyParties,
                admin_note: data.adminNote,
            }, {
                onSuccess: () => {
                    toast.success('Booking reassigned successfully');
                    handleReassignModalClose();
                    router.reload({ only: ['bookings'] });
                },
                onError: (errors) => {
                    toast.error('Failed to reassign booking. Please try again.');
                    console.error('Reassign errors:', errors);
                }
            });
        }
    };

    const handleReassignCancelBooking = () => {
        if (reassignModal.booking) {
            handleCancelClick(reassignModal.booking);
            handleReassignModalClose();
        }
    };

    const handleCancelFromDetails = () => {
        if (detailsModal.booking) {
            handleCancelClick(detailsModal.booking);
            handleDetailsModalClose();
        }
    };

    const handleSelectAll = (checked: boolean) => {
        if (checked) {
            setSelectedBookings(bookings.data.map(booking => booking.id));
        } else {
            setSelectedBookings([]);
        }
    };

    const handleSelectBooking = (bookingId: number, checked: boolean) => {
        if (checked) {
            setSelectedBookings(prev => [...prev, bookingId]);
        } else {
            setSelectedBookings(prev => prev.filter(id => id !== bookingId));
        }
    };

    const getStatusBadge = (status: string) => {
        const config = statusConfig[status as keyof typeof statusConfig];
        if (!config) return null;

        const Icon = config.icon;
        return (
            <Badge className={`${config.color} flex items-center gap-1`}>
                <Icon className="h-3 w-3" />
                {config.label}
            </Badge>
        );
    };

    const formatTime = (time: string) => {
        return format(new Date(`2000-01-01T${time}`), 'h:mm a');
    };

    const formatDate = (date: string) => {
        return format(new Date(date), 'MMM dd');
    };

    return (
        <AdminLayout pageTitle="Booking Management" showRightSidebar={false}>
            <Head title="Booking Overview" />
            <div onClick={handlePageClick}>

            <div className="py-2">

                {/* Breadcrumbs */}
                <Breadcrumbs
                    breadcrumbs={[
                        { title: 'Dashboard', href: route('admin.dashboard') },
                        { title: 'Manage Bookings', href: route('admin.bookings.index') }
                    ]}
                />
            </div>
            <div className="py-6">

                {/* Header */}
                <div className="mb-6">
                    <h1 className="text-2xl font-bold text-gray-900">Booking Overview</h1>
                </div>

                {/* Search and Filters */}
                <div className="mb-6">
                    <div className="flex flex-col gap-4">
                        {/* Search Bar */}
                        <div className="w-full">
                            <div className="relative">
                                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                                <Input
                                    placeholder="Search by Name / Email"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="pl-10 w-full rounded-full"
                                />
                            </div>
                        </div>
                        
                        {/* Filters Row */}
                        <div className="flex flex-col sm:flex-row gap-3">
                            <div className="flex flex-col sm:flex-row gap-3 flex-1">
                                <Select value={statusFilter} onValueChange={setStatusFilter}>
                                    <SelectTrigger className="w-full sm:w-48 rounded-full">
                                        <SelectValue placeholder="Select Status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {Object.entries(statusOptions).map(([value, label]) => (
                                            <SelectItem key={value} value={value}>
                                                {label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>

                                <Select value={subjectFilter} onValueChange={setSubjectFilter}>
                                    <SelectTrigger className="w-full sm:w-48 rounded-full">
                                        <SelectValue placeholder="Select Subject" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All Subjects</SelectItem>
                                        {subjects.map((subject) => (
                                            <SelectItem key={subject.id} value={subject.id.toString()}>
                                                {subject.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>

                                <Select value={dateFilter} onValueChange={setDateFilter}>
                                    <SelectTrigger className="w-full sm:w-32 rounded-full">
                                        <SelectValue placeholder="Date" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All Dates</SelectItem>
                                        <SelectItem value="today">Today</SelectItem>
                                        <SelectItem value="tomorrow">Tomorrow</SelectItem>
                                        <SelectItem value="this_week">This Week</SelectItem>
                                        <SelectItem value="next_week">Next Week</SelectItem>
                                        <SelectItem value="this_month">This Month</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <Button onClick={handleSearch} className="w-full sm:w-auto border border-green-600 text-teal-600 hover:bg-teal-50 rounded-full bg-transparent">
                                Search
                            </Button>
                        </div>
                    </div>
                </div>


                {/* Bookings Table */}
                <div className="bg-white rounded-lg border border-gray-200 overflow-auto scrollbar-hide">
                    <table className="w-full">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-6 py-3 text-left">
                                    <Checkbox
                                        checked={selectedBookings.length === bookings.data.length && bookings.data.length > 0}
                                        onCheckedChange={handleSelectAll}
                                    />
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Student Name
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Teacher
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Subject
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Time
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                            {bookings.data.map((booking) => (
                                <tr key={booking.id} className="hover:bg-gray-50">
                                    <td className="px-6 py-4">
                                        <Checkbox
                                            checked={selectedBookings.includes(booking.id)}
                                            onCheckedChange={(checked) => handleSelectBooking(booking.id, checked as boolean)}
                                        />
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {booking.student.name}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {booking.teacher.name}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {booking.subject?.template?.name || 'Unknown Subject'}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {formatDate(booking.booking_date)}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {formatTime(booking.start_time)}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        {getStatusBadge(booking.status)}
                                    </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <div onClick={(e) => e.stopPropagation()}>
                                                    <DropdownMenu 
                                                        open={openDropdown === booking.id}
                                                        onOpenChange={(open) => setOpenDropdown(open ? booking.id : null)}
                                                    >
                                                        <DropdownMenuTrigger asChild>
                                                            <Button variant="ghost" size="sm">
                                                                <MoreVertical className="h-4 w-4" />
                                                            </Button>
                                                        </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end" className="w-48">
                                                {booking.status === 'pending' && (
                                                    <DropdownMenuItem
                                                        onClick={() => handleApproveClick(booking)}
                                                        className="flex items-center justify-between"
                                                    >
                                                        <span>Approve Sessions</span>
                                                        <CheckCircle className="h-4 w-4 text-green-600" />
                                                    </DropdownMenuItem>
                                                )}
                                                
                                                <DropdownMenuItem
                                                    onClick={() => handleViewDetails(booking)}
                                                    className="flex items-center justify-between"
                                                >
                                                    <span>View Details</span>
                                                    <ClipboardList className="h-4 w-4" />
                                                </DropdownMenuItem>
                                                
                                                {!['completed', 'cancelled'].includes(booking.status) && (
                                                    <DropdownMenuItem
                                                        onClick={() => {
                                                            setRescheduleModal({
                                                                isOpen: true,
                                                                booking: booking,
                                                            });
                                                            setOpenDropdown(null);
                                                        }}
                                                        className="flex items-center justify-between "
                                                    >
                                                        <span>Reschedule</span>
                                                        <Pencil className="h-4 w-4" />
                                                    </DropdownMenuItem>
                                                )}
                                                
                                                <DropdownMenuItem
                                                    onClick={() => {
                                                        setReassignModal({
                                                            isOpen: true,
                                                            booking: booking,
                                                        });
                                                        setOpenDropdown(null);
                                                    }}
                                                    className="flex items-center justify-between"
                                                >
                                                    <span>Reassign Teacher</span>
                                                    <UserCheck className="h-4 w-4" />
                                                </DropdownMenuItem>
                                                
                                                <DropdownMenuItem
                                                    onClick={() => {
                                                        // TODO: Implement message functionality
                                                        toast.info('Message functionality coming soon');
                                                    }}
                                                    className="flex items-center justify-between"
                                                >
                                                    <span>Message</span>
                                                    <MessageSquare className="h-4 w-4" />
                                                </DropdownMenuItem>
                                                
                                                {!['completed', 'cancelled'].includes(booking.status) && (
                                                    <DropdownMenuItem
                                                        onClick={() => handleCancelClick(booking)}
                                                        className="flex items-center justify-between text-red-600"
                                                    >
                                                        <span>Cancel Sessions</span>
                                                        <XCircle className="h-4 w-4 text-red-600" />
                                                    </DropdownMenuItem>
                                                )}
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                                </div>
                                        </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                {/* Pagination */}
                {bookings.last_page > 1 && (
                    <div className="mt-6 flex items-center justify-between">
                        <div className="text-sm text-teal-700">
                            Showing {((bookings.current_page - 1) * bookings.per_page) + 1} to{' '}
                            {Math.min(bookings.current_page * bookings.per_page, bookings.total)} of{' '}
                            {bookings.total} results
                        </div>
                        <div className="flex gap-1">
                            {/* Previous Button */}
                            {bookings.current_page > 1 && (
                                <Button
                                    variant="outline"
                                    className="rounded-full"
                                    size="sm"
                                    onClick={() => router.visit(route('admin.bookings.index', { ...filters, page: bookings.current_page - 1 }))}
                                >
                                    Previous
                                </Button>
                            )}

                            {/* Page Numbers */}
                            {(() => {
                                const currentPage = bookings.current_page;
                                const lastPage = bookings.last_page;
                                const maxVisiblePages = 5;
                                
                                let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
                                let endPage = Math.min(lastPage, startPage + maxVisiblePages - 1);
                                
                                // Adjust start page if we're near the end
                                if (endPage - startPage + 1 < maxVisiblePages) {
                                    startPage = Math.max(1, endPage - maxVisiblePages + 1);
                                }

                                const pages = [];
                                
                                // First page and ellipsis
                                if (startPage > 1) {
                                    pages.push(
                                        <Button
                                            key={1}
                                            variant="outline"
                                            className="rounded-full"
                                            size="sm"
                                            onClick={() => router.visit(route('admin.bookings.index', { ...filters, page: 1 }))}
                                        >
                                            1
                                        </Button>
                                    );
                                    if (startPage > 2) {
                                        pages.push(
                                            <span key="ellipsis1" className="px-2 py-1 text-sm text-teal-500">
                                                ...
                                            </span>
                                        );
                                    }
                                }

                                // Page numbers
                                for (let i = startPage; i <= endPage; i++) {
                                    pages.push(
                                        <Button
                                            key={i}
                                            variant={i === currentPage ? "default" : "outline"}
                                            className="rounded-full "
                                            size="sm"
                                            onClick={() => router.visit(route('admin.bookings.index', { ...filters, page: i }))}
                                        >
                                            {i}
                                        </Button>
                                    );
                                }

                                // Last page and ellipsis
                                if (endPage < lastPage) {
                                    if (endPage < lastPage - 1) {
                                        pages.push(
                                            <span key="ellipsis2" className="px-2 py-1 text-sm text-teal-500">
                                                ...
                                            </span>
                                        );
                                    }
                                    pages.push(
                                        <Button
                                            key={lastPage}
                                            variant="outline"
                                            className="rounded-full"
                                            size="sm"
                                            onClick={() => router.visit(route('admin.bookings.index', { ...filters, page: lastPage }))}
                                        >
                                            {lastPage}
                                        </Button>
                                    );
                                }

                                return pages;
                            })()}

                            {/* Next Button */}
                            {bookings.current_page < bookings.last_page && (
                                <Button
                                    variant="outline"
                                    className="rounded-full"
                                    size="sm"
                                    onClick={() => router.visit(route('admin.bookings.index', { ...filters, page: bookings.current_page + 1 }))}
                                >
                                    Next
                                </Button>
                            )}
                        </div>
                    </div>
                )}

                {/* Approval Dialog */}
                {approvalDialog.booking && (
                    <BookingApprovalDialog
                        isOpen={approvalDialog.isOpen}
                        onClose={handleApprovalCancel}
                        onConfirm={handleApprovalConfirm}
                        onOpenChange={handleApprovalDialogClose}
                        booking={approvalDialog.booking}
                    />
                )}

                {/* Cancel Dialog */}
                {cancelDialog.booking && (
                    <BookingCancelDialog
                        isOpen={cancelDialog.isOpen}
                        onClose={handleCancelDialogClose}
                        onConfirm={handleCancelConfirm}
                        onOpenChange={handleCancelDialogStateChange}
                        booking={cancelDialog.booking}
                    />
                )}

                {/* Booking Details Modal */}
                {detailsModal.booking && (
                    <BookingDetailsModal
                        isOpen={detailsModal.isOpen}
                        onClose={handleDetailsModalClose}
                        booking={{
                            ...detailsModal.booking,
                            teacher: {
                                ...detailsModal.booking.teacher,
                                is_available: !detailsModal.booking.teacher.teacherAvailabilities?.[0]?.holiday_mode
                            }
                        }}
                        onReschedule={handleReschedule}
                        onReassign={handleReassign}
                        onCancel={handleCancelFromDetails}
                    />
                )}

                    {/* Reschedule Modal */}
                    {rescheduleModal.booking && (
                        <BookingRescheduleModal
                            isOpen={rescheduleModal.isOpen}
                            onClose={handleRescheduleModalClose}
                            onConfirm={handleRescheduleConfirm}
                            booking={rescheduleModal.booking}
                        />
                    )}

                    {/* Reassign Modal */}
                    {reassignModal.booking && (
                        <BookingReassignModal
                            isOpen={reassignModal.isOpen}
                            onClose={handleReassignModalClose}
                            onConfirm={handleReassignConfirm}
                            onCancelBooking={handleReassignCancelBooking}
                            booking={reassignModal.booking}
                        />
                    )}
            </div>
            </div>
        </AdminLayout>
    );
}
