<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminForumManageService;
use App\Services\Admin\AdminPrivilegeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminForumReportReasonsController extends Controller
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
            $data = $this->forum->reportReasonShow($id, $langId);
            if ($data === null) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['data' => $data]);
        });
    }

    public function store(Request $request): JsonResponse
    {
        return $this->save($request, 0);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        return $this->save($request, $id);
    }

    public function changeStatus(Request $request, int $id): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $id) {
            $status = $request->boolean('active') ? 1 : 0;
            if (! $this->forum->reportReasonChangeStatus($id, $status)) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['message' => 'Status updated successfully']);
        });
    }

    public function updateOrder(Request $request): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request) {
            $ids = array_values(array_map(
                'intval',
                $request->input('ForumReportIssueReasons', $request->input('forumReportIssueReasons', [])),
            ));
            if ($ids === [] || ! $this->forum->reportReasonUpdateOrder($ids)) {
                return response()->json(['message' => 'Unable to update order'], 422);
            }

            return response()->json(['message' => 'Order updated successfully']);
        });
    }

    private function save(Request $request, int $id): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $id) {
            $langId = max(1, $request->integer('lang_id', 1));

            try {
                $savedId = $this->forum->reportReasonSave($id, $request->only([
                    'frireason_identifier',
                    'frireason_name',
                    'frireason_active',
                    'update_langs_data',
                ]), $langId);
            } catch (\InvalidArgumentException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return response()->json([
                'message' => 'Setup successful',
                'data' => ['frireason_id' => $savedId, 'id' => $savedId],
            ]);
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
