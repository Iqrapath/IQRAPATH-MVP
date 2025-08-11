import { Head, Link } from "@inertiajs/react";
import AdminLayout from "@/layouts/admin/admin-layout";
import { Breadcrumbs } from "@/components/breadcrumbs";
import { Button } from "@/components/ui/button";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";

interface User {
  id: number;
  name: string;
  email: string;
  avatar: string | null;
  role: string;
  status: string;
  created_at: string;
  email_verified_at: string | null;
}

interface ShowUserProps {
  user: User;
}

export default function ShowUser({ user }: ShowUserProps) {
  const breadcrumbs = [
    { title: "Dashboard", href: route("admin.dashboard") },
    { title: "User Management", href: route("admin.user-management.index") },
    { title: user.name, href: route("admin.user-management.show", user.id) },
  ];

  const getInitials = (name: string) => {
    return name
      .split(" ")
      .map((n) => n[0])
      .join("")
      .toUpperCase();
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

  return (
    <AdminLayout pageTitle={`User: ${user.name}`} showRightSidebar={false}>
      <Head title={`User: ${user.name}`} />
      <div className="py-6">
        <Breadcrumbs breadcrumbs={breadcrumbs} />
        <div className="bg-white rounded-md p-6">
          <div className="flex justify-between items-start mb-6">
            <h1 className="text-2xl font-bold text-gray-900">User Details</h1>
            <Button asChild>
              <Link href={route("admin.user-management.edit", user.id)}>
                Edit User
              </Link>
            </Button>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <div className="flex items-center space-x-4 mb-6">
                <Avatar className="w-20 h-20 border">
                  <AvatarImage src={user.avatar || ""} />
                  <AvatarFallback className="bg-gray-200 text-gray-600 text-xl">
                    {getInitials(user.name)}
                  </AvatarFallback>
                </Avatar>
                <div>
                  <h2 className="text-xl font-semibold text-gray-900">{user.name}</h2>
                  <p className="text-gray-600">{user.email}</p>
                </div>
              </div>
              
              <div className="space-y-4">
                <div>
                  <label className="text-sm font-medium text-gray-500">Role</label>
                  <p className="text-gray-900 capitalize">{user.role || 'Unassigned'}</p>
                </div>
                
                <div>
                  <label className="text-sm font-medium text-gray-500">Status</label>
                  <p>{getStatusBadge(user.status)}</p>
                </div>
                
                <div>
                  <label className="text-sm font-medium text-gray-500">Created</label>
                  <p className="text-gray-900">{new Date(user.created_at).toLocaleDateString()}</p>
                </div>
                
                {user.email_verified_at && (
                  <div>
                    <label className="text-sm font-medium text-gray-500">Email Verified</label>
                    <p className="text-gray-900">{new Date(user.email_verified_at).toLocaleDateString()}</p>
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      </div>
    </AdminLayout>
  );
}
