import { Head, Link, router } from "@inertiajs/react";
import { Button } from "@/components/ui/button";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import { Input } from "@/components/ui/input";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { MoreVertical, Eye, Search, Calendar, Copy, XCircle, Link as LinkIcon } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { useState } from "react";
import { Pagination } from "@/components/ui/pagination";
import { debounce } from "lodash";
import { toast } from "sonner";
import AdminLayout from "@/layouts/admin/admin-layout";
import { Breadcrumbs } from "@/components/breadcrumbs";
import { SendIcon } from "@/components/icons/send-icon";
import { VerifiedIcon } from "@/components/icons/verified-icon";
import ScheduleVerificationModal from "./components/ScheduleVerificationModal";
import VerificationCallDetailsModal from "./components/VerificationCallDetailsModal";

interface VerificationRequest {
    id: string;
    status: 'pending' | 'verified' | 'rejected' | 'live_video';
    docs_status: 'pending' | 'verified' | 'rejected';
    video_status: 'not_scheduled' | 'scheduled' | 'completed' | 'passed' | 'failed';
    submitted_at: string;
    can_approve: boolean;
    approval_block_reason?: string;
    status_suggestion?: 'pending' | 'verified' | 'rejected' | 'live_video';
    needs_status_review?: boolean;
    calculated_docs_status?: 'pending' | 'verified' | 'rejected';
    teacher_profile?: {
        user?: {
            id: number;
            name: string;
            email: string;
            avatar?: string;
        };
        documents?: Array<{
            id: number;
            type: string;
            name: string;
            status: string;
        }>;
    };
    calls?: Array<{
        id: number | string;
        scheduled_at: string;
        platform: string;
        meeting_link?: string;
        notes?: string;
        status: string;
    }>;
}

