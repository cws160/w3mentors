<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\TeacherDashboardCoursesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardCoursesController extends Controller
{
    public function __construct(private TeacherDashboardCoursesService $courses)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->user_is_teacher) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $langId = $request->integer('lang_id', 0) > 0
            ? $request->integer('lang_id')
            : (int) ($user->user_lang_id ?: 1);

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

    public function filters(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->user_is_teacher) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $langId = $request->integer('lang_id', 0) > 0
            ? $request->integer('lang_id')
            : (int) ($user->user_lang_id ?: 1);

        return response()->json(['data' => $this->courses->filters($langId)]);
    }
}
