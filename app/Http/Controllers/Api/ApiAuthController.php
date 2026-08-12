<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\PersonalAccessToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ApiAuthController extends Controller
{
    /**
     * Login and get access token for external API consumers.
     *
     * This endpoint allows external applications to authenticate
     * and receive a Sanctum personal access token for API access.
     *
     * @param LoginRequest $request
     * @return JsonResponse
     *
     * @throws ValidationException
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Determine device name for token
        $deviceName = $request->device_name ?? 'external-app';

        // Revoke old tokens for this device if exists
        $existingToken = PersonalAccessToken::query()
            ->where('name', $deviceName)
            ->where('tokenable_type', User::class)
            ->where('tokenable_id', $user->id)
            ->first();

        if ($existingToken) {
            $existingToken->delete();
        }

        // Create new token
        $token = $user->createToken($deviceName);

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at?->toDateTimeString(),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ]);
    }

    /**
     * Logout and revoke the current token.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user) {
            // Revoke the current token
            $token = $request->bearerToken();
            if ($token) {
                $personalAccessToken = PersonalAccessToken::findToken($token);
                if ($personalAccessToken) {
                    $personalAccessToken->delete();
                }
            }

            // Alternatively, revoke all tokens for the user
            // $user->tokens()->delete();
        }

        return response()->json([
            'message' => 'Successfully logged out and token revoked.',
        ]);
    }

    /**
     * Get the authenticated user's token information.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        // Get current token info
        $token = $request->bearerToken();
        $tokenInfo = null;

        if ($token) {
            $personalAccessToken = PersonalAccessToken::findToken($token);
            if ($personalAccessToken) {
                $tokenInfo = [
                    'name' => $personalAccessToken->name,
                    'created_at' => $personalAccessToken->created_at->toDateTimeString(),
                    'last_used_at' => $personalAccessToken->last_used_at?->toDateTimeString(),
                    'expires_at' => $personalAccessToken->expires_at?->toDateTimeString(),
                ];
            }
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'corporation_id' => $user->corporation_id,
                'email_verified_at' => $user->email_verified_at?->toDateTimeString(),
                'created_at' => $user->created_at->toDateTimeString(),
                'updated_at' => $user->updated_at->toDateTimeString(),
            ],
            'token' => $tokenInfo,
        ]);
    }

    /**
     * Revoke a specific token by name.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function revokeToken(Request $request): JsonResponse
    {
        $request->validate([
            'token_name' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $token = PersonalAccessToken::query()
            ->where('tokenable_type', User::class)
            ->where('tokenable_id', $user->id)
            ->where('name', $request->token_name)
            ->first();

        if ($token) {
            $token->delete();
            return response()->json([
                'message' => 'Token revoked successfully.',
            ]);
        }

        return response()->json([
            'message' => 'Token not found.',
        ], 404);
    }

    /**
     * Revoke all tokens for the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function revokeAllTokens(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $user->tokens()->delete();

        return response()->json([
            'message' => 'All tokens revoked successfully.',
        ]);
    }
}
