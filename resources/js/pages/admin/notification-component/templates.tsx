import { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/layouts/admin/admin-layout';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { PlusCircle, Search, Edit, Eye, Trash2 } from 'lucide-react';

interface Template {
  id: number;
  name: string;
  title: string;
  type: string;
  is_active: boolean;
  created_at: string;
}

interface TemplatesProps {
  templates: {
    data: Template[];
    meta: {
      current_page: number;
      from: number;
      last_page: number;
      links: Array<{
        url: string | null;
        label: string;
        active: boolean;
      }>;
      path: string;
      per_page: number;
      to: number;
      total: number;
    };
  };
  filters: {
    search?: string;
    type?: string;
  };
}

export default function Templates({ templates, filters }: TemplatesProps) {
  const [search, setSearch] = useState(filters.search || '');
  
  // Breadcrumb items
  const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Notifications', href: route('admin.notification.index') },
    { title: 'Templates', href: '#' },
  ];

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    window.location.href = route('admin.notification.templates', { search });
  };

  return (
    <AdminLayout pageTitle="Notification Templates" showRightSidebar={false}>
      <Head title="Notification Templates" />
      <div className="py-6">
        {/* Breadcrumbs */}
        <div className="mb-6">
          <Breadcrumbs breadcrumbs={breadcrumbs} />
        </div>
        
        {/* Header */}
        <div className="flex justify-between items-center mb-6">
          <h2 className="text-xl font-semibold">Notification Templates</h2>
          <Link href={route('admin.notification.templates.create')}>
            <Button className="flex items-center gap-2 bg-teal-600 hover:bg-teal-700">
              <PlusCircle className="w-4 h-4" />
              <span>Create Template</span>
            </Button>
          </Link>
        </div>
        
        {/* Search */}
        <Card className="mb-6">
          <CardContent className="p-4">
            <form onSubmit={handleSearch} className="flex gap-2">
              <div className="relative flex-1">
                <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                <Input
                  type="search"
                  placeholder="Search templates..."
                  className="pl-8"
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                />
              </div>
              <Button type="submit">Search</Button>
            </form>
          </CardContent>
        </Card>
        
        {/* Templates Table */}
        <Card>
          <CardContent className="p-0">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Name</TableHead>
                  <TableHead>Title</TableHead>
                  <TableHead>Type</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead className="w-[100px]">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {templates.data.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={5} className="text-center py-8 text-muted-foreground">
                      No templates found
                    </TableCell>
                  </TableRow>
                ) : (
                  templates.data.map((template) => (
                    <TableRow key={template.id}>
                      <TableCell className="font-medium">{template.name}</TableCell>
                      <TableCell>{template.title}</TableCell>
                      <TableCell>
                        <Badge variant="outline" className="capitalize">
                          {template.type}
                        </Badge>
                      </TableCell>
                      <TableCell>
                        {template.is_active ? (
                          <Badge className="bg-green-500">Active</Badge>
                        ) : (
                          <Badge variant="outline" className="text-muted-foreground">
                            Inactive
                          </Badge>
                        )}
                      </TableCell>
                      <TableCell>
                        <div className="flex items-center gap-2">
                          <Link href={route('admin.notification.templates.edit', template.id)}>
                            <Button variant="ghost" size="icon" className="h-8 w-8">
                              <Edit className="h-4 w-4" />
                              <span className="sr-only">Edit</span>
                            </Button>
                          </Link>
                          <Link href={route('admin.notification.templates.show', template.id)}>
                            <Button variant="ghost" size="icon" className="h-8 w-8">
                              <Eye className="h-4 w-4" />
                              <span className="sr-only">View</span>
                            </Button>
                          </Link>
                          <Button variant="ghost" size="icon" className="h-8 w-8 text-red-500 hover:text-red-700">
                            <Trash2 className="h-4 w-4" />
                            <span className="sr-only">Delete</span>
                          </Button>
                        </div>
                      </TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
          </CardContent>
        </Card>
        
        {/* Pagination */}
        {templates.meta.last_page > 1 && (
          <div className="flex justify-end mt-4">
            <div className="flex gap-1">
              {templates.meta.links.map((link, index) => (
                <Link
                  key={index}
                  href={link.url || '#'}
                  className={`px-3 py-1 rounded ${
                    link.active
                      ? 'bg-primary text-primary-foreground'
                      : 'bg-background hover:bg-accent'
                  } ${!link.url ? 'opacity-50 cursor-not-allowed' : ''}`}
                  preserveScroll
                >
                  <div dangerouslySetInnerHTML={{ __html: link.label }} />
                </Link>
              ))}
            </div>
          </div>
        )}
      </div>
    </AdminLayout>
  );
} 