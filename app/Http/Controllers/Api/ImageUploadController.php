<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;

class ImageUploadController
{
    private const MEDIA_MAX_SIZE_KB = 102400;
    private const ALLOWED_MEDIA_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'mp4', 'webm', 'mov', 'm4v'];

    public function store(Request $request): JsonResponse
    {
        return $this->storeImage($request);
    }

    public function storeImage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $file = $validated['image'];
        $storedPath = $file->store('property-images', 'public');

        return response()->json($this->buildUploadResponse($file, $storedPath));
    }

    public function storeMedia(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => [
                'required',
                'file',
                'max:'.self::MEDIA_MAX_SIZE_KB,
            ],
        ], [
            'file.required' => 'Please choose an image or video file to upload.',
            'file.file' => 'The uploaded media is invalid. Please try selecting the file again.',
            'file.max' => 'The media file is too large. Maximum allowed size is 100 MB.',
        ]);

        $validator->after(function ($validator) use ($request): void {
            $file = $request->file('file');
            if (! $file instanceof UploadedFile) {
                return;
            }

            $extension = strtolower((string) $file->getClientOriginalExtension());
            if (! in_array($extension, self::ALLOWED_MEDIA_EXTENSIONS, true)) {
                $validator->errors()->add('file', 'Unsupported media type. Allowed formats: jpg, jpeg, png, webp, mp4, webm, mov, m4v.');
            }
        });

        $validated = $validator->validate();

        $file = $validated['file'];
        $storedPath = $file->store('property-media', 'public');

        return response()->json($this->buildUploadResponse($file, $storedPath));
    }

    /**
     * @return array<string, int|string>
     */
    private function buildUploadResponse(UploadedFile $file, string $storedPath): array
    {
        $mimeType = (string) $file->getMimeType();
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $isVideo = str_starts_with($mimeType, 'video/') || in_array($extension, ['mp4', 'webm', 'mov', 'm4v'], true);

        return [
            'path' => '/storage/'.$storedPath,
            'filename' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime_type' => $mimeType,
            'kind' => $isVideo ? 'video' : 'image',
        ];
    }
}