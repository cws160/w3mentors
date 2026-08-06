<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminForumManageService;
use App\Services\Admin\AdminPrivilegeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminForumReportedQuestionsController extends Controller
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
            $data = $this->forum->reportedQuestionShow($id, $langId);
            if ($data === null) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['data' => $data]);
        });
    }

    public function action(Request $request, int $id): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $id) {
            try {
                $this->forum->reportedQuestionAction($id, $request->only([
                    'fquerep_status',
                    'fquerep_admin_comments',
                ]));
            } catch (\InvalidArgumentException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return response()->json(['message' => 'Action performed successfully']);
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
