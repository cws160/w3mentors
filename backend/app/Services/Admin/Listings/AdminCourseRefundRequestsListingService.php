<?php

namespace App\Services\Admin\Listings;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminCourseRefundRequestsListingService
{
    use AdminListingSupport;

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    public function search(Request $request): array
    {
        $langId = $this->langId($request);

        $query = DB::table('tbl_course_refund_requests as corere')
            ->join('tbl_order_courses as ordcrs', 'ordcrs.ordcrs_id', '=', 'corere.corere_ordcrs_id')
            ->join('tbl_courses as course', 'course.course_id', '=', 'ordcrs.ordcrs_course_id')
            ->join('tbl_users as u', 'u.user_id', '=', 'corere.corere_user_id')
            ->leftJoin('tbl_course_details as crsdetail', 'crsdetail.course_id', '=', 'course.course_id')
            ->select([
                'corere.corere_id as id',
                DB::raw('IFNULL(crsdetail.course_title, course.course_slug) as title'),
                'corere.corere_status as status',
                'corere.corere_created as created_at',
                DB::raw('CONCAT(u.user_first_name, " ", COALESCE(u.user_last_name, "")) as learner_name'),
            ]);

        $this->applyKeyword($request, $query, ['crsdetail.course_title', 'course.course_slug']);

        $status = $request->query('corere_status');
        if ($status !== null && $status !== '') {
            $query->where('corere.corere_status', '=', (int) $status);
        }

        return $this->runQuery($request, $query, 'corere.corere_id');
    }
}
