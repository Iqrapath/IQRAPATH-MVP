export interface Student {
  id: number;
  name: string;
  email: string | null;
  phone: string | null;
  avatar: string | null;
  role: string;
  status: string;
  registration_date: string | null;
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
    grade_level: string | null;
    school_name: string | null;
    learning_goals: string | null;
    subjects_of_interest: string[] | null;
    preferred_learning_times: string[] | null;
    teaching_mode: string | null;
    additional_notes: string | null;
    age_group: string | null;
  }[] | null;
  profile?: {
    date_of_birth: string | null;
    gender: string | null;
    grade_level: string | null;
    school_name: string | null;
    learning_goals: string | null;
    subjects_of_interest: string[] | null;
    preferred_learning_times: string[] | null;
    teaching_mode: string | null;
    additional_notes: string | null;
    age_group: string | null;
  } | null;
  stats?: {
    completed_sessions: number;
    total_sessions: number;
    attendance_percentage: number;
    missed_sessions: number;
    average_engagement: number;
  };
  upcoming_sessions?: Array<{
    date: string;
    time: string;
    teacher_name: string;
  }>;
  rescheduled_sessions?: number;
  is_guardian?: boolean;
}

export interface Subscription {
  plan_name: string;
  start_date: string;
  end_date: string;
  amount_paid: number;
  currency: string;
  status: string;
  auto_renew: boolean;
  student_name?: string; // For guardian views to show which child
}

export interface LearningProgressItem {
  subject: string;
  progress_percentage: number;
  completed_sessions: number;
  total_sessions: number;
  certificates_earned: number;
  student_name?: string; // For guardian views
}
