<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminPreferenceManageService;
use App\Services\Admin\AdminPrivilegeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPreferencesController extends Controller
{
    public function __construct(
        private AdminPreferenceManageService $preferences,
        private AdminPrivilegeService $privileges,
    ) {
    }

    public function show(Request $request, int $preferId): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canView($admin->admin_id, AdminPrivilegeService::SECTION_TEACHER_PREFFERENCES)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $langId = max(1, $request->integer('lang_id', 1));
        $data = $this->preferences->show($preferId, $langId);
        if ($data === null) {
            return response()->json(['message' => 'Preference not found'], 404);
        }

        return response()->json(['data' => $data]);
    }

    public function store(Request $request): JsonResponse
    {
        return $this->save($request, 0);
    }

    public function update(Request $request, int $preferId): JsonResponse
    {
        return $this->save($request, $preferId);
    }

    public function destroy(Request $request, int $preferId): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canEdit($admin->admin_id, AdminPrivilegeService::SECTION_TEACHER_PREFFERENCES)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (! $this->preferences->delete($preferId)) {
            return response()->json(['message' => 'Preference not found'], 404);
        }

        return response()->json(['message' => 'Record deleted successfully']);
    }

    public function updateOrder(Request $request): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canEdit($admin->admin_id, AdminPrivilegeService::SECTION_TEACHER_PREFFERENCES)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $ids = array_values(array_map('intval', $request->input('preferences', [])));
        if ($ids === [] || ! $this->preferences->updateOrder($ids)) {
            return response()->json(['message' => 'Unable to update order'], 422);
        }

        return response()->json(['message' => 'Order updated successfully']);
    }

    private function save(Request $request, int $preferId): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canEdit($admin->admin_id, AdminPrivilegeService::SECTION_TEACHER_PREFFERENCES)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $langId = max(1, $request->integer('lang_id', 1));

        try {
            $id = $this->preferences->save($preferId, $request->only([
                'prefer_identifier',
                'prefer_title',
                'prefer_type',
                'update_langs_data',
            ]), $langId);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Setup successful',
            'data' => ['prefer_id' => $id],
        ]);
    }
}
