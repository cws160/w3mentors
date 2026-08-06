<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\LectureDetailResource;
use App\Models\Course;
use App\Models\Lecture;
use App\Services\CourseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LectureController extends Controller
{
    public function __construct(private CourseService $courses)
    {
    }

    public function show(Request $request, Course $course, Lecture $lecture): JsonResponse
    {
        if (
            (int) $lecture->lecture_course_id !== (int) $course->course_id
            || $lecture->lecture_deleted
            || $lecture->lecture_archived
        ) {
            return response()->json(['message' => 'Lecture not found'], 404);
        }

        if (!$this->courses->canAccessLecture($request->user(), $lecture)) {
            return response()->json(['message' => 'Enroll in this course to access this lecture'], 403);
        }

        $lecture->load(['resources' => fn ($q) => $q->active(), 'section']);

        $progress = null;
        if ($request->user()) {
            $enrollment = $this->courses->findEnrollment($request->user(), $course->course_id);
            $progress = $enrollment?->progress;
        }

        return response()->json([
            'data' => new LectureDetailResource($lecture),
            'progress' => $progress ? [
                'percent' => (float) $progress->crspro_progress,
                'covered_lectures' => $progress->coveredLectureIds(),
                'is_completed' => in_array($lecture->lecture_id, $progress->coveredLectureIds(), true),
            ] : null,
        ]);
    }
}
