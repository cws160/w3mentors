<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseProgress;
use App\Models\OrderCourse;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CourseEnrollmentService
{
    public const ORDER_TYPE_COURSE = 5;

    public const ORDER_STATUS_INPROCESS = 1;

    public const ORDER_STATUS_COMPLETED = 2;

    public const ORDER_UNPAID = 0;

    public const ORDER_PAID = 1;

    public const ORDCOURSE_UNPAID = 0;

    public const ORDCOURSE_PAID = 1;

    public function __construct(private CourseService $courses)
    {
    }

    public function enroll(User $user, Course $course): array
    {
        if (!$course->course_active || $course->course_deleted || (int) $course->course_status !== Course::STATUS_PUBLISHED) {
            abort(404, 'Course not found');
        }

        if ((int) $course->course_user_id === (int) $user->user_id) {
            abort(422, 'You cannot enroll in your own course');
        }

        if ($this->courses->isEnrolled($user, $course->course_id)) {
            abort(422, 'You are already enrolled in this course');
        }

        if (!$course->isFree() && (float) $course->course_price > 0) {
            return [
                'enrolled' => false,
                'payment_required' => true,
                'message' => 'Paid course checkout is not available in the app yet. Use the legacy cart to complete purchase.',
            ];
        }

        $enrollment = DB::transaction(function () use ($user, $course) {
            return $this->createFreeEnrollment($user, $course);
        });

        return [
            'enrolled' => true,
            'payment_required' => false,
            'enrollment' => [
                'order_course_id' => $enrollment->ordcrs_id,
                'status' => (int) $enrollment->ordcrs_status,
                'is_enrolled' => true,
            ],
        ];
    }

    private function createFreeEnrollment(User $user, Course $course): OrderCourse
    {
        $commission = $this->teacherCommission((int) $course->course_user_id);
        $amount = (float) $course->course_price;
        $discount = 0;
        $commissionAmount = round($amount * ($commission / 100), 2);
        $now = now();

        $orderId = DB::table('tbl_orders')->insertGetId([
            'order_type' => self::ORDER_TYPE_COURSE,
            'order_user_id' => $user->user_id,
            'order_item_count' => 1,
            'order_pmethod_id' => 0,
            'order_discount_value' => 0,
            'order_credit_discount' => 0,
            'order_reward_value' => 0,
            'order_currency_code' => 'USD',
            'order_currency_value' => 1,
            'order_payment_status' => self::ORDER_PAID,
            'order_status' => self::ORDER_STATUS_COMPLETED,
            'order_total_amount' => $amount,
            'order_net_amount' => 0,
            'order_addedon' => $now,
        ]);

        $ordcrsId = DB::table('tbl_order_courses')->insertGetId([
            'ordcrs_order_id' => $orderId,
            'ordcrs_course_id' => $course->course_id,
            'ordcrs_course_type' => (int) $course->course_type,
            'ordcrs_commission' => $commission,
            'ordcrs_commission_amount' => $commissionAmount,
            'ordcrs_amount' => $amount,
            'ordcrs_discount' => $discount,
            'ordcrs_status' => OrderCourse::STATUS_IN_PROGRESS,
            'ordcrs_payment' => self::ORDCOURSE_PAID,
            'ordcrs_certificate_number' => '',
            'ordcrs_reviewed' => 0,
            'ordcrs_updated' => $now,
            'ordcrs_affiliate_commission' => 0,
        ]);

        CourseProgress::create([
            'crspro_ordcrs_id' => $ordcrsId,
            'crspro_lecture_id' => 0,
            'crspro_progress' => 0,
            'crspro_status' => CourseProgress::STATUS_PENDING,
        ]);

        DB::table('tbl_courses')
            ->where('course_id', $course->course_id)
            ->update([
                'course_students' => DB::raw('course_students + 1'),
            ]);

        return OrderCourse::query()->findOrFail($ordcrsId);
    }

    private function teacherCommission(int $teacherId): float
    {
        $value = DB::table('tbl_admin_commissions')
            ->where('comm_user_id', $teacherId)
            ->value('comm_courses');

        if ($value !== null) {
            return (float) $value;
        }

        return (float) DB::table('tbl_admin_commissions')
            ->where('comm_user_id', 0)
            ->value('comm_courses') ?? 0;
    }
}
