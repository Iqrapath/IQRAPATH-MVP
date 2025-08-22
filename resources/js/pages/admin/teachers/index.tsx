import { Head, Link, router, usePage } from "@inertiajs/react";
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
import { useState, FormEvent } from "react";
import { Pagination } from "@/components/ui/pagination";
import { debounce } from "lodash";
import AdminLayout from "@/layouts/admin/admin-layout";
import { Breadcrumb, BreadcrumbList, BreadcrumbItem, BreadcrumbLink } from "@/components/ui/breadcrumb";
import { Breadcrumbs } from "@/components/breadcrumbs";
import { CancelIcon } from "@/components/icons/cancel-icon";

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
    let params = { search, status, subject, rating };
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

        <div className="bg-white rounded-md">
          <Table>
            <TableHeader className="bg-gray-50">
              <TableRow>
                <TableHead className="w-12">
                  <input type="checkbox" className="checkbox" />
                </TableHead>
                <TableHead>Profile</TableHead>
                <TableHead>Teacher's Name</TableHead>
                <TableHead>Email</TableHead>
                <TableHead>Subject(s)</TableHead>
                <TableHead>Rating</TableHead>
                <TableHead>Classes Held</TableHead>
                <TableHead>Status</TableHead>
                <TableHead className="w-12">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {teachers.data.length === 0 ? (
                <TableRow>
                  <TableCell
                    colSpan={9}
                    className="text-center py-8 text-muted-foreground"
                  >
                    No teachers found
                  </TableCell>
                </TableRow>
              ) : (
                teachers.data.map((teacher) => (
                  <TableRow key={teacher.id}>
                    <TableCell>
                      <input type="checkbox" className="checkbox" />
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
                              onClick={() => {
                                // Show a modal for rejection reason
                                // This would need to be implemented
                                alert("Implement rejection modal");
                              }}
                            >
                              <span>Reject</span>
                              <CancelIcon className="h-5 w-5 text-red-600" />
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
    </AdminLayout>
  );
} 