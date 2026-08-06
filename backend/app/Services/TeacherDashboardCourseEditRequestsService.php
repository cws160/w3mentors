<?php

namespace App\Services;

use App\Models\Course;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TeacherDashboardCourseEditRequestsService
{
    public const STATUS_PENDING = 0;

    public const STATUS_APPROVED = 1;

    public const STATUS_DECLINED = 2;

    public function filters(): array
    {
        return [
            'statuses' => [
                ['id' => self::STATUS_PENDING, 'label' => 'Edit request pending'],
                ['id' => self::STATUS_APPROVED, 'label' => 'Edit request approved'],
                ['id' => self::STATUS_DECLINED, 'label' => 'Edit request declined'],
            ],
        ];
    }

    public function search(int $teacherId, int $langId, array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = DB::table('tbl_course_edit_requests as req')
            ->join('tbl_courses as course', 'course.course_id', '=', 'req.coedre_course_id')
            ->join('tbl_course_details as crsdetail', 'crsdetail.course_id', '=', 'course.course_id')
            ->where('course.course_user_id', $teacherId)
            ->select([
                'req.coedre_id as id',
                'req.coedre_status as status',
                'req.coedre_created as created_at',
                'req.coedre_updated as updated_at',
                'req.coedre_duration as duration_days',
                'crsdetail.course_title as course_title',
            ])
            ->orderByDesc('req.coedre_id');

        if (! empty($filters['keyword'])) {
            $query->where('crsdetail.course_title', 'like', '%'.trim($filters['keyword']).'%');
        }
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('req.coedre_status', (int) $filters['status']);
        }

        return $query->paginate($perPage);
    }

    public function formatRow(object $row): array
    {
        $status = (int) $row->status;
        $expiredAt = null;
        if ($status === self::STATUS_APPROVED && $row->updated_at && $row->duration_days) {
            $expiredAt = Carbon::parse($row->updated_at)
                ->addDays((int) $row->duration_days)
                ->format('Y-m-d H:i:s');
        }

        return [
            'id' => (int) $row->id,
            'course_title' => $row->course_title,
            'status' => $status,
            'status_label' => $this->statusLabel($status),
            'created_at' => $row->created_at,
            'expired_at' => $expiredAt,
        ];
    }

    private function statusLabel(int $status): string
    {
        return match ($status) {
            self::STATUS_APPROVED => 'Edit request approved',
            self::STATUS_DECLINED => 'Edit request declined',
            default => 'Edit request pending',
        };
    }
}
