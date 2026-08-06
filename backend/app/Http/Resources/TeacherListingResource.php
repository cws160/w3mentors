<?php

namespace App\Http\Resources;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class TeacherListingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $langId = 1;
        $teachLangs = DB::table('tbl_user_teach_languages as utl')
            ->join('tbl_teach_languages_lang as tll', 'utl.utlang_tlang_id', '=', 'tll.tlanglang_tlang_id')
            ->where('utl.utlang_user_id', $this->user_id)
            ->where('tll.tlanglang_lang_id', $langId)
            ->pluck('tll.tlang_name')
            ->take(3)
            ->implode(', ');

        $speakLangs = DB::table('tbl_user_speak_languages as usl')
            ->join('tbl_speak_languages_lang as sll', 'usl.uslang_slang_id', '=', 'sll.slanglang_slang_id')
            ->where('usl.uslang_user_id', $this->user_id)
            ->where('sll.slanglang_lang_id', $langId)
            ->pluck('sll.slang_name')
            ->take(3)
            ->implode(', ');

        $biography = DB::table('tbl_users_lang')
            ->where('userlang_user_id', $this->user_id)
            ->where('userlang_lang_id', $langId)
            ->value('user_biography');

        $videoLink = DB::table('tbl_user_settings')
            ->where('user_id', $this->user_id)
            ->value('user_video_link');

        $courseCount = DB::table('tbl_courses')
            ->where('course_user_id', $this->user_id)
            ->where('course_active', 1)
            ->whereNull('course_deleted')
            ->where('course_status', Course::STATUS_PUBLISHED)
            ->count();

        return [
            'id' => $this->user_id,
            'username' => $this->user_username,
            'full_name' => $this->full_name,
            'first_name' => $this->user_first_name,
            'last_name' => $this->user_last_name,
            'biography' => $biography ?? '',
            'is_featured' => (bool) $this->user_featured,
            'video_link' => $videoLink ?? '',
            'ratings' => (float) ($this->testat_ratings ?? 0),
            'reviews' => (int) ($this->testat_reviewes ?? 0),
            'students' => (int) ($this->testat_students ?? 0),
            'lessons' => (int) ($this->testat_lessons ?? 0),
            'classes' => (int) ($this->testat_classes ?? 0),
            'courses' => $courseCount,
            'min_price' => (float) ($this->testat_minprice ?? 0),
            'max_price' => (float) ($this->testat_maxprice ?? 0),
            'teach_languages' => $teachLangs,
            'speak_languages' => $speakLangs ?: $teachLangs,
        ];
    }
}
