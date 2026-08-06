<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminPrivilegeService;
use App\Services\Admin\AdminWithdrawRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminWithdrawRequestsController extends Controller
{
    public function __construct(
        private AdminPrivilegeService $privileges,
        private AdminWithdrawRequestService $withdrawRequests,
    ) {
    }

    public function updateStatus(Request $request, int $withdrawalId): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canEdit($admin->admin_id, AdminPrivilegeService::SECTION_WITHDRAW_REQUESTS)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $status = $request->integer('status');
        try {
            $this->withdrawRequests->updateStatus($withdrawalId, $status);
        } catch (\RuntimeException $e) {
            $code = (int) $e->getCode();

            return response()->json(
                ['message' => $e->getMessage()],
                ($code >= 400 && $code < 600) ? $code : 500,
            );
        }

        return response()->json(['message' => 'Status updated successfully']);
    }

    public function export(Request $request): StreamedResponse|JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canView($admin->admin_id, AdminPrivilegeService::SECTION_WITHDRAW_REQUESTS)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $result = $this->withdrawRequests->exportList($request);
        $rows = $result['data'] ?? [];

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'User', 'Email', 'Txn fee', 'Amount', 'Account', 'Comments', 'Date', 'Status']);
            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['request_number'] ?? '',
                    $row['full_name'] ?? '',
                    $row['email'] ?? '',
                    $row['transaction_fee'] ?? '',
                    $row['amount'] ?? '',
                    strip_tags((string) ($row['account_details'] ?? '')),
                    $row['comments'] ?? '',
                    $row['created_at'] ?? '',
                    $row['status_label'] ?? '',
                ]);
            }
            fclose($handle);
        }, 'withdraw-requests.csv', ['Content-Type' => 'text/csv']);
    }
}
