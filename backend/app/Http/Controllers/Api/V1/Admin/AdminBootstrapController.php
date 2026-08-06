<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminDashboardService;
use App\Services\Admin\AdminNavigationService;
use App\Services\Admin\AdminPrivilegeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminBootstrapController extends Controller
{
    public function __construct(
        private AdminNavigationService $navigation,
        private AdminPrivilegeService $privileges,
        private AdminDashboardService $dashboard,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();

        return response()->json([
            'admin' => [
                'id' => $admin->admin_id,
                'username' => $admin->admin_username,
                'name' => $admin->admin_name,
                'email' => $admin->admin_email,
            ],
            'navigation' => $this->navigation->menu($admin->admin_id),
            'privileges' => $this->privileges->privilegeFlags($admin->admin_id),
            'features' => $this->dashboard->featureFlags(),
            'report_generated_at' => $this->dashboard->reportGeneratedLabel($admin->admin_id),
        ]);
    }
}
