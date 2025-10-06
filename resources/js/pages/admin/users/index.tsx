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
import { toast } from "sonner";
import { Toaster } from "sonner";
import { ConfirmationModal } from "@/components/ui/confirmation-modal";

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
  filters?: {
    search?: string;
    status?: string;
    role?: string;
  };
  roles?: string[];
}

export default function UsersIndex({
  users,
  filters,
  roles,
}: UsersIndexProps) {
  // Provide default values for filters and roles to prevent undefined errors
  const safeFilters = filters || {};
  const safeRoles = roles || ['super-admin', 'admin', 'teacher', 'student', 'guardian', 'unassigned'];
  
  const [search, setSearch] = useState(safeFilters.search || '');
  const [status, setStatus] = useState(safeFilters.status || 'all');
  const [role, setRole] = useState(safeFilters.role || 'all');
  const [editingRole, setEditingRole] = useState<number | null>(null);
  const [updatingRole, setUpdatingRole] = useState<number | null>(null);
  const [confirmationModal, setConfirmationModal] = useState<{
    isOpen: boolean;
    userId: number;
    newRole: string;
    currentRole: string;
    userName: string;
  }>({
    isOpen: false,
    userId: 0,
    newRole: '',
    currentRole: '',
    userName: '',
  });

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
    const params = { search, status, role };
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

  const handleRoleChange = async (userId: number, newRole: string) => {
    if (updatingRole === userId) return;
    
    // Get the user object to show current role
    const user = users.data.find(u => u.id === userId);
    const currentRole = user?.role ? user.role.charAt(0).toUpperCase() + user.role.slice(1) : 'Unassigned';
    const newRoleDisplay = newRole === 'unassigned' ? 'Unassigned' : newRole.charAt(0).toUpperCase() + newRole.slice(1);
    
    // Show confirmation modal
    setConfirmationModal({
      isOpen: true,
      userId,
      newRole,
      currentRole,
      userName: user?.name || '',
    });
  };

  const confirmRoleChange = async () => {
    const { userId, newRole } = confirmationModal;
    
    setUpdatingRole(userId);
    setEditingRole(null);
    setConfirmationModal({ ...confirmationModal, isOpen: false });
    
    try {
      const response = await fetch(`/admin/user-management/${userId}`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({ role: newRole }),
      });

      const data = await response.json();

      if (data.success) {
        toast.success(data.message);
        // Refresh the page to show updated data
        router.reload();
      } else {
        toast.error(data.message || 'Failed to update role');
      }
    } catch (error) {
      console.error('Error updating role:', error);
      toast.error('Failed to update role. Please try again.');
    } finally {
      setUpdatingRole(null);
    }
  };

  const startRoleEdit = (userId: number) => {
    setEditingRole(userId);
  };

  const cancelRoleEdit = () => {
    setEditingRole(null);
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
                   {safeRoles
                     .filter(r => r !== 'unassigned')
                     .sort()
                     .map((r) => (
                       <SelectItem key={r} value={r}>{r.charAt(0).toUpperCase() + r.slice(1)}</SelectItem>
                     ))}
                   <SelectItem value="unassigned">Unassigned</SelectItem>
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
                  <TableRow 
                    key={user.id}
                    className={updatingRole === user.id ? 'bg-blue-50' : ''}
                  >
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
                    <TableCell>
                      {editingRole === user.id ? (
                        <div className="flex items-center space-x-2 p-2 bg-blue-50 border border-blue-200 rounded">
                                                     <Select
                             value={user.role || 'unassigned'}
                             onValueChange={(value) => handleRoleChange(user.id, value)}
                           >
                             <SelectTrigger className="w-32 h-8 text-xs">
                               <SelectValue placeholder="Select Role" />
                             </SelectTrigger>
                             <SelectContent>
                               {safeRoles
                                 .filter(r => r !== 'unassigned')
                                 .sort()
                                 .map((r) => (
                                   <SelectItem key={r} value={r}>
                                     {r.charAt(0).toUpperCase() + r.slice(1)}
                                   </SelectItem>
                                 ))}
                               <SelectItem value="unassigned">Unassigned</SelectItem>
                             </SelectContent>
                           </Select>
                          <button
                            onClick={cancelRoleEdit}
                            className="text-gray-400 hover:text-gray-600 text-xs px-2 py-1 hover:bg-gray-100 rounded"
                          >
                            Cancel
                          </button>
                        </div>
                      ) : (
                                                 <div className="flex items-center space-x-2">
                           <span className="capitalize font-medium">
                             {user.role ? user.role.charAt(0).toUpperCase() + user.role.slice(1) : 'Unassigned'}
                           </span>
                          <button
                            onClick={() => startRoleEdit(user.id)}
                            className="text-blue-600 hover:text-blue-800 text-xs underline hover:no-underline"
                            disabled={updatingRole === user.id}
                          >
                            {updatingRole === user.id ? (
                              <span className="text-gray-500">Updating...</span>
                            ) : (
                              'Change'
                            )}
                          </button>
                        </div>
                      )}
                    </TableCell>
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
             <Toaster position="top-right" />
       
       {/* Confirmation Modal */}
       <ConfirmationModal
         isOpen={confirmationModal.isOpen}
         onClose={() => setConfirmationModal({ ...confirmationModal, isOpen: false })}
         onConfirm={confirmRoleChange}
         title="Confirm Role Change"
         description={`Are you sure you want to change ${confirmationModal.userName}'s role from "${confirmationModal.currentRole}" to "${confirmationModal.newRole === 'unassigned' ? 'Unassigned' : confirmationModal.newRole.charAt(0).toUpperCase() + confirmationModal.newRole.slice(1)}"? This action will create a new profile and delete the old one.`}
         confirmText="Change Role"
         cancelText="Cancel"
         variant="warning"
         isLoading={updatingRole === confirmationModal.userId}
       />
     </AdminLayout>
   );
 }
