<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct(private ChatService $chats)
    {
    }

    public function privateThread(Request $request): JsonResponse
    {
        $request->validate([
            'receiver_id' => ['required', 'integer', 'min:1'],
            'message' => ['nullable', 'string', 'max:5000'],
        ]);

        $sender = $request->user();
        $receiverId = $request->integer('receiver_id');

        if ($receiverId === $sender->user_id) {
            return response()->json(['message' => 'Invalid request'], 422);
        }

        $receiver = User::query()
            ->where('user_id', $receiverId)
            ->whereNull('user_deleted')
            ->where('user_active', 1)
            ->first();

        if (! $receiver) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $existing = $this->chats->findPrivateThread($sender->user_id, $receiverId);
        if ($existing && !$request->filled('message')) {
            return response()->json([
                'data' => [
                    'thread_id' => $existing,
                    'exists' => true,
                ],
            ]);
        }

        if (!$existing && !$request->filled('message')) {
            return response()->json([
                'data' => [
                    'exists' => false,
                    'needs_message' => true,
                ],
            ]);
        }

        try {
            $threadId = $this->chats->ensurePrivateThread(
                $sender->user_id,
                $receiverId,
                (string) $request->input('message', '')
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'data' => [
                'thread_id' => $threadId,
                'exists' => (bool) $existing,
            ],
        ]);
    }
}
