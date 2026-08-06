<?php

namespace App\Services\Admin;

use App\Models\CourseProgress;
use App\Models\OrderCourse;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AdminCourseRefundRequestService
{
    public const STATUS_PENDING = 0;

    public const STATUS_APPROVED = 1;

    public const STATUS_DECLINED = 2;

    private const TXN_TYPE_LEARNER_REFUND = 8;

    /** @return array<string, mixed>|null */
    public function show(int $requestId): ?array
    {
        $row = DB::table('tbl_course_refund_requests as corere')
            ->join('tbl_order_courses as ordcrs', 'ordcrs.ordcrs_id', '=', 'corere.corere_ordcrs_id')
            ->join('tbl_courses as course', 'course.course_id', '=', 'ordcrs.ordcrs_course_id')
            ->leftJoin('tbl_users as u', 'u.user_id', '=', 'corere.corere_user_id')
            ->leftJoin('tbl_course_details as crsdetail', 'crsdetail.course_id', '=', 'course.course_id')
            ->leftJoin('tbl_course_progresses as crspro', 'crspro.crspro_ordcrs_id', '=', 'ordcrs.ordcrs_id')
            ->leftJoin('tbl_orders as orders', 'orders.order_id', '=', 'ordcrs.ordcrs_order_id')
            ->where('corere.corere_id', $requestId)
            ->first([
                'corere.corere_id as id',
                'corere.corere_status as status',
                'corere.corere_remark as remark',
                'corere.corere_comment as comment',
                'corere.corere_created as created_at',
                'ordcrs.ordcrs_id as order_course_id',
                'ordcrs.ordcrs_amount as order_amount',
                'ordcrs.ordcrs_discount as order_discount',
                'orders.order_id as order_id',
                'orders.order_reward_value as order_reward_discount',
                'course.course_price as course_price',
                DB::raw('IFNULL(crsdetail.course_title, course.course_slug) as course_title'),
                'course.course_duration as course_duration',
                'course.course_status as course_status',
                'u.user_first_name as learner_first_name',
                'u.user_last_name as learner_last_name',
                'u.user_email as learner_email',
                'u.user_username as learner_username',
                DB::raw('IFNULL(crspro.crspro_progress, 0) as progress_percent'),
                'crspro.crspro_status as progress_status',
            ]);

        if (! $row) {
            return null;
        }

        $data = (array) $row;
        $data['learner_first_name'] = (string) ($data['learner_first_name'] ?? '');
        $data['learner_last_name'] = (string) ($data['learner_last_name'] ?? '');
        $data['learner_name'] = trim($data['learner_first_name'].' '.$data['learner_last_name']);
        $data['learner_email'] = (string) ($data['learner_email'] ?? '');
        $data['learner_username'] = (string) ($data['learner_username'] ?? '');
        $data['email_username'] = $data['learner_email'] !== ''
            ? $data['learner_email']
            : ($data['learner_username'] !== '' ? $data['learner_username'] : '-');
        $data['child'] = null;
        $data['status_label'] = $this->refundStatusLabel((int) $data['status']);
        $data['course_status_label'] = $this->courseStatusLabel((int) $data['course_status']);
        $data['completed_progress'] = (int) ($data['progress_status'] ?? 0) === CourseProgress::STATUS_COMPLETED
            ? 100
            : (int) round((float) $data['progress_percent']);
        $data['course_duration_label'] = $this->formatDuration((int) $data['course_duration']);
        $data['course_price'] = (float) ($data['course_price'] ?? 0);
        $data['order_discount'] = (float) ($data['order_discount'] ?? 0);
        $data['order_reward_discount'] = (float) ($data['order_reward_discount'] ?? 0);
        $data['order_amount'] = (float) ($data['order_amount'] ?? 0);

        return $data;
    }

    /** @return array<string, mixed>|null */
    public function statusForm(int $requestId): ?array
    {
        $row = DB::table('tbl_course_refund_requests as corere')
            ->join('tbl_order_courses as ordcrs', 'ordcrs.ordcrs_id', '=', 'corere.corere_ordcrs_id')
            ->leftJoin('tbl_course_progresses as crspro', 'crspro.crspro_ordcrs_id', '=', 'ordcrs.ordcrs_id')
            ->where('corere.corere_id', $requestId)
            ->first([
                'corere.corere_id as id',
                'corere.corere_status as status',
                DB::raw('IFNULL(crspro.crspro_progress, 0) as progress_percent'),
                'crspro.crspro_status as progress_status',
            ]);

        if (! $row) {
            return null;
        }

        $data = (array) $row;
        $data['completed_progress'] = (int) ($data['progress_status'] ?? 0) === CourseProgress::STATUS_COMPLETED
            ? 100
            : (int) round((float) $data['progress_percent']);

        return $data;
    }

    public function updateStatus(int $requestId, int $status, string $comment = ''): void
    {
        if (! in_array($status, [self::STATUS_APPROVED, self::STATUS_DECLINED], true)) {
            throw new RuntimeException('Invalid status', 422);
        }

        if ($status === self::STATUS_DECLINED && trim($comment) === '') {
            throw new RuntimeException('Comment is required when declining a refund request', 422);
        }

        DB::transaction(function () use ($requestId, $status, $comment) {
            $request = DB::table('tbl_course_refund_requests as corere')
                ->join('tbl_order_courses as ordcrs', 'ordcrs.ordcrs_id', '=', 'corere.corere_ordcrs_id')
                ->join('tbl_orders as orders', 'orders.order_id', '=', 'ordcrs.ordcrs_order_id')
                ->where('corere.corere_id', $requestId)
                ->where('corere.corere_status', self::STATUS_PENDING)
                ->lockForUpdate()
                ->first([
                    'corere.corere_id',
                    'corere.corere_user_id',
                    'ordcrs.ordcrs_id',
                    'ordcrs.ordcrs_amount',
                    'ordcrs.ordcrs_discount',
                    'orders.order_id',
                    'orders.order_reward_value',
                ]);

            if (! $request) {
                throw new RuntimeException('Invalid request', 404);
            }

            DB::table('tbl_course_refund_requests')
                ->where('corere_id', $requestId)
                ->update([
                    'corere_status' => $status,
                    'corere_comment' => $comment,
                    'corere_updated' => now(),
                ]);

            if ($status !== self::STATUS_APPROVED) {
                return;
            }

            DB::table('tbl_order_courses')
                ->where('ordcrs_id', $request->ordcrs_id)
                ->update([
                    'ordcrs_status' => OrderCourse::STATUS_CANCELLED,
                    'ordcrs_updated' => now(),
                ]);

            DB::table('tbl_course_progresses')
                ->where('crspro_ordcrs_id', $request->ordcrs_id)
                ->update(['crspro_status' => CourseProgress::STATUS_CANCELLED]);

            $refundAmount = (float) $request->ordcrs_amount
                - ((float) $request->ordcrs_discount + (float) ($request->order_reward_value ?? 0));

            DB::table('tbl_order_courses')
                ->where('ordcrs_id', $request->ordcrs_id)
                ->update([
                    'ordcrs_refund' => max(0, $refundAmount),
                    'ordcrs_teacher_paid' => 0,
                ]);

            if ($refundAmount > 0) {
                $txnComment = 'Course refunded #'.str_pad((string) $request->order_id, 6, '0', STR_PAD_LEFT);
                DB::table('tbl_user_transactions')->insert([
                    'usrtxn_type' => self::TXN_TYPE_LEARNER_REFUND,
                    'usrtxn_user_id' => $request->corere_user_id,
                    'usrtxn_amount' => $refundAmount,
                    'usrtxn_comment' => $txnComment,
                    'usrtxn_datetime' => now(),
                ]);

                DB::table('tbl_user_settings')
                    ->where('user_id', $request->corere_user_id)
                    ->update([
                        'user_wallet_balance' => DB::raw('user_wallet_balance + '.$refundAmount),
                    ]);
            }

            $courseId = DB::table('tbl_order_courses')
                ->where('ordcrs_id', $request->ordcrs_id)
                ->value('ordcrs_course_id');

            if ($courseId) {
                $studentCount = DB::table('tbl_order_courses as ordcrs')
                    ->join('tbl_orders as orders', 'orders.order_id', '=', 'ordcrs.ordcrs_order_id')
                    ->where('ordcrs.ordcrs_course_id', $courseId)
                    ->where('orders.order_payment_status', 1)
                    ->where('ordcrs.ordcrs_status', '!=', OrderCourse::STATUS_CANCELLED)
                    ->count();

                DB::table('tbl_courses')
                    ->where('course_id', $courseId)
                    ->update(['course_students' => $studentCount]);
            }
        });
    }

    public function refundStatusLabel(int $status): string
    {
        return match ($status) {
            self::STATUS_PENDING => 'Refund pending',
            self::STATUS_APPROVED => 'Refund approved',
            self::STATUS_DECLINED => 'Refund declined',
            default => 'Unknown',
        };
    }

    private function courseStatusLabel(int $status): string
    {
        return match ($status) {
            1 => 'Drafted',
            2 => 'Submitted for approval',
            3 => 'Published',
            default => 'Unknown',
        };
    }

    private function formatDuration(int $seconds): string
    {
        if ($seconds < 1) {
            return '';
        }

        $parts = [];
        $hours = intdiv($seconds, 3600);
        if ($hours > 0) {
            $parts[] = $hours.'h';
        }
        $minutes = intdiv($seconds % 3600, 60);
        if ($minutes > 0) {
            $parts[] = $minutes.'m';
        }

        return $parts !== [] ? implode(' ', $parts) : '0m';
    }
}
