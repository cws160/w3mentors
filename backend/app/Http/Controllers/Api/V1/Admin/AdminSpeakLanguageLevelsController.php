<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminPrivilegeService;
use App\Services\Admin\AdminSpeakLanguageLevelManageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSpeakLanguageLevelsController extends Controller
{
    public function __construct(
        private AdminSpeakLanguageLevelManageService $levels,
        private AdminPrivilegeService $privileges,
    ) {
    }

    public function show(Request $request, int $slanglvlId): JsonResponse
    {
        return $this->guardView($request, function () use ($request, $slanglvlId) {
            $langId = max(1, $request->integer('lang_id', 1));
            $data = $this->levels->show($slanglvlId, $langId);
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

    public function update(Request $request, int $slanglvlId): JsonResponse
    {
        return $this->save($request, $slanglvlId);
    }

    public function changeStatus(Request $request, int $slanglvlId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $slanglvlId) {
            $status = $request->boolean('active') ? 1 : 0;
            if (! $this->levels->changeStatus($slanglvlId, $status)) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['message' => 'Status updated successfully']);
        });
    }

    public function destroy(Request $request, int $slanglvlId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($slanglvlId) {
            if (! $this->levels->delete($slanglvlId)) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['message' => 'Record deleted successfully']);
        });
    }

    public function updateOrder(Request $request): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request) {
            $ids = array_values(array_map('intval', $request->input('spokenLanguageLevels', $request->input('levels', []))));
            if ($ids === [] || ! $this->levels->updateOrder($ids)) {
                return response()->json(['message' => 'Unable to update order'], 422);
            }

            return response()->json(['message' => 'Order updated successfully']);
        });
    }

    private function save(Request $request, int $slanglvlId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $slanglvlId) {
            $langId = max(1, $request->integer('lang_id', 1));

            try {
                $id = $this->levels->save($slanglvlId, $request->only([
                    'slanglvl_identifier',
                    'slanglvl_name',
                    'slanglvl_active',
                    'update_langs_data',
                ]), $langId);
            } catch (\InvalidArgumentException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return response()->json([
                'message' => 'Setup successful',
                'data' => ['slanglvl_id' => $id],
            ]);
        });
    }

    private function guardView(Request $request, callable $callback): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canView($admin->admin_id, AdminPrivilegeService::SECTION_SPEAK_LANGUAGE_LEVELS)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $callback();
    }

    private function guardEdit(Request $request, callable $callback): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canEdit($admin->admin_id, AdminPrivilegeService::SECTION_SPEAK_LANGUAGE_LEVELS)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $callback();
    }
}
