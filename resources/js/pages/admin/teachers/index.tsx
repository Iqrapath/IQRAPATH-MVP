import { Head, Link, router, usePage } from "@inertiajs/react";
import { useState, FormEvent, useCallback } from "react";
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
import { MoreVertical, Check, X, Edit, Eye, BarChart, Search } from "lucide-react";
import { VerifiedIcon } from "@/components/icons/verified-icon";
import { Badge } from "@/components/ui/badge";
import { Pagination } from "@/components/ui/pagination";
import { debounce } from "lodash";
import AdminLayout from "@/layouts/admin/admin-layout";
import { Breadcrumb, BreadcrumbList, BreadcrumbItem, BreadcrumbLink } from "@/components/ui/breadcrumb";
import { Breadcrumbs } from "@/components/breadcrumbs";
import { CancelIcon } from "@/components/icons/cancel-icon";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Textarea } from "@/components/ui/textarea";
import { Label } from "@/components/ui/label";
import { toast } from "sonner";
import { useTeacherStatusUpdates } from "@/hooks/useTeacherStatusUpdates";

interface Teacher {
  id: number;
  name: string;
  email: string;
  avatar: string | null;
  subjects: string;
  rating: any; // Allow any type since we handle it safely
  classes_held: number;
  status: string;
  can_approve?: boolean;
  approval_block_reason?: string | null;
  verification_request_id?: number | null;
  last_updated?: string;
  // Account management fields
  account_status: string;
  account_status_display: string;
  account_status_color: string;
  suspended_at?: string | null;
  suspension_reason?: string | null;
  is_deleted?: boolean;
}

interface TeachersIndexProps {
  teachers: {
    data: Teacher[];
    links: any[];
    current_page: number;
    last_page: number;
  };
  filters: {
    search: string;
    status: string;
    subject: string;
    rating: string;
  };
  subjects: string[];
}

