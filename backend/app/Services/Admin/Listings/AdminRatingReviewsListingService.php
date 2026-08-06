<?php

namespace App\Services\Admin\Listings;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminRatingReviewsListingService
{
    use AdminListingSupport;

    private const TYPE_COURSE = 3;

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    public function search(Request $request): array
    {
        $courseFilter = $this->isCourseFilter($request);

        $query = DB::table('tbl_rating_reviews as rr')
            ->join('tbl_users as learner', 'learner.user_id', '=', 'rr.ratrev_user_id')
            ->join('tbl_users as teacher', 'teacher.user_id', '=', 'rr.ratrev_teacher_id')
            ->leftJoin('tbl_courses as course', 'course.course_id', '=', 'rr.ratrev_type_id')
            ->leftJoin('tbl_course_details as cd', 'cd.course_id', '=', 'course.course_id')
            ->select([
                'rr.ratrev_id as id',
                DB::raw('CONCAT(learner.user_first_name, " ", COALESCE(learner.user_last_name, "")) as learner_name'),
                DB::raw('CASE
                    WHEN rr.ratrev_type = '.self::TYPE_COURSE.' THEN IFNULL(cd.course_title, course.course_slug)
                    WHEN course.course_id IS NOT NULL AND course.course_reviews > 0 THEN IFNULL(cd.course_title, course.course_slug)
                    ELSE CONCAT(teacher.user_first_name, " ", COALESCE(teacher.user_last_name, ""))
                END as teacher_name'),
                'rr.ratrev_title as title',
                'rr.ratrev_overall as rating',
                'rr.ratrev_status as status',
                'rr.ratrev_created as created_at',
                'rr.ratrev_type as review_type',
                'rr.ratrev_type_id as review_type_id',
            ]);

        if ($courseFilter) {
            $query->where(function ($q) {
                $q->where('rr.ratrev_type', '=', self::TYPE_COURSE)
                    ->orWhere(function ($inner) {
                        $inner->whereNotNull('course.course_id')
                            ->where('course.course_reviews', '>', 0);
                    });
            });
        } else {
            $query->where('rr.ratrev_type', '!=', self::TYPE_COURSE)
                ->where(function ($q) {
                    $q->whereNull('course.course_id')
                        ->orWhere('course.course_reviews', '<=', 0);
                });
        }

        $keyword = trim((string) $request->query('keyword', ''));
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where(DB::raw('CONCAT(learner.user_first_name, " ", COALESCE(learner.user_last_name, ""))'), 'like', "%{$keyword}%")
                    ->orWhere(DB::raw('CONCAT(teacher.user_first_name, " ", COALESCE(teacher.user_last_name, ""))'), 'like', "%{$keyword}%")
                    ->orWhere('cd.course_title', 'like', "%{$keyword}%")
                    ->orWhere('rr.ratrev_title', 'like', "%{$keyword}%");
            });
        }

        $status = $request->query('status', '');
        if ($status !== '' && $status !== null) {
            $query->where('rr.ratrev_status', (int) $status);
        }

        $dateFrom = trim((string) $request->query('date_from', ''));
        if ($dateFrom !== '') {
            $query->where('rr.ratrev_created', '>=', $dateFrom.' 00:00:00');
        }

        $dateTo = trim((string) $request->query('date_to', ''));
        if ($dateTo !== '') {
            $query->where('rr.ratrev_created', '<=', $dateTo.' 23:59:59');
        }

        $page = max(1, $request->integer('page', 1));
        $perPage = $this->adminPageSize($request);
        $total = (clone $query)->count();
        $statusLabels = [
            0 => 'Pending',
            1 => 'Approved',
            2 => 'Declined',
        ];

        $rows = $query
            ->orderBy('rr.ratrev_status')
            ->orderByDesc('rr.ratrev_id')
            ->forPage($page, $perPage)
            ->get()
            ->map(function ($row) use ($statusLabels) {
                $data = (array) $row;
                $data['status_label'] = $statusLabels[(int) $data['status']] ?? (string) $data['status'];

                return $data;
            })
            ->all();

        return $this->paginateResult($request, $rows, $total);
    }

    private function isCourseFilter(Request $request): bool
    {
        $reviewType = $request->query('ratrev_type', $request->query('type', ''));

        return $reviewType === 'course' || (int) $reviewType === self::TYPE_COURSE;
    }
}
