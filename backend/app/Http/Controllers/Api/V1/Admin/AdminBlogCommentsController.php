<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminBlogCommentManageService;
use App\Services\Admin\AdminPrivilegeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminBlogCommentsController extends Controller
{
    public function __construct(
        private AdminPrivilegeService $privileges,
        private AdminBlogCommentManageService $manage,
    ) {
    }

    public function show(Request $request, int $commentId): JsonResponse
    {
        $langId = max(1, $request->integer('lang_id', 1));

        return $this->guardEdit($request, function () use ($commentId, $langId) {
            $data = $this->manage->show($commentId, $langId);
            if (! $data) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['data' => $data]);
        });
    }

    public function updateStatus(Request $request, int $commentId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $commentId) {
            $result = $this->manage->updateStatus($commentId, $request->all());
            if (! ($result['ok'] ?? false)) {
                return response()->json(['message' => $result['message'] ?? 'Unable to update'], 422);
            }

            return response()->json(['message' => 'Comment status updated successfully']);
        });
    }

    public function destroy(Request $request, int $commentId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($commentId) {
            $result = $this->manage->delete($commentId);
            if (! ($result['ok'] ?? false)) {
                return response()->json(['message' => $result['message'] ?? 'Unable to delete'], 422);
            }

            return response()->json(['message' => 'Record deleted successfully']);
        });
    }

    private function guardEdit(Request $request, callable $callback): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();

        if (! $this->privileges->canEdit($admin->admin_id, AdminPrivilegeService::SECTION_BLOG_COMMENTS)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $callback();
    }
}
