<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\TeacherDashboardCourseEditRequestsService;
use App\Services\TeacherDashboardCoursesService;
use App\Services\TeacherDashboardResourcesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardTeacherCoursesController extends Controller
{
    public function __construct(
        private TeacherDashboardCoursesService $courses,
        private TeacherDashboardResourcesService $resources,
        private TeacherDashboardCourseEditRequestsService $editRequests
    ) {
    }

    public function courseFilters(Request $request): JsonResponse
    {
        $user = $this->requireTeacher($request);
        $langId = $this->langId($request, $user);

        return response()->json(['data' => $this->courses->filters($langId)]);
    }

    public function searchCourses(Request $request): JsonResponse
    {
        $user = $this->requireTeacher($request);
        $langId = $this->langId($request, $user);
        $paginator = $this->courses->search(
            $user->user_id,
            $langId,
            $request->only(['keyword', 'course_cateid', 'course_subcateid', 'course_status', 'course_type']),
            $request->integer('per_page', 20)
        );

        $data = collect($paginator->items())->map(fn ($row) => $this->courses->formatCourseRow($row));

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function searchResources(Request $request): JsonResponse
    {
        $user = $this->requireTeacher($request);
        $paginator = $this->resources->search(
            $user->user_id,
            $request->only(['keyword']),
            $request->integer('per_page', 20)
        );

        $data = collect($paginator->items())->map(fn ($row) => $this->resources->formatRow($row));

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function editRequestFilters(Request $request): JsonResponse
    {
        $this->requireTeacher($request);

        return response()->json(['data' => $this->editRequests->filters()]);
    }

    public function searchEditRequests(Request $request): JsonResponse
    {
        $user = $this->requireTeacher($request);
        $langId = $this->langId($request, $user);
        $paginator = $this->editRequests->search(
            $user->user_id,
            $langId,
            $request->only(['keyword', 'status']),
            $request->integer('per_page', 20)
        );

        $data = collect($paginator->items())->map(fn ($row) => $this->editRequests->formatRow($row));

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    private function requireTeacher(Request $request)
    {
        $user = $request->user();
        if (! $user || ! $user->user_is_teacher) {
            abort(403, 'Teacher account required.');
        }

        return $user;
    }

    private function langId(Request $request, $user): int
    {
        $langId = $request->integer('lang_id', 0);

        return $langId > 0 ? $langId : (int) ($user->user_lang_id ?: 1);
    }
}
