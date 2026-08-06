<?php

namespace App\Services;

use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CoursePublicService
{
    public const REVIEW_TYPE_COURSE = 3;

    public const REVIEW_STATUS_APPROVED = 1;

    public function getTags(Course $course): array
    {
        $raw = $course->details?->course_srchtags;
        if (!$raw) {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? array_values(array_filter($decoded)) : [];
    }

    public function getResourcesCount(int $courseId): int
    {
        return (int) DB::table('tbl_lectures_resources')
            ->where('lecsrc_course_id', $courseId)
            ->whereNull('lecsrc_deleted')
            ->whereIn('lecsrc_type', [2, 3])
            ->count();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getMoreCourses(Course $course, int $limit = 6): array
    {
        $teacherId = (int) $course->course_user_id;

        return Course::query()
            ->published()
            ->with('details')
            ->where('course_user_id', $teacherId)
            ->where('course_id', '!=', $course->course_id)
            ->orderByDesc('course_created')
            ->limit($limit)
            ->get()
            ->map(fn (Course $c) => [
                'id' => $c->course_id,
                'slug' => $c->course_slug,
                'title' => $c->details?->course_title,
                'price' => (float) $c->course_price,
                'ratings' => (float) $c->course_ratings,
                'reviews' => (int) $c->course_reviews,
                'duration' => (int) $c->course_duration,
                'lectures' => (int) $c->course_lectures,
                'is_free' => $c->isFree(),
            ])
            ->values()
            ->all();
    }

    public function getTeacherDetail(Course $course, int $langId = 1): ?array
    {
        $teacher = $course->teacher;
        if (!$teacher) {
            return null;
        }

        $stats = DB::table('tbl_teacher_stats')
            ->where('testat_user_id', $teacher->user_id)
            ->first();

        $biography = DB::table('tbl_users_lang')
            ->where('userlang_user_id', $teacher->user_id)
            ->where('userlang_lang_id', $langId)
            ->value('user_biography');

        $courseCount = DB::table('tbl_courses')
            ->where('course_user_id', $teacher->user_id)
            ->where('course_active', 1)
            ->whereNull('course_deleted')
            ->where('course_status', Course::STATUS_PUBLISHED)
            ->count();

        $profileComplete = $teacher->user_username !== ''
            && $teacher->user_username !== null;

        return [
            'id' => $teacher->user_id,
            'username' => $teacher->user_username,
            'full_name' => $teacher->full_name,
            'first_name' => $teacher->user_first_name,
            'last_name' => $teacher->user_last_name,
            'biography' => $biography ?? '',
            'ratings' => (float) ($stats->testat_ratings ?? 0),
            'reviews' => (int) ($stats->testat_reviewes ?? 0),
            'courses' => $courseCount,
            'profile_complete' => $profileComplete,
        ];
    }

    /**
     * @return list<array{rating: int, count: int, percent: float}>
     */
    public function getReviewStats(int $courseId): array
    {
        $rows = DB::table('tbl_rating_reviews')
            ->selectRaw('ratrev_overall as rating, COUNT(ratrev_id) as count')
            ->where('ratrev_status', self::REVIEW_STATUS_APPROVED)
            ->where('ratrev_type', self::REVIEW_TYPE_COURSE)
            ->where('ratrev_type_id', $courseId)
            ->groupBy('ratrev_overall')
            ->pluck('count', 'rating');

        $total = $rows->sum();
        $stats = [];

        for ($i = 5; $i >= 1; $i--) {
            $count = (int) ($rows[$i] ?? 0);
            $stats[] = [
                'rating' => $i,
                'count' => $count,
                'percent' => $total > 0 ? round(($count / $total) * 100, 2) : 0,
            ];
        }

        return $stats;
    }

    public function getReviews(int $courseId, string $sort = 'DESC', int $page = 1, int $perPage = 10): array
    {
        $sort = strtoupper($sort) === 'ASC' ? 'ASC' : 'DESC';

        $query = DB::table('tbl_rating_reviews as r')
            ->join('tbl_users as u', 'u.user_id', '=', 'r.ratrev_user_id')
            ->where('r.ratrev_status', self::REVIEW_STATUS_APPROVED)
            ->where('r.ratrev_type', self::REVIEW_TYPE_COURSE)
            ->where('r.ratrev_type_id', $courseId)
            ->orderBy('r.ratrev_id', $sort)
            ->select([
                'r.ratrev_id as id',
                'r.ratrev_user_id as user_id',
                'u.user_first_name as first_name',
                'u.user_last_name as last_name',
                'r.ratrev_title as title',
                'r.ratrev_detail as detail',
                'r.ratrev_overall as rating',
                'r.ratrev_created as created_at',
            ]);

        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $paginated->items(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ];
    }
}
