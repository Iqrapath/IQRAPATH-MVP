import { Button } from '@/components/ui/button';
import { Plus } from 'lucide-react';
import { router } from '@inertiajs/react';

interface IntroVideoProps {
    intro_video_url?: string | null;
}

export default function IntroVideo({ intro_video_url }: IntroVideoProps) {
    const handleAddIntroVideo = () => {
        router.visit(route('teacher.profile.intro-video'));
    };

    return (
        <div className="bg-white rounded-xl shadow-md border">
            <div className="p-6">
                <h3 className="text-lg font-semibold text-gray-900 mb-2">Intro video</h3>
                <p className="text-sm text-gray-600 mb-6">
                    Make a connection with potential buyers while building credibility and gaining trust.
                </p>
                
                <div className="flex justify-end">
                    <Button
                        variant="ghost"
                        size="sm"
                        className="text-green-600 hover:text-green-700 hover:bg-green-50 p-0"
                        onClick={handleAddIntroVideo}
                    >
                        <Plus className="h-4 w-4 mr-2" />
                        Add Intro Video
                    </Button>
                </div>
            </div>
        </div>
    );
}
