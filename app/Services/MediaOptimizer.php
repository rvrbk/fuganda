<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class MediaOptimizer
{
    private const MAX_IMAGE_WIDTH = 1920;
    private const MAX_IMAGE_HEIGHT = 1080;
    private const IMAGE_QUALITY = 85;

    public function optimizeImage(UploadedFile $file): UploadedFile
    {
        // Only optimize actual image files
        if (! $this->isImage($file)) {
            return $file;
        }

        try {
            $manager = new ImageManager(new Driver());
            $image = $manager->read($file->getRealPath());

            // Resize if too large while maintaining aspect ratio
            if ($image->width() > self::MAX_IMAGE_WIDTH || $image->height() > self::MAX_IMAGE_HEIGHT) {
                $image->scaleDown(
                    width: self::MAX_IMAGE_WIDTH,
                    height: self::MAX_IMAGE_HEIGHT,
                    mode: 'aspect'
                );
            }

            // Save optimized version to temp file
            $tempPath = tempnam(sys_get_temp_dir(), 'opt_' . $file->getClientOriginalName());
            $image->save($tempPath, quality: self::IMAGE_QUALITY);

            // Create a new UploadedFile from the optimized temp file
            return new UploadedFile(
                $tempPath,
                $file->getClientOriginalName(),
                $file->getClientMimeType(),
                $file->getClientSize(),
                $file->getError(),
                true // test mode - we're creating this file, not from user upload
            );
        } catch (\Exception $e) {
            // If optimization fails, return the original file
            // This ensures uploads still work even if optimization fails
            return $file;
        }
    }

    public function isImage(UploadedFile $file): bool
    {
        $mimeType = strtolower($file->getClientMimeType());
        $extension = strtolower($file->getClientOriginalExtension());

        $imageMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];
        $imageExtensions = ['jpeg', 'jpg', 'png', 'webp', 'gif'];

        return in_array($mimeType, $imageMimes, true) || in_array($extension, $imageExtensions, true);
    }
}
