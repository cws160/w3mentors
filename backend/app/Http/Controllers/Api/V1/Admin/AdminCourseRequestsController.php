<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminCourseRequestService;
use App\Services\Admin\AdminPrivilegeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class AdminCourseRequestsController extends Controller
{
    public function __construct(
        private AdminPrivilegeService $privileges,
        private AdminCourseRequestService $requests,
    ) {
    }

    public function show(Request $request, int $requestId): JsonResponse
    {
        return $this->guardView($request, function () use ($request, $requestId) {
            $data = $this->requests->show($requestId, $this->langId($request));
            if (! $data) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['data' => $data]);
        });
    }

    public function updateStatus(Request $request, int $requestId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $requestId) {
            try {
                $this->requests->updateStatus(
                    $requestId,
                    (int) $request->input('status', 0),
                    (string) $request->input('remark', ''),
                );
            } catch (RuntimeException $e) {
                return response()->json(['message' => $e->getMessage()], (int) ($e->getCode() ?: 422));
            }

            return response()->json(['message' => 'Status updated successfully']);
        });
    }

    private function guardView(Request $request, callable $callback): JsonResponse
    {
        $adminId = $this->adminId($request);
        if (! $this->privileges->canView($adminId, AdminPrivilegeService::SECTION_COURSE_REQUESTS)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return $callback();
    }

    private function guardEdit(Request $request, callable $callback): JsonResponse
    {
        $adminId = $this->adminId($request);
        if (! $this->privileges->canEdit($adminId, AdminPrivilegeService::SECTION_COURSE_REQUESTS)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return $callback();
    }

    private function adminId(Request $request): int
    {
        /** @var Admin $admin */
        $admin = $request->user();

        return (int) $admin->admin_id;
    }

    private function langId(Request $request): int
    {
        return max(1, $request->integer('lang_id', 1));
    }
}
