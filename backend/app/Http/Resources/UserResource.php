<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->user_id,
            'first_name' => $this->user_first_name,
            'last_name' => $this->user_last_name,
            'full_name' => $this->full_name,
            'email' => $this->user_email,
            'username' => $this->user_username,
            'timezone' => $this->user_timezone,
            'is_teacher' => (bool) $this->user_is_teacher,
            'is_affiliate' => (bool) $this->user_is_affiliate,
            'is_featured' => (bool) $this->user_featured,
            'verified_at' => optional($this->user_verified)?->toIso8601String(),
            'created_at' => optional($this->user_created)?->toIso8601String(),
        ];
    }
}
