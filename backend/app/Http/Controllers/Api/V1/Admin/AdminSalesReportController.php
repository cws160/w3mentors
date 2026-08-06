<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminDashboardService;
use App\Services\Admin\AdminPrivilegeService;
use App\Services\Admin\AdminSalesReportRegenerateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminSalesReportController extends Controller
{
    public function __construct(
        private AdminSalesReportRegenerateService $regenerateService,
        private AdminDashboardService $dashboard,
        private AdminPrivilegeService $privileges,
    ) {
    }

    public function regenerate(Request $request): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();

        if (! $this->privileges->canEdit($admin->admin_id, AdminPrivilegeService::SECTION_REPORT_STATS_REGENERATE)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            $this->regenerateService->regenerate();
        } catch (\Throwable $e) {
            Log::error('Sales report regenerate failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Something went wrong'], 500);
        }

        $reportGeneratedAt = $this->dashboard->reportGeneratedLabel($admin->admin_id);

        return response()->json([
            'status' => 1,
            'message' => 'Report regenerated successfully',
            'msg' => 'Report regenerated successfully',
            'report_generated_at' => $reportGeneratedAt,
            'regendatedtime' => $reportGeneratedAt,
        ]);
    }
}
