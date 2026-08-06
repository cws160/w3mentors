<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminModuleRegistry;
use App\Services\Admin\AdminPrivilegeService;
use App\Services\Admin\AdminRatingReviewService;
use App\Services\Admin\Listings\AdminReportedIssuesListingService;
use App\Services\Admin\Listings\AdminUsersListingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminModuleController extends Controller
{
    public function __construct(
        private AdminModuleRegistry $registry,
        private AdminPrivilegeService $privileges,
        private AdminUsersListingService $users,
        private AdminReportedIssuesListingService $reportedIssues,
    ) {
    }

    public function search(Request $request, string $module): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();

        $section = $this->sectionForModule($module, $request);
        if ($section === null) {
            return response()->json(['message' => 'Module not found'], 404);
        }

        if (! $this->privileges->canView($admin->admin_id, $section)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $result = $this->registry->search($module, $request);
        if ($result === null) {
            return response()->json([
                'data' => [],
                'meta' => ['current_page' => 1, 'per_page' => 10, 'total' => 0, 'last_page' => 1],
                'message' => 'Listing not available for this module',
            ]);
        }

        return response()->json($result);
    }

    public function updateUserStatus(Request $request, int $userId): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();

        if (! $this->privileges->canEdit($admin->admin_id, AdminPrivilegeService::SECTION_USERS)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $active = $request->boolean('active');
        if (! $this->users->updateStatus($userId, $active ? 1 : 0)) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json(['message' => 'Status updated', 'active' => $active]);
    }

    public function reportedIssueDetail(Request $request, int $issueId): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();

        if (! $this->privileges->canView($admin->admin_id, AdminPrivilegeService::SECTION_ISSUES_REPORTED)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $detail = $this->reportedIssues->detail($issueId);
        if ($detail === null) {
            return response()->json(['message' => 'Issue not found'], 404);
        }

        return response()->json(['data' => $detail]);
    }

    private function sectionForModule(string $module, Request $request): ?int
    {
        if ($module === 'rating-reviews') {
            $reviewType = $request->query('ratrev_type', $request->query('type', ''));

            if ($reviewType === 'course' || (int) $reviewType === AdminRatingReviewService::TYPE_COURSE) {
                return AdminPrivilegeService::SECTION_COURSE_REVIEWS;
            }

            return AdminPrivilegeService::SECTION_TEACHER_REVIEWS;
        }

        return $this->registry->sectionFor($module);
    }
}
