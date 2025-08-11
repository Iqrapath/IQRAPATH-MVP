import React, { useState, useRef } from 'react';

interface VideoProps {
  videoUrl?: string;
  name?: string;
  showPlayButton?: boolean;
  className?: string;
}

const Video: React.FC<VideoProps> = ({
  videoUrl = '',
  name = '',
  showPlayButton = true,
  className = ''
}) => {
  const [isPlaying, setIsPlaying] = useState(false);
  const videoRef = useRef<HTMLVideoElement>(null);

  const handlePlayClick = () => {
    if (videoRef.current) {
      if (isPlaying) {
        videoRef.current.pause();
        setIsPlaying(false);
      } else {
        videoRef.current.play();
        setIsPlaying(true);
      }
    }
  };

  const handleVideoEnded = () => {
    setIsPlaying(false);
  };

  const handleVideoPlay = () => {
    setIsPlaying(true);
  };

  const handleVideoPause = () => {
    setIsPlaying(false);
  };

  return (
    <div className={`relative w-full aspect-[4/3] rounded-2xl overflow-hidden ${className}`}>
      {videoUrl ? (
        <video
          ref={videoRef}
          className="w-full h-full object-cover"
          onEnded={handleVideoEnded}
          onPlay={handleVideoPlay}
          onPause={handleVideoPause}
          preload="metadata"
        >
          <source src={videoUrl} type="video/mp4" />
          <source src={videoUrl} type="video/mov" />
          <source src={videoUrl} type="video/avi" />
          Your browser does not support the video tag.
        </video>
      ) : (
        <div className="w-full h-full bg-gray-100 flex flex-col items-center justify-center">
          <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 14 14">
            <path fill="currentColor" fill-rule="evenodd" d="M.183.183a.625.625 0 0 1 .884 0L2.634 1.75h6.241A1.625 1.625 0 0 1 10.5 3.375v.79l1.97-.873c.345-.133.74-.086 1.045.124c.303.21.487.562.485.93v5.307c.002.369-.182.721-.485.931c-.304.21-.7.257-1.045.124l-1.576-.698l2.923 2.923a.625.625 0 1 1-.884.884L.183 1.067a.625.625 0 0 1 0-.884M.625 3.5c.345 0 .625.28.625.625v6.5a.375.375 0 0 0 .375.375h6.5a.625.625 0 1 1 0 1.25h-6.5A1.625 1.625 0 0 1 0 10.625v-6.5C0 3.78.28 3.5.625 3.5" clip-rule="evenodd" />
          </svg>
          <p className="text-gray-500 text-sm font-medium text-center"> {name} has not uploaded a video yet</p>
        </div>
      )}

      {/* Play/Pause Button Overlay */}
      {showPlayButton && videoUrl && (
        <div className="absolute right-4 bottom-4">
          <div
            className="w-10 h-10 rounded-full bg-[#2EBCAE] flex items-center justify-center shadow-lg hover:bg-[#2EBCAE]/90 transition-colors cursor-pointer"
            onClick={handlePlayClick}
          >
            {isPlaying ? (
              <svg
                className="w-4 h-4 text-white"
                viewBox="0 0 24 24"
                fill="currentColor"
              >
                <path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z" />
              </svg>
            ) : (
              <svg
                className="w-4 h-4 text-white ml-0.5"
                viewBox="0 0 24 24"
                fill="currentColor"
              >
                <path d="M8 5.14v14l11-7-11-7z" />
              </svg>
            )}
          </div>
        </div>
      )}
    </div>
  );
};

export default Video;
