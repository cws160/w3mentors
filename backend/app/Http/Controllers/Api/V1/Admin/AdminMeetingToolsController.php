<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminMeetingToolManageService;
use App\Services\Admin\AdminPrivilegeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminMeetingToolsController extends Controller
{
    public function __construct(
        private AdminMeetingToolManageService $tools,
        private AdminPrivilegeService $privileges,
    ) {
    }

    public function form(Request $request, int $toolId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($toolId) {
            try {
                return response()->json(['data' => $this->tools->form($toolId)]);
            } catch (\InvalidArgumentException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        });
    }

    public function setup(Request $request): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request) {
            try {
                $toolId = $this->tools->setup($request->all());

                return response()->json([
                    'message' => 'Setup successful',
                    'data' => ['metool_id' => $toolId],
                ]);
            } catch (\InvalidArgumentException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        });
    }

    public function changeStatus(Request $request, int $toolId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $toolId) {
            try {
                $status = $request->boolean('active') ? 1 : (int) $request->input('status', 0);
                $this->tools->changeStatus($toolId, $status);

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
        if (! $this->privileges->canEdit($admin->admin_id, AdminPrivilegeService::SECTION_MEETING_TOOL)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $callback();
    }
}
