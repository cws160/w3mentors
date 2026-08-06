<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminPrivilegeService;
use App\Services\Admin\AdminQuizManageService;
use App\Services\Admin\Listings\AdminQuizzesListingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminQuizzesController extends Controller
{
    public function __construct(
        private AdminPrivilegeService $privileges,
        private AdminQuizManageService $quizzes,
        private AdminQuizzesListingService $listing,
    ) {
    }

    public function show(Request $request, int $quizId): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canView($admin->admin_id, AdminPrivilegeService::SECTION_QUIZZES)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            return response()->json(['data' => $this->quizzes->show($quizId)]);
        } catch (\RuntimeException $e) {
            $code = (int) $e->getCode();

            return response()->json(
                ['message' => $e->getMessage()],
                ($code >= 400 && $code < 600) ? $code : 500,
            );
        }
    }

    public function export(Request $request): StreamedResponse|JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canView($admin->admin_id, AdminPrivilegeService::SECTION_QUIZZES)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $rows = $this->listing->exportRows($request);

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Sr. No',
                'Title',
                'Type',
                'Teacher',
                'No. of questions',
                'Duration',
                'Attempts',
                'Pass percent',
                'Active',
                'Status',
                'Added on',
            ]);
            foreach ($rows as $index => $row) {
                fputcsv($handle, [
                    $index + 1,
                    $row['title'] ?? '',
                    $row['type_label'] ?? '',
                    $row['teacher_name'] ?? '',
                    $row['questions_count'] ?? '',
                    $row['duration_label'] ?? '',
                    $row['attempts'] ?? '',
                    $row['passmark_label'] ?? '',
                    $row['active_label'] ?? '',
                    $row['status_label'] ?? '',
                    $row['created_at'] ?? '',
                ]);
            }
            fclose($handle);
        }, 'quizzes.csv', ['Content-Type' => 'text/csv']);
    }
}
