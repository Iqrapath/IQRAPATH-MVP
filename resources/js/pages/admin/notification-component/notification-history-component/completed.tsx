import { useState } from 'react';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { Eye, MoreVertical } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { 
  DropdownMenu, 
  DropdownMenuContent, 
  DropdownMenuItem, 
  DropdownMenuTrigger 
} from '@/components/ui/dropdown-menu';

interface User {
  id: number;
  name: string;
  email: string;
}

interface Subject {
  id: number;
  name: string;
  teacher_profile_id: number;
}

interface TeachingSession {
  id: number;
  session_uuid: string;
  booking_id: number | null;
  teacher_id: number;
  student_id: number;
  subject_id: number;
  session_date: string;
  start_time: string;
  end_time: string;
  actual_duration_minutes: number | null;
  status: string;
  meeting_link: string | null;
  meeting_platform: string | null;
  meeting_password: string | null;
  recording_url: string | null;
  teacher_notes: string | null;
  student_notes: string | null;
  teacher?: User;
  student?: User;
  subject?: Subject;
}

interface CompletedProps {
  completedClasses?: {
    data: TeachingSession[];
    links?: any;
    meta?: {
      current_page: number;
      from: number;
      last_page: number;
      links: any[];
      path: string;
      per_page: number;
      to: number;
      total: number;
    };
  };
  filters?: {
    search?: string;
    status?: string;
    subject?: string;
    rating?: string;
  };
}

