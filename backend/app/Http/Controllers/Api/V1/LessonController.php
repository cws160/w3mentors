<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\LessonResource;
use App\Models\OrderLesson;
use App\Services\LessonListingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    public function __construct(private LessonListingService $listing)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $langId = (int) ($request->input('lang_id') ?: $user->user_lang_id ?: 1);
        $status = $request->input('status');
        $statusFilter = $status === null || $status === 'all' || $status === ''
            ? -1
            : (int) $status;

        $result = $this->listing->list(
            (int) $user->user_id,
            (bool) $user->user_is_teacher,
            $langId,
            [
                'status' => $statusFilter,
                'keyword' => $request->input('keyword'),
                'page' => $request->integer('page', 1),
                'per_page' => $request->integer('per_page', 20),
            ]
        );

        return response()->json([
            'data' => $result['items'],
            'groups' => $result['groups'],
            'meta' => $result['meta'],
        ]);
    }

    public function show(Request $request, OrderLesson $lesson): JsonResponse
    {
        $lesson->load(['teacher', 'order.user']);
        $user = $request->user();

        $allowed = $user->user_is_teacher
            ? (int) $lesson->ordles_teacher_id === (int) $user->user_id
            : (int) $lesson->order?->order_user_id === (int) $user->user_id;

        if (! $allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json(['data' => new LessonResource($lesson)]);
    }
}
