<?php

namespace App\Services\Admin\Listings;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminCourseRequestsListingService
{
    use AdminListingSupport;

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    public function search(Request $request): array
    {
        $query = DB::table('tbl_course_approval_requests as coapre')
            ->join('tbl_courses as course', 'course.course_id', '=', 'coapre.coapre_course_id')
            ->join('tbl_users as u', 'u.user_id', '=', 'course.course_user_id')
            ->select([
                'coapre.coapre_id as id',
                'coapre.coapre_title as title',
                'coapre.coapre_status as status',
                'coapre.coapre_created as created_at',
                'coapre.coapre_course_id as course_id',
                'course.course_deleted',
                DB::raw('CONCAT(u.user_first_name, " ", COALESCE(u.user_last_name, "")) as teacher_name'),
                'u.user_id as user_id',
            ]);

        $this->applyKeyword($request, $query, ['coapre.coapre_title', 'coapre.coapre_subtitle']);

        $teacherId = $request->integer('teacher_id', 0);
        if ($teacherId > 0) {
            $query->where('course.course_user_id', '=', $teacherId);
        } else {
            $teacher = trim((string) $request->query('teacher', ''));
            if ($teacher !== '') {
                $query->whereRaw('CONCAT(u.user_first_name, " ", COALESCE(u.user_last_name, "")) LIKE ?', ["%{$teacher}%"]);
            }
        }

        $status = $request->query('coapre_status');
        if ($status !== null && $status !== '') {
            $query->where('coapre.coapre_status', '=', (int) $status);
        }

        $dateFrom = trim((string) $request->query('start_date', ''));
        if ($dateFrom !== '') {
            $query->where('coapre.coapre_created', '>=', $dateFrom.' 00:00:00');
        }

        $dateTo = trim((string) $request->query('end_date', ''));
        if ($dateTo !== '') {
            $query->where('coapre.coapre_created', '<=', $dateTo.' 23:59:59');
        }

        return $this->runQuery($request, $query, 'coapre.coapre_id');
    }
}