export default function TeachersIndex({
  teachers,
  filters,
  subjects,
}: TeachersIndexProps) {
  const [search, setSearch] = useState(filters.search);
  const [status, setStatus] = useState(filters.status);
  const [subject, setSubject] = useState(filters.subject);
  const [rating, setRating] = useState(filters.rating);
  
  // Reject modal state
  const [rejectModalOpen, setRejectModalOpen] = useState(false);
  const [selectedTeacher, setSelectedTeacher] = useState<Teacher | null>(null);
  const [rejectionReason, setRejectionReason] = useState("");
  const [isRejecting, setIsRejecting] = useState(false);

  // Account management modal states
  const [accountModalOpen, setAccountModalOpen] = useState(false);
  const [accountAction, setAccountAction] = useState<'suspend' | 'unsuspend' | 'delete' | 'restore' | 'force-delete'>('suspend');
  const [accountReason, setAccountReason] = useState("");
  const [isProcessingAccount, setIsProcessingAccount] = useState(false);

  // Bulk operations state
  const [selectedTeachers, setSelectedTeachers] = useState<number[]>([]);
  const [bulkModalOpen, setBulkModalOpen] = useState(false);
  const [bulkAction, setBulkAction] = useState<'suspend' | 'unsuspend' | 'delete' | 'restore'>('suspend');
  const [bulkReason, setBulkReason] = useState("");
  const [isProcessingBulk, setIsProcessingBulk] = useState(false);

  // Real-time updates
  const { isConnected, connectionError } = useTeacherStatusUpdates({
    onStatusUpdate: useCallback((update: any) => {
      // Update the teacher in the list if it exists
      const teacherIndex = teachers.data.findIndex(t => t.id === update.teacher_id);
      if (teacherIndex !== -1) {
        // Update the teacher data
        teachers.data[teacherIndex] = {
          ...teachers.data[teacherIndex],
          status: update.status,
          can_approve: update.can_approve,
          approval_block_reason: update.approval_block_reason,
          verification_request_id: update.verification_request_id,
          last_updated: update.last_updated,
        };
      }
    }, [teachers.data]),
    onTeacherApproved: useCallback((teacherId: number) => {
      // Refresh the page to get updated data
      router.reload({ only: ['teachers'] });
    }, []),
    onTeacherRejected: useCallback((teacherId: number) => {
      // Refresh the page to get updated data
      router.reload({ only: ['teachers'] });
    }, []),
  });

  const handleSearch = debounce((value: string) => {
    router.get(
      route("admin.teachers.index"),
      { search: value, status, subject, rating },
      { preserveState: true, replace: true }
    );
  }, 300);

  const handleFilter = (
    filterType: "status" | "subject" | "rating",
    value: string
  ) => {
    const params = { search, status, subject, rating };
    params[filterType] = value;

    if (filterType === "status") setStatus(value);
    if (filterType === "subject") setSubject(value);
    if (filterType === "rating") setRating(value);

    router.get(route("admin.teachers.index"), params, {
      preserveState: true,
      replace: true,
    });
  };

  const handleSearchSubmit = (e: FormEvent) => {
    e.preventDefault();
    router.get(
      route("admin.teachers.index"),
      { search, status, subject, rating },
      { preserveState: true }
    );
  };

  const getStatusBadge = (status: string) => {
    switch (status) {
      case "Approved":
        return (
          <span className="text-green-500 font-medium">
            Approved
          </span>
        );
      case "Pending":
        return (
          <span className="text-yellow-500 font-medium">
            Pending
          </span>
        );
      case "Inactive":
        return (
          <span className="text-red-500 font-medium">
            Inactive
          </span>
        );
      // Handle legacy status format from verification requests
      case "verified":
        return (
          <span className="text-green-500 font-medium">
            Approved
          </span>
        );
      case "pending":
        return (
          <span className="text-yellow-500 font-medium">
            Pending
          </span>
        );
      case "rejected":
        return (
          <span className="text-red-500 font-medium">
            Inactive
          </span>
        );
      default:
        return (
          <span className="text-red-500 font-medium">
            Inactive
          </span>
        );
    }
  };

  const getInitials = (name: string) => {
    return name
      .split(" ")
      .map((n) => n[0])
      .join("")
      .toUpperCase();
  };

  const formatRating = (rating: number | null) => {
    if (!rating || rating === 0 || typeof rating !== 'number') return 'N/A';
    return rating.toFixed(1);
  };

  const getRatingStars = (rating: number | null) => {
    if (!rating || rating === 0 || typeof rating !== 'number') return 0;
    return Math.round(rating);
  };

  const safeRating = (rating: any): number | null => {
    if (rating === null || rating === undefined) return null;
    if (typeof rating === 'number') return rating;
    if (typeof rating === 'string') {
      const parsed = parseFloat(rating);
      return isNaN(parsed) ? null : parsed;
    }
    return null;
  };

  const formatSubjects = (subjects: string): string => {
    if (!subjects) return '';
    
    const subjectList = subjects.split(', ');
    if (subjectList.length <= 2) {
      return subjects;
    }
    
    return `${subjectList[0]}, ${subjectList[1]}, ...`;
  };

  const handleRejectClick = (teacher: Teacher) => {
    setSelectedTeacher(teacher);
    setRejectionReason("");
    setRejectModalOpen(true);
  };

  const handleRejectSubmit = async () => {
    if (!selectedTeacher || !rejectionReason.trim()) {
      toast.error("Please provide a reason for rejection");
      return;
    }

    setIsRejecting(true);
    try {
      router.patch(
        route("admin.teachers.reject", selectedTeacher.id),
        { rejection_reason: rejectionReason.trim() },
        {
          onSuccess: () => {
            toast.success("Teacher rejected successfully");
            setRejectModalOpen(false);
            setSelectedTeacher(null);
            setRejectionReason("");
          },
          onError: (errors) => {
            toast.error("Failed to reject teacher");
            console.error("Rejection errors:", errors);
          },
          onFinish: () => {
            setIsRejecting(false);
          }
        }
      );
    } catch (error) {
      toast.error("An error occurred while rejecting the teacher");
      setIsRejecting(false);
    }
  };

  const handleRejectCancel = () => {
    setRejectModalOpen(false);
    setSelectedTeacher(null);
    setRejectionReason("");
  };

  // Account management functions
  const openAccountModal = (teacher: Teacher, action: 'suspend' | 'unsuspend' | 'delete' | 'restore' | 'force-delete') => {
    setSelectedTeacher(teacher);
    setAccountAction(action);
    setAccountReason("");
    setAccountModalOpen(true);
  };

  const handleAccountAction = async () => {
    if (!selectedTeacher) return;

    setIsProcessingAccount(true);
    try {
      const endpoint = `/admin/user-management/${selectedTeacher.id}/${accountAction}`;
      
      const response = await fetch(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ reason: accountReason }),
      });

      const data = await response.json();

      if (data.success) {
        toast.success(data.message);
        setAccountModalOpen(false);
        setSelectedTeacher(null);
        setAccountReason("");
        // Refresh the page to get updated data
        window.location.reload();
      } else {
        toast.error(data.message || `Failed to ${accountAction} teacher account`);
      }
    } catch (error) {
      toast.error(`Failed to ${accountAction} teacher account`);
    } finally {
      setIsProcessingAccount(false);
    }
  };

  const cancelAccountModal = () => {
    setAccountModalOpen(false);
    setSelectedTeacher(null);
    setAccountReason("");
  };

  // Bulk operations
  const toggleTeacherSelection = (teacherId: number) => {
    setSelectedTeachers(prev => 
      prev.includes(teacherId) 
        ? prev.filter(id => id !== teacherId)
        : [...prev, teacherId]
    );
  };

  const selectAllTeachers = () => {
    setSelectedTeachers(teachers.data.map(teacher => teacher.id));
  };

  const clearSelection = () => {
    setSelectedTeachers([]);
  };

  const openBulkModal = (action: 'suspend' | 'unsuspend' | 'delete' | 'restore') => {
    if (selectedTeachers.length === 0) {
      toast.error("Please select teachers first");
      return;
    }
    setBulkAction(action);
    setBulkReason("");
    setBulkModalOpen(true);
  };

  const handleBulkAction = async () => {
    if (selectedTeachers.length === 0) return;

    setIsProcessingBulk(true);
    try {
      const response = await fetch('/admin/user-management/bulk-action', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({
          action: bulkAction,
          user_ids: selectedTeachers,
          reason: bulkReason
        }),
      });

      const data = await response.json();

      if (data.success) {
        toast.success(data.message);
        setBulkModalOpen(false);
        setSelectedTeachers([]);
        setBulkReason("");
        // Refresh the page to get updated data
        window.location.reload();
      } else {
        toast.error(data.message || `Failed to perform bulk ${bulkAction}`);
      }
    } catch (error) {
      toast.error(`Failed to perform bulk ${bulkAction}`);
    } finally {
      setIsProcessingBulk(false);
    }
  };

  const cancelBulkModal = () => {
    setBulkModalOpen(false);
    setBulkReason("");
  };

  const breadcrumbs = [
    { title: "Dashboard", href: route("admin.dashboard") },
    { title: "Teacher Management", href: route("admin.teachers.index") }
  ];

  return (
    <AdminLayout pageTitle="Teacher Management" showRightSidebar={false}>
      <Head title="Teacher Management" />
      <div className="py-6">
        <Breadcrumbs breadcrumbs={breadcrumbs} />
        <div className="flex justify-end mb-6">
          <Button className="bg-teal-600 hover:bg-teal-700" asChild>
            <Link href={route("admin.teachers.create")}>Add New Teachers</Link>
          </Button>
        </div>
        <div className="mb-6">
          <div className="flex flex-col md:flex-row gap-4 mb-4">
            <div className="flex-1">
              <div className="relative">
                <Input
                  placeholder="Search by Name / Email"
                  value={search}
                  onChange={(e) => {
                    setSearch(e.target.value);
                    handleSearch(e.target.value);
                  }}
                  className="pl-10 border rounded-full h-11"
                />
                <div className="absolute left-3 top-3 text-gray-400">
                  <Search size={18} />
                </div>
              </div>
            </div>
            <div className="flex gap-2">
              <Select
                value={status}
                onValueChange={(value) => handleFilter("status", value)}
              >
                <SelectTrigger className="w-[140px] border rounded-full">
                  <SelectValue placeholder="Select Status" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Statuses</SelectItem>
                  <SelectItem value="verified">Approved</SelectItem>
                  <SelectItem value="pending">Pending</SelectItem>
                  <SelectItem value="inactive">Inactive</SelectItem>
                </SelectContent>
              </Select>
              
              <Select
                value={subject}
                onValueChange={(value) => handleFilter("subject", value)}
              >
                <SelectTrigger className="w-[140px] border rounded-full">
                  <SelectValue placeholder="Select Subject" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Subjects</SelectItem>
                  {subjects.map((subj) => (
                    <SelectItem key={subj} value={subj}>
                      {subj}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>

              <Select
                value={rating}
                onValueChange={(value) => handleFilter("rating", value)}
              >
                <SelectTrigger className="w-[140px] border rounded-full">
                  <SelectValue placeholder="Rating" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Ratings</SelectItem>
                  <SelectItem value="5">5.0 Stars</SelectItem>
                  <SelectItem value="4.5">4.5+ Stars</SelectItem>
                  <SelectItem value="4">4.0+ Stars</SelectItem>
                  <SelectItem value="3.5">3.5+ Stars</SelectItem>
                  <SelectItem value="3">3.0+ Stars</SelectItem>
                </SelectContent>
              </Select>

              <Button
                type="button"
                onClick={() => {
                  router.get(route("admin.teachers.index"));
                  setSearch("");
                  setStatus("all");
                  setSubject("all");
                  setRating("all");
                }}
                className="bg-teal-600 hover:bg-teal-700 rounded-full"
              >
                Search
              </Button>
            </div>
          </div>
        </div>

        {/* Bulk Actions */}
        {selectedTeachers.length > 0 && (
          <div className="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-4">
                <span className="text-sm font-medium text-blue-900">
                  {selectedTeachers.length} teacher(s) selected
                </span>
                <div className="flex gap-2">
                  <Button
                    size="sm"
                    variant="outline"
                    onClick={() => openBulkModal('suspend')}
                    className="text-orange-600 border-orange-200 hover:bg-orange-50"
                  >
                    Suspend
                  </Button>
                  <Button
                    size="sm"
                    variant="outline"
                    onClick={() => openBulkModal('unsuspend')}
                    className="text-yellow-600 border-yellow-200 hover:bg-yellow-50"
                  >
                    Unsuspend
                  </Button>
                  <Button
                    size="sm"
                    variant="outline"
                    onClick={() => openBulkModal('delete')}
                    className="text-red-600 border-red-200 hover:bg-red-50"
                  >
                    Delete
                  </Button>
                  <Button
                    size="sm"
                    variant="outline"
                    onClick={() => openBulkModal('restore')}
                    className="text-green-600 border-green-200 hover:bg-green-50"
                  >
                    Restore
                  </Button>
                </div>
              </div>
              <Button
                size="sm"
                variant="ghost"
                onClick={clearSelection}
                className="text-gray-600"
              >
                Clear Selection
              </Button>
            </div>
          </div>
        )}

        <div className="bg-white rounded-md">
          <Table>
            <TableHeader className="bg-gray-50">
              <TableRow>
                <TableHead className="w-12">
                  <input 
                    type="checkbox" 
                    className="checkbox" 
                    checked={selectedTeachers.length === teachers.data.length && teachers.data.length > 0}
                    onChange={selectedTeachers.length === teachers.data.length ? clearSelection : selectAllTeachers}
                  />
                </TableHead>
                <TableHead>Profile</TableHead>
                <TableHead>Teacher's Name</TableHead>
                <TableHead>Email</TableHead>
                <TableHead>Subject(s)</TableHead>
                <TableHead>Rating</TableHead>
                <TableHead>Classes Held</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Account Status</TableHead>
                <TableHead className="w-12">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {teachers.data.length === 0 ? (
                <TableRow>
                  <TableCell
                    colSpan={10}
                    className="text-center py-8 text-muted-foreground"
                  >
                    No teachers found
                  </TableCell>
                </TableRow>
              ) : (
                teachers.data.map((teacher) => (
                  <TableRow key={teacher.id}>
                    <TableCell>
                      <input 
                        type="checkbox" 
                        className="checkbox" 
                        checked={selectedTeachers.includes(teacher.id)}
                        onChange={() => toggleTeacherSelection(teacher.id)}
                      />
                    </TableCell>
                    <TableCell>
                      <Avatar className="w-10 h-10 border">
                        <AvatarImage src={teacher.avatar || ""} />
                        <AvatarFallback className="bg-gray-200 text-gray-600">
                          {getInitials(teacher.name)}
                        </AvatarFallback>
                      </Avatar>
                    </TableCell>
                    <TableCell className="font-medium">{teacher.name}</TableCell>
                    <TableCell>{teacher.email}</TableCell>
                    <TableCell>{formatSubjects(teacher.subjects)}</TableCell>
                    <TableCell>
                      {(() => {
                        try {
                          return (
                            <div className="flex items-center">
                              <div className="flex text-yellow-500 mr-2">
                                {[1, 2, 3, 4, 5].map((star) => {
                                  const safeRatingValue = safeRating(teacher.rating);
                                  return (
                                    <span key={star} className={star <= getRatingStars(safeRatingValue) ? 'text-yellow-500' : 'text-gray-300'}>
                                      ★
                                    </span>
                                  );
                                })}
                              </div>
                              <span className="text-sm text-gray-600">
                                {formatRating(safeRating(teacher.rating))}
                              </span>
                            </div>
                          );
                        } catch (error) {
                          console.error('Error rendering teacher rating:', error, 'Teacher:', teacher);
                          return (
                            <div className="flex items-center">
                              <span className="text-sm text-gray-400">N/A</span>
                            </div>
                          );
                        }
                      })()}
                    </TableCell>
                    <TableCell className="text-center">{teacher.classes_held}</TableCell>
                    <TableCell>{getStatusBadge(teacher.status)}</TableCell>
                    <TableCell>
                      <Badge 
                        variant="outline" 
                        className={`${
                          teacher.account_status === 'active' 
                            ? 'text-green-600 border-green-600 bg-green-50' 
                            : teacher.account_status === 'suspended' 
                            ? 'text-red-600 border-red-600 bg-red-50'
                            : teacher.account_status === 'inactive'
                            ? 'text-gray-600 border-gray-600 bg-gray-50'
                            : teacher.account_status === 'pending'
                            ? 'text-yellow-600 border-yellow-600 bg-yellow-50'
                            : 'text-gray-600 border-gray-600 bg-gray-50'
                        }`}
                      >
                        {teacher.account_status_display || teacher.account_status}
                      </Badge>
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
                          {teacher.status !== "Approved" && (
                            <DropdownMenuItem 
                              className={`flex items-center justify-between px-4 py-3 cursor-pointer w-full ${
                                teacher.can_approve ? 'hover:bg-gray-50' : 'opacity-50 cursor-not-allowed'
                              }`}
                              onClick={() => {
                                if (!teacher.can_approve) {
                                  alert(teacher.approval_block_reason || 'Cannot approve this teacher yet.');
                                  return;
                                }
                                router.patch(
                                  route("admin.teachers.approve", teacher.id)
                                );
                              }}
                              title={teacher.approval_block_reason || 'Approve Teacher'}
                            >
                              <span>{teacher.can_approve ? 'Approve Teacher' : 'Cannot Approve'}</span>
                              <VerifiedIcon className={`h-5 w-5 ${teacher.can_approve ? 'text-green-600' : 'text-gray-400'}`} />
                            </DropdownMenuItem>
                          )}
                          <DropdownMenuItem asChild className="px-0 py-0">
                            <Link 
                              href={route("admin.teachers.edit", teacher.id)}
                              className="flex items-center justify-between px-4 py-3 hover:bg-gray-50 cursor-pointer w-full"
                            >
                              <span>Edit Profile</span>
                              <Edit className="h-5 w-5 text-gray-600" />
                            </Link>
                          </DropdownMenuItem>
                          <DropdownMenuItem asChild className="px-0 py-0">
                            <Link 
                              href={route("admin.teachers.show", teacher.id)}
                              className="flex items-center justify-between px-4 py-3 hover:bg-gray-50 cursor-pointer w-full"
                            >
                              <span>View Profile</span>
                              <Eye className="h-5 w-5 text-gray-600" />
                            </Link>
                          </DropdownMenuItem>
                          <DropdownMenuItem className="flex items-center justify-between px-4 py-3 hover:bg-gray-50 cursor-pointer">
                            <span>View Performance</span>
                            <BarChart className="h-5 w-5 text-gray-600" />
                          </DropdownMenuItem>
                          {teacher.status !== "Inactive" && (
                            <DropdownMenuItem 
                              className="flex items-center justify-between px-4 py-3 hover:bg-gray-50 cursor-pointer"
                              onClick={() => handleRejectClick(teacher)}
                            >
                              <span>Reject</span>
                              <CancelIcon className="h-5 w-5 text-red-600" />
                            </DropdownMenuItem>
                          )}
                          
                          {/* Account Management Actions */}
                          <div className="border-t my-1"></div>
                          
                          {teacher.account_status === 'active' && (
                            <DropdownMenuItem 
                              className="flex items-center justify-between px-4 py-3 hover:bg-gray-50 cursor-pointer"
                              onClick={() => openAccountModal(teacher, 'suspend')}
                            >
                              <span>Suspend Account</span>
                              <div className="h-5 w-5 text-orange-600" />
                            </DropdownMenuItem>
                          )}
                          
                          {teacher.account_status === 'suspended' && (
                            <DropdownMenuItem 
                              className="flex items-center justify-between px-4 py-3 hover:bg-gray-50 cursor-pointer"
                              onClick={() => openAccountModal(teacher, 'unsuspend')}
                            >
                              <span>Unsuspend Account</span>
                              <div className="h-5 w-5 text-green-600" />
                            </DropdownMenuItem>
                          )}
                          
                          {!teacher.is_deleted && (
                            <DropdownMenuItem 
                              className="flex items-center justify-between px-4 py-3 hover:bg-gray-50 cursor-pointer"
                              onClick={() => openAccountModal(teacher, 'delete')}
                            >
                              <span>Delete Account</span>
                              <div className="h-5 w-5 text-red-600" />
                            </DropdownMenuItem>
                          )}
                          
                          {teacher.is_deleted && (
                            <DropdownMenuItem 
                              className="flex items-center justify-between px-4 py-3 hover:bg-gray-50 cursor-pointer"
                              onClick={() => openAccountModal(teacher, 'restore')}
                            >
                              <span>Restore Account</span>
                              <div className="h-5 w-5 text-green-600" />
                            </DropdownMenuItem>
                          )}
                        </DropdownMenuContent>
                      </DropdownMenu>
                    </TableCell>
                  </TableRow>
                ))
              )}
            </TableBody>
          </Table>
        </div>

        {teachers.last_page > 1 && (
          <Pagination
            className="mt-4 flex justify-end"
            currentPage={teachers.current_page}
            totalPages={teachers.last_page}
            onPageChange={(page) =>
              router.get(
                route("admin.teachers.index", { page }),
                { search, status, subject, rating },
                { preserveState: true }
              )
            }
          />
        )}
      </div>

      {/* Reject Teacher Modal */}
      <Dialog open={rejectModalOpen} onOpenChange={setRejectModalOpen}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Reject Teacher</DialogTitle>
            <DialogDescription>
              Are you sure you want to reject {selectedTeacher?.name}? Please provide a reason for the rejection.
            </DialogDescription>
          </DialogHeader>
          
          <div className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="rejection-reason">Rejection Reason *</Label>
              <Textarea
                id="rejection-reason"
                placeholder="Please provide a detailed reason for rejecting this teacher..."
                value={rejectionReason}
                onChange={(e) => setRejectionReason(e.target.value)}
                className="min-h-[100px] resize-none"
                maxLength={500}
              />
              <div className="text-xs text-gray-500 text-right">
                {rejectionReason.length}/500 characters
              </div>
            </div>
          </div>

          <DialogFooter className="flex gap-2">
            <Button
              variant="outline"
              onClick={handleRejectCancel}
              disabled={isRejecting}
            >
              Cancel
            </Button>
            <Button
              variant="destructive"
              onClick={handleRejectSubmit}
              disabled={isRejecting || !rejectionReason.trim()}
              className="flex items-center gap-2"
            >
              {isRejecting ? (
                <>
                  <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                  Rejecting...
                </>
              ) : (
                <>
                  <X className="h-4 w-4" />
                  Reject Teacher
                </>
              )}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Account Management Modal */}
      <Dialog open={accountModalOpen} onOpenChange={setAccountModalOpen}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>
              {accountAction === 'suspend' && 'Suspend Account'}
              {accountAction === 'unsuspend' && 'Unsuspend Account'}
              {accountAction === 'delete' && 'Delete Account'}
              {accountAction === 'restore' && 'Restore Account'}
              {accountAction === 'force-delete' && 'Permanently Delete Account'}
            </DialogTitle>
            <DialogDescription>
              {accountAction === 'suspend' && `Are you sure you want to suspend ${selectedTeacher?.name}'s account?`}
              {accountAction === 'unsuspend' && `Are you sure you want to unsuspend ${selectedTeacher?.name}'s account?`}
              {accountAction === 'delete' && `Are you sure you want to delete ${selectedTeacher?.name}'s account? This can be restored.`}
              {accountAction === 'restore' && `Are you sure you want to restore ${selectedTeacher?.name}'s account?`}
              {accountAction === 'force-delete' && `Are you sure you want to permanently delete ${selectedTeacher?.name}'s account? This cannot be undone.`}
            </DialogDescription>
          </DialogHeader>
          
          <div className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="account-reason">Reason *</Label>
              <Textarea
                id="account-reason"
                placeholder={`Please provide a reason for ${accountAction}ing this account...`}
                value={accountReason}
                onChange={(e) => setAccountReason(e.target.value)}
                className="min-h-[100px] resize-none"
                maxLength={500}
              />
              <div className="text-xs text-gray-500 text-right">
                {accountReason.length}/500 characters
              </div>
            </div>
          </div>

          <DialogFooter className="flex gap-2">
            <Button
              variant="outline"
              onClick={cancelAccountModal}
              disabled={isProcessingAccount}
            >
              Cancel
            </Button>
            <Button
              variant={accountAction === 'delete' || accountAction === 'force-delete' ? 'destructive' : 'default'}
              onClick={handleAccountAction}
              disabled={isProcessingAccount || !accountReason.trim()}
              className="flex items-center gap-2"
            >
              {isProcessingAccount ? (
                <>
                  <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                  Processing...
                </>
              ) : (
                <>
                  {accountAction === 'suspend' && 'Suspend Account'}
                  {accountAction === 'unsuspend' && 'Unsuspend Account'}
                  {accountAction === 'delete' && 'Delete Account'}
                  {accountAction === 'restore' && 'Restore Account'}
                  {accountAction === 'force-delete' && 'Permanently Delete'}
                </>
              )}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Bulk Operations Modal */}
      <Dialog open={bulkModalOpen} onOpenChange={setBulkModalOpen}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>
              Bulk {bulkAction.charAt(0).toUpperCase() + bulkAction.slice(1)} Accounts
            </DialogTitle>
            <DialogDescription>
              Are you sure you want to {bulkAction} {selectedTeachers.length} teacher account(s)? This action will affect all selected teachers.
            </DialogDescription>
          </DialogHeader>
          
          <div className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="bulk-reason">Reason *</Label>
              <Textarea
                id="bulk-reason"
                placeholder={`Please provide a reason for ${bulkAction}ing these accounts...`}
                value={bulkReason}
                onChange={(e) => setBulkReason(e.target.value)}
                className="min-h-[100px] resize-none"
                maxLength={500}
              />
              <div className="text-xs text-gray-500 text-right">
                {bulkReason.length}/500 characters
              </div>
            </div>
          </div>

          <DialogFooter className="flex gap-2">
            <Button
              variant="outline"
              onClick={cancelBulkModal}
              disabled={isProcessingBulk}
            >
              Cancel
            </Button>
            <Button
              variant={bulkAction === 'delete' ? 'destructive' : 'default'}
              onClick={handleBulkAction}
              disabled={isProcessingBulk || !bulkReason.trim()}
              className="flex items-center gap-2"
            >
              {isProcessingBulk ? (
                <>
                  <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                  Processing...
                </>
              ) : (
                `Bulk ${bulkAction.charAt(0).toUpperCase() + bulkAction.slice(1)}`
              )}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </AdminLayout>
  );
} 