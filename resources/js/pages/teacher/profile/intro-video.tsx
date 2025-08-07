import { Head } from '@inertiajs/react';
import TeacherLayout from '@/layouts/teacher/teacher-layout';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { ArrowLeft, Upload, Info } from 'lucide-react';
import { useForm } from '@inertiajs/react';
import { toast } from 'sonner';
import { router } from '@inertiajs/react';

interface IntroVideoPageProps {
    intro_video_url?: string | null;
}

export default function IntroVideoPage({ intro_video_url }: IntroVideoPageProps) {
    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    const [isDragOver, setIsDragOver] = useState(false);
    const [uploadProgress, setUploadProgress] = useState(0);

    const { data, setData, post, processing, errors } = useForm({
        video: null as File | null,
    });

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            handleFile(file);
        }
    };

    const handleFile = (file: File) => {
        // Validate file type
        if (!file.type.startsWith('video/')) {
            toast.error('Please select a valid video file');
            return;
        }

        // Validate file size (7MB max)
        const maxSize = 7 * 1024 * 1024; // 7MB
        if (file.size > maxSize) {
            toast.error('Video file size must be less than 7MB', {
                description: 'Please compress your video before uploading.',
            });
            return;
        }

        setSelectedFile(file);
        setData('video', file);
    };

    const handleDragOver = (e: React.DragEvent) => {
        e.preventDefault();
        setIsDragOver(true);
    };

    const handleDragLeave = (e: React.DragEvent) => {
        e.preventDefault();
        setIsDragOver(false);
    };

    const handleDrop = (e: React.DragEvent) => {
        e.preventDefault();
        setIsDragOver(false);
        
        const file = e.dataTransfer.files[0];
        if (file) {
            handleFile(file);
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        
        if (!selectedFile) {
            toast.error('Please select a video file');
            return;
        }

        // Check file size before upload
        const maxSize = 7 * 1024 * 1024; // 7MB
        if (selectedFile.size > maxSize) {
            toast.error('File size too large', {
                description: 'Please select a video file smaller than 7MB.',
            });
            return;
        }

        post(route('teacher.profile.upload-intro-video'), {
            preserveScroll: true,
            onStart: () => {
                setUploadProgress(0);
                toast.info('Uploading video...', {
                    description: 'This may take a few minutes for large files.',
                });
            },
            onProgress: (progress) => {
                if (progress && typeof progress === 'object' && 'loaded' in progress && 'total' in progress && progress.total) {
                    const percentage = (progress.loaded / progress.total) * 100;
                    setUploadProgress(Math.round(percentage));
                }
            },
            onSuccess: () => {
                setUploadProgress(100);
                toast.success('Intro video uploaded successfully!', {
                    description: 'Your intro video has been saved.',
                });
                router.visit(route('teacher.profile.index'));
            },
            onError: (errors) => {
                setUploadProgress(0);
                console.error('Upload errors:', errors);
                toast.error('Failed to upload intro video', {
                    description: Object.values(errors).flat().join(', '),
                });
            },
            onFinish: () => {
                // This will be called regardless of success or error
            },
        });
    };

    const handleBack = () => {
        router.visit(route('teacher.profile.index'));
    };

    return (
        <TeacherLayout pageTitle="Intro Video">
            <Head title="Intro Video" />

            <div className="container mx-auto py-6 px-4">
                {/* Header */}
                <div className="mb-8">
                    <h1 className="text-3xl font-bold text-gray-900 mb-2">Intro Video</h1>
                    <h2 className="text-xl font-semibold text-gray-900 mb-2">Upload your intro video</h2>
                    <p className="text-gray-600">
                        Connecting over video is a great way to build credibility, gain trust, and increase student conversion rate
                    </p>
                </div>

                {/* Information Box */}
                <div className="bg-green-50 border border-green-200 rounded-lg p-4 mb-8">
                    <div className="flex items-start">
                        <div className="bg-blue-100 rounded-full p-2 mr-3 mt-0.5">
                            <Info className="h-4 w-4 text-blue-600" />
                        </div>
                        <div>
                            <p className="text-sm font-medium text-gray-900 mb-1">
                                Review our guidelines before uploading your file
                            </p>
                            <p className="text-sm text-gray-600">
                                Videos that don't follow these guidelines or have poor audio or video quality can hurt sales and won't be approved. Please ensure your video is under 7MB before uploading.
                            </p>
                            <p className="text-xs text-gray-500 mt-2">
                                💡 Tip: Use online tools like HandBrake, FFmpeg, or online video compressors to reduce file size while maintaining quality.
                            </p>
                        </div>
                    </div>
                </div>

                {/* Video Preview */}

                
                {/* Main Content - Two Columns */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    {/* Left Column - Video Requirements */}
                    <div>
                        <h3 className="text-lg font-semibold text-gray-900 mb-4">Video requirements:</h3>
                        <ul className="space-y-3">
                            <li className="flex items-start">
                                <span className="w-2 h-2 bg-gray-400 rounded-full mt-2 mr-3 flex-shrink-0"></span>
                                <span className="text-gray-700">Length: 20 - 60 seconds</span>
                            </li>
                            <li className="flex items-start">
                                <span className="w-2 h-2 bg-gray-400 rounded-full mt-2 mr-3 flex-shrink-0"></span>
                                <span className="text-gray-700">Minimum resolution: 1280x720</span>
                            </li>
                            <li className="flex items-start">
                                <span className="w-2 h-2 bg-gray-400 rounded-full mt-2 mr-3 flex-shrink-0"></span>
                                <span className="text-gray-700">Aspect ratio: 16:9 (landscape)</span>
                            </li>
                            <li className="flex items-start">
                                <span className="w-2 h-2 bg-gray-400 rounded-full mt-2 mr-3 flex-shrink-0"></span>
                                <span className="text-gray-700">File size: Up to 7 MB</span>
                            </li>
                        </ul>
                    </div>

                    {/* Right Column - Video Upload Area */}
                    <div>
                        <div 
                            className={`border-2 border-dashed rounded-lg p-8 text-center transition-colors ${
                                isDragOver 
                                    ? 'border-green-400 bg-green-50' 
                                    : 'border-gray-300 bg-gray-50'
                            }`}
                            onDragOver={handleDragOver}
                            onDragLeave={handleDragLeave}
                            onDrop={handleDrop}
                        >
                            <Upload className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                            <h3 className="text-xl font-semibold text-gray-900 mb-2">Upload your video</h3>
                            
                            <input
                                type="file"
                                accept="video/*"
                                onChange={handleFileChange}
                                className="hidden"
                                id="video-upload"
                            />
                            <label
                                htmlFor="video-upload"
                                className="inline-block text-green-600 hover:text-green-700 cursor-pointer font-medium"
                            >
                                Choose a file or drop it here
                            </label>
                            
                            {selectedFile && (
                                <div className="mt-4 p-3 bg-green-50 border border-green-200 rounded-lg">
                                    <p className="text-sm text-green-800 font-medium">
                                        Selected: {selectedFile.name}
                                    </p>
                                    <p className="text-xs text-green-600 mt-1">
                                        Size: {(selectedFile.size / (1024 * 1024)).toFixed(2)} MB
                                    </p>
                                </div>
                            )}
                            
                            <p className="text-sm text-gray-500 mt-4">
                                You can upload the following formats: .mp4, .mov, .avi up to 7MB
                            </p>
                        </div>

                        {errors.video && (
                            <p className="text-red-500 text-sm mt-2">{errors.video}</p>
                        )}
                        
                        {/* Upload Progress */}
                        {processing && uploadProgress > 0 && (
                            <div className="mt-4">
                                <div className="flex justify-between text-sm text-gray-600 mb-1">
                                    <span>Uploading...</span>
                                    <span>{uploadProgress}%</span>
                                </div>
                                <div className="w-full bg-gray-200 rounded-full h-2">
                                    <div 
                                        className="bg-green-600 h-2 rounded-full transition-all duration-300"
                                        style={{ width: `${uploadProgress}%` }}
                                    ></div>
                                </div>
                            </div>
                        )}
                    </div>
                </div>

                {/* Submit Button */}
                <div className="flex justify-end mt-8">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={handleBack}
                        className="mr-4"
                    >
                        Cancel
                    </Button>
                    <Button
                        onClick={handleSubmit}
                        disabled={processing || !selectedFile}
                        className="bg-green-600 text-white hover:bg-green-700 px-8 py-3 text-base font-medium"
                    >
                        {processing ? `Uploading... ${uploadProgress > 0 ? `${uploadProgress}%` : ''}` : 'Submit'}
                    </Button>
                </div>
            </div>
        </TeacherLayout>
    );
}
