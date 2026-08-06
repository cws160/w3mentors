<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->ordles_id,
            'status' => $this->ordles_status,
            'duration' => $this->ordles_duration,
            'amount' => $this->ordles_amount,
            'offline' => (bool) $this->ordles_offline,
            'start_time' => optional($this->ordles_lesson_starttime)?->toIso8601String(),
            'end_time' => optional($this->ordles_lesson_endtime)?->toIso8601String(),
            'teacher' => $this->whenLoaded('teacher', fn () => new TeacherResource($this->teacher)),
            'learner' => $this->whenLoaded('order', function () {
                $learner = $this->order?->user;
                if (! $learner) {
                    return null;
                }

                return [
                    'id' => $learner->user_id,
                    'full_name' => $learner->full_name,
                    'first_name' => $learner->user_first_name,
                ];
            }),
        ];
    }
}
