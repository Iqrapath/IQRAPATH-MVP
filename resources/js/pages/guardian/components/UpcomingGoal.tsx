interface UpcomingGoalProps {
    goal: string;
}

export default function UpcomingGoal({ goal }: UpcomingGoalProps) {
    return (
        <div className="bg-white rounded-3xl border border-gray-100 shadow-sm p-6">
            <div className="flex items-center">
                <div className="w-2 h-2 bg-teal-500 rounded-full mr-3" />
                <div className="text-gray-900">
                    <span className="font-semibold">Upcoming Goal:</span>
                    <span className="ml-1 font-normal">{goal}</span>
                </div>
            </div>
        </div>
    );
}
