<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AttachQuizService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class DashboardAttachQuizzesController extends Controller
{
    public function __construct(private readonly AttachQuizService $attachQuiz) {}

    public function search(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->user_is_teacher) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $recordId = $request->integer('record_id');
        $recordType = $request->integer('record_type');

        try {
            $result = $this->attachQuiz->searchAvailable(
                (int) $user->user_id,
                $recordId,
                $recordType,
                [
                    'keyword' => $request->input('keyword'),
                    'quiz_type' => $request->input('quiz_type'),
                    'page' => $request->integer('page', 1),
                    'per_page' => $request->integer('per_page', 10),
                ]
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'data' => $result['items'],
            'meta' => $result['meta'],
            'quiz_types' => AttachQuizService::quizTypeLabels(),
        ]);
    }

    public function attach(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->user_is_teacher) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'record_id' => 'required|integer|min:1',
            'record_type' => 'required|integer|in:1,2,3',
            'quiz_ids' => 'required|array|min:1',
            'quiz_ids.*' => 'integer|min:1',
        ]);

        try {
            $this->attachQuiz->attach(
                (int) $user->user_id,
                (int) $validated['record_id'],
                (int) $validated['record_type'],
                $validated['quiz_ids']
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Quizzes attached successfully',
        ]);
    }
}