export default function Completed({ completedClasses = { data: [] }, filters = {} }: CompletedProps) {
  const [search, setSearch] = useState(filters.search || '');
  const [status, setStatus] = useState(filters.status || '');
  const [subject, setSubject] = useState(filters.subject || '');
  const [rating, setRating] = useState(filters.rating || '');
  const [selectedItems, setSelectedItems] = useState<number[]>([]);

  // Format date for display
  const formatDate = (dateString: string) => {
    if (!dateString) return { date: '-', time: '-' };
    
    const date = new Date(dateString);
    return {
      date: date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }),
      time: date.toLocaleTimeString('en-US', { hour: 'numeric', minute: 'numeric', hour12: true })
    };
  };

  // Format time for display
  const formatTime = (timeString: string) => {
    if (!timeString) return '-';
    
    // Parse time string (HH:MM:SS)
    const [hours, minutes] = timeString.split(':');
    const date = new Date();
    date.setHours(parseInt(hours, 10));
    date.setMinutes(parseInt(minutes, 10));
    
    return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: 'numeric', hour12: true });
  };

  // Format duration in minutes to hours and minutes
  const formatDuration = (minutes: number | null) => {
    if (!minutes) return '0 min';
    
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    
    if (hours === 0) return `${mins} min`;
    if (mins === 0) return `${hours} hr`;
    return `${hours} hr ${mins} min`;
  };

  // Handle search form submission
  const handleSearch = () => {
    const url = new URL(window.location.href);
    if (search) url.searchParams.set('search', search);
    else url.searchParams.delete('search');
    
    if (status && status !== 'all') url.searchParams.set('status', status);
    else url.searchParams.delete('status');
    
    if (subject && subject !== 'all') url.searchParams.set('subject', subject);
    else url.searchParams.delete('subject');
    
    if (rating && rating !== 'all') url.searchParams.set('rating', rating);
    else url.searchParams.delete('rating');
    
    window.location.href = url.toString();
  };

  // Handle select all checkbox
  const handleSelectAll = (checked: boolean) => {
    if (checked && completedClasses?.data) {
      setSelectedItems(completedClasses.data.map(item => item.id));
    } else {
      setSelectedItems([]);
    }
  };

  // Handle select individual item
  const handleSelectItem = (checked: boolean, id: number) => {
    if (checked) {
      setSelectedItems([...selectedItems, id]);
    } else {
      setSelectedItems(selectedItems.filter(item => item !== id));
    }
  };

  const isSelected = (id: number) => selectedItems.includes(id);
  const isAllSelected = completedClasses?.data?.length > 0 && selectedItems.length === completedClasses.data.length;

  return (
    <>
      {/* Completed Classes Header */}
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-2xl font-bold">Completed Classes</h1>
        <Link href="/admin/classes/reports">
          <Button className="bg-teal-600 hover:bg-teal-700">
            Generate Report
          </Button>
        </Link>
      </div>

      {/* Search and filters */}
      <div className="flex flex-wrap gap-4 mb-6">
        <div className="flex-1 min-w-[240px]">
          <Input 
            placeholder="Search by Teacher / Student" 
            value={search} 
            onChange={(e) => setSearch(e.target.value)}
            className="w-full"
          />
        </div>
        
        <div className="w-40">
          <Select value={status} onValueChange={setStatus}>
            <SelectTrigger>
              <SelectValue placeholder="Status" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All Status</SelectItem>
              <SelectItem value="completed">Completed</SelectItem>
              <SelectItem value="cancelled">Cancelled</SelectItem>
              <SelectItem value="no_show">No Show</SelectItem>
            </SelectContent>
          </Select>
        </div>
        
        <div className="w-40">
          <Select value={subject} onValueChange={setSubject}>
            <SelectTrigger>
              <SelectValue placeholder="Subject" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All Subjects</SelectItem>
              <SelectItem value="tajweed">Tajweed</SelectItem>
              <SelectItem value="quran">Quran</SelectItem>
              <SelectItem value="arabic">Arabic</SelectItem>
              <SelectItem value="islamic-studies">Islamic Studies</SelectItem>
              <SelectItem value="fiqh">Fiqh</SelectItem>
            </SelectContent>
          </Select>
        </div>
        
        <Button 
          className="bg-white text-[#338078] border-2 border-[#338078] rounded-full hover:bg-[#338078] hover:text-white transition-all duration-300 cursor-pointer"
          onClick={handleSearch}
        >
          Search
        </Button>
      </div>
      
      {/* Completed Classes table */}
      <div className="bg-white rounded-lg shadow overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead className="bg-gray-50 text-left">
              <tr>
                <th className="py-3 px-4 w-10">
                  <Checkbox 
                    checked={isAllSelected}
                    onCheckedChange={(checked) => handleSelectAll(!!checked)}
                  />
                </th>
                <th className="py-3 px-4 font-medium text-sm">Date</th>
                <th className="py-3 px-4 font-medium text-sm">Time</th>
                <th className="py-3 px-4 font-medium text-sm">Subject</th>
                <th className="py-3 px-4 font-medium text-sm">Teacher</th>
                <th className="py-3 px-4 font-medium text-sm">Student</th>
                <th className="py-3 px-4 font-medium text-sm">Duration</th>
                <th className="py-3 px-4 font-medium text-sm">Status</th>
                <th className="py-3 px-4 font-medium text-sm text-center">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200">
              {completedClasses.data.length > 0 ? (
                completedClasses.data.map((session) => {
                  const { date } = formatDate(session.session_date);
                  return (
                    <tr key={session.id} className="hover:bg-gray-50">
                      <td className="py-4 px-4">
                        <Checkbox 
                          checked={isSelected(session.id)}
                          onCheckedChange={(checked) => handleSelectItem(!!checked, session.id)}
                        />
                      </td>
                      <td className="py-4 px-4 text-sm">
                        {date}
                      </td>
                      <td className="py-4 px-4 text-sm">
                        {formatTime(session.start_time)} - {formatTime(session.end_time)}
                      </td>
                      <td className="py-4 px-4">
                        {session.subject?.name || 'Unknown Subject'}
                      </td>
                      <td className="py-4 px-4">
                        {session.teacher?.name || 'Unknown Teacher'}
                      </td>
                      <td className="py-4 px-4">
                        {session.student?.name || 'Unknown Student'}
                      </td>
                      <td className="py-4 px-4">
                        {formatDuration(session.actual_duration_minutes)}
                      </td>
                      <td className="py-4 px-4">
                        <span className={`px-2 py-1 rounded-full text-xs ${
                          session.status === 'completed'
                            ? 'bg-green-100 text-green-800' 
                            : session.status === 'cancelled'
                              ? 'bg-red-100 text-red-800'
                              : 'bg-yellow-100 text-yellow-800'
                        }`}>
                          {session.status.replace('_', ' ')}
                        </span>
                      </td>
                      <td className="py-4 px-4 text-center">
                        <div className="flex justify-center">
                          <Button variant="ghost" size="sm" className="text-gray-500 hover:text-gray-700 mr-2">
                            <Eye className="h-5 w-5" />
                          </Button>
                          <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                              <Button variant="ghost" size="sm" className="text-gray-500 hover:text-gray-700">
                                <MoreVertical className="h-5 w-5" />
                              </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                              <DropdownMenuItem>View Details</DropdownMenuItem>
                              {session.recording_url && (
                                <DropdownMenuItem>View Recording</DropdownMenuItem>
                              )}
                              {session.teacher_notes && (
                                <DropdownMenuItem>View Teacher Notes</DropdownMenuItem>
                              )}
                              <DropdownMenuItem>Generate Report</DropdownMenuItem>
                            </DropdownMenuContent>
                          </DropdownMenu>
                        </div>
                      </td>
                    </tr>
                  );
                })
              ) : (
                <tr>
                  <td colSpan={9} className="py-8 text-center text-gray-500">
                    No completed classes found
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
        
        {/* Pagination */}
        {completedClasses.meta && completedClasses.meta.last_page > 1 && (
          <div className="px-6 py-3 flex items-center justify-between border-t border-gray-200">
            <div className="flex-1 flex justify-between sm:hidden">
              <Button 
                variant="outline" 
                disabled={completedClasses.meta.current_page === 1}
                onClick={() => window.location.href = completedClasses.links.prev}
                className="bg-white text-[#338078] border-2 border-[#338078] rounded-full hover:bg-[#338078] hover:text-white transition-all duration-300 cursor-pointer"
              >
                Previous
              </Button>
              <Button 
                variant="outline" 
                disabled={completedClasses.meta.current_page === completedClasses.meta.last_page}
                onClick={() => window.location.href = completedClasses.links.next}
                className="bg-white text-[#338078] border-2 border-[#338078] rounded-full hover:bg-[#338078] hover:text-white transition-all duration-300 cursor-pointer"
              >
                Next
              </Button>
            </div>
            <div className="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
              <div>
                <p className="text-sm text-gray-700">
                  Showing <span className="font-medium">{completedClasses.meta.from}</span> to{' '}
                  <span className="font-medium">{completedClasses.meta.to}</span> of{' '}
                  <span className="font-medium">{completedClasses.meta.total}</span> results
                </p>
              </div>
              <div>
                <nav className="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                  {completedClasses.meta.links.map((link, index) => {
                    if (link.url === null) return null;
                    
                    return (
                      <Link
                        key={index}
                        href={link.url}
                        className={`relative inline-flex items-center px-4 py-2 border text-sm font-medium ${
                          link.active 
                            ? 'z-10 bg-teal-600 border-teal-600 text-white' 
                            : 'bg-white text-[#338078] border-2 border-[#338078] rounded-full hover:bg-[#338078] hover:text-white transition-all duration-300 shadow-sm cursor-pointer'
                        } ${
                          link.label.includes('Previous') 
                            ? 'rounded-l-md' 
                            : link.label.includes('Next') 
                              ? 'rounded-r-md' 
                              : ''
                        }`}
                        dangerouslySetInnerHTML={{ __html: link.label }}
                      />
                    );
                  })}
                </nav>
              </div>
            </div>
          </div>
        )}
      </div>
    </>
  );
}
