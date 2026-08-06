<?php

namespace App\Services\Admin\Listings;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminCourseEditRequestsListingService
{
    use AdminListingSupport;

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    public function search(Request $request): array
    {
        $query = DB::table('tbl_course_edit_requests as coedre')
            ->join('tbl_courses as course', 'course.course_id', '=', 'coedre.coedre_course_id')
            ->join('tbl_users as u', 'u.user_id', '=', 'course.course_user_id')
            ->leftJoin('tbl_course_details as crsdetail', 'crsdetail.course_id', '=', 'course.course_id')
            ->select([
                'coedre.coedre_id as id',
                DB::raw('IFNULL(crsdetail.course_title, course.course_slug) as title'),
                'coedre.coedre_status as status',
                'coedre.coedre_created as created_at',
                'coedre.coedre_updated as updated_at',
                'coedre.coedre_duration as duration_days',
                'course.course_deleted',
                DB::raw('CONCAT(u.user_first_name, " ", COALESCE(u.user_last_name, "")) as teacher_name'),
            ]);

        $this->applyKeyword($request, $query, ['crsdetail.course_title', 'course.course_slug']);

        $status = $request->query('coedre_status');
        if ($status !== null && $status !== '') {
            $query->where('coedre.coedre_status', '=', (int) $status);
        }

        $result = $this->runQuery($request, $query, 'coedre.coedre_id');
        $result['data'] = array_map(function (array $row) {
            $row['expired_at'] = $this->computeExpiredAt(
                (string) ($row['updated_at'] ?? ''),
                (int) ($row['duration_days'] ?? 0),
                (int) ($row['status'] ?? 0),
            );
            unset($row['updated_at'], $row['duration_days']);

            return $row;
        }, $result['data']);

        return $result;
    }

    private function computeExpiredAt(string $updatedAt, int $durationDays, int $status): string
    {
        if ($status !== 1 || $updatedAt === '' || $durationDays < 1) {
            return '';
        }

        return date('Y-m-d H:i:s', strtotime($updatedAt.' + '.$durationDays.' days'));
    }
}
