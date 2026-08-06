<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminAffiliateCommissionManageService;
use App\Services\Admin\AdminPrivilegeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAffiliateCommissionController extends Controller
{
    public function __construct(
        private AdminPrivilegeService $privileges,
        private AdminAffiliateCommissionManageService $manage,
    ) {
    }

    public function show(Request $request, int $commissionId): JsonResponse
    {
        return $this->guardView($request, function () use ($commissionId) {
            $data = $this->manage->show($commissionId);
            if ($data === null) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['data' => $data]);
        });
    }

    public function setup(Request $request): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request) {
            $result = $this->manage->setup($request->all());
            if (! ($result['ok'] ?? false)) {
                return response()->json(['message' => $result['message'] ?? 'Unable to save'], 422);
            }

            return response()->json([
                'message' => 'Setup successful',
                'data' => ['afcomm_id' => $result['afcomm_id'] ?? 0],
            ]);
        });
    }

    public function history(Request $request): JsonResponse
    {
        return $this->guardView($request, function () use ($request) {
            $userId = $request->integer('user_id', 0);

            return response()->json(['data' => $this->manage->history($userId)]);
        });
    }

    public function autocomplete(Request $request): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request) {
            $keyword = (string) $request->query('keyword', '');

            return response()->json(['data' => $this->manage->autocomplete($keyword)]);
        });
    }

    public function destroy(Request $request, int $commissionId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($commissionId) {
            $result = $this->manage->delete($commissionId);
            if (! ($result['ok'] ?? false)) {
                return response()->json(['message' => $result['message'] ?? 'Unable to delete'], 422);
            }

            return response()->json(['message' => 'Commission deleted successfully']);
        });
    }

    private function guardView(Request $request, callable $callback): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();

        if (! $this->privileges->canView($admin->admin_id, AdminPrivilegeService::SECTION_AFFILIATE_COMMISSION)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $callback();
    }

    private function guardEdit(Request $request, callable $callback): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();

        if (! $this->privileges->canEdit($admin->admin_id, AdminPrivilegeService::SECTION_AFFILIATE_COMMISSION)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $callback();
    }
}
