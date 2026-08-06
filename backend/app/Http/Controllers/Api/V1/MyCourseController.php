<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\EnrolledCourseResource;
use App\Models\Course;
use App\Models\OrderCourse;
use App\Services\CourseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MyCourseController extends Controller
{
    public function __construct(private CourseService $courses)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $enrollments = OrderCourse::query()
            ->active()
            ->with(['course.details', 'course.teacher', 'progress'])
            ->whereHas('order', fn ($q) => $q->where('order_user_id', $user->user_id))
            ->orderByDesc('ordcrs_id')
            ->paginate($request->integer('per_page', 12));

        return response()->json([
            'data' => EnrolledCourseResource::collection($enrollments),
            'meta' => [
                'current_page' => $enrollments->currentPage(),
                'last_page' => $enrollments->lastPage(),
                'per_page' => $enrollments->perPage(),
                'total' => $enrollments->total(),
            ],
        ]);
    }

    public function show(Request $request, Course $course): JsonResponse
    {
        $enrollment = $this->courses->findEnrollment($request->user(), $course->course_id);

        if (!$enrollment) {
            return response()->json(['message' => 'You are not enrolled in this course'], 403);
        }

        $enrollment->load(['course.details', 'course.teacher', 'progress']);
        $curriculum = $this->courses->getCurriculum($course, $request->user());

        return response()->json([
            'data' => new EnrolledCourseResource($enrollment),
            'curriculum' => $curriculum,
        ]);
    }

    public function start(Request $request, Course $course): JsonResponse
    {
        $enrollment = $this->courses->findEnrollment($request->user(), $course->course_id);

        if (!$enrollment) {
            return response()->json(['message' => 'You are not enrolled in this course'], 403);
        }

        $progress = $this->courses->ensureProgress($enrollment);

        return response()->json([
            'message' => 'Course started',
            'progress' => [
                'id' => $progress->crspro_id,
                'percent' => (float) $progress->crspro_progress,
                'status' => (int) $progress->crspro_status,
                'covered_lectures' => $progress->coveredLectureIds(),
            ],
        ]);
    }

    public function updateProgress(Request $request, Course $course): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'lecture_id' => ['required', 'integer'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $enrollment = $this->courses->findEnrollment($request->user(), $course->course_id);

        if (!$enrollment) {
            return response()->json(['message' => 'You are not enrolled in this course'], 403);
        }

        $progress = $this->courses->ensureProgress($enrollment);
        $progress = $this->courses->markLectureComplete(
            $progress,
            $course,
            $request->integer('lecture_id')
        );

        return response()->json([
            'message' => 'Progress updated',
            'progress' => [
                'id' => $progress->crspro_id,
                'percent' => (float) $progress->crspro_progress,
                'status' => (int) $progress->crspro_status,
                'current_lecture_id' => (int) $progress->crspro_lecture_id,
                'covered_lectures' => $progress->coveredLectureIds(),
                'completed_at' => optional($progress->crspro_completed)?->toIso8601String(),
            ],
        ]);
    }
}
