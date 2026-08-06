<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminBlogContributionManageService;
use App\Services\Admin\AdminPrivilegeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminBlogContributionsController extends Controller
{
    public function __construct(
        private AdminPrivilegeService $privileges,
        private AdminBlogContributionManageService $manage,
    ) {
    }

    public function show(Request $request, int $contributionId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($contributionId) {
            $data = $this->manage->show($contributionId);
            if (! $data) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['data' => $data]);
        });
    }

    public function updateStatus(Request $request, int $contributionId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $contributionId) {
            $result = $this->manage->updateStatus($contributionId, $request->all());
            if (! ($result['ok'] ?? false)) {
                return response()->json(['message' => $result['message'] ?? 'Unable to update'], 422);
            }

            return response()->json(['message' => 'Contribution status updated successfully']);
        });
    }

    public function destroy(Request $request, int $contributionId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($contributionId) {
            $result = $this->manage->delete($contributionId);
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

        if (! $this->privileges->canEdit($admin->admin_id, AdminPrivilegeService::SECTION_BLOG_CONTRIBUTIONS)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $callback();
    }
}
