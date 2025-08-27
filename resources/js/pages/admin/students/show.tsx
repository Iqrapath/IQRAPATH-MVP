import React from 'react';
import { Head } from '@inertiajs/react';
import AdminLayout from '@/layouts/admin/admin-layout';
import { Breadcrumbs } from '@/components/breadcrumbs';
import StudentProfileHeader from './show-components/student-profile-header';
import StudentContactDetails from './show-components/student-contact-details';
import StudentSubscriptionInfo from './show-components/student-subscription-info';
import StudentLearningPreferences from './show-components/student-learning-preferences';
import StudentPlanProgress from './show-components/student-plan-progress';
import StudentLearningProgress from './show-components/student-learning-progress';
import StudentBookingActivity from './show-components/student-booking-activity';
import StudentActionButtons from './show-components/student-action-buttons';
import { Student, Subscription, LearningProgressItem } from '@/types/student';


interface Props {
  student: Student;
  subscription?: Subscription | null;
  learningProgress?: LearningProgressItem[];
  learningPreferencesOptions?: {
    subjects: string[];
    ageGroups: string[];
    timeSlots: string[];
  };
}

export default function StudentShow({ 
  student,
  subscription,
  learningProgress,
  learningPreferencesOptions
}: Props) {
  const breadcrumbs = [
    { title: "Dashboard", href: "/admin/dashboard" },
    { title: "Student/Parent Management", href: "/admin/students" },
    { title: student.name, href: `/admin/students/${student.id}` },
  ];

  const getUserType = () => {
    return student.role === 'student' ? 'Student' : 'Parent';
  };

  return (
    <AdminLayout pageTitle={`${student.name} - ${getUserType()} Profile`} showRightSidebar={false}>
      <Head title={`${student.name} - ${getUserType()} Profile`} />
      
      <div className="py-6">
        <div className="flex items-center justify-between mb-8">
          <Breadcrumbs breadcrumbs={breadcrumbs} />
        </div>

        {/* Profile Header */}
        <div className="bg-white rounded-xl shadow-sm mb-6">
          <StudentProfileHeader 
            student={student} 
          />
        </div>

                 {/* Contact Details Section */}
         <div className="mb-6">
           <StudentContactDetails 
             student={student}
             subscription={subscription}
           />
         </div>

         {/* Subscription Information Section */}
         <div className="mb-6">
           <StudentSubscriptionInfo 
             subscription={Array.isArray(subscription) ? null : subscription}
             isGuardian={student.is_guardian}
             childrenSubscriptions={Array.isArray(subscription) ? subscription : undefined}
           />
         </div>

                   {/* Learning Preferences Section */}
         {student.is_guardian && student.children ? (
           // For guardians, show learning preferences for each child
           student.children.map((child) => (
             <div key={child.id} className="mb-6">
               <div className="bg-white rounded-xl shadow-sm">
                 <div className="border-b border-gray-200 px-6 py-4">
                   <h3 className="text-lg font-bold text-gray-800">
                     Learning Preferences: {child.name}
                   </h3>
                 </div>
                 <StudentLearningPreferences 
                   profile={{
                     subjects_of_interest: child.subjects_of_interest,
                     preferred_learning_times: child.preferred_learning_times,
                     learning_goals: child.learning_goals,
                     teaching_mode: child.teaching_mode,
                     additional_notes: child.additional_notes,
                     age_group: child.age_group,
                   }}
                   studentId={child.id}
                   options={learningPreferencesOptions}
                 />
               </div>
             </div>
           ))
         ) : student.profile ? (
           // For students, show their own learning preferences
           <div className="mb-6">
             <StudentLearningPreferences 
               profile={student.profile}
               studentId={student.id}
               options={learningPreferencesOptions}
             />
           </div>
         ) : (
           <div className="mb-6">
             <div className="bg-white rounded-xl shadow-sm p-6">
               <div className="flex-1">
                 <h3 className="text-lg font-bold text-gray-800 mb-4">Learning Preferences</h3>
                 <div className="text-gray-500 text-center py-8">
                   No learning preferences data available
                 </div>
               </div>
             </div>
           </div>
         )}

         {/* Student Plan Progress Section */}
         {student.is_guardian && student.children ? (
           // For guardians, show plan progress for each child
           student.children.map((child) => (
             <div key={child.id} className="mb-6">
               <div className="bg-white rounded-xl shadow-sm">
                 <div className="border-b border-gray-200 px-6 py-4">
                   <h3 className="text-lg font-bold text-gray-800">
                     Student Plan Progress: {child.name}
                   </h3>
                 </div>
                 <StudentPlanProgress 
                   student={{
                     ...student,
                     id: child.id,
                     name: child.name,
                     profile: {
                       grade_level: child.grade_level,
                       school_name: child.school_name,
                       learning_goals: child.learning_goals,
                     }
                   } as Student}
                 />
               </div>
             </div>
           ))
         ) : (
           // For students, show their own plan progress
           <div className="mb-6">
             <StudentPlanProgress 
               student={student}
             />
           </div>
         )}

         {/* Learning Progress Section */}
         {student.is_guardian && student.children ? (
           // For guardians, show learning progress for each child
           student.children.map((child) => (
             <div key={child.id} className="mb-6">
               <div className="bg-white rounded-xl shadow-sm">
                 <div className="border-b border-gray-200 px-6 py-4">
                   <h3 className="text-lg font-bold text-gray-800">
                     Learning Progress: {child.name}
                   </h3>
                 </div>
                 <StudentLearningProgress 
                   student={{
                     ...student,
                     id: child.id,
                     name: child.name,
                     is_guardian: true,
                     profile: {
                       grade_level: child.grade_level,
                       school_name: child.school_name,
                       learning_goals: child.learning_goals,
                     }
                   } as Student}
                   learningProgress={learningProgress?.filter(item => item.student_name === child.name)}
                 />
               </div>
             </div>
           ))
         ) : (
           // For students, show their own learning progress
           <div className="mb-6">
             <StudentLearningProgress 
               student={student}
               learningProgress={learningProgress}
             />
           </div>
         )}

         {/* Booking Activity Section */}
         {student.is_guardian && student.children ? (
           // For guardians, show booking activity for each child
           student.children.map((child) => (
             <div key={child.id} className="mb-6">
               <div className="bg-white rounded-xl shadow-sm">
                 <div className="border-b border-gray-200 px-6 py-4">
                   <h3 className="text-lg font-bold text-gray-800">
                     Booking Activity: {child.name}
                   </h3>
                 </div>
                 <StudentBookingActivity 
                   student={{
                     ...student,
                     id: child.id,
                     name: child.name,
                     profile: {
                       grade_level: child.grade_level,
                       school_name: child.school_name,
                       learning_goals: child.learning_goals,
                     }
                   } as Student}
                 />
               </div>
             </div>
           ))
         ) : (
           // For students, show their own booking activity
           <div className="mb-6">
             <StudentBookingActivity 
               student={student}
             />
           </div>
         )}

         {/* Action Buttons Section */}
         <div className="mb-6">
           <StudentActionButtons 
             student={student}
             onApprove={(studentId) => {
               // Handle approve action
               console.log('Approve student:', studentId);
             }}
             onSendMessage={(studentId) => {
               // Handle send message action
               console.log('Send message to student:', studentId);
             }}
             onReject={(studentId) => {
               // Handle reject action
               console.log('Reject student:', studentId);
             }}
             onDeleteAccount={(studentId) => {
               // Handle delete account action
               console.log('Delete account for student:', studentId);
             }}
           />
         </div>

       </div>
    </AdminLayout>
  );
}
