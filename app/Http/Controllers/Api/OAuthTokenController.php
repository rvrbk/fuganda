<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiClient;
use App\Models\PersonalAccessToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class OAuthTokenController extends Controller
{
    /**
     * Issue access token based on grant type.
     * Supports: client_credentials, password
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function issueToken(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'grant_type' => ['required', 'string', 'in:client_credentials,password'],
            'client_id' => ['required', 'string'],
            'client_secret' => ['required', 'string'],
            'scope' => ['sometimes', 'string'],
            'username' => ['required_if:grant_type,password', 'email'],
            'password' => ['required_if:grant_type,password', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'invalid_request',
                'error_description' => 'The request is missing a required parameter.',
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 400);
        }

        // Find and validate the client
        $client = ApiClient::where('client_id', $request->client_id)
            ->where('revoked', false)
            ->first();

        if (!$client || !Hash::check($request->client_secret, $client->client_secret)) {
            return response()->json([
                'error' => 'invalid_client',
                'error_description' => 'The client credentials are invalid.',
            ], 401);
        }

        // Handle different grant types
        if ($request->grant_type === 'client_credentials') {
            return $this->handleClientCredentialsGrant($request, $client);
        }

        if ($request->grant_type === 'password') {
            return $this->handlePasswordGrant($request, $client);
        }

        return response()->json([
            'error' => 'unsupported_grant_type',
            'error_description' => 'The authorization grant type is not supported.',
        ], 400);
    }

    /**
     * Handle Client Credentials Grant.
     */
    protected function handleClientCredentialsGrant(Request $request, ApiClient $client): JsonResponse
    {
        // Get the associated user (if any)
        $user = $client->user;

        // If no user is associated, use a default service user
        if (!$user) {
            $serviceUserEmail = config('auth.oauth.service_user', 'api-service@mycanopy.verbeek.ug');
            $user = User::where('email', $serviceUserEmail)->first();

            if (!$user) {
                return response()->json([
                    'error' => 'server_error',
                    'error_description' => 'No service user configured for OAuth. Create a user with email: ' . $serviceUserEmail,
                ], 500);
            }
        }

        // Check scopes if provided
        $requestedScopes = $request->scope ? explode(' ', $request->scope) : ['*'];
        $clientScopes = $client->scopes ?? ['*'];

        if ($clientScopes !== ['*'] && !empty(array_diff($requestedScopes, $clientScopes))) {
            return response()->json([
                'error' => 'invalid_scope',
                'error_description' => 'The requested scope is invalid.',
            ], 400);
        }

        // Create token for the user
        $tokenName = 'client-' . $client->name . '-' . now()->timestamp;
        $token = $user->createToken($tokenName, $requestedScopes);

        return response()->json([
            'token_type' => 'Bearer',
            'expires_in' => config('sanctum.expiration', 3600) ?: null,
            'access_token' => $token->plainTextToken,
            'refresh_token' => null,
            'client_id' => $client->client_id,
            'user_id' => $user->id,
            'scopes' => $requestedScopes,
        ]);
    }

    /**
     * Handle Password Grant.
     */
    protected function handlePasswordGrant(Request $request, ApiClient $client): JsonResponse
    {
        // Client must be a password client
        if (!$client->password_client) {
            return response()->json([
                'error' => 'invalid_client',
                'error_description' => 'This client is not authorized for password grant.',
            ], 401);
        }

        // Validate user credentials
        $user = User::where('email', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'error' => 'invalid_grant',
                'error_description' => 'The provided credentials are incorrect.',
            ], 400);
        }

        // Check scopes
        $requestedScopes = $request->scope ? explode(' ', $request->scope) : ['*'];
        $clientScopes = $client->scopes ?? ['*'];

        if ($clientScopes !== ['*'] && !empty(array_diff($requestedScopes, $clientScopes))) {
            return response()->json([
                'error' => 'invalid_scope',
                'error_description' => 'The requested scope is invalid.',
            ], 400);
        }

        // Create token
        $tokenName = 'password-' . $client->name . '-' . $user->id . '-' . now()->timestamp;
        $token = $user->createToken($tokenName, $requestedScopes);

        return response()->json([
            'token_type' => 'Bearer',
            'expires_in' => config('sanctum.expiration', 3600) ?: null,
            'access_token' => $token->plainTextToken,
            'refresh_token' => null,
            'client_id' => $client->client_id,
            'user_id' => $user->id,
            'scopes' => $requestedScopes,
        ]);
    }

    /**
     * Revoke a token.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function revokeToken(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
        ]);

        $token = PersonalAccessToken::findToken($request->token);

        if (!$token) {
            return response()->json([
                'error' => 'invalid_token',
                'error_description' => 'The token was not found.',
            ], 404);
        }

        $token->delete();

        return response()->json([
            'message' => 'Token revoked successfully.',
        ]);
    }

    /**
     * Get token information.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function tokenInfo(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
        ]);

        $token = PersonalAccessToken::findToken($request->token);

        if (!$token) {
            return response()->json([
                'error' => 'invalid_token',
                'error_description' => 'The token was not found or has been revoked.',
            ], 404);
        }

        $tokenable = $token->tokenable;

        return response()->json([
            'token' => [
                'id' => $token->id,
                'name' => $token->name,
                'abilities' => $token->abilities,
                'last_used_at' => $token->last_used_at?->toDateTimeString(),
                'expires_at' => $token->expires_at?->toDateTimeString(),
                'created_at' => $token->created_at->toDateTimeString(),
            ],
            'tokenable' => [
                'type' => get_class($tokenable),
                'id' => $tokenable->id,
                'name' => $tokenable->name ?? null,
            ],
        ]);
    }
}
