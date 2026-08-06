<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ForumListingService;
use App\Services\ForumQuestionCommentsService;
use App\Services\ForumQuestionFormService;
use App\Services\ForumTagRequestFormService;
use App\Services\ForumTagRequestListingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardForumController extends Controller
{
    public function __construct(
        private readonly ForumListingService $forum,
        private readonly ForumQuestionFormService $questionFormService,
        private readonly ForumQuestionCommentsService $questionComments,
        private readonly ForumTagRequestListingService $tagRequests,
        private readonly ForumTagRequestFormService $tagRequestForm,
    ) {
    }

    public function questions(Request $request): JsonResponse
    {
        $result = $this->forum->listMyQuestions((int) $request->user()->user_id, [
            'keyword' => $request->string('keyword')->toString(),
            'status' => $request->has('status') ? $request->integer('status') : null,
            'page' => $request->integer('page', 1),
            'per_page' => $request->integer('per_page', 20),
        ]);

        return response()->json([
            'data' => $result['items'],
            'meta' => $result['meta'],
        ]);
    }

    public function questionForm(Request $request, ?int $questionId = null): JsonResponse
    {
        try {
            $result = $this->questionFormService->getForm(
                (int) $request->user()->user_id,
                $questionId ?? 0
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($result);
    }

    public function saveQuestion(Request $request): JsonResponse
    {
        try {
            $result = $this->questionFormService->save((int) $request->user()->user_id, [
                'fque_id' => $request->integer('fque_id'),
                'fque_title' => $request->string('fque_title')->toString(),
                'fque_slug' => $request->string('fque_slug')->toString(),
                'fque_description' => $request->string('fque_description')->toString(),
                'fque_lang_id' => $request->integer('fque_lang_id'),
                'fque_status' => $request->integer('fque_status'),
                'fque_comments_allowed' => $request->integer('fque_comments_allowed'),
                'fque_sel_tags' => $request->string('fque_sel_tags')->toString(),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'data' => $result,
            'message' => 'Forum question saved successfully.',
        ]);
    }

    public function formatQuestionSlug(Request $request): JsonResponse
    {
        $slug = $this->questionFormService->formatSlug($request->string('slug')->toString());

        return response()->json(['data' => ['slug' => $slug]]);
    }

    public function questionComments(Request $request, int $questionId): JsonResponse
    {
        try {
            $result = $this->questionComments->listForQuestion(
                (int) $request->user()->user_id,
                $questionId,
                $request->integer('page', 1),
                $request->integer('per_page', 20),
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'data' => $result['items'],
            'meta' => $result['meta'],
        ]);
    }

    public function tagRequests(Request $request): JsonResponse
    {
        $result = $this->tagRequests->list((int) $request->user()->user_id);

        return response()->json(['data' => $result['items']]);
    }

    public function tagRequestForm(Request $request, ?int $requestId = null): JsonResponse
    {
        try {
            $result = $this->tagRequestForm->getForm(
                (int) $request->user()->user_id,
                $requestId ?? 0
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($result);
    }

    public function saveTagRequest(Request $request): JsonResponse
    {
        try {
            $result = $this->tagRequestForm->save(
                (int) $request->user()->user_id,
                $request->integer('ftagreq_id'),
                $request->string('ftagreq_name')->toString(),
                $request->integer('ftagreq_language_id'),
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'data' => $result,
            'message' => 'Tag request saved successfully.',
        ]);
    }
}
