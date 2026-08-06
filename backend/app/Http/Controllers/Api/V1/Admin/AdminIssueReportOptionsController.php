<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminIssueReportOptionManageService;
use App\Services\Admin\AdminPrivilegeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminIssueReportOptionsController extends Controller
{
    public function __construct(
        private AdminIssueReportOptionManageService $options,
        private AdminPrivilegeService $privileges,
    ) {
    }

    public function show(Request $request, int $optId): JsonResponse
    {
        return $this->guardView($request, function () use ($request, $optId) {
            $langId = max(1, $request->integer('lang_id', 1));
            $data = $this->options->show($optId, $langId);
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

    public function update(Request $request, int $optId): JsonResponse
    {
        return $this->save($request, $optId);
    }

    public function changeStatus(Request $request, int $optId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $optId) {
            $status = $request->boolean('active') ? 1 : 0;
            if (! $this->options->changeStatus($optId, $status)) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['message' => 'Status updated successfully']);
        });
    }

    public function destroy(Request $request, int $optId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($optId) {
            if (! $this->options->delete($optId)) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['message' => 'Record deleted successfully']);
        });
    }

    public function updateOrder(Request $request): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request) {
            $ids = array_values(array_map(
                'intval',
                $request->input('IssueReportOptions', $request->input('issueReportOptions', [])),
            ));
            if ($ids === [] || ! $this->options->updateOrder($ids)) {
                return response()->json(['message' => 'Unable to update order'], 422);
            }

            return response()->json(['message' => 'Order updated successfully']);
        });
    }

    private function save(Request $request, int $optId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $optId) {
            $langId = max(1, $request->integer('lang_id', 1));

            try {
                $id = $this->options->save($optId, $request->only([
                    'tissueopt_identifier',
                    'tissueoptlang_title',
                    'tissueopt_active',
                    'update_langs_data',
                ]), $langId);
            } catch (\InvalidArgumentException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return response()->json([
                'message' => 'Setup successful',
                'data' => ['tissueopt_id' => $id, 'optId' => $id],
            ]);
        });
    }

    private function guardView(Request $request, callable $callback): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canView($admin->admin_id, AdminPrivilegeService::SECTION_ISSUE_REPORT_OPTIONS)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $callback();
    }

    private function guardEdit(Request $request, callable $callback): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canEdit($admin->admin_id, AdminPrivilegeService::SECTION_ISSUE_REPORT_OPTIONS)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $callback();
    }
}
