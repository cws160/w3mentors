<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminCommissionManageService;
use App\Services\Admin\AdminPrivilegeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCommissionController extends Controller
{
    public function __construct(
        private AdminCommissionManageService $commissions,
        private AdminPrivilegeService $privileges,
    ) {
    }

    public function show(Request $request, int $commissionId): JsonResponse
    {
        return $this->guardView($request, function () use ($commissionId) {
            $data = $this->commissions->show($commissionId);
            if ($data === null) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['data' => $data]);
        });
    }

    public function setup(Request $request): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request) {
            $result = $this->commissions->setup($request->all());
            if (! ($result['ok'] ?? false)) {
                return response()->json(['message' => $result['message'] ?? 'Unable to save'], 422);
            }

            return response()->json([
                'message' => 'Setup successful',
                'data' => ['comm_id' => $result['comm_id'] ?? 0],
            ]);
        });
    }

    public function history(Request $request): JsonResponse
    {
        return $this->guardView($request, function () use ($request) {
            $userId = $request->integer('user_id', 0);
            $page = max(1, $request->integer('page', 1));
            $result = $this->commissions->history($userId, $page);

            return response()->json([
                'data' => $result['data'],
                'meta' => $result['meta'],
            ]);
        });
    }

    public function autocomplete(Request $request): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request) {
            $keyword = trim((string) $request->query('keyword', ''));

            return response()->json([
                'data' => $this->commissions->autocomplete($keyword),
            ]);
        });
    }

    private function guardView(Request $request, callable $callback): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canView($admin->admin_id, AdminPrivilegeService::SECTION_COMMISSION)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $callback();
    }

    private function guardEdit(Request $request, callable $callback): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canEdit($admin->admin_id, AdminPrivilegeService::SECTION_COMMISSION)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $callback();
    }
}
