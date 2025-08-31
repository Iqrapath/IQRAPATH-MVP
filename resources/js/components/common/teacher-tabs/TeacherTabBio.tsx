import React from 'react';

interface TeacherTabBioProps {
  teacher: {
    bio?: string;
    experience_years?: string;
    teaching_type?: string;
    teaching_mode?: string;
    education?: string;
    qualification?: string;
    languages?: string[] | string;
  } & Record<string, any>;
}

export default function TeacherTabBio({ teacher }: TeacherTabBioProps) {
  
  // Get experience years from database or default
  const experienceYears = teacher.experience_years || "10+";
  
  // Get teaching type/mode from database or defaults
  const teachingType = teacher.teaching_type || "Islamic centers";
  const teachingMode = teacher.teaching_mode || "one-on-one personalized learning";
  
  return (
    <div className="bg-white border border-gray-100 rounded-2xl p-6">
      <p className="text-gray-700 leading-relaxed mb-4">
        {teacher.bio}
      </p>
      
      <div className="space-y-2">
        <h4 className="font-medium text-gray-900 mb-3">Experience & Teaching Style:</h4>
        <ul className="space-y-2 text-sm text-gray-700">
          <li className="flex items-start">
            <span className="inline-block w-2 h-2 bg-[#2C7870] rounded-full mt-2 mr-3 flex-shrink-0"></span>
            Teaching for {experienceYears} years in different {teachingType}
          </li>
          <li className="flex items-start">
            <span className="inline-block w-2 h-2 bg-[#2C7870] rounded-full mt-2 mr-3 flex-shrink-0"></span>
            Specializes in {teachingMode}
          </li>
          <li className="flex items-start">
            <span className="inline-block w-2 h-2 bg-[#2C7870] rounded-full mt-2 mr-3 flex-shrink-0"></span>
            Teaches students of all ages & levels
          </li>
        </ul>
      </div>
    </div>
  );
}


