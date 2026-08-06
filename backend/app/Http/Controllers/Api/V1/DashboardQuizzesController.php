<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\QuizFormService;
use App\Services\QuizListingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardQuizzesController extends Controller
{
    public function __construct(
        private QuizListingService $quizzes,
        private QuizFormService $quizForm,
    ) {
    }

    public function showForm(Request $request, int $quizId = 0): JsonResponse
    {
        $user = $request->user();
        if (! $user->user_is_teacher) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $this->quizForm->getBasic((int) $user->user_id, $quizId);
        if ($data === null) {
            return response()->json(['message' => 'Quiz not found'], 404);
        }

        return response()->json([
            'data' => $data,
            'meta' => [
                'types' => [
                    ['value' => QuizFormService::TYPE_AUTO_GRADED, 'label' => 'Auto graded'],
                    ['value' => QuizFormService::TYPE_NON_GRADED, 'label' => 'Non graded'],
                ],
            ],
        ]);
    }

    public function saveForm(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->user_is_teacher) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->validate([
            'quiz_id' => ['nullable', 'integer', 'min:0'],
            'title' => ['required', 'string', 'min:10', 'max:120'],
            'type' => ['required', 'integer', 'in:1,2'],
            'detail' => ['required', 'string'],
        ]);

        $error = null;
        $result = $this->quizForm->saveBasic((int) $user->user_id, [
            'quiz_id' => $request->integer('quiz_id', 0),
            'title' => $request->input('title'),
            'type' => $request->integer('type'),
            'detail' => $request->input('detail'),
        ], $error);

        if ($result === null) {
            return response()->json(['message' => $error ?? 'Invalid request'], 422);
        }

        return response()->json([
            'data' => $result,
            'message' => 'Setup successful',
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->user_is_teacher) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $result = $this->quizzes->list((int) $user->user_id, [
            'keyword' => $request->input('keyword'),
            'type' => $request->input('type'),
            'status' => $request->input('status'),
            'active' => $request->input('active'),
            'page' => $request->integer('page', 1),
            'per_page' => $request->integer('per_page', 20),
        ]);

        return response()->json([
            'data' => $result['items'],
            'meta' => $result['meta'],
        ]);
    }

    public function updateActive(Request $request, int $quizId): JsonResponse
    {
        $user = $request->user();
        if (! $user->user_is_teacher) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->validate(['active' => ['required', 'integer']]);

        if (! $this->quizzes->toggleActive((int) $user->user_id, $quizId, $request->integer('active'))) {
            return response()->json(['message' => 'Invalid request'], 404);
        }

        return response()->json(['message' => 'Status updated successfully']);
    }

    public function destroy(Request $request, int $quizId): JsonResponse
    {
        $user = $request->user();
        if (! $user->user_is_teacher) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (! $this->quizzes->delete((int) $user->user_id, $quizId)) {
            return response()->json(['message' => 'Invalid request'], 404);
        }

        return response()->json(['message' => 'Deleted successfully']);
    }

    public function showSettings(Request $request, int $quizId): JsonResponse
    {
        $user = $request->user();
        if (! $user->user_is_teacher) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $langId = (int) ($user->user_lang_id ?: 1);
        $data = $this->quizForm->getSettings((int) $user->user_id, $quizId, $langId);
        if ($data === null) {
            return response()->json(['message' => 'Quiz not found'], 404);
        }

        return response()->json(['data' => $data]);
    }

    public function saveSettings(Request $request, int $quizId): JsonResponse
    {
        $user = $request->user();
        if (! $user->user_is_teacher) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->validate([
            'duration' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'attempts' => ['required', 'integer', 'min:1', 'max:10'],
            'pass_percent' => ['required', 'numeric', 'min:1', 'max:100'],
            'validity' => ['required', 'integer', 'min:1', 'max:9999'],
            'certificate' => ['nullable', 'integer', 'in:0,1'],
            'fail_message' => ['required', 'string', 'min:10', 'max:255'],
            'pass_message' => ['required', 'string', 'min:10', 'max:255'],
        ]);

        $langId = (int) ($user->user_lang_id ?: 1);
        $error = null;
        if (! $this->quizForm->saveSettings((int) $user->user_id, $langId, [
            'quiz_id' => $quizId,
            'duration' => $request->integer('duration', 0),
            'attempts' => $request->integer('attempts'),
            'pass_percent' => (float) $request->input('pass_percent'),
            'validity' => $request->integer('validity'),
            'certificate' => $request->integer('certificate', 0),
            'fail_message' => $request->input('fail_message'),
            'pass_message' => $request->input('pass_message'),
        ], $error)) {
            return response()->json(['message' => $error ?? 'Invalid request'], 422);
        }

        return response()->json(['message' => 'Setup successful']);
    }

    public function completionStatus(Request $request, int $quizId): JsonResponse
    {
        $user = $request->user();
        if (! $user->user_is_teacher) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json([
            'data' => $this->quizForm->getCompletedStatus((int) $user->user_id, $quizId),
        ]);
    }
}
