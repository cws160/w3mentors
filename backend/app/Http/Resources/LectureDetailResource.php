<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LectureDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->lecture_id,
            'title' => $this->lecture_title,
            'details' => $this->lecture_details,
            'duration' => $this->lecture_duration,
            'order' => $this->lecture_order,
            'is_trial' => (bool) $this->lecture_is_trial,
            'section' => $this->whenLoaded('section', fn () => [
                'id' => $this->section->section_id,
                'title' => $this->section->section_title,
            ]),
            'resources' => $this->whenLoaded('resources', fn () => $this->resources->map(fn ($r) => [
                'id' => $r->lecsrc_id,
                'type' => (int) $r->lecsrc_type,
                'link' => $r->lecsrc_link,
                'name' => $r->lecsrc_link_name,
                'duration' => (int) $r->lecsrc_duration,
            ])),
        ];
    }
}
