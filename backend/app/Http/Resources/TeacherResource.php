<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeacherResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->user_id,
            'username' => $this->user_username,
            'full_name' => $this->full_name,
            'first_name' => $this->user_first_name,
            'last_name' => $this->user_last_name,
            'is_featured' => (bool) $this->user_featured,
            'offline_sessions' => (bool) $this->user_offline_sessions,
            'last_seen' => optional($this->user_lastseen)?->toIso8601String(),
            'ratings' => isset($this->testat_ratings) ? (float) $this->testat_ratings : 0,
            'students' => (int) ($this->testat_students ?? 0),
            'lessons' => (int) ($this->testat_lessons ?? 0),
            'classes' => (int) ($this->testat_classes ?? 0),
            'courses' => (int) ($this->testat_courses ?? 0),
        ];
    }
}
