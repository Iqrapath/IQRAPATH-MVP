import { TeacherUpcomingSessions } from '../components/TeacherUpcomingSessions';

interface UpcomingSession {
    id: number;
    student_name: string;
    subject: string;
    date: string;
    time: string;
    status: string;
}

interface UpcomingSessionTabProps {
    sessions: UpcomingSession[];
    onViewDetails: (session: UpcomingSession) => void;
}

export function UpcomingSessionTab({ sessions, onViewDetails }: UpcomingSessionTabProps) {
    return (
        <TeacherUpcomingSessions sessions={sessions} />
    );
}
