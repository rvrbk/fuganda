<?php

namespace Tests\Feature;

use App\Mail\GuestPropertyInquiryMail;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class GuestPropertyContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_contact_seller_by_email_without_creating_in_app_message(): void
    {
        Mail::fake();

        $seller = User::factory()->seller()->create();
        $property = Property::query()->create([
            'user_id' => $seller->id,
            'corporation_id' => $seller->corporation_id,
            'title' => 'Guest Contact Listing',
            'description' => 'Listing for guest contact flow',
            'price_ugx' => 1000000,
            'listing_type' => 'rent',
            'property_type' => 'apartment',
            'district' => 'Kampala',
            'city' => 'Kampala',
            'address' => 'Guest street',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->postJson('/api/public/property-contact', [
            'property_id' => $property->id,
            'email' => 'guest@example.com',
            'subject' => 'Interested in viewing',
            'body' => 'Hello, is this still available this week?',
        ]);

        $response->assertOk()->assertJsonPath('status', 'sent');

        Mail::assertSent(GuestPropertyInquiryMail::class, function (GuestPropertyInquiryMail $mail) use ($seller): bool {
            return $mail->hasTo($seller->email)
                && $mail->guestEmail === 'guest@example.com'
                && $mail->subjectLine === 'Interested in viewing';
        });

        $this->assertDatabaseCount('messages', 0);
    }

    public function test_guest_contact_requires_email_address(): void
    {
        $seller = User::factory()->seller()->create();
        $property = Property::query()->create([
            'user_id' => $seller->id,
            'corporation_id' => $seller->corporation_id,
            'title' => 'Guest Contact Validation',
            'description' => 'Listing for validation',
            'price_ugx' => 1200000,
            'listing_type' => 'rent',
            'property_type' => 'house',
            'district' => 'Wakiso',
            'city' => 'Entebbe',
            'address' => 'Validation street',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->postJson('/api/public/property-contact', [
            'property_id' => $property->id,
            'subject' => 'Missing email',
            'body' => 'This should fail because email is missing.',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['email']);
    }
}
