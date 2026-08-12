<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ApiClientController extends Controller
{
    /**
     * Create a new API client for external app authentication.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'redirect_uri' => ['sometimes', 'url'],
            'scopes' => ['sometimes', 'array'],
            'scopes.*' => ['sometimes', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $client = ApiClient::create([
            'name' => $request->name,
            'uuid' => ApiClient::generateUuid(),
            'client_id' => ApiClient::generateClientId(),
            'client_secret' => ApiClient::generateClientSecret(),
            'redirect_uri' => $request->redirect_uri,
            'password_client' => true,
            'personal_access_client' => false,
            'scopes' => $request->scopes ?? ['*'],
        ]);

        return response()->json([
            'message' => 'API client created successfully.',
            'client' => [
                'id' => $client->id,
                'uuid' => $client->uuid,
                'name' => $client->name,
                'client_id' => $client->client_id,
                'client_secret' => $client->client_secret, // Only shown once!
                'redirect_uri' => $client->redirect_uri,
                'scopes' => $client->scopes,
                'created_at' => $client->created_at->toDateTimeString(),
            ],
            'warning' => 'Store the client_secret securely. It will not be shown again.',
        ], 201);
    }

    /**
     * List all API clients (for admin).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $clients = ApiClient::query()
            ->orderBy('created_at', 'desc')
            ->get([
                'id',
                'uuid',
                'name',
                'client_id',
                'redirect_uri',
                'revoked',
                'created_at',
            ]);

        return response()->json([
            'clients' => $clients,
        ]);
    }

    /**
     * Get a specific API client.
     *
     * @param Request $request
     * @param ApiClient $client
     * @return JsonResponse
     */
    public function show(Request $request, ApiClient $client): JsonResponse
    {
        return response()->json([
            'client' => [
                'id' => $client->id,
                'uuid' => $client->uuid,
                'name' => $client->name,
                'client_id' => $client->client_id,
                'redirect_uri' => $client->redirect_uri,
                'revoked' => $client->revoked,
                'scopes' => $client->scopes,
                'created_at' => $client->created_at->toDateTimeString(),
                'updated_at' => $client->updated_at->toDateTimeString(),
            ],
        ]);
    }

    /**
     * Revoke an API client.
     *
     * @param Request $request
     * @param ApiClient $client
     * @return JsonResponse
     */
    public function destroy(Request $request, ApiClient $client): JsonResponse
    {
        $client->update(['revoked' => true]);

        // Revoke all tokens issued by this client
        $client->tokens()->delete();

        return response()->json([
            'message' => 'API client revoked successfully.',
        ]);
    }

    /**
     * Regenerate client secret.
     *
     * @param Request $request
     * @param ApiClient $client
     * @return JsonResponse
     */
    public function regenerateSecret(Request $request, ApiClient $client): JsonResponse
    {
        $newSecret = ApiClient::generateClientSecret();
        $client->update(['client_secret' => $newSecret]);

        return response()->json([
            'message' => 'Client secret regenerated.',
            'client_secret' => $newSecret,
            'warning' => 'Store this secret securely. The old secret is now invalid.',
        ]);
    }
}
