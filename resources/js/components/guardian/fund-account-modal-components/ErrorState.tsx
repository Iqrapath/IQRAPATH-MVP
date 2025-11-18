interface ErrorStateProps {
    error: string;
}

export default function ErrorState({ error }: ErrorStateProps) {
    return (
        <div className="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
            <p className="text-red-800 text-sm">{error}</p>
            <button
                onClick={() => window.location.reload()}
                className="mt-2 text-sm text-red-600 hover:text-red-800 underline"
            >
                Refresh Page
            </button>
        </div>
    );
}

