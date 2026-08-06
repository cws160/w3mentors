<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\Listings\AdminLessonStatsListingService;
use App\Services\Admin\AdminPrivilegeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminLessonStatsController extends Controller
{
    public function __construct(
        private AdminLessonStatsListingService $lessonStats,
        private AdminPrivilegeService $privileges,
    ) {
    }

    public function logs(Request $request, int $userId): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();

        if (! $this->privileges->canView($admin->admin_id, AdminPrivilegeService::SECTION_LESSON_STATS)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($this->lessonStats->searchLogs($request, $userId));
    }
}
