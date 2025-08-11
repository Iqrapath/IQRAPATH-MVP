import { Head } from "@inertiajs/react";
import AdminLayout from "@/layouts/admin/admin-layout";
import { Breadcrumbs } from "@/components/breadcrumbs";

export default function CreateUser() {
  const breadcrumbs = [
    { title: "Dashboard", href: route("admin.dashboard") },
    { title: "User Management", href: route("admin.user-management.index") },
    { title: "Create User", href: route("admin.user-management.create") },
  ];

  return (
    <AdminLayout pageTitle="Create User" showRightSidebar={false}>
      <Head title="Create User" />
      <div className="py-6">
        <Breadcrumbs breadcrumbs={breadcrumbs} />
        <div className="bg-white rounded-md p-6">
          <h1 className="text-2xl font-bold text-gray-900 mb-6">Create New User</h1>
          <p className="text-gray-600">User creation form coming soon...</p>
        </div>
      </div>
    </AdminLayout>
  );
}
