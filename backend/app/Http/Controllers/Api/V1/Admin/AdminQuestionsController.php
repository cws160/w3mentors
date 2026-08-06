<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminPrivilegeService;
use App\Services\Admin\AdminQuestionManageService;
use App\Services\Admin\Listings\AdminQuestionsListingService;
use App\Services\QuestionListingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminQuestionsController extends Controller
{
    public function __construct(
        private AdminPrivilegeService $privileges,
        private AdminQuestionManageService $questions,
        private AdminQuestionsListingService $listing,
        private QuestionListingService $questionCategories,
    ) {
    }

    public function quizCategories(Request $request): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canView($admin->admin_id, AdminPrivilegeService::SECTION_QUESTIONS)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $langId = max(1, $request->integer('lang_id', 1));
        $parentId = max(0, $request->integer('parent_id', 0));

        return response()->json([
            'data' => $this->questionCategories->categories($langId, $parentId),
        ]);
    }

    public function show(Request $request, int $questionId): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canView($admin->admin_id, AdminPrivilegeService::SECTION_QUESTIONS)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $langId = max(1, $request->integer('lang_id', 1));

        try {
            return response()->json(['data' => $this->questions->show($questionId, $langId)]);
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
        if (! $this->privileges->canView($admin->admin_id, AdminPrivilegeService::SECTION_QUESTIONS)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $rows = $this->listing->exportRows($request);

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Sr. No',
                'Question title',
                'Type',
                'Category',
                'Subcategory',
                'Teacher',
                'Added on',
            ]);
            foreach ($rows as $index => $row) {
                fputcsv($handle, [
                    $index + 1,
                    $row['title'] ?? '',
                    $row['type_label'] ?? '',
                    $row['category_name'] ?? '',
                    $row['subcategory_name'] ?? '',
                    $row['teacher_name'] ?? '',
                    $row['created_at'] ?? '',
                ]);
            }
            fclose($handle);
        }, 'questions.csv', ['Content-Type' => 'text/csv']);
    }
}
