<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\UserGdprService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserGdprController extends Controller
{
    public function __construct(private UserGdprService $gdpr)
    {
    }

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        return response()->json([
            'data' => [
                'has_pending_request' => $this->gdpr->hasPendingRequest((int) $user->user_id),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        $validator = Validator::make($request->all(), [
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $this->gdpr->submitDeleteRequest(
                (int) $user->user_id,
                $validator->validated()['reason']
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Request placed successfully',
            'data' => ['has_pending_request' => true],
        ]);
    }
}
