<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\QuizQuestionsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardQuizQuestionsController extends Controller
{
    public function __construct(private readonly QuizQuestionsService $quizQuestions)
    {
    }

    public function index(Request $request, int $quizId): JsonResponse
    {
        $user = $request->user();
        if (! $user->user_is_teacher) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $langId = (int) ($user->user_lang_id ?: 1);
        $items = $this->quizQuestions->listAttached((int) $user->user_id, $quizId, $langId);

        return response()->json(['data' => $items]);
    }

    public function search(Request $request, int $quizId): JsonResponse
    {
        $user = $request->user();
        if (! $user->user_is_teacher) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $langId = (int) ($user->user_lang_id ?: 1);
        $result = $this->quizQuestions->searchAvailable((int) $user->user_id, $quizId, $langId, [
            'keyword' => $request->string('keyword')->toString(),
            'category_id' => $request->integer('category_id') ?: null,
            'subcategory_id' => $request->integer('subcategory_id') ?: null,
            'page' => $request->integer('page', 1),
            'per_page' => $request->integer('per_page', 10),
        ]);

        return response()->json([
            'data' => $result['items'],
            'meta' => $result['meta'],
        ]);
    }

    public function attach(Request $request, int $quizId): JsonResponse
    {
        $user = $request->user();
        if (! $user->user_is_teacher) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->validate([
            'question_ids' => ['required', 'array', 'min:1'],
            'question_ids.*' => ['integer', 'min:1'],
        ]);

        $error = null;
        if (! $this->quizQuestions->attach(
            (int) $user->user_id,
            $quizId,
            $request->input('question_ids'),
            $error
        )) {
            return response()->json(['message' => $error ?? 'Invalid request'], 422);
        }

        return response()->json(['message' => 'Setup successful']);
    }

    public function destroy(Request $request, int $quizId, int $questionId): JsonResponse
    {
        $user = $request->user();
        if (! $user->user_is_teacher) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $error = null;
        if (! $this->quizQuestions->remove((int) $user->user_id, $quizId, $questionId, $error)) {
            return response()->json(['message' => $error ?? 'Invalid request'], 422);
        }

        return response()->json(['message' => 'Deleted successfully']);
    }

    public function updateOrder(Request $request, int $quizId): JsonResponse
    {
        $user = $request->user();
        if (! $user->user_is_teacher) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->validate([
            'order' => ['required', 'array', 'min:1'],
            'order.*' => ['integer'],
        ]);

        $error = null;
        if (! $this->quizQuestions->updateOrder(
            (int) $user->user_id,
            $quizId,
            $request->input('order'),
            $error
        )) {
            return response()->json(['message' => $error ?? 'Invalid request'], 422);
        }

        return response()->json(['message' => 'Order updated successfully']);
    }
}
