<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminPrivilegeService;
use App\Services\Admin\AdminThemesManageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminThemesController extends Controller
{
    public function __construct(
        private AdminThemesManageService $themes,
        private AdminPrivilegeService $privileges,
    ) {
    }

    public function form(Request $request, int $themeId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $themeId) {
            $action = (string) $request->query('action', 'update');
            if (! in_array($action, ['update', 'clone'], true)) {
                $action = 'update';
            }

            try {
                return response()->json(['data' => $this->themes->form($themeId, $action)]);
            } catch (\InvalidArgumentException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        });
    }

    public function setup(Request $request): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request) {
            try {
                $themeId = $this->themes->setup($request->all());

                return response()->json(['data' => ['theme_id' => $themeId]]);
            } catch (\InvalidArgumentException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        });
    }

    public function activate(Request $request, int $themeId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($themeId) {
            try {
                $this->themes->activate($themeId);

                return response()->json(['message' => 'Theme activated successfully']);
            } catch (\InvalidArgumentException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        });
    }

    public function destroy(Request $request, int $themeId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($themeId) {
            try {
                $this->themes->delete($themeId);

                return response()->json(['message' => 'Theme deleted successfully']);
            } catch (\InvalidArgumentException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        });
    }

    private function guardEdit(Request $request, callable $callback): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();

        if (! $this->privileges->canEdit($admin->admin_id, AdminPrivilegeService::SECTION_THEME_MANAGEMENT)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $callback();
    }
}
