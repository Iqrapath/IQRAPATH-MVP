import { Button } from "@/components/ui/button";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { MessageCircleIcon } from "@/components/icons/message-circle-icon";
import { StudentIcon } from "@/components/icons/student-icon";
import { PlusIcon } from "@/components/icons/plus-icon";
import { MessageCircleStudentIcon } from "@/components/icons/message-circle-student-icon";
import { Link } from "@inertiajs/react";


interface Student {
  id: number;
  name: string;
  avatar: string | null;
  email: string;
  created_at: string;
}

interface RecentStudentsProps {
  students: Student[];
  totalCount: number;
}

export function RecentStudents({ students, totalCount }: RecentStudentsProps) {
  // Show only the first 3 students
  const displayStudents = students.slice(0, 3);

  return (
    <div className="rounded-2xl bg-teal-50/50 p-6 border">
      <div className="flex justify-between items-center mb-2">
        <div className="flex items-center gap-2">
          <StudentIcon className="text-black w-8 h-8" />
          <h2 className="text-lg font-medium">Recent Students</h2>
        </div>
        <Button size="sm" className="bg-teal-600 hover:bg-teal-700 h-10 w-10 p-0 cursor-pointer rounded-full">
          <PlusIcon className="text-white" />
        </Button>
      </div>
      
      <p className="text-sm text-muted-foreground mb-6">
        You have {totalCount.toLocaleString()} students
      </p>

      <div className="space-y-4">
        {displayStudents.map((student) => (
          <div key={student.id} className="flex items-center justify-between">
            <div className="flex items-center gap-3">
              <Avatar className="h-10 w-10 border-2 border-white">
                <AvatarImage src={student.avatar || undefined} />
                <AvatarFallback>{student.name.substring(0, 2).toUpperCase()}</AvatarFallback>
              </Avatar>
              <Link href={`/admin/students/${student.id}`}>
                <div className="font-medium">{student.name}</div>
              </Link>
            </div>
            <Button variant="ghost" size="lg" className="h-8 w-8 p-0 rounded-full bg-white border-2 border-gray-200 cursor-pointer">
              <MessageCircleStudentIcon className="text-[#338078]" />
            </Button>
          </div>
        ))}
      </div>

      <div className="mt-6 text-center">
        <Button variant="link" className="text-teal-600 p-0 h-auto">
          View more
        </Button>
      </div>
    </div>
  );
} 