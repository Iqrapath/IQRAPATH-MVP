import { Head, Link } from "@inertiajs/react";
import AdminLayout from "@/layouts/admin/admin-layout";
import { Breadcrumbs } from "@/components/breadcrumbs";
import { Button } from "@/components/ui/button";

interface User {
  id: number;
  name: string;
  email: string;
  avatar: string | null;
  role: string;
  status: string;
}

interface EditUserProps {
  user: User;
}

export default function EditUser({ user }: EditUserProps) {
  const breadcrumbs = [
    { title: "Dashboard", href: route("admin.dashboard") },
    { title: "User Management", href: route("admin.user-management.index") },
    { title: user.name, href: route("admin.user-management.show", user.id) },
    { title: "Edit", href: route("admin.user-management.edit", user.id) },
  ];

  return (
    <AdminLayout pageTitle={`Edit User: ${user.name}`} showRightSidebar={false}>
      <Head title={`Edit User: ${user.name}`} />
      <div className="py-6">
        <Breadcrumbs breadcrumbs={breadcrumbs} />
        <div className="bg-white rounded-md p-6">
          <div className="flex justify-between items-start mb-6">
            <h1 className="text-2xl font-bold text-gray-900">Edit User</h1>
            <Button variant="outline" asChild>
              <Link href={route("admin.user-management.show", user.id)}>
                Cancel
              </Link>
            </Button>
          </div>
          
          <div className="space-y-6">
            <div>
              <label className="text-sm font-medium text-gray-500">Name</label>
              <p className="text-gray-900">{user.name}</p>
            </div>
            
            <div>
              <label className="text-sm font-medium text-gray-500">Email</label>
              <p className="text-gray-900">{user.email}</p>
            </div>
            
            <div>
              <label className="text-sm font-medium text-gray-500">Role</label>
              <p className="text-gray-900 capitalize">{user.role || 'Unassigned'}</p>
            </div>
            
            <div>
              <label className="text-sm font-medium text-gray-500">Status</label>
              <p className="text-gray-900">{user.status}</p>
            </div>
            
            <div className="pt-4">
              <p className="text-gray-600">Edit form coming soon...</p>
            </div>
          </div>
        </div>
      </div>
    </AdminLayout>
  );
}
