<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminCoursePreviewService;
use App\Services\Admin\AdminCoursesManageService;
use App\Services\Admin\AdminPrivilegeService;
use App\Services\Admin\AdminUserService;
use App\Services\Admin\Listings\AdminCoursesListingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminCoursesController extends Controller
{
    public function __construct(
        private AdminPrivilegeService $privileges,
        private AdminCoursesManageService $manage,
        private AdminCoursesListingService $listings,
        private AdminUserService $users,
        private AdminCoursePreviewService $preview,
    ) {
    }

    public function searchForm(Request $request): JsonResponse
    {
        return $this->guardView($request, function () {
            return response()->json([
                'data' => [
                    'categories' => $this->manage->rootCategories(),
                ],
            ]);
        });
    }

    public function subcategories(Request $request): JsonResponse
    {
        return $this->guardView($request, function () use ($request) {
            $parentId = $request->integer('parent_id', 0);

            return response()->json([
                'data' => $this->manage->subcategories($parentId),
            ]);
        });
    }

    public function courseLanguageAutocomplete(Request $request): JsonResponse
    {
        return $this->guardView($request, function () use ($request) {
            $keyword = (string) $request->query('keyword', '');

            return response()->json([
                'data' => $this->manage->courseLanguageAutocomplete($keyword),
            ]);
        });
    }

    public function show(Request $request, int $courseId): JsonResponse
    {
        return $this->guardView($request, function () use ($courseId) {
            $data = $this->manage->show($courseId);
            if (! $data) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['data' => $data]);
        });
    }

    public function export(Request $request): StreamedResponse|JsonResponse
    {
        return $this->guardView($request, function () use ($request) {
            $rows = $this->listings->exportRows($request);

            return response()->streamDownload(function () use ($rows) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['ID', 'Title', 'Teacher', 'Category', 'Subcategory', 'Published On', 'Status']);
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row['id'] ?? '',
                        $row['title'] ?? '',
                        $row['teacher_name'] ?? '',
                        $row['category_name'] ?? '',
                        $row['subcategory_name'] ?? '',
                        $row['published_at'] ?? '',
                        ((int) ($row['active'] ?? 0)) === 1 ? 'Active' : 'Inactive',
                    ]);
                }
                fclose($handle);
            }, 'courses.csv', ['Content-Type' => 'text/csv']);
        });
    }

    public function preview(Request $request, int $courseId): JsonResponse
    {
        return $this->guardView($request, function () use ($courseId) {
            $data = $this->preview->preview($courseId);
            if (! $data) {
                return response()->json(['message' => 'Course preview is not available'], 404);
            }

            return response()->json(['data' => $data]);
        });
    }

    public function previewLecture(Request $request, int $courseId, int $lectureId): JsonResponse
    {
        return $this->guardView($request, function () use ($courseId, $lectureId) {
            $data = $this->preview->previewLecture($courseId, $lectureId);
            if (! $data) {
                return response()->json(['message' => 'Lecture not found'], 404);
            }

            return response()->json(['data' => $data]);
        });
    }

    public function previewNotes(Request $request, int $courseId): JsonResponse
    {
        return $this->guardView($request, function () use ($request, $courseId) {
            $course = $this->preview->preview($courseId);
            if (! $course) {
                return response()->json(['message' => 'Course preview is not available'], 404);
            }

            $teacherId = (int) ($course['course']['teacher_id'] ?? 0);
            $data = $this->preview->previewNotes(
                $courseId,
                $teacherId,
                (string) $request->query('keyword', ''),
                $request->integer('page', 1),
            );

            return response()->json(['data' => $data]);
        });
    }

    public function previewReviews(Request $request, int $courseId): JsonResponse
    {
        return $this->guardView($request, function () use ($request, $courseId) {
            $sort = strtoupper((string) $request->query('sort', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
            $data = $this->preview->previewReviews($courseId, $sort, $request->integer('page', 1));
            if ($data === []) {
                return response()->json(['message' => 'Course preview is not available'], 404);
            }

            return response()->json(['data' => $data]);
        });
    }

    public function previewDownloadResource(Request $request, int $courseId, int $resourceId): StreamedResponse|JsonResponse
    {
        return $this->guardView($request, function () use ($courseId, $resourceId) {
            $response = $this->preview->downloadResource($courseId, $resourceId);
            if (! $response) {
                return response()->json(['message' => 'Resource not found'], 404);
            }

            return $response;
        });
    }

    public function previewBridge(Request $request, int $courseId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $courseId) {
            $teacherId = $request->integer('teacher_id', 0);
            if ($teacherId < 1) {
                return response()->json(['message' => 'Invalid request'], 422);
            }

            try {
                return response()->json($this->users->createDashboardBridgeUrl($teacherId, $courseId));
            } catch (\RuntimeException $e) {
                return response()->json(['message' => $e->getMessage()], (int) ($e->getCode() ?: 422));
            }
        });
    }

    public function updateStatus(Request $request, int $courseId): JsonResponse
    {
        $adminId = $this->adminId($request);
        if (! $this->privileges->canEdit($adminId, AdminPrivilegeService::SECTION_COURSE)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $status = (int) $request->input('status', 0);
        $result = $this->manage->updateActiveStatus($courseId, $status);
        if (! ($result['ok'] ?? false)) {
            return response()->json(['message' => $result['message'] ?? 'Unable to update status'], 422);
        }

        return response()->json(['message' => 'Status updated successfully']);
    }

    private function guardView(Request $request, callable $callback): JsonResponse|StreamedResponse
    {
        $adminId = $this->adminId($request);
        if (! $this->privileges->canView($adminId, AdminPrivilegeService::SECTION_COURSE)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return $callback();
    }

    private function guardEdit(Request $request, callable $callback): JsonResponse
    {
        $adminId = $this->adminId($request);
        if (! $this->privileges->canEdit($adminId, AdminPrivilegeService::SECTION_COURSE)) {
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
}
