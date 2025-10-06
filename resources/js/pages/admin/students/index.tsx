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
import { MoreVertical, Search, CheckCircle, Edit, Eye, Settings, XCircle } from "lucide-react";
import { useState, FormEvent } from "react";
import { Pagination } from "@/components/ui/pagination";
import { debounce } from "lodash";
import AdminLayout from "@/layouts/admin/admin-layout";
import { Breadcrumbs } from "@/components/breadcrumbs";

interface Student {
  id: number;
  name: string;
  email: string;
  avatar: string | null;
  role: string;
  status: string;
  guardian_name: string | null;
  registration_date: string | null;
  completed_sessions: number;
  attendance_percentage: number;
}

interface Subject {
  id: number;
  name: string;
}

interface StudentsIndexProps {
  students: {
    data: Student[];
    links: any[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
  filters: {
    search: string;
    status: string;
    subject: string;
    rating: string;
    role: string;
  };
  subjects: Subject[];
}

export default function StudentsIndex({
  students,
  filters,
  subjects,
}: StudentsIndexProps) {
  // Provide safe defaults to prevent undefined errors
  const safeStudents = students || { 
    data: [], 
    current_page: 1, 
    last_page: 1, 
    per_page: 15, 
    total: 0 
  };
  const safeFilters = filters || {};
  
  // Deduplicate subjects by name to avoid React key conflicts
  const uniqueSubjects = subjects ? subjects.filter((subject, index, self) =>
    index === self.findIndex((s) => s.name === subject.name)
  ) : [];
  const safeSubjects = uniqueSubjects;

  // Data is loading correctly

  const [search, setSearch] = useState(safeFilters.search || '');
  const [status, setStatus] = useState(safeFilters.status || 'all');
  const [subject, setSubject] = useState(safeFilters.subject || 'all');
  const [rating, setRating] = useState(safeFilters.rating || 'all');
  const [role, setRole] = useState(safeFilters.role || 'all');

  const breadcrumbs = [
    { title: "Dashboard", href: route("admin.dashboard") },
    { title: "Student/Parent Management", href: route("admin.students.index") },
  ];

  const handleSearch = debounce((value: string) => {
    router.get(
      route("admin.students.index"),
      { search: value, status, subject, rating },
      { preserveState: true, replace: true }
    );
  }, 300);

  const handleFilter = (
    filterType: "status" | "subject" | "rating" | "role",
    value: string
  ) => {
    const params = { search, status, subject, rating, role };
    params[filterType] = value;

    if (filterType === "status") setStatus(value);
    if (filterType === "subject") setSubject(value);
    if (filterType === "rating") setRating(value);
    if (filterType === "role") setRole(value);

    router.get(route("admin.students.index"), params, {
      preserveState: true,
      replace: true,
    });
  };

  const handleSearchSubmit = (e: FormEvent) => {
    e.preventDefault();
    router.get(
      route("admin.students.index"),
      { search, status, subject, rating, role },
      { preserveState: true }
    );
  };

  const handleAction = (studentId: number, action: string) => {
    switch (action) {
      case 'approve':
        router.post(route("admin.students.approve", studentId));
        break;
      case 'suspend':
        router.post(route("admin.students.suspend", studentId));
        break;
      case 'view':
        router.get(route("admin.students.show", studentId));
        break;
      case 'edit-contact':
        // This would open a modal or redirect to edit page
        break;
      case 'edit-preferences':
        // This would open a modal or redirect to edit page
        break;
    }
  };

  const handlePageChange = (page: number) => {
    router.get(
      route("admin.students.index"),
      { search, status, subject, rating, role, page },
      { preserveState: true }
    );
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'active':
        return 'text-green-600 bg-green-50';
      case 'suspended':
        return 'text-red-600 bg-red-50';
      case 'inactive':
        return 'text-gray-600 bg-gray-50';
      default:
        return 'text-gray-600 bg-gray-50';
    }
  };

  return (
    <AdminLayout pageTitle="Student Management" showRightSidebar={false}>
      <Head title="Student Management" />
      
      <div className="py-6">
        <div className="flex items-center justify-between mb-6">
        <Breadcrumbs breadcrumbs={breadcrumbs} />
        </div>
        
        {/* Header */}
        <div className="flex items-center justify-between mb-6">
          <h1 className="text-2xl font-semibold text-gray-900">Student/Parent Management</h1>
          <Button className="bg-teal-600 hover:bg-teal-700">
            Add New Student/Parent
          </Button>
        </div>

        {/* Search and Filters */}
        <div className=" mb-6">
          <form onSubmit={handleSearchSubmit} className="flex flex-wrap gap-4 items-center">
            {/* Search Input */}
            <div className="relative flex-1 min-w-64">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
              <Input
                type="text"
                placeholder="Search by Name / Email"
                value={search}
                onChange={(e) => {
                  setSearch(e.target.value);
                  handleSearch(e.target.value);
                }}
                className="pl-10 border-gray-300 rounded-full"
              />
            </div>

            {/* Role Filter */}
            <Select value={role} onValueChange={(value) => handleFilter("role", value)}>
              <SelectTrigger className="w-32 border-gray-300 rounded-full">
                <SelectValue placeholder="Role" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Roles</SelectItem>
                <SelectItem value="student">Student</SelectItem>
                <SelectItem value="guardian">Parent</SelectItem>
              </SelectContent>
            </Select>

            {/* Status Filter */}
            <Select value={status} onValueChange={(value) => handleFilter("status", value)}>
              <SelectTrigger className="w-40 border-gray-300 rounded-full">
                <SelectValue placeholder="Select Status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Status</SelectItem>
                <SelectItem value="active">Active</SelectItem>
                <SelectItem value="inactive">Inactive</SelectItem>
                <SelectItem value="suspended">Suspended</SelectItem>
              </SelectContent>
            </Select>

            {/* Subject Filter */}
            <Select value={subject} onValueChange={(value) => handleFilter("subject", value)}>
              <SelectTrigger className="w-40 border-gray-300 rounded-full">
                <SelectValue placeholder="Select Subject" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Subjects</SelectItem>
                {safeSubjects.map((subj) => (
                  <SelectItem key={subj.id} value={subj.name}>
                    {subj.name}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>

            {/* Rating Filter */}
            <Select value={rating} onValueChange={(value) => handleFilter("rating", value)}>
              <SelectTrigger className="w-32 border-gray-300 rounded-full">
                <SelectValue placeholder="Rating" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Ratings</SelectItem>
                <SelectItem value="5">5 Stars</SelectItem>
                <SelectItem value="4">4 Stars</SelectItem>
                <SelectItem value="3">3 Stars</SelectItem>
                <SelectItem value="2">2 Stars</SelectItem>
                <SelectItem value="1">1 Star</SelectItem>
              </SelectContent>
            </Select>

            {/* Search Button */}
            <Button 
              type="submit" 
              className="bg-teal-600 hover:bg-teal-700 rounded-full px-6"
            >
              Search
            </Button>
          </form>
        </div>

        {/* Students Table */}
        <div className="bg-white rounded-lg border border-gray-200 overflow-hidden">
          <Table>
            <TableHeader>
              <TableRow className="border-b border-gray-200">
                <TableHead className="w-12">
                  <input type="checkbox" className="rounded border-gray-300" />
                </TableHead>
                <TableHead className="text-gray-600 font-medium">Profile</TableHead>
                <TableHead className="text-gray-600 font-medium">Name</TableHead>
                <TableHead className="text-gray-600 font-medium">Email</TableHead>
                <TableHead className="text-gray-600 font-medium">Role</TableHead>
                <TableHead className="text-gray-600 font-medium">Status</TableHead>
                <TableHead className="text-gray-600 font-medium">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {safeStudents.data.map((student) => (
                <TableRow key={student.id} className="border-b border-gray-100 hover:bg-gray-50">
                  <TableCell>
                    <input type="checkbox" className="rounded border-gray-300" />
                  </TableCell>
                  <TableCell>
                    <Avatar className="h-10 w-10">
                      <AvatarImage src={student.avatar || undefined} />
                      <AvatarFallback className="bg-teal-100 text-teal-700">
                        {student.name.substring(0, 2).toUpperCase()}
                      </AvatarFallback>
                    </Avatar>
                  </TableCell>
                  <TableCell className="font-medium text-gray-900">
                    {student.name}
                  </TableCell>
                  <TableCell className="text-gray-600">
                    {student.email}
                  </TableCell>
                  <TableCell className="text-gray-600">
                    {student.role}
                  </TableCell>
                  <TableCell>
                    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusColor(student.status)}`}>
                      {student.status === 'active' ? 'Active' : student.status}
                    </span>
                  </TableCell>
                  <TableCell>
                    <DropdownMenu>
                      <DropdownMenuTrigger asChild>
                        <Button variant="ghost" className="h-8 w-8 p-0">
                          <MoreVertical className="h-4 w-4" />
                        </Button>
                      </DropdownMenuTrigger>
                      <DropdownMenuContent align="end" className="w-48">
                        <DropdownMenuItem 
                          onClick={() => handleAction(student.id, 'approve')}
                          className="text-green-600"
                        >
                          <CheckCircle className="mr-2 h-4 w-4" />
                          Approve
                        </DropdownMenuItem>
                        <DropdownMenuItem 
                          onClick={() => handleAction(student.id, 'edit-contact')}
                        >
                          <Edit className="mr-2 h-4 w-4" />
                          Edit Contact Info
                        </DropdownMenuItem>
                        <DropdownMenuItem 
                          onClick={() => handleAction(student.id, 'view')}
                        >
                          <Eye className="mr-2 h-4 w-4" />
                          View Full Profile
                        </DropdownMenuItem>
                        <DropdownMenuItem 
                          onClick={() => handleAction(student.id, 'edit-preferences')}
                        >
                          <Settings className="mr-2 h-4 w-4" />
                          Edit Preferences
                        </DropdownMenuItem>
                        <DropdownMenuItem 
                          onClick={() => handleAction(student.id, 'suspend')}
                          className="text-red-600"
                        >
                          <XCircle className="mr-2 h-4 w-4" />
                          Suspend
                        </DropdownMenuItem>
                      </DropdownMenuContent>
                    </DropdownMenu>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>

          {/* Pagination */}
          {safeStudents.last_page > 1 && (
            <div className="px-6 py-4 border-t border-gray-200">
              <div className="flex items-center justify-between">
                <div className="text-sm text-gray-500">
                  Showing {((safeStudents.current_page - 1) * safeStudents.per_page) + 1} to {Math.min(safeStudents.current_page * safeStudents.per_page, safeStudents.total)} of {safeStudents.total} students
                </div>
                <Pagination
                  currentPage={safeStudents.current_page}
                  totalPages={safeStudents.last_page}
                  onPageChange={handlePageChange}
                />
              </div>
            </div>
          )}

          {/* Empty State */}
          {safeStudents.data.length === 0 && (
            <div className="text-center py-12">
              <div className="text-gray-500 text-lg mb-2">No students found</div>
              <div className="text-gray-400 text-sm">
                Try adjusting your search or filter criteria
              </div>
            </div>
          )}
        </div>
      </div>
    </AdminLayout>
  );
}
