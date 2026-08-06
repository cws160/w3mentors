<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\QuestionListingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardQuestionsController extends Controller
{
    public function __construct(private QuestionListingService $questions)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->user_is_teacher) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $langId = (int) ($user->user_lang_id ?: 1);
        $result = $this->questions->list((int) $user->user_id, $langId, [
            'keyword' => $request->input('keyword'),
            'category_id' => $request->input('category_id'),
            'subcategory_id' => $request->input('subcategory_id'),
            'type' => $request->input('type'),
            'page' => $request->integer('page', 1),
            'per_page' => $request->integer('per_page', 20),
        ]);

        return response()->json([
            'data' => $result['items'],
            'meta' => $result['meta'],
        ]);
    }

    public function categories(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->user_is_teacher) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $langId = (int) ($user->user_lang_id ?: 1);
        $parentId = $request->integer('parent_id', 0);

        return response()->json([
            'data' => $this->questions->categories($langId, $parentId),
        ]);
    }

    public function updateStatus(Request $request, int $questionId): JsonResponse
    {
        $user = $request->user();
        if (! $user->user_is_teacher) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->validate(['status' => ['required', 'integer']]);

        if (! $this->questions->toggleStatus((int) $user->user_id, $questionId, $request->integer('status'))) {
            return response()->json(['message' => 'Invalid request'], 404);
        }

        return response()->json(['message' => 'Status updated successfully']);
    }

    public function destroy(Request $request, int $questionId): JsonResponse
    {
        $user = $request->user();
        if (! $user->user_is_teacher) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $error = $this->questions->delete((int) $user->user_id, $questionId);
        if ($error !== null) {
            return response()->json(['message' => $error], 422);
        }

        return response()->json(['message' => 'Deleted successfully']);
    }
}
