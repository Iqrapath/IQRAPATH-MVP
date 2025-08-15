import React, { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Search, MoreVertical, Eye, RefreshCw, Loader2 } from 'lucide-react';
import { toast } from 'sonner';

interface CompletedClass {
  id: number;
  session_uuid: string;
  session_date: string;
  start_time: string;
  end_time: string;
  actual_duration_minutes: number;
  completion_date: string;
  attendance_count: number;
  teacher_rating: number;
  student_rating: number;
  notifications_sent_count: number;
  notification_history: any[];
  status: string;
  teacher: {
    id: number;
    name: string;
    email: string;
  };
  student: {
    id: number;
    name: string;
    email: string;
  };
  subject: {
    id: number;
    name: string;
  };
}

interface Props {
  completedClasses?: CompletedClass[];
}

export default function CompletedClasses({ completedClasses }: Props) {
  const [classes, setClasses] = useState<CompletedClass[]>([]);
  const [loading, setLoading] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');
  const [teacherFilter, setTeacherFilter] = useState('all');
  const [subjectFilter, setSubjectFilter] = useState('all');
  const [dateFilter, setDateFilter] = useState('all');
  const [selectedClasses, setSelectedClasses] = useState<number[]>([]);

  // Initialize with props data
  useEffect(() => {
    if (completedClasses && completedClasses.length > 0) {
      setClasses(completedClasses);
    }
  }, [completedClasses]);

  // Search and filter classes locally
  const searchClasses = () => {
    setLoading(true);
    
    // Start with the original data from props
    let filteredData = completedClasses || [];
    
    // Filter by search query
    if (searchQuery) {
      filteredData = filteredData.filter(cls => 
        cls.session_uuid.toLowerCase().includes(searchQuery.toLowerCase()) ||
        cls.teacher.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
        cls.student.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
        cls.subject.name.toLowerCase().includes(searchQuery.toLowerCase())
      );
    }
    
    // Filter by teacher
    if (teacherFilter !== 'all') {
      filteredData = filteredData.filter(cls => 
        cls.teacher.id.toString() === teacherFilter
      );
    }
    
    // Filter by subject
    if (subjectFilter !== 'all') {
      filteredData = filteredData.filter(cls => 
        cls.subject.id.toString() === subjectFilter
      );
    }
    
    // Filter by date range
    if (dateFilter !== 'all') {
      const now = new Date();
      let startDate: Date;
      
      switch (dateFilter) {
        case 'today':
          startDate = new Date(now.getFullYear(), now.getMonth(), now.getDate());
          break;
        case 'week':
          startDate = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
          break;
        case 'month':
          startDate = new Date(now.getFullYear(), now.getMonth(), 1);
          break;
        default:
          startDate = new Date(0);
      }
      
      filteredData = filteredData.filter(cls => 
        new Date(cls.completion_date) >= startDate
      );
    }
    
    setClasses(filteredData);
    setLoading(false);
  };

  // Resend notifications for a class
  const resendNotifications = async (classId: number) => {
    try {
      const response = await fetch(`/api/admin/completed-classes/${classId}/resend-notifications`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        credentials: 'include',
      });

      if (response.ok) {
        toast.success('Notifications resent successfully');
        // Refresh the data
        if (completedClasses && completedClasses.length > 0) {
          setClasses(completedClasses);
        }
      } else {
        toast.error('Failed to resend notifications');
      }
    } catch (error) {
      console.error('Error resending notifications:', error);
      toast.error('Error resending notifications');
    }
  };

  const handleSelectAll = (checked: boolean) => {
    if (checked) {
      setSelectedClasses(classes.map(cls => cls.id));
    } else {
      setSelectedClasses([]);
    }
  };

  const handleSelectClass = (id: number, checked: boolean) => {
    if (checked) {
      setSelectedClasses(prev => [...prev, id]);
    } else {
      setSelectedClasses(prev => prev.filter(cls => cls !== id));
    }
  };

  const handleSearch = () => {
    searchClasses();
  };

  const handleClearFilters = () => {
    setSearchQuery('');
    setTeacherFilter('all');
    setSubjectFilter('all');
    setDateFilter('all');
    // Reset to original data from props
    if (completedClasses && completedClasses.length > 0) {
      setClasses(completedClasses);
    }
  };

  const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
      hour12: true
    });
  };

  const formatDuration = (minutes: number) => {
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return hours > 0 ? `${hours}h ${mins}m` : `${mins}m`;
  };

  const getAttendancePercentage = (count: number) => {
    const total = 2; // teacher + student
    return Math.round((count / total) * 100);
  };

  const getAverageRating = (teacherRating: number, studentRating: number) => {
    const ratings = [teacherRating, studentRating].filter(r => r > 0);
    if (ratings.length > 0) {
      return (ratings.reduce((a, b) => a + b, 0) / ratings.length).toFixed(1);
    }
    return 'N/A';
  };

  // Get unique teachers and subjects for filters
  const teachers = completedClasses ? 
    Array.from(
      completedClasses.reduce((map, cls) => {
        map.set(cls.teacher.id, { id: cls.teacher.id, name: cls.teacher.name });
        return map;
      }, new Map()).values()
    ) : [];
  
  const subjects = completedClasses ? 
    Array.from(
      completedClasses.reduce((map, cls) => {
        map.set(cls.subject.id, { id: cls.subject.id, name: cls.subject.name });
        return map;
      }, new Map()).values()
    ) : [];

  return (
    <div>
      {/* Header */}
      <div className="flex justify-between items-center mb-6">
        <h2 className="text-xl font-semibold text-gray-900">Completed Classes</h2>
        <div className="flex gap-2">
          <Button
            variant="outline"
            onClick={() => window.open('/admin/completed-classes/export', '_blank')}
            className="border-gray-300 text-gray-700 hover:bg-gray-50"
          >
            Export Data
          </Button>
          <Button
            variant="outline"
            onClick={() => window.open('/admin/completed-classes/reports', '_blank')}
            className="border-teal-600 text-teal-600 hover:bg-teal-50"
          >
            View Reports
          </Button>
        </div>
      </div>

      {/* Search and Filter Bar */}
      <div className="mb-6">
        <div className="flex flex-col md:flex-row gap-4">
          <div className="flex-1">
            <div className="relative">
              <Input
                placeholder="Search by Session ID, Teacher, Student, or Subject"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="pl-10 border rounded-lg h-11"
              />
              <div className="absolute left-3 top-3 text-gray-400">
                <Search size={18} />
              </div>
            </div>
          </div>
          <div className="flex gap-2">
            <Select value={teacherFilter} onValueChange={setTeacherFilter}>
              <SelectTrigger className="w-[140px] border rounded-lg">
                <SelectValue placeholder="Teacher" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Teachers</SelectItem>
                {teachers.map(teacher => (
                  <SelectItem key={teacher.id} value={teacher.id.toString()}>
                    {teacher.name}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            
            <Select value={subjectFilter} onValueChange={setSubjectFilter}>
              <SelectTrigger className="w-[140px] border rounded-lg">
                <SelectValue placeholder="Subject" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Subjects</SelectItem>
                {subjects.map(subject => (
                  <SelectItem key={subject.id} value={subject.id.toString()}>
                    {subject.name}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            
            <Select value={dateFilter} onValueChange={setDateFilter}>
              <SelectTrigger className="w-[140px] border rounded-lg">
                <SelectValue placeholder="Date Range" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Time</SelectItem>
                <SelectItem value="today">Today</SelectItem>
                <SelectItem value="week">Last 7 Days</SelectItem>
                <SelectItem value="month">This Month</SelectItem>
              </SelectContent>
            </Select>
            
            <Button
              type="button"
              onClick={handleSearch}
              disabled={loading}
              className="bg-teal-600 hover:bg-teal-700 text-white border-teal-600"
            >
              {loading ? <Loader2 className="h-4 w-4 animate-spin mr-2" /> : null}
              Search
            </Button>
            
            <Button
              type="button"
              onClick={handleClearFilters}
              disabled={loading}
              variant="outline"
              className="border-gray-300 text-gray-700 hover:bg-gray-50"
            >
              Clear
            </Button>
          </div>
        </div>
      </div>

      {/* Table */}
      <div className="bg-white rounded-lg border">
        <Table>
          <TableHeader className="bg-gray-50">
            <TableRow>
              <TableHead className="w-12">
                <input
                  type="checkbox"
                  className="rounded border-gray-300"
                  checked={selectedClasses.length === classes.length && classes.length > 0}
                  onChange={(e) => handleSelectAll(e.target.checked)}
                />
              </TableHead>
              <TableHead className="font-medium text-gray-900">Session ID</TableHead>
              <TableHead className="font-medium text-gray-900">Subject</TableHead>
              <TableHead className="font-medium text-gray-900">Teacher</TableHead>
              <TableHead className="font-medium text-gray-900">Student</TableHead>
              <TableHead className="font-medium text-gray-900">Completion Date</TableHead>
              <TableHead className="font-medium text-gray-900">Duration</TableHead>
              <TableHead className="font-medium text-gray-900">Attendance</TableHead>
              <TableHead className="font-medium text-gray-900">Rating</TableHead>
              <TableHead className="font-medium text-gray-900">Notifications</TableHead>
              <TableHead className="font-medium text-gray-900">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {loading ? (
              <TableRow>
                <TableCell colSpan={12} className="text-center py-8">
                  <div className="flex items-center justify-center">
                    <Loader2 className="h-6 w-6 animate-spin mr-2" />
                    <span className="text-gray-500">Loading completed classes...</span>
                  </div>
                </TableCell>
              </TableRow>
            ) : classes.length === 0 ? (
              <TableRow>
                <TableCell colSpan={12} className="text-center py-8 text-gray-500">
                  No completed classes found
                </TableCell>
              </TableRow>
            ) : (
              classes.map((cls) => (
                <TableRow key={cls.id} className="hover:bg-gray-50">
                  <TableCell>
                    <input
                      type="checkbox"
                      className="rounded border-gray-300"
                      checked={selectedClasses.includes(cls.id)}
                      onChange={(e) => handleSelectClass(cls.id, e.target.checked)}
                    />
                  </TableCell>
                  <TableCell>
                    <div className="font-medium text-gray-900">{cls.session_uuid}</div>
                  </TableCell>
                  <TableCell>
                    <div className="text-sm text-gray-600">{cls.subject.name}</div>
                  </TableCell>
                  <TableCell>
                    <div className="text-sm">
                      <div className="font-medium">{cls.teacher.name}</div>
                      <div className="text-gray-600">{cls.teacher.email}</div>
                    </div>
                  </TableCell>
                  <TableCell>
                    <div className="text-sm">
                      <div className="font-medium">{cls.student.name}</div>
                      <div className="text-gray-600">{cls.student.email}</div>
                    </div>
                  </TableCell>
                  <TableCell className="text-sm text-gray-900">
                    {formatDate(cls.completion_date)}
                  </TableCell>
                  <TableCell className="text-sm text-gray-600">
                    {formatDuration(cls.actual_duration_minutes || 0)}
                  </TableCell>
                  <TableCell>
                    <div className="text-sm">
                      <div className="font-medium">{cls.attendance_count}/2</div>
                      <div className="text-gray-600">{getAttendancePercentage(cls.attendance_count)}%</div>
                    </div>
                  </TableCell>
                  <TableCell className="text-sm text-gray-600">
                    {getAverageRating(cls.teacher_rating, cls.student_rating)}
                  </TableCell>
                  <TableCell>
                    <div className="text-sm">
                      <div className="font-medium">{cls.notifications_sent_count}</div>
                      <div className="text-gray-600">sent</div>
                    </div>
                  </TableCell>
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
                      <DropdownMenuContent align="end" className="w-48">
                        <DropdownMenuItem className="cursor-pointer">
                          <Eye className="h-4 w-4 mr-2" />
                          View Details
                        </DropdownMenuItem>
                        <DropdownMenuItem 
                          className="cursor-pointer"
                          onClick={() => resendNotifications(cls.id)}
                        >
                          <RefreshCw className="h-4 w-4 mr-2" />
                          Resend Notifications
                        </DropdownMenuItem>
                        <DropdownMenuItem className="cursor-pointer">
                          View Notification History
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

      {/* Selected Actions */}
      {selectedClasses.length > 0 && (
        <div className="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
          <div className="flex items-center justify-between">
            <span className="text-sm text-blue-800">
              {selectedClasses.length} class(es) selected
            </span>
            <div className="flex gap-2">
              <Button
                size="sm"
                variant="outline"
                className="border-blue-300 text-blue-700 hover:bg-blue-100"
              >
                Export Selected
              </Button>
              <Button
                size="sm"
                variant="outline"
                className="border-teal-300 text-teal-700 hover:bg-teal-100"
                onClick={() => {
                  selectedClasses.forEach(id => resendNotifications(id));
                  setSelectedClasses([]);
                }}
              >
                Resend Notifications
              </Button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
