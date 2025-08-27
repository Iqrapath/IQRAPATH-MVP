import React, { useState } from 'react';
import { Mail, Phone, Users, BookOpen, Clock, CheckCircle, X, User, MapPin } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { router } from '@inertiajs/react';

interface Student {
  id: number;
  name: string;
  email: string | null;
  phone: string | null;
  role: string;
  location: string | null;
  guardian?: {
    id: number;
    name: string;
    email: string;
    phone: string | null;
  } | null;
  children?: {
    id: number;
    name: string;
    age: number | null;
  }[] | null;
  profile?: {
    date_of_birth: string | null;
  } | null;
  stats?: {
    completed_sessions: number;
  };
  status?: string;
  registration_date: string | null;
}

interface Subscription {
  plan_name: string;
  start_date: string;
  end_date: string;
  amount_paid: number;
  currency: string;
  status: string;
  auto_renew: boolean;
}

interface Props {
  student: Student;
  subscription?: Subscription | null;
}

export default function StudentContactDetails({ student, subscription }: Props) {
  const [isEditModalOpen, setIsEditModalOpen] = useState(false);
  const [isSaving, setIsSaving] = useState(false);
  
  // Debug: Log student data
  // console.log('StudentContactDetails - Student data:', student);
  
  const [formData, setFormData] = useState({
    fullName: student.name || '',
    phoneNumber: student.phone || '',
    emailAddress: student.email || '',
    location: student.location || '',
    role: student.role || 'student',
    accountStatus: student.status || 'active',
    registrationDate: student.registration_date || ''
  });

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-NG', {
      style: 'currency',
      currency: 'NGN',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(amount);
  };

  const getParentInfo = () => {
    if (student.role === 'student' && student.guardian) {
      const age = student.profile?.date_of_birth
        ? new Date().getFullYear() - new Date(student.profile.date_of_birth).getFullYear()
        : null;
      return age ? `Parent of: ${student.guardian.name} (Age ${age})` : `Parent of: ${student.guardian.name}`;
    } else if (student.role === 'guardian' && student.children && student.children.length > 0) {
      const firstChild = student.children[0];
      const age = firstChild.age;
      return age ? `Parent of: ${firstChild.name} (Age ${age})` : `Parent of: ${firstChild.name}`;
    }
    return student.role === 'guardian' ? 'Guardian Account' : 'Student Account';
  };

  const getSubscriptionInfo = () => {
    if (subscription && subscription.plan_name && subscription.amount_paid) {
      return `${subscription.plan_name} (${formatCurrency(subscription.amount_paid)}/month)`;
    }
    return subscription?.plan_name || 'No Plan Assigned';
  };

  const getSessionsCompleted = () => {
    if (student.stats?.completed_sessions !== undefined && student.stats.completed_sessions !== null) {
      return `${student.stats.completed_sessions} Sessions Completed`;
    }
    return '0 Sessions Completed';
  };

  const getSubscriptionStatus = () => {
    if (subscription && subscription.status) {
      return subscription.status === 'active' ? 'Active Subscription' : `${subscription.status.charAt(0).toUpperCase() + subscription.status.slice(1)} Subscription`;
    }
    return 'No Subscription';
  };

  const handleEdit = () => {
    setIsEditModalOpen(true);
  };

  const handleCloseModal = () => {
    setIsEditModalOpen(false);
    // Reset form data to original values
    setFormData({
      fullName: student.name || '',
      phoneNumber: student.phone || '',
      emailAddress: student.email || '',
      location: student.location || '',
      role: student.role || 'student',
      accountStatus: student.status || 'active',
      registrationDate: student.registration_date || ''
    });
  };

  const handleInputChange = (field: string, value: string) => {
    setFormData(prev => ({
      ...prev,
      [field]: value
    }));
  };

  const handleSaveAndContinue = () => {
    setIsSaving(true);
    
    // console.log('Sending data to:', `/admin/students/${student.id}/contact-info`);
    // console.log('Data being sent:', {
    //   name: formData.fullName,
    //   email: formData.emailAddress,
    //   phone: formData.phoneNumber,
    //   location: formData.location,
    //   role: formData.role,
    //   account_status: formData.accountStatus,
    // });
    
    // Try Inertia router first
    try {
      router.put(`/admin/students/${student.id}/contact-info`, {
        name: formData.fullName,
        email: formData.emailAddress,
        phone: formData.phoneNumber,
        location: formData.location,
        role: formData.role,
        account_status: formData.accountStatus,
      }, {
        onSuccess: () => {
          // console.log('Contact info updated successfully');
          setIsEditModalOpen(false);
          setIsSaving(false);
          
          // Refresh the page data to show updated information
          router.reload();
        },
        onError: (errors) => {
          setIsSaving(false);
          // console.error('Failed to update contact info:', errors);
          
          // Check for specific error types
          if (errors && typeof errors === 'object') {
            if (errors.message) {
              alert(`Error: ${errors.message}`);
            } else if (errors.error) {
              alert(`Error: ${errors.error}`);
            } else {
              alert('Failed to update contact info. Please check the console for details.');
            }
          } else {
            alert('Failed to update contact info. Please check the console for details.');
          }
        },
        onFinish: () => {
          // console.log('Request finished');
        },
      });
    } catch (error) {
      // console.error('Inertia router error:', error);
      setIsSaving(false);
      alert('Failed to send request. Please check the console for details.');
    }
  };

  return (
    <div className="bg-white rounded-xl shadow-sm p-6">
      <div className="flex items-center justify-between">
        <div className="space-y-6">
          {/* First Row: Email and Phone */}
          <div className="flex items-center gap-6">
                         <div className="flex items-center gap-3">
               <Mail className="h-5 w-5 text-teal-600" />
               <span className="text-gray-700">{student.email || 'No email provided'}</span>
             </div>
             <div className="flex items-center gap-3">
               <Phone className="h-5 w-5 text-teal-600" />
               <span className="text-gray-700">{student.phone || 'No phone provided'}</span>
             </div>
          </div>

          {/* Second Row: Parent Info and Subscription Plan */}
          <div className="flex items-center gap-6">
            <div className="flex items-center gap-3">
              <Users className="h-5 w-5 text-teal-600" />
              <span className="text-gray-700">{getParentInfo()}</span>
            </div>
            <div className="flex items-center gap-3">
              <BookOpen className="h-5 w-5 text-teal-600" />
              <span className="text-gray-700">{getSubscriptionInfo()}</span>
            </div>
          </div>

          {/* Third Row: Sessions Completed and Active Subscription */}
          <div className="flex items-center gap-6">
            <div className="flex items-center gap-3">
              <Clock className="h-5 w-5 text-teal-600" />
              <span className="text-gray-700">{getSessionsCompleted()}</span>
            </div>
                         <div className="flex items-center gap-3">
               <CheckCircle className="h-5 w-5 text-teal-600" />
               <span className="text-gray-700">{getSubscriptionStatus()}</span>
             </div>
          </div>
        </div>
        
        {/* Edit Button - positioned on the right side */}
        <div className="text-right">
          <span 
            onClick={handleEdit}
            className="text-teal-600 text-sm hover:underline cursor-pointer"
          >
            Edit
          </span>
        </div>
      </div>

      {/* Edit Modal */}
      <Dialog open={isEditModalOpen} onOpenChange={setIsEditModalOpen}>
        <DialogContent className="max-w-2xl">
                     <DialogHeader>
             <div className="flex items-center justify-between">
               <DialogTitle className="text-xl font-bold text-gray-800">
                 Student Basic Information
               </DialogTitle>
             </div>
             <DialogDescription className="text-gray-600">
               Update the student's contact information and basic details.
             </DialogDescription>
           </DialogHeader>

          <div className="space-y-6 mt-6">
            {/* Full Name and Phone Number - Side by side */}
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="fullName" className="text-sm font-medium text-gray-700">
                  Full Name
                </Label>
                <div className="relative">
                  <User className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                  <Input
                    id="fullName"
                    value={formData.fullName}
                    onChange={(e) => handleInputChange('fullName', e.target.value)}
                    placeholder="Enter your username"
                    className="pl-10 bg-gray-50 border-gray-200"
                  />
                </div>
              </div>

              <div className="space-y-2">
                <Label htmlFor="phoneNumber" className="text-sm font-medium text-gray-700">
                  Phone Number
                </Label>
                <div className="relative">
                  <Phone className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                  <Input
                    id="phoneNumber"
                    value={formData.phoneNumber}
                    onChange={(e) => handleInputChange('phoneNumber', e.target.value)}
                    placeholder="Enter your Phone Number"
                    className="pl-10 bg-gray-50 border-gray-200"
                  />
                </div>
              </div>
            </div>

            {/* Email Address - Full width */}
            <div className="space-y-2">
              <Label htmlFor="emailAddress" className="text-sm font-medium text-gray-700">
                Email Address
              </Label>
              <div className="relative">
                <Mail className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                <Input
                  id="emailAddress"
                  value={formData.emailAddress}
                  onChange={(e) => handleInputChange('emailAddress', e.target.value)}
                  placeholder="Enter your Delivery Address"
                  className="pl-10 bg-gray-50 border-gray-200"
                />
              </div>
            </div>

            {/* Location - Full width */}
            <div className="space-y-2">
              <Label htmlFor="location" className="text-sm font-medium text-gray-700">
                Location
              </Label>
              <div className="relative">
                <MapPin className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                <Input
                  id="location"
                  value={formData.location}
                  onChange={(e) => handleInputChange('location', e.target.value)}
                  placeholder="Select your location"
                  className="pl-10 bg-gray-50 border-gray-200"
                />
              </div>
            </div>

            {/* Role and Account Status - Side by side */}
            <div className="grid grid-cols-2 gap-4">
                             <div className="space-y-2">
                 <Label htmlFor="role" className="text-sm font-medium text-gray-700">
                   Role
                 </Label>
                 <Select value={formData.role} onValueChange={(value) => handleInputChange('role', value)}>
                   <SelectTrigger className="bg-gray-50 border-gray-200">
                     <SelectValue placeholder="Select role" />
                   </SelectTrigger>
                   <SelectContent>
                     <SelectItem value="student">Student</SelectItem>
                     <SelectItem value="guardian">Guardian</SelectItem>
                   </SelectContent>
                 </Select>
               </div>

               <div className="space-y-2">
                 <Label htmlFor="accountStatus" className="text-sm font-medium text-gray-700">
                   Account Status
                 </Label>
                 <Select value={formData.accountStatus} onValueChange={(value) => handleInputChange('accountStatus', value)}>
                   <SelectTrigger className="bg-gray-50 border-gray-200">
                     <SelectValue placeholder="Select status" />
                   </SelectTrigger>
                   <SelectContent>
                     <SelectItem value="active">Active</SelectItem>
                     <SelectItem value="suspended">Suspended</SelectItem>
                     <SelectItem value="pending">Pending</SelectItem>
                   </SelectContent>
                 </Select>
               </div>
            </div>

            {/* Registration Date - Single field */}
            <div className="space-y-2">
              <Label htmlFor="registrationDate" className="text-sm font-medium text-gray-700">
                Registration Date
              </Label>
              <Input
                id="registrationDate"
                value={formData.registrationDate}
                onChange={(e) => handleInputChange('registrationDate', e.target.value)}
                className="bg-gray-50 border-gray-200"
                readOnly
              />
            </div>
          </div>

          {/* Save and Continue Button */}
          <div className="flex justify-end mt-8">
            <Button
              onClick={handleSaveAndContinue}
              disabled={isSaving}
              className="bg-teal-600 hover:bg-teal-700 text-white px-6 py-2 rounded-lg"
            >
              {isSaving ? 'Saving...' : 'Save and Continue'}
            </Button>
          </div>
        </DialogContent>
      </Dialog>
    </div>
  );
}
