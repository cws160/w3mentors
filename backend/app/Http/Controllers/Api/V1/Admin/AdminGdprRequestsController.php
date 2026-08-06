<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminGdprRequestService;
use App\Services\Admin\AdminPrivilegeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminGdprRequestsController extends Controller
{
    public function __construct(
        private AdminPrivilegeService $privileges,
        private AdminGdprRequestService $gdprRequests,
    ) {
    }

    public function show(Request $request, int $requestId): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canView($admin->admin_id, AdminPrivilegeService::SECTION_GDPR_REQUESTS)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            return response()->json(['data' => $this->gdprRequests->show($requestId)]);
        } catch (\RuntimeException $e) {
            $code = (int) $e->getCode();

            return response()->json(
                ['message' => $e->getMessage()],
                ($code >= 400 && $code < 600) ? $code : 500,
            );
        }
    }

    public function updateStatus(Request $request, int $requestId): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canEdit($admin->admin_id, AdminPrivilegeService::SECTION_GDPR_REQUESTS)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $status = $request->integer('status');
        $comment = trim((string) $request->input('comment', ''));

        try {
            $this->gdprRequests->updateStatus($requestId, $status, $comment);
        } catch (\RuntimeException $e) {
            $code = (int) $e->getCode();

            return response()->json(
                ['message' => $e->getMessage()],
                ($code >= 400 && $code < 600) ? $code : 500,
            );
        }

        return response()->json(['message' => 'Updated successfully']);
    }

    public function export(Request $request): StreamedResponse|JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canView($admin->admin_id, AdminPrivilegeService::SECTION_GDPR_REQUESTS)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $result = $this->gdprRequests->exportList($request);
        $rows = $result['data'] ?? [];

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'User', 'Email', 'Reason', 'Requested on', 'Updated on', 'Status']);
            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['id'] ?? '',
                    $row['full_name'] ?? '',
                    $row['email'] ?? '',
                    $row['reason'] ?? '',
                    $row['created_at'] ?? '',
                    $row['updated_at'] ?? '',
                    $row['status_label'] ?? '',
                ]);
            }
            fclose($handle);
        }, 'gdpr-requests.csv', ['Content-Type' => 'text/csv']);
    }
}
