<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnrolledCourseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $course = $this->course;
        $progress = $this->progress;

        return [
            'enrollment_id' => $this->ordcrs_id,
            'status' => (int) $this->ordcrs_status,
            'amount' => (float) $this->ordcrs_amount,
            'progress' => [
                'percent' => (float) ($progress?->crspro_progress ?? 0),
                'status' => (int) ($progress?->crspro_status ?? 1),
                'current_lecture_id' => (int) ($progress?->crspro_lecture_id ?? 0),
                'covered_lectures' => $progress?->coveredLectureIds() ?? [],
                'started_at' => optional($progress?->crspro_started)?->toIso8601String(),
                'completed_at' => optional($progress?->crspro_completed)?->toIso8601String(),
            ],
            'course' => $course ? new CourseResource($course) : null,
        ];
    }
}
