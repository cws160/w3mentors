<?php

namespace App\Http\Resources;

use App\Models\Course;
use App\Services\TeacherBookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeacherProfileResource extends TeacherListingResource
{
    public function toArray(Request $request): array
    {
        $base = parent::toArray($request);
        $langId = 1;

        $country = DB::table('tbl_countries_lang')
            ->where('countrylang_country_id', $this->user_country_id ?? 0)
            ->where('countrylang_lang_id', $langId)
            ->value('country_name');

        $qualifications = DB::table('tbl_user_qualifications')
            ->where('uqualification_user_id', $this->user_id)
            ->where('uqualification_active', 1)
            ->orderBy('uqualification_id')
            ->get([
                'uqualification_id as id',
                'uqualification_title as title',
                'uqualification_institute_name as institute_name',
                'uqualification_institute_address as institute_address',
                'uqualification_start_year as start_year',
                'uqualification_end_year as end_year',
                'uqualification_experience_type as type',
            ]);

        $moreCourses = DB::table('tbl_courses as c')
            ->join('tbl_course_details as cd', 'c.course_id', '=', 'cd.course_id')
            ->where('c.course_user_id', $this->user_id)
            ->where('c.course_active', 1)
            ->whereNull('c.course_deleted')
            ->where('c.course_status', Course::STATUS_PUBLISHED)
            ->orderByDesc('c.course_created')
            ->limit(6)
            ->get([
                'c.course_id as id',
                'c.course_slug as slug',
                'cd.course_title as title',
                'c.course_price as price',
                'c.course_ratings as ratings',
                'c.course_reviews as reviews',
            ]);

        $moreCourses = $moreCourses->map(fn ($course) => [
            'id' => (int) $course->id,
            'slug' => $course->slug,
            'title' => $course->title,
            'price' => (float) $course->price,
            'ratings' => (float) $course->ratings,
            'reviews' => (int) $course->reviews,
        ]);

        $settings = DB::table('tbl_user_settings')
            ->where('user_id', $this->user_id)
            ->first(['user_slots']);

        $userSlots = array_values(array_filter(array_map(
            'intval',
            json_decode($settings?->user_slots ?? '[]', true) ?: []
        )));

        $pricing = app(TeacherBookingService::class)->pricingTable($this->user_id, $langId);

        return array_merge($base, [
            'country' => $country ?? '',
            'video_link' => $base['video_link'] ?? '',
            'reviews_count' => (int) ($this->testat_reviewes ?? 0),
            'qualifications' => $qualifications,
            'more_courses' => $moreCourses->values(),
            'user_slots' => $userSlots,
            'pricing_languages' => $pricing,
        ]);
    }
}
