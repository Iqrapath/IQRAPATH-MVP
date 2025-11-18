export default function LoadingState() {
    return (
        <div className="mb-6 text-center py-8">
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-[#2C7870] mx-auto mb-4"></div>
            <p className="text-gray-600">Loading payment system...</p>
        </div>
    );
}

