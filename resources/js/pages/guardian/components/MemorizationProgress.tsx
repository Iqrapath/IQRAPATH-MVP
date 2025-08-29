interface Subject {
    name: string;
    status: string;
    color: 'yellow' | 'green' | 'none';
}

interface MemorizationProgressProps {
    currentJuz: string;
    progressPercentage: number;
    subjects: Subject[];
}

const getSubjectColor = (color: 'yellow' | 'green' | 'none') => {
    switch (color) {
        case 'yellow':
            return 'bg-yellow-400';
        case 'green':
            return 'bg-green-400';
        default:
            return '';
    }
};

export default function MemorizationProgress({ currentJuz, progressPercentage, subjects }: MemorizationProgressProps) {
    const radius = 60;
    const circumference = 2 * Math.PI * radius;
    const strokeDasharray = circumference;
    const strokeDashoffset = circumference - (progressPercentage / 100) * circumference;

    return (
        <div className="bg-white rounded-3xl border border-gray-100 shadow-sm p-6">
            <h3 className="text-lg font-semibold text-gray-900 mb-6">
                Memorization Progress
            </h3>

            <div className="flex items-start gap-8">
                {/* Progress Circle Section */}
                <div className="flex items-center gap-4">
                    <div className="text-gray-900 font-medium">
                        {currentJuz}
                    </div>
                    <div className="relative">
                        <svg className="w-32 h-32 transform -rotate-90" viewBox="0 0 140 140">
                            {/* Background circle */}
                            <circle
                                cx="70"
                                cy="70"
                                r={radius}
                                stroke="#e5e7eb"
                                strokeWidth="8"
                                fill="none"
                            />
                            {/* Progress circle */}
                            <circle
                                cx="70"
                                cy="70"
                                r={radius}
                                stroke="url(#gradient)"
                                strokeWidth="8"
                                fill="none"
                                strokeDasharray={strokeDasharray}
                                strokeDashoffset={strokeDashoffset}
                                strokeLinecap="round"
                                className="transition-all duration-1000 ease-out"
                            />
                            {/* Gradient definition */}
                            <defs>
                                <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stopColor="#16a34a" />
                                    <stop offset="100%" stopColor="#84cc16" />
                                </linearGradient>
                            </defs>
                        </svg>
                        {/* Center text */}
                        <div className="absolute inset-0 flex flex-col items-center justify-center">
                            <span className="text-xs text-gray-600">Completed</span>
                            <span className="text-2xl font-bold text-gray-900">{progressPercentage}%</span>
                        </div>
                    </div>
                </div>

                {/* Subjects Section */}
                <div className="flex-1">
                    <h4 className="text-gray-900 font-medium mb-3">Subjects:</h4>
                    <ul className="space-y-2">
                        {subjects.map((subject, index) => (
                            <li key={index} className="flex items-center gap-2">
                                {subject.color !== 'none' && (
                                    <div className={`w-2 h-2 rounded-full ${getSubjectColor(subject.color)}`} />
                                )}
                                <span className="text-gray-900">
                                    <span className="font-medium">{subject.name}:</span> {subject.status}
                                </span>
                            </li>
                        ))}
                    </ul>
                </div>
            </div>

            {/* Download Link */}
            <div className="mt-6">
                <button className="text-teal-600 hover:text-teal-700 font-medium text-sm transition-colors underline">
                    Download Progress Report PDF
                </button>
            </div>
        </div>
    );
}
