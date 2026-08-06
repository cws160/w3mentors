<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminAdminPermissionService;
use App\Services\Admin\AdminPrivilegeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAdminPermissionsController extends Controller
{
    public function __construct(
        private AdminPrivilegeService $privileges,
        private AdminAdminPermissionService $permissions,
    ) {
    }

    public function show(Request $request, int $adminId): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canView($admin->admin_id, AdminPrivilegeService::SECTION_ADMIN_PERMISSIONS)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            $data = $this->permissions->pageData($adminId, (int) $admin->admin_id);

            return response()->json(['data' => $data]);
        } catch (\RuntimeException $e) {
            $code = (int) $e->getCode();

            return response()->json(
                ['message' => $e->getMessage()],
                ($code >= 400 && $code < 600) ? $code : 500,
            );
        }
    }

    public function update(Request $request, int $adminId): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canEdit($admin->admin_id, AdminPrivilegeService::SECTION_ADMIN_PERMISSIONS)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $sectionId = $request->integer('section_id');
        $permission = $request->integer('permission');

        try {
            $this->permissions->updatePermission($adminId, $sectionId, $permission, (int) $admin->admin_id);

            return response()->json([
                'message' => 'Updated successfully',
                'section_id' => $sectionId,
            ]);
        } catch (\RuntimeException $e) {
            $code = (int) $e->getCode();

            return response()->json(
                ['message' => $e->getMessage()],
                ($code >= 400 && $code < 600) ? $code : 500,
            );
        }
    }
}
