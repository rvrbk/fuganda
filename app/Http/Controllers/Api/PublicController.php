<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\GuestPropertyInquiryMail;
use App\Models\Corporation;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class PublicController extends Controller
{
    public function ping(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'service' => config('app.name'),
        ]);
    }

    public function tenantByDomain(Request $request): JsonResponse
    {
        $host = strtolower((string) $request->getHost());

        $tenant = Corporation::query()->where('domain', $host)->first();

        return response()->json([
            'host' => $host,
            'tenant' => $tenant ? [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'domain' => $tenant->domain,
            ] : null,
        ]);
    }

    public function contactSeller(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'property_id' => ['required', 'integer', 'exists:properties,id'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:180'],
            'body' => ['required', 'string', 'max:4000'],
        ]);

        $property = Property::query()
            ->with('user:id,name,email')
            ->findOrFail((int) $validated['property_id']);

        $seller = $property->user;

        if (! $seller || empty($seller->email)) {
            return response()->json([
                'message' => 'Seller contact email is unavailable for this property.',
            ], 422);
        }

        Mail::to($seller->email)->send(new GuestPropertyInquiryMail(
            property: $property,
            sellerName: (string) ($seller->name ?? 'Seller'),
            guestEmail: (string) $validated['email'],
            subjectLine: (string) $validated['subject'],
            body: (string) $validated['body'],
        ));

        return response()->json([
            'status' => 'sent',
        ]);
    }
}
