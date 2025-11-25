interface RecordingControlsProps {
    recordingTime: number;
    onCancel: () => void;
    onSend: () => void;
}

export function RecordingControls({ recordingTime, onCancel, onSend }: RecordingControlsProps) {
    const formatRecordingTime = (seconds: number) => {
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    };

    return (
        <div className="flex items-center gap-3 bg-red-50 px-4 py-2 rounded-full">
            <div className="flex items-center gap-2">
                <div className="w-2 h-2 bg-red-500 rounded-full animate-pulse"></div>
                <span className="text-sm font-medium text-red-600">
                    {formatRecordingTime(recordingTime)}
                </span>
            </div>
            <button 
                onClick={onCancel}
                className="text-gray-600 hover:text-gray-800 text-sm"
            >
                Cancel
            </button>
            <button 
                onClick={onSend}
                className="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded-full text-sm"
            >
                Send
            </button>
        </div>
    );
}
