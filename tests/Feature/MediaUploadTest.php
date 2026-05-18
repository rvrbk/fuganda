<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MediaUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_upload_image_to_media_endpoint(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/uploads/media', [
            'file' => UploadedFile::fake()->image('listing.jpg'),
        ]);

        $response->assertOk();
        $response->assertJsonPath('kind', 'image');

        $storedPath = ltrim(str_replace('/storage', '', (string) $response->json('path')), '/');
        Storage::disk('public')->assertExists($storedPath);
    }

    public function test_authenticated_user_can_upload_video_to_media_endpoint(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/uploads/media', [
            'file' => UploadedFile::fake()->create('tour.mp4', 1024, 'video/mp4'),
        ]);

        $response->assertOk();
        $response->assertJsonPath('kind', 'video');

        $storedPath = ltrim(str_replace('/storage', '', (string) $response->json('path')), '/');
        Storage::disk('public')->assertExists($storedPath);
    }

    public function test_authenticated_user_can_upload_mp4_with_generic_mime_type(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/uploads/media', [
            'file' => UploadedFile::fake()->create('tour.mp4', 1024, 'application/octet-stream'),
        ]);

        $response->assertOk();
        $response->assertJsonPath('kind', 'video');

        $storedPath = ltrim(str_replace('/storage', '', (string) $response->json('path')), '/');
        Storage::disk('public')->assertExists($storedPath);
    }

    public function test_invalid_media_type_is_rejected_with_clear_message(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/uploads/media', [
            'file' => UploadedFile::fake()->create('notes.pdf', 128, 'application/pdf'),
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['file']);
        $response->assertJsonPath('errors.file.0', 'Unsupported media type. Allowed formats: jpg, jpeg, png, webp, mp4, webm, mov, m4v.');
    }

    public function test_oversized_media_is_rejected_with_clear_message(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/uploads/media', [
            'file' => UploadedFile::fake()->create('huge-tour.mp4', 102401, 'video/mp4'),
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['file']);
        $response->assertJsonPath('errors.file.0', 'The media file is too large. Maximum allowed size is 100 MB.');
    }

    public function test_unauthenticated_request_to_media_endpoint_is_unauthorized(): void
    {
        Storage::fake('public');

        $this->postJson('/api/uploads/media', [
            'file' => UploadedFile::fake()->image('listing.jpg'),
        ])->assertUnauthorized();
    }
}
