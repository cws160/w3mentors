<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminForumManageService;
use App\Services\Admin\AdminPrivilegeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminForumQuestionsController extends Controller
{
    public function __construct(
        private AdminForumManageService $forum,
        private AdminPrivilegeService $privileges,
    ) {
    }

    public function show(Request $request, int $id): JsonResponse
    {
        return $this->guardView($request, function () use ($request, $id) {
            $langId = max(1, $request->integer('lang_id', 1));
            $data = $this->forum->questionShow($id, $langId);
            if ($data === null) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['data' => $data]);
        });
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        return $this->guardEdit($request, function () use ($id) {
            if (! $this->forum->questionDelete($id)) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['message' => 'Question deleted successfully']);
        });
    }

    public function comments(Request $request, int $id): JsonResponse
    {
        return $this->guardView($request, function () use ($request, $id) {
            $page = max(1, $request->integer('page', 1));
            $perPage = min(50, max(1, $request->integer('per_page', 10)));

            return response()->json($this->forum->questionComments($id, $page, $perPage));
        });
    }

    public function destroyComment(Request $request, int $id, int $commentId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($id, $commentId) {
            if (! $this->forum->commentDelete($id, $commentId)) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['message' => 'Record deleted successfully']);
        });
    }

    private function guardView(Request $request, callable $callback): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canView($admin->admin_id, AdminPrivilegeService::SECTION_DISCUSSION_FORUM)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $callback();
    }

    private function guardEdit(Request $request, callable $callback): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canEdit($admin->admin_id, AdminPrivilegeService::SECTION_DISCUSSION_FORUM)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $callback();
    }
}
