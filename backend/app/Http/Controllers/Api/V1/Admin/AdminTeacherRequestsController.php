<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminModuleRegistry;
use App\Services\Admin\AdminPrivilegeService;
use App\Services\Admin\AdminTeacherRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminTeacherRequestsController extends Controller
{
    public function __construct(
        private AdminModuleRegistry $registry,
        private AdminPrivilegeService $privileges,
        private AdminTeacherRequestService $teacherRequests,
    ) {
    }

    public function show(Request $request, int $requestId): JsonResponse
    {
        return $this->guardView($request, function () use ($request, $requestId) {
            return response()->json([
                'data' => $this->teacherRequests->view($requestId, $this->langId($request)),
            ]);
        });
    }

    public function qualifications(Request $request, int $userId): JsonResponse
    {
        return $this->guardView($request, function () use ($request, $userId) {
            return response()->json($this->teacherRequests->qualifications($userId, $request));
        });
    }

    public function statusForm(Request $request, int $requestId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($requestId) {
            return response()->json($this->teacherRequests->statusForm($requestId));
        });
    }

    public function updateStatus(Request $request, int $requestId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $requestId) {
            $this->teacherRequests->updateStatus($requestId, $request->all());

            return response()->json(['message' => 'Status updated successfully']);
        });
    }

    public function export(Request $request): StreamedResponse|JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();

        if (! $this->privileges->canView($admin->admin_id, AdminPrivilegeService::SECTION_TEACHER_REQUEST)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->merge(['export' => true]);
        $result = $this->registry->search('teacher-requests', $request);
        $rows = $result['data'] ?? [];

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Reference number', 'Name', 'Email', 'Comments', 'Requested on', 'Status']);
            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['reference'] ?? '',
                    $row['full_name'] ?? '',
                    $row['email'] ?? '',
                    $row['comments'] ?? '',
                    $row['created_at'] ?? '',
                    $this->teacherRequests->statusLabel((int) ($row['status'] ?? -1)),
                ]);
            }
            fclose($handle);
        }, 'teacher-requests.csv', ['Content-Type' => 'text/csv']);
    }

    private function langId(Request $request): int
    {
        $lang = $request->integer('lang_id', 1);

        return $lang > 0 ? $lang : 1;
    }

  /** @param  callable(): JsonResponse  $callback */
    private function guardView(Request $request, callable $callback): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canView($admin->admin_id, AdminPrivilegeService::SECTION_TEACHER_REQUEST)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            return $callback();
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], $this->httpStatusFromException($e));
        }
    }

    /** @param  callable(): JsonResponse  $callback */
    private function guardEdit(Request $request, callable $callback): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canEdit($admin->admin_id, AdminPrivilegeService::SECTION_TEACHER_REQUEST)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            return $callback();
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], $this->httpStatusFromException($e));
        }
    }

    private function httpStatusFromException(\Throwable $e): int
    {
        $code = (int) $e->getCode();

        return ($code >= 400 && $code < 600) ? $code : 500;
    }
}
