<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class AdminRatingReviewService
{
    public const STATUS_PENDING = 0;

    public const STATUS_APPROVED = 1;

    public const STATUS_DECLINED = 2;

    public const TYPE_COURSE = 3;

    /** @return array<string, mixed> */
    public function show(int $reviewId): array
    {
        $row = DB::table('tbl_rating_reviews as rr')
            ->join('tbl_users as learner', 'learner.user_id', '=', 'rr.ratrev_user_id')
            ->join('tbl_users as teacher', 'teacher.user_id', '=', 'rr.ratrev_teacher_id')
            ->leftJoin('tbl_courses as course', 'course.course_id', '=', 'rr.ratrev_type_id')
            ->leftJoin('tbl_course_details as cd', 'cd.course_id', '=', 'course.course_id')
            ->where('rr.ratrev_id', $reviewId)
            ->first([
                'rr.ratrev_id as id',
                'rr.ratrev_type as type',
                'rr.ratrev_type_id as type_id',
                'rr.ratrev_overall as rating',
                'rr.ratrev_title as title',
                'rr.ratrev_detail as detail',
                'rr.ratrev_status as status',
                'rr.ratrev_teacher_notify as teacher_notify',
                'rr.ratrev_teacher_id as teacher_id',
                'course.course_id as resolved_course_id',
                'course.course_slug as course_slug',
                'cd.course_title as course_title',
                'learner.user_first_name as learner_first_name',
                'learner.user_last_name as learner_last_name',
                'learner.user_deleted as learner_deleted',
                'teacher.user_first_name as teacher_first_name',
                'teacher.user_last_name as teacher_last_name',
                'teacher.user_deleted as teacher_deleted',
            ]);

        if (! $row) {
            throw new RuntimeException('Invalid request', 404);
        }

        $data = (array) $row;
        $data['learner_name'] = trim($data['learner_first_name'].' '.($data['learner_last_name'] ?? ''));
        $data['teacher_name'] = trim($data['teacher_first_name'].' '.($data['teacher_last_name'] ?? ''));
        $data['status_label'] = $this->statusLabel((int) $data['status']);
        $data['teacher_deleted'] = ! empty($data['teacher_deleted']);
        $data['course_name'] = null;

        $isCourseReview = (int) $data['type'] === self::TYPE_COURSE || ! empty($data['resolved_course_id']);
        if ($isCourseReview) {
            $data['course_name'] = trim((string) ($data['course_title'] ?? ''));
            if ($data['course_name'] === '') {
                $data['course_name'] = DB::table('tbl_course_details')
                    ->where('course_id', $data['type_id'])
                    ->value('course_title');
            }
            if (empty($data['course_name'])) {
                $data['course_name'] = $data['course_slug'] ?? null;
            }
            if ((int) $data['type'] !== self::TYPE_COURSE) {
                $data['type'] = self::TYPE_COURSE;
            }
        }

        unset($data['resolved_course_id'], $data['course_title'], $data['course_slug']);

        return $data;
    }

    public function updateStatus(int $reviewId, int $status): void
    {
        if (! in_array($status, [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_DECLINED], true)) {
            throw new RuntimeException('Invalid request', 422);
        }

        $review = DB::table('tbl_rating_reviews')
            ->where('ratrev_id', $reviewId)
            ->first(['ratrev_id', 'ratrev_teacher_id', 'ratrev_type', 'ratrev_type_id']);

        if (! $review) {
            throw new RuntimeException('Invalid request', 404);
        }

        DB::table('tbl_rating_reviews')
            ->where('ratrev_id', $reviewId)
            ->update(['ratrev_status' => $status]);

        $this->refreshTeacherStats((int) $review->ratrev_teacher_id);

        if ((int) $review->ratrev_type === self::TYPE_COURSE) {
            $this->refreshCourseStats((int) $review->ratrev_type_id);
        }
    }

    public function statusLabel(int $status): string
    {
        return match ($status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_DECLINED => 'Declined',
            default => (string) $status,
        };
    }

    private function refreshTeacherStats(int $teacherId): void
    {
        $stats = DB::table('tbl_rating_reviews')
            ->where('ratrev_teacher_id', $teacherId)
            ->where('ratrev_status', self::STATUS_APPROVED)
            ->selectRaw('COUNT(*) as reviews, ROUND(AVG(ratrev_overall), 2) as ratings')
            ->first();

        DB::table('tbl_teacher_stats')->updateOrInsert(
            ['testat_user_id' => $teacherId],
            [
                'testat_ratings' => (float) ($stats->ratings ?? 0),
                'testat_reviewes' => (int) ($stats->reviews ?? 0),
            ],
        );
    }

    private function refreshCourseStats(int $courseId): void
    {
        $stats = DB::table('tbl_rating_reviews')
            ->where('ratrev_type', self::TYPE_COURSE)
            ->where('ratrev_type_id', $courseId)
            ->where('ratrev_status', self::STATUS_APPROVED)
            ->selectRaw('COUNT(*) as reviews, ROUND(AVG(ratrev_overall), 2) as ratings')
            ->first();

        DB::table('tbl_courses')
            ->where('course_id', $courseId)
            ->update([
                'course_ratings' => (float) ($stats->ratings ?? 0),
                'course_reviews' => (int) ($stats->reviews ?? 0),
            ]);
    }
}
