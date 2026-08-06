<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminPrivilegeService;
use App\Services\Admin\AdminSocialPlatformManageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSocialPlatformController extends Controller
{
    public function __construct(
        private AdminSocialPlatformManageService $platforms,
        private AdminPrivilegeService $privileges,
    ) {
    }

    public function form(Request $request, int $platformId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($platformId) {
            try {
                return response()->json(['data' => $this->platforms->form($platformId)]);
            } catch (\InvalidArgumentException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        });
    }

    public function setup(Request $request): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request) {
            try {
                $platformId = $this->platforms->setup($request->all());

                return response()->json([
                    'message' => 'Setup successful',
                    'data' => ['splatform_id' => $platformId],
                ]);
            } catch (\InvalidArgumentException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        });
    }

    public function changeStatus(Request $request, int $platformId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $platformId) {
            try {
                $status = $request->boolean('active') ? 1 : 0;
                $this->platforms->changeStatus($platformId, $status);

                return response()->json(['message' => 'Action performed successfully']);
            } catch (\InvalidArgumentException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        });
    }

    private function guardEdit(Request $request, callable $callback): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canEdit($admin->admin_id, AdminPrivilegeService::SECTION_SOCIALPLATFORM)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $callback();
    }
}
