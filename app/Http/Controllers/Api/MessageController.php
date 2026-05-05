<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMessageRequest;
use App\Services\MessagingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function __construct(private readonly MessagingService $messagingService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'property_id' => ['sometimes', 'integer', 'exists:properties,id'],
            'counterpart_id' => ['sometimes', 'integer', 'exists:users,id'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $messages = $this->messagingService->inbox($request->user(), $validated);

        return response()->json($messages);
    }

    public function store(StoreMessageRequest $request): JsonResponse
    {
        $message = $this->messagingService->send($request->user(), $request->validated());

        return response()->json($message, 201);
    }
}