interface VerificationIndexProps {
    verificationRequests: {
        data: VerificationRequest[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters: {
        status: string;
        search?: string;
        date?: string;
    };
    stats: {
        pending: number;
        verified: number;
        rejected: number;
        live_video: number;
    };
}

export default function VerificationIndex({
    verificationRequests,
    filters,
}: VerificationIndexProps) {
    const [search, setSearch] = useState(filters.search || '');
    const [statusFilter, setStatusFilter] = useState(filters.status || 'all');
    const [dateFilter, setDateFilter] = useState(filters.date || '');
    
    // Schedule verification modal state
    const [scheduleModalOpen, setScheduleModalOpen] = useState(false);
    const [selectedVerificationRequest, setSelectedVerificationRequest] = useState<VerificationRequest | null>(null);
    
    // Call details modal state
    const [callDetailsModalOpen, setCallDetailsModalOpen] = useState(false);
    const [selectedCallDetails, setSelectedCallDetails] = useState<VerificationRequest | null>(null);

    const handleSearch = debounce((value: string) => {
        setSearch(value);
        router.get(
            route("admin.verification.index"),
            { search: value, status: statusFilter, date: dateFilter },
            { preserveState: true, replace: true }
        );
    }, 300);

    const handleFilter = (
        filterType: "status" | "date",
        value: string
    ) => {
        if (filterType === "status") setStatusFilter(value);
        if (filterType === "date") setDateFilter(value);

        const params = {
            search,
            status: filterType === "status" ? value : statusFilter,
            date: filterType === "date" ? value : dateFilter
        };

        router.get(route("admin.verification.index"), params, {
            preserveState: true,
            replace: true,
        });
    };

    const openScheduleModal = (request: VerificationRequest) => {
        setSelectedVerificationRequest(request);
        setScheduleModalOpen(true);
    };

    const closeScheduleModal = () => {
        setScheduleModalOpen(false);
        setSelectedVerificationRequest(null);
    };

    const openCallDetailsModal = (request: VerificationRequest) => {
        setSelectedCallDetails(request);
        setCallDetailsModalOpen(true);
    };



    const getStatusBadge = (status: string) => {
        switch (status) {
            case "pending":
                return (
                    <Badge variant="outline" className="text-yellow-600 border-yellow-600 bg-yellow-50">
                        Pending
                    </Badge>
                );
            case "verified":
                return (
                    <Badge variant="outline" className="text-green-600 border-green-600 bg-green-50">
                        Verified
                    </Badge>
                );
            case "rejected":
                return (
                    <Badge variant="outline" className="text-red-600 border-red-600 bg-red-50">
                        Rejected
                    </Badge>
                );
            case "live_video":
                return (
                    <Badge variant="outline" className="text-blue-600 border-blue-600 bg-blue-50">
                        Live Video
                    </Badge>
                );
            // Handle unified status format from TeacherStatusService
            case "Approved":
                return (
                    <Badge variant="outline" className="text-green-600 border-green-600 bg-green-50">
                        Verified
                    </Badge>
                );
            case "Pending":
                return (
                    <Badge variant="outline" className="text-yellow-600 border-yellow-600 bg-yellow-50">
                        Pending
                    </Badge>
                );
            case "Inactive":
                return (
                    <Badge variant="outline" className="text-red-600 border-red-600 bg-red-50">
                        Rejected
                    </Badge>
                );
            default:
                return (
                    <Badge variant="outline" className="text-gray-600 border-gray-600 bg-gray-50">
                        {status}
                    </Badge>
                );
        }
    };

    const getDocsStatusText = (request: VerificationRequest) => {
        // Use calculated docs status from backend if available
        if (request.calculated_docs_status) {
            const statusMap: Record<string, string> = {
                'verified': '✅ Verified',
                'rejected': '❌ Rejected', 
                'pending': '⏳ Pending'
            };
            return statusMap[request.calculated_docs_status] || 'Unknown';
        }
        
        // Fallback to document count (legacy)
        const documents = request.teacher_profile?.documents || [];
        if (documents.length === 0) return 'No Files';
        return `${documents.length} Files`;
    };

    const getVideoStatusText = (videoStatus: string) => {
        const statusMap = {
            'not_scheduled': 'Not Scheduled',
            'scheduled': 'Scheduled',
            'completed': 'Completed',
            'passed': 'Passed',
            'failed': 'Failed',
        };
        return statusMap[videoStatus as keyof typeof statusMap] || videoStatus;
    };

    const getInitials = (name: string) => {
        return name
            .split(" ")
            .map((n) => n[0])
            .join("")
            .toUpperCase();
    };

    const breadcrumbs = [
        { title: "Dashboard", href: route("admin.dashboard") },
        { title: "Verification Requests", href: route("admin.verification.index") }
    ];

    return (
        <AdminLayout pageTitle="Verification Requests" showRightSidebar={false}>
            <Head title="Verification Requests" />
            <div className="py-6">
                <div className="mb-6">
                    <Breadcrumbs breadcrumbs={breadcrumbs} />
                </div>

                <div className="mb-6">
                    <h1 className="text-2xl font-semibold text-gray-900 mb-2">Verification Requests</h1>
                    <p className="text-gray-600">
                        Review teacher documents and conduct live video verification before approving full access to the platform.
                    </p>
                </div>

                <div className="mb-6">
                    <div className="flex flex-col md:flex-row gap-4 mb-4">
                        <div className="flex-1">
                            <div className="relative w-auto">
                                <Input
                                    placeholder="Search by Name/Email"
                                    value={search}
                                    onChange={(e) => {
                                        setSearch(e.target.value);
                                        handleSearch(e.target.value);
                                    }}
                                    className="pl-10 border rounded-full h-10"
                                />
                                <div className="absolute left-3 top-3 text-gray-400">
                                    <Search size={20} />
                                </div>
                            </div>
                        </div>
                        <div className="flex gap-2">
                            <Select
                                value={statusFilter}
                                onValueChange={(value) => handleFilter("status", value)}
                            >
                                <SelectTrigger className="w-[140px] border rounded-full">
                                    <SelectValue placeholder="Select Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All</SelectItem>
                                    <SelectItem value="pending">Pending</SelectItem>
                                    <SelectItem value="verified">Verified</SelectItem>
                                    <SelectItem value="live_video">Live Video</SelectItem>
                                    <SelectItem value="rejected">Rejected</SelectItem>
                                </SelectContent>
                            </Select>

                            <Input
                                type="date"
                                value={dateFilter}
                                onChange={(e) => handleFilter("date", e.target.value)}
                                className="w-auto border rounded-full h-11"
                                placeholder="mm/dd/yyyy"
                            />

                            <Button
                                type="button"
                                onClick={() => {
                                    router.get(route("admin.verification.index"), {
                                        search,
                                        status: statusFilter,
                                        date: dateFilter
                                    }, { preserveState: true, replace: true });
                                }}
                                className="border border-[#338078] text-[#338078] bg-transparent hover:bg-transparent rounded-full px-6"
                            >
                                Search
                            </Button>
                        </div>
                    </div>
                </div>

                <div className="bg-white rounded-md">
                    <div className="p-4 border-b">
                        <h2 className="text-lg font-semibold">Verification Request Table</h2>
                    </div>
                    <Table>
                        <TableHeader className="bg-gray-50">
                            <TableRow>
                                <TableHead className="w-12">
                                    <input type="checkbox" className="checkbox" />
                                </TableHead>
                                <TableHead>Profile Photo</TableHead>
                                <TableHead>Name</TableHead>
                                <TableHead>Email</TableHead>
                                <TableHead>Docs Status</TableHead>
                                <TableHead>Video Status</TableHead>
                                <TableHead>Submitted On</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="w-12">Action</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {verificationRequests.data.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={9}
                                        className="text-center py-8 text-muted-foreground"
                                    >
                                        No verification requests found
                                    </TableCell>
                                </TableRow>
                            ) : (
                                verificationRequests.data.map((request) => (
                                    <TableRow key={request.id}>
                                        <TableCell>
                                            <input type="checkbox" className="checkbox" />
                                        </TableCell>
                                        <TableCell>
                                            <Avatar className="w-10 h-10 border">
                                                <AvatarImage src={request.teacher_profile?.user?.avatar || ""} />
                                                <AvatarFallback className="bg-gray-200 text-gray-600">
                                                    {getInitials(request.teacher_profile?.user?.name || 'NA')}
                                                </AvatarFallback>
                                            </Avatar>
                                        </TableCell>
                                        <TableCell className="font-medium">
                                            {request.teacher_profile?.user?.name || 'Name not available'}
                                        </TableCell>
                                        <TableCell>
                                            {request.teacher_profile?.user?.email || 'Email not available'}
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant="outline">
                                                {getDocsStatusText(request)}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant="outline">
                                                {getVideoStatusText(request.video_status)}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            {request.submitted_at ?
                                                new Date(request.submitted_at).toLocaleDateString('en-US', {
                                                    month: 'short',
                                                    day: '2-digit',
                                                    year: 'numeric'
                                                }) :
                                                'N/A'
                                            }
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex flex-col gap-1">
                                                <div className="flex items-center gap-2">
                                                    {getStatusBadge(request.status_suggestion || request.status)}
                                                    {request.needs_status_review && (
                                                        <span className="text-xs text-amber-600 bg-amber-50 px-2 py-1 rounded">
                                                            ⚠️ Needs Review
                                                        </span>
                                                    )}
                                                </div>
                                                {!request.can_approve && request.approval_block_reason && (
                                                    <div className="text-xs text-red-600 bg-red-50 px-2 py-1 rounded">
                                                        ⚠️ {request.approval_block_reason}
                                                    </div>
                                                )}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <Button
                                                        variant="ghost"
                                                        className="h-8 w-8 p-0"
                                                        aria-label="Open menu"
                                                    >
                                                        <MoreVertical className="h-4 w-4" />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end" className="w-56 p-0 border rounded-lg shadow-lg">
                                                    <DropdownMenuItem
                                                        className={`flex items-center justify-between px-4 py-3 cursor-pointer w-full ${request.can_approve
                                                                ? 'hover:bg-gray-50'
                                                                : 'opacity-50 cursor-not-allowed'
                                                            }`}
                                                        onClick={() => {
                                                            if (!request.can_approve) {
                                                                toast.error(request.approval_block_reason || 'Cannot approve this teacher');
                                                                return;
                                                            }
                                                            if (confirm('Are you sure you want to verify this teacher?')) {
                                                                router.patch(route('admin.verification.approve', request.id), {}, {
                                                                    onSuccess: () => {
                                                                        toast.success(
                                                                            `${request.teacher_profile?.user?.name || 'Teacher'} has been verified and can now start teaching.`
                                                                        );
                                                                    },
                                                                    onError: () => {
                                                                        toast.error('There was an error verifying the teacher. Please try again.');
                                                                    }
                                                                });
                                                            }
                                                        }}
                                                        title={request.approval_block_reason || 'Approve Teacher'}
                                                    >
                                                        <span>{request.can_approve ? 'Verify' : 'Cannot Verify'}</span>
                                                        <VerifiedIcon className={`mr-2 h-4 w-4 ${request.can_approve ? 'text-green-600' : 'text-gray-400'
                                                            }`} />
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem asChild className="px-0 py-0">
                                                        <Link
                                                            href={route("admin.verification.show", request.id)}
                                                            className="flex items-center justify-between px-4 py-3 hover:bg-gray-50 cursor-pointer w-full"
                                                        >
                                                            <span>View</span>
                                                            <Eye className="mr-2 h-4 w-4 text-gray-600" />
                                                        </Link>
                                                    </DropdownMenuItem>
                                                    {request.video_status === 'not_scheduled' ? (
                                                        <DropdownMenuItem
                                                            className="flex items-center justify-between px-4 py-3 hover:bg-gray-50 cursor-pointer w-full"
                                                            onClick={() => {
                                                                openScheduleModal(request);
                                                            }}
                                                        >
                                                            <span>Schedule Call</span>
                                                            <SendIcon className="mr-2 h-4 w-4 text-[#338078]" />
                                                        </DropdownMenuItem>
                                                    ) : (
                                                        <DropdownMenuItem
                                                            className="flex items-center justify-between px-4 py-3 hover:bg-gray-50 cursor-pointer w-full"
                                                            onClick={() => {
                                                                openCallDetailsModal(request);
                                                            }}
                                                        >
                                                            <span>View Schedule Details</span>
                                                            <Calendar className="mr-2 h-4 w-4 text-[#338078]" />
                                                        </DropdownMenuItem>
                                                    )}
                                                    <DropdownMenuItem
                                                        className="flex items-center justify-between px-4 py-3 hover:bg-gray-50 cursor-pointer w-full"
                                                        onClick={async () => {
                                                            try {
                                                                const verificationUrl = route('admin.verification.show', request.id);
                                                                await navigator.clipboard.writeText(verificationUrl);
                                                                toast.success('Verification URL copied to clipboard');
                                                            } catch {
                                                                toast.error('Failed to copy URL');
                                                            }
                                                        }}
                                                    >
                                                        <span>Copy Verification URL</span>
                                                        <LinkIcon className="mr-2 h-4 w-4 text-gray-600" />
                                                    </DropdownMenuItem>
                                                    {request.calls && request.calls.length > 0 && request.calls[0].meeting_link && (
                                                        <DropdownMenuItem
                                                            className="flex items-center justify-between px-4 py-3 hover:bg-gray-50 cursor-pointer w-full"
                                                            onClick={async () => {
                                                                try {
                                                                    const meetingLink = request.calls?.[0]?.meeting_link;
                                                                    if (meetingLink) {
                                                                        await navigator.clipboard.writeText(meetingLink);
                                                                        toast.success('Meeting link copied to clipboard');
                                                                    } else {
                                                                        toast.error('No meeting link available');
                                                                    }
                                                                } catch {
                                                                    toast.error('Failed to copy meeting link');
                                                                }
                                                            }}
                                                        >
                                                            <span>Copy Meeting Link</span>
                                                            <Copy className="mr-2 h-4 w-4 text-gray-600" />
                                                        </DropdownMenuItem>
                                                    )}
                                                    <DropdownMenuItem
                                                        className={`flex items-center justify-between px-4 w-full ${
                                                            request.status === 'rejected' || request.status === 'verified' 
                                                                ? 'text-gray-400 cursor-not-allowed' 
                                                                : 'hover:bg-gray-50'
                                                        }`}
                                                        onClick={() => {
                                                            if (request.status === 'rejected') {
                                                                toast.error('This verification request has already been rejected.');
                                                                return;
                                                            }
                                                            if (request.status === 'verified') {
                                                                toast.error('This verification request has already been verified.');
                                                                return;
                                                            }
                                                            
                                                            const reason = prompt('Please provide a rejection reason:');
                                                            if (reason && reason.trim()) {
                                                                router.patch(route('admin.verification.reject', request.id), {
                                                                    rejection_reason: reason.trim()
                                                                }, {
                                                                    onSuccess: () => {
                                                                        toast.error(
                                                                            `${request.teacher_profile?.user?.name || 'Teacher'} has been rejected. Reason: ${reason.trim()}`
                                                                        );
                                                                    },
                                                                    onError: () => {
                                                                        toast.error('There was an error rejecting the teacher. Please try again.');
                                                                    }
                                                                });
                                                            }
                                                        }}
                                                        disabled={request.status === 'rejected' || request.status === 'verified'}
                                                    >
                                                        <span>{request.status === 'rejected' ? 'Already Rejected' : 'Reject'}</span>
                                                        <XCircle className={`mr-2 h-4 w-4 ${
                                                            request.status === 'rejected' || request.status === 'verified' 
                                                                ? 'text-gray-400' 
                                                                : 'text-red-600'
                                                        }`} />
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </div>

                {verificationRequests.last_page > 1 && (
                    <Pagination
                        className="mt-4 flex justify-end"
                        currentPage={verificationRequests.current_page}
                        totalPages={verificationRequests.last_page}
                        onPageChange={(page) =>
                            router.get(
                                route("admin.verification.index", { page }),
                                { search, status: statusFilter, date: dateFilter },
                                { preserveState: true }
                            )
                        }
                    />
                )}
            </div>

            {/* Schedule Verification Modal */}
            {selectedVerificationRequest && (
                <ScheduleVerificationModal
                    isOpen={scheduleModalOpen}
                    onOpenChange={setScheduleModalOpen}
                    verificationRequestId={selectedVerificationRequest.id}
                    verificationStatus={selectedVerificationRequest.status}
                    onScheduled={() => {
                        closeScheduleModal();
                        // Refresh the page to update the dropdown options
                        router.reload({ only: ['verificationRequests'] });
                    }}
                />
            )}

            {/* Call Details Modal */}
            {selectedCallDetails && (
                <VerificationCallDetailsModal
                    isOpen={callDetailsModalOpen}
                    onOpenChange={setCallDetailsModalOpen}
                    call={selectedCallDetails.calls && selectedCallDetails.calls.length > 0 ? selectedCallDetails.calls[0] : null}
                    verificationRequestId={selectedCallDetails.id}
                    videoStatus={selectedCallDetails.video_status}
                    requestStatus={selectedCallDetails.status}
                />
            )}
        </AdminLayout>
    );
}
