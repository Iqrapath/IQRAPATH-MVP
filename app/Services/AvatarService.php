<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AvatarService
{
    /**
     * Download and store OAuth avatar
     * 
     * @param string $avatarUrl
     * @param string $provider
     * @param string $providerId
     * @return string|null Local path to stored avatar
     */
    public function downloadOAuthAvatar(string $avatarUrl, string $provider, string $providerId): ?string
    {
        try {
            // Validate URL
            if (!filter_var($avatarUrl, FILTER_VALIDATE_URL)) {
                Log::warning('Invalid OAuth avatar URL', [
                    'url' => $avatarUrl,
                    'provider' => $provider,
                ]);
                return null;
            }

            // Download avatar with timeout
            $response = Http::timeout(10)->get($avatarUrl);

            if (!$response->successful()) {
                Log::warning('Failed to download OAuth avatar', [
                    'url' => $avatarUrl,
                    'provider' => $provider,
                    'status' => $response->status(),
                ]);
                return null;
            }

            // Get content type and validate it's an image
            $contentType = $response->header('Content-Type');
            if (!str_starts_with($contentType, 'image/')) {
                Log::warning('OAuth avatar is not an image', [
                    'url' => $avatarUrl,
                    'provider' => $provider,
                    'content_type' => $contentType,
                ]);
                return null;
            }

            // Determine file extension
            $extension = $this->getExtensionFromContentType($contentType);
            
            // Generate unique filename
            $filename = sprintf(
                'oauth/%s/%s_%s.%s',
                $provider,
                $providerId,
                Str::random(8),
                $extension
            );

            // Store avatar
            $stored = Storage::disk('public')->put($filename, $response->body());

            if (!$stored) {
                Log::error('Failed to store OAuth avatar', [
                    'provider' => $provider,
                    'filename' => $filename,
                ]);
                return null;
            }

            Log::info('OAuth avatar downloaded and cached', [
                'provider' => $provider,
                'provider_id' => $providerId,
                'filename' => $filename,
            ]);

            return $filename;
        } catch (\Exception $e) {
            Log::error('Exception downloading OAuth avatar', [
                'url' => $avatarUrl,
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get file extension from content type
     * 
     * @param string $contentType
     * @return string
     */
    private function getExtensionFromContentType(string $contentType): string
    {
        return match (strtolower($contentType)) {
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => 'jpg',
        };
    }

    /**
     * Delete old OAuth avatar
     * 
     * @param string|null $avatarPath
     * @return bool
     */
    public function deleteOAuthAvatar(?string $avatarPath): bool
    {
        if (!$avatarPath) {
            return false;
        }

        // Only delete OAuth avatars (safety check)
        if (!str_starts_with($avatarPath, 'oauth/')) {
            return false;
        }

        try {
            if (Storage::disk('public')->exists($avatarPath)) {
                Storage::disk('public')->delete($avatarPath);
                
                Log::info('OAuth avatar deleted', [
                    'path' => $avatarPath,
                ]);
                
                return true;
            }
        } catch (\Exception $e) {
            Log::error('Failed to delete OAuth avatar', [
                'path' => $avatarPath,
                'error' => $e->getMessage(),
            ]);
        }

        return false;
    }

    /**
     * Cleanup old OAuth avatars for a user
     * 
     * @param string $provider
     * @param string $providerId
     * @param string|null $keepPath Path to keep (current avatar)
     * @return int Number of avatars deleted
     */
    public function cleanupOldAvatars(string $provider, string $providerId, ?string $keepPath = null): int
    {
        try {
            $directory = "oauth/{$provider}";
            $pattern = "{$providerId}_";
            
            $files = Storage::disk('public')->files($directory);
            $deleted = 0;

            foreach ($files as $file) {
                // Skip if this is the current avatar
                if ($keepPath && $file === $keepPath) {
                    continue;
                }

                // Check if file belongs to this provider ID
                if (str_contains(basename($file), $pattern)) {
                    Storage::disk('public')->delete($file);
                    $deleted++;
                }
            }

            if ($deleted > 0) {
                Log::info('Cleaned up old OAuth avatars', [
                    'provider' => $provider,
                    'provider_id' => $providerId,
                    'deleted_count' => $deleted,
                ]);
            }

            return $deleted;
        } catch (\Exception $e) {
            Log::error('Failed to cleanup old OAuth avatars', [
                'provider' => $provider,
                'provider_id' => $providerId,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Get avatar URL for display
     * 
     * @param string|null $avatarPath
     * @return string|null
     */
    public function getAvatarUrl(?string $avatarPath): ?string
    {
        if (!$avatarPath) {
            return null;
        }

        // If it's already a full URL, return as is
        if (str_starts_with($avatarPath, 'http://') || str_starts_with($avatarPath, 'https://')) {
            return $avatarPath;
        }

        // Generate storage URL
        return Storage::disk('public')->url($avatarPath);
    }
}
