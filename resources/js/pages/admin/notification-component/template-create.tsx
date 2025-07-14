import { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AdminLayout from '@/layouts/admin/admin-layout';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { Card, CardContent } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

export default function TemplateCreate() {
  const [placeholderInput, setPlaceholderInput] = useState('');
  
  const { data, setData, post, processing, errors } = useForm({
    name: '',
    title: '',
    body: '',
    type: 'custom',
    placeholders: [] as string[],
    is_active: true as boolean,
  });
  
  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    post(route('admin.notification.templates.store'));
  };
  
  const addPlaceholder = () => {
    if (placeholderInput && !data.placeholders.includes(placeholderInput)) {
      setData('placeholders', [...data.placeholders, placeholderInput]);
      setPlaceholderInput('');
    }
  };
  
  const removePlaceholder = (placeholder: string) => {
    setData('placeholders', data.placeholders.filter(p => p !== placeholder));
  };
  
  // Breadcrumb items
  const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Notifications', href: route('admin.notification.index') },
    { title: 'Templates', href: route('admin.notification.templates') },
    { title: 'Create Template', href: '#' },
  ];

  return (
    <AdminLayout pageTitle="Create Notification Template" showRightSidebar={false}>
      <Head title="Create Notification Template" />
      <div className="py-6">
        {/* Breadcrumbs */}
        <div className="mb-6">
          <Breadcrumbs breadcrumbs={breadcrumbs} />
        </div>
        
        <h2 className="text-xl font-semibold border-b pb-3 mb-6">Template Form</h2>
        <Card>
          <CardContent className="p-6">
            <form onSubmit={handleSubmit} className="space-y-8">
              {/* Template Name */}
              <div className="space-y-2">
                <Label htmlFor="name" className="text-base font-medium">Template Name</Label>
                <Input 
                  id="name" 
                  value={data.name} 
                  onChange={(e) => setData('name', e.target.value)}
                  className="max-w-md"
                  placeholder="welcome_user, payment_confirmation, etc."
                />
                {errors.name && <p className="text-sm text-red-500">{errors.name}</p>}
                <p className="text-sm text-muted-foreground">
                  Use snake_case for template names (e.g., welcome_user, payment_confirmation)
                </p>
              </div>
              
              {/* Template Title */}
              <div className="space-y-2">
                <Label htmlFor="title" className="text-base font-medium">Notification Title</Label>
                <Input 
                  id="title" 
                  value={data.title} 
                  onChange={(e) => setData('title', e.target.value)}
                  className="max-w-md"
                  placeholder="Welcome to IQRAPATH!"
                />
                {errors.title && <p className="text-sm text-red-500">{errors.title}</p>}
              </div>
              
              {/* Template Body */}
              <div className="space-y-2">
                <Label htmlFor="body" className="text-base font-medium">Message Body</Label>
                <Textarea 
                  id="body" 
                  value={data.body} 
                  onChange={(e) => setData('body', e.target.value)}
                  rows={8}
                  className="max-w-2xl"
                  placeholder="Dear [User_Name],\n\nWelcome to IQRAPATH! We're excited to have you join our community."
                />
                {errors.body && <p className="text-sm text-red-500">{errors.body}</p>}
              </div>
              
              {/* Template Type */}
              <div className="space-y-2">
                <Label htmlFor="type" className="text-base font-medium">Template Type</Label>
                <Select 
                  value={data.type} 
                  onValueChange={(value) => setData('type', value)}
                >
                  <SelectTrigger className="max-w-md">
                    <SelectValue placeholder="Select template type" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="custom">Custom</SelectItem>
                    <SelectItem value="system">System</SelectItem>
                    <SelectItem value="payment">Payment</SelectItem>
                    <SelectItem value="class">Class</SelectItem>
                    <SelectItem value="subscription">Subscription</SelectItem>
                    <SelectItem value="feature">Feature</SelectItem>
                  </SelectContent>
                </Select>
                {errors.type && <p className="text-sm text-red-500">{errors.type}</p>}
              </div>
              
              {/* Placeholders */}
              <div className="space-y-4">
                <Label className="text-base font-medium">Placeholders</Label>
                <div className="flex gap-2 max-w-md">
                  <Input 
                    value={placeholderInput}
                    onChange={(e) => setPlaceholderInput(e.target.value)}
                    placeholder="User_Name"
                    className="flex-1"
                  />
                  <Button 
                    type="button" 
                    onClick={addPlaceholder}
                    variant="outline"
                  >
                    Add
                  </Button>
                </div>
                {errors.placeholders && <p className="text-sm text-red-500">{errors.placeholders}</p>}
                
                {data.placeholders.length > 0 && (
                  <div className="flex flex-wrap gap-2 mt-2">
                    {data.placeholders.map((placeholder) => (
                      <div 
                        key={placeholder} 
                        className="px-3 py-1 bg-muted rounded-md flex items-center gap-2"
                      >
                        <span>[{placeholder}]</span>
                        <button 
                          type="button" 
                          onClick={() => removePlaceholder(placeholder)}
                          className="text-muted-foreground hover:text-destructive"
                        >
                          &times;
                        </button>
                      </div>
                    ))}
                  </div>
                )}
                <p className="text-sm text-muted-foreground">
                  Add placeholders that can be used in the template (e.g., User_Name, Amount_Paid)
                </p>
              </div>
              
              {/* Is Active */}
              <div className="flex items-center gap-2">
                <Switch 
                  id="is_active"
                  checked={data.is_active} 
                  onCheckedChange={(checked) => setData('is_active', checked)}
                />
                <Label htmlFor="is_active" className="text-base font-medium">Active</Label>
                {errors.is_active && <p className="text-sm text-red-500">{errors.is_active}</p>}
              </div>
              
              {/* Buttons */}
              <div className="flex gap-4 pt-4">
                <Button 
                  type="button" 
                  variant="outline" 
                  onClick={() => window.history.back()}
                >
                  Cancel
                </Button>
                <Button 
                  type="submit" 
                  className="bg-teal-600 hover:bg-teal-700"
                  disabled={processing}
                >
                  Create Template
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      </div>
    </AdminLayout>
  );
} 