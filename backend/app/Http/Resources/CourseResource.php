<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->course_id,
            'slug' => $this->course_slug,
            'title' => $this->details?->course_title,
            'subtitle' => $this->details?->course_subtitle,
            'description' => $this->details?->course_details,
            'price' => $this->course_price,
            'duration' => $this->course_duration,
            'sections' => $this->course_sections,
            'lectures' => $this->course_lectures,
            'students' => $this->course_students,
            'ratings' => $this->course_ratings,
            'reviews' => $this->course_reviews,
            'certificate' => (bool) $this->course_certificate,
            'is_free' => (int) $this->course_type === 1,
            'level' => (int) $this->course_level,
            'preview_video' => $this->course_preview_video,
            'teacher' => $this->whenLoaded('teacher', fn () => new TeacherResource($this->teacher)),
            'created_at' => optional($this->course_created)?->toIso8601String(),
        ];
    }
}
