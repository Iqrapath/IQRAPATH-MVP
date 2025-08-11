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
import { MoreVertical, Edit, Eye, X, Search } from "lucide-react";
import { useState, FormEvent } from "react";
import { Pagination } from "@/components/ui/pagination";
import { debounce } from "lodash";
import AdminLayout from "@/layouts/admin/admin-layout";
import { Breadcrumbs } from "@/components/breadcrumbs";

interface User {
  id: number;
  name: string;
  email: string;
  avatar: string | null;
  role: string;
  status: string;
}

interface UsersIndexProps {
  users: {
    data: User[];
    links: any[];
    current_page: number;
    last_page: number;
  };
  filters: {
    search: string;
    status: string;
    role: string;
  };
  roles: string[];
}

export default function UsersIndex({
  users,
  filters,
  roles,
}: UsersIndexProps) {
  const [search, setSearch] = useState(filters.search);
  const [status, setStatus] = useState(filters.status);
  const [role, setRole] = useState(filters.role);

  const handleSearch = debounce((value: string) => {
    router.get(
      route("admin.user-management.index"),
      { search: value, status, role },
      { preserveState: true, replace: true }
    );
  }, 300);

  const handleFilter = (
    filterType: "status" | "role",
    value: string
  ) => {
    let params = { search, status, role };
    params[filterType] = value;

    if (filterType === "status") setStatus(value);
    if (filterType === "role") setRole(value);

    router.get(route("admin.user-management.index"), params, {
      preserveState: true,
      replace: true,
    });
  };

  const handleSearchSubmit = (e: FormEvent) => {
    e.preventDefault();
    router.get(
      route("admin.user-management.index"),
      { search, status, role },
      { preserveState: true }
    );
  };

  const getStatusBadge = (status: string) => {
    switch (status) {
      case "active":
        return (
          <span className="text-green-500 font-medium">Active</span>
        );
      case "inactive":
        return (
          <span className="text-red-500 font-medium">Inactive</span>
        );
      default:
        return (
          <span className="text-gray-500 font-medium">{status}</span>
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

  const breadcrumbs = [
    { title: "Dashboard", href: route("admin.dashboard") },
    { title: "User Management", href: route("admin.user-management.index") },
  ];

  return (
    <AdminLayout pageTitle="User Management" showRightSidebar={false}>
      <Head title="User Management" />
      <div className="py-6">
        <Breadcrumbs breadcrumbs={breadcrumbs} />
        <div className="flex justify-end mb-6">
          <Button className="bg-teal-600 hover:bg-teal-700" asChild>
            <Link href={route("admin.user-management.create")}>Add New User</Link>
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
                  <SelectItem value="active">Active</SelectItem>
                  <SelectItem value="inactive">Inactive</SelectItem>
                </SelectContent>
              </Select>
              <Select
                value={role}
                onValueChange={(value) => handleFilter("role", value)}
              >
                <SelectTrigger className="w-[140px] border rounded-full">
                  <SelectValue placeholder="Select Role" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Roles</SelectItem>
                  {roles.map((r) => (
                    <SelectItem key={r} value={r}>{r.charAt(0).toUpperCase() + r.slice(1)}</SelectItem>
                  ))}
                </SelectContent>
              </Select>
                             <Button
                 type="button"
                 onClick={() => {
                   router.get(route("admin.user-management.index"));
                   setSearch("");
                   setStatus("all");
                   setRole("all");
                 }}
                 className="bg-teal-700 rounded-full"
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
                <TableHead>Name</TableHead>
                <TableHead>Email</TableHead>
                <TableHead>Role</TableHead>
                <TableHead>Status</TableHead>
                <TableHead className="w-12">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {users.data.length === 0 ? (
                <TableRow>
                  <TableCell
                    colSpan={7}
                    className="text-center py-8 text-muted-foreground"
                  >
                    No users found
                  </TableCell>
                </TableRow>
              ) : (
                users.data.map((user) => (
                  <TableRow key={user.id}>
                    <TableCell>
                      <input type="checkbox" className="checkbox" />
                    </TableCell>
                    <TableCell>
                      <Avatar className="w-10 h-10 border">
                        <AvatarImage src={user.avatar || ""} />
                        <AvatarFallback className="bg-gray-200 text-gray-600">
                          {getInitials(user.name)}
                        </AvatarFallback>
                      </Avatar>
                    </TableCell>
                    <TableCell className="font-medium">{user.name}</TableCell>
                    <TableCell>{user.email}</TableCell>
                    <TableCell className="capitalize">{user.role || 'Unassigned'}</TableCell>
                    <TableCell>{getStatusBadge(user.status)}</TableCell>
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
                          <DropdownMenuItem asChild className="px-0 py-0 focus:bg-transparent">
                                                       <Link 
                             href={route("admin.user-management.edit", user.id)}
                             className="flex items-center px-4 py-3 hover:bg-gray-50 w-full"
                           >
                             <div className="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center mr-3">
                               <Edit className="h-5 w-5 text-gray-600" />
                             </div>
                             <span>Edit User</span>
                           </Link>
                          </DropdownMenuItem>
                          <DropdownMenuItem asChild className="px-0 py-0 focus:bg-transparent">
                                                       <Link 
                             href={route("admin.user-management.show", user.id)}
                             className="flex items-center px-4 py-3 hover:bg-gray-50 w-full"
                           >
                             <div className="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center mr-3">
                               <Eye className="h-5 w-5 text-gray-600" />
                             </div>
                             <span>View User</span>
                           </Link>
                          </DropdownMenuItem>
                          <DropdownMenuItem className="flex items-center px-4 py-3 hover:bg-gray-50 cursor-pointer">
                            <div className="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center mr-3">
                              <X className="h-5 w-5 text-red-600" />
                            </div>
                            <span>Delete User (coming soon)</span>
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
        {users.last_page > 1 && (
          <Pagination
            className="mt-4 flex justify-end"
            currentPage={users.current_page}
            totalPages={users.last_page}
                         onPageChange={(page) =>
               router.get(
                 route("admin.user-management.index", { page }),
                 { search, status, role },
                 { preserveState: true }
               )
             }
          />
        )}
      </div>
    </AdminLayout>
  );
}
