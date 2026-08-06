<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class AdminOrderManageService
{
  /** @return array<string, mixed>|null */
    public function showOrder(int $orderId, int $langId = 1): ?array
    {
        $order = DB::table('tbl_orders as orders')
            ->leftJoin('tbl_users as learner', 'learner.user_id', '=', 'orders.order_user_id')
            ->where('orders.order_id', $orderId)
            ->whereNull('learner.user_deleted')
            ->select([
                'orders.*',
                'learner.user_email as learner_email',
                'learner.user_timezone as user_timezone',
                DB::raw('TRIM(CONCAT(COALESCE(learner.user_first_name, ""), " ", COALESCE(learner.user_last_name, ""))) as learner_full_name'),
            ])
            ->first();

        if (! $order) {
            return null;
        }

        $order = (array) $order;
        $orderId = (int) $order['order_id'];
        $orderType = (int) $order['order_type'];

        $payments = DB::table('tbl_order_payments')
            ->where('ordpay_order_id', $orderId)
            ->orderBy('ordpay_id')
            ->get()
            ->map(fn ($p) => (array) $p)
            ->all();

        $totalPaid = array_sum(array_column($payments, 'ordpay_amount'));
        $netAmount = (float) $order['order_net_amount'];
        $pendingAmount = $totalPaid < $netAmount ? round($netAmount - $totalPaid, 2) : 0;

        $paymentMethods = DB::table('tbl_payment_methods')
            ->where('pmethod_active', 1)
            ->pluck('pmethod_code', 'pmethod_id')
            ->all();

        $childOrder = $this->getChildOrderDetails($orderType, $orderId, $langId);

        $bankTransfers = [];
        if (DB::getSchemaBuilder()->hasTable('tbl_bank_transfers')) {
            $bankTransfers = DB::table('tbl_bank_transfers')
                ->where('bnktras_order_id', $orderId)
                ->orderByDesc('bnktras_id')
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all();
        }

        return [
            'order' => [
                'order_id' => $orderId,
                'order_id_formatted' => AdminOrderHelper::formatOrderId($orderId),
                'order_type' => $orderType,
                'order_type_label' => AdminOrderHelper::orderTypeLabel($orderType),
                'order_status' => (int) $order['order_status'],
                'order_status_label' => AdminOrderHelper::orderStatusLabel((int) $order['order_status']),
                'order_payment_status' => (int) $order['order_payment_status'],
                'order_payment_status_label' => AdminOrderHelper::paymentStatusLabel((int) $order['order_payment_status']),
                'order_total_amount' => (float) $order['order_total_amount'],
                'order_discount_value' => (float) $order['order_discount_value'],
                'order_reward_value' => (float) $order['order_reward_value'],
                'order_net_amount' => $netAmount,
                'order_pmethod_id' => (int) ($order['order_pmethod_id'] ?? 0),
                'order_user_id' => (int) $order['order_user_id'],
                'order_addedon' => (string) $order['order_addedon'],
                'order_currency_code' => (string) ($order['order_currency_code'] ?? ''),
                'learner_full_name' => (string) $order['learner_full_name'],
                'learner_email' => (string) ($order['learner_email'] ?? ''),
                'user_timezone' => (string) ($order['user_timezone'] ?? ''),
            ],
            'payments' => $payments,
            'total_paid_amount' => round($totalPaid, 2),
            'pending_amount' => $pendingAmount,
            'payment_methods' => $paymentMethods,
            'child_order' => $childOrder,
            'bank_transfers' => $bankTransfers,
            'can_cancel' => (int) $order['order_payment_status'] === AdminOrderHelper::UNPAID
                && (int) $order['order_status'] !== AdminOrderHelper::STATUS_CANCELLED,
            'can_add_payment' => (int) $order['order_payment_status'] === AdminOrderHelper::UNPAID
                && (int) $order['order_status'] === AdminOrderHelper::STATUS_INPROCESS
                && $pendingAmount > 0,
        ];
    }

    public function cancelOrder(int $orderId): void
    {
        $order = DB::table('tbl_orders as orders')
            ->leftJoin('tbl_coupon_history as couhis', 'couhis.couhis_order_id', '=', 'orders.order_id')
            ->join('tbl_users as user', 'user.user_id', '=', 'orders.order_user_id')
            ->where('orders.order_id', $orderId)
            ->where('orders.order_payment_status', AdminOrderHelper::UNPAID)
            ->where('orders.order_status', '!=', AdminOrderHelper::STATUS_CANCELLED)
            ->select([
                'orders.order_id',
                'orders.order_type',
                'orders.order_user_id',
                'orders.order_reward_value',
                'couhis.couhis_id',
                'couhis.couhis_coupon_id',
            ])
            ->first();

        if (! $order) {
            throw new RuntimeException('Invalid request', 422);
        }

        $order = (array) $order;
        $orderType = (int) $order['order_type'];

        DB::transaction(function () use ($order, $orderId, $orderType) {
            DB::table('tbl_orders')
                ->where('order_id', $orderId)
                ->update(['order_status' => AdminOrderHelper::STATUS_CANCELLED]);

            if (! empty($order['couhis_id'])) {
                DB::table('tbl_coupons')
                    ->where('coupon_id', $order['couhis_coupon_id'])
                    ->decrement('coupon_used');
                DB::table('tbl_coupon_history')
                    ->where('couhis_id', $order['couhis_id'])
                    ->delete();
            }

            match ($orderType) {
                AdminOrderHelper::TYPE_LESSON => DB::table('tbl_order_lessons')
                    ->where('ordles_order_id', $orderId)
                    ->update(['ordles_status' => AdminOrderHelper::LESSON_CANCELLED]),
                AdminOrderHelper::TYPE_SUBSCR => $this->cancelSubscriptionOrder($orderId),
                AdminOrderHelper::TYPE_GCLASS => DB::table('tbl_order_classes')
                    ->where('ordcls_order_id', $orderId)
                    ->update(['ordcls_status' => 3]),
                AdminOrderHelper::TYPE_PACKGE => $this->cancelPackageOrder($orderId),
                AdminOrderHelper::TYPE_GFTCRD => DB::table('tbl_order_giftcards')
                    ->where('ordgift_order_id', $orderId)
                    ->update(['ordgift_status' => 2]),
                AdminOrderHelper::TYPE_COURSE => $this->cancelCourseOrder($orderId),
                default => null,
            };
        });
    }

    /** @param  array<string, mixed>  $data */
    public function addPayment(int $orderId, array $data): void
    {
        $order = DB::table('tbl_orders')
            ->where('order_id', $orderId)
            ->where('order_payment_status', AdminOrderHelper::UNPAID)
            ->where('order_status', AdminOrderHelper::STATUS_INPROCESS)
            ->where('order_net_amount', '>', 0)
            ->first();

        if (! $order) {
            throw new RuntimeException('Invalid request', 422);
        }

        $order = (array) $order;
        $netAmount = (float) $order['order_net_amount'];
        $paid = (float) DB::table('tbl_order_payments')
            ->where('ordpay_order_id', $orderId)
            ->sum('ordpay_amount');
        $pending = round($netAmount - $paid, 2);

        if ($pending <= 0) {
            throw new RuntimeException('Invalid request', 422);
        }

        $amount = round((float) ($data['ordpay_amount'] ?? 0), 2);
        if ($amount !== $pending) {
            throw new RuntimeException('Amount must be '.$pending, 422);
        }

        $pmethodId = (int) ($data['ordpay_pmethod_id'] ?? $order['order_pmethod_id']);
        $txnId = trim((string) ($data['ordpay_txn_id'] ?? ''));
        $response = trim((string) ($data['ordpay_response'] ?? ''));

        if ($txnId === '' || $response === '' || $pmethodId <= 0) {
            throw new RuntimeException('Payment details are required', 422);
        }

        DB::transaction(function () use ($orderId, $order, $amount, $pmethodId, $txnId, $data, $response) {
            DB::table('tbl_order_payments')->insert([
                'ordpay_txn_id' => $txnId,
                'ordpay_amount' => $amount,
                'ordpay_pmethod_id' => $pmethodId,
                'ordpay_order_id' => $orderId,
                'ordpay_response' => json_encode(array_merge($data, ['ordpay_response' => $response])),
                'ordpay_datetime' => now()->format('Y-m-d H:i:s'),
            ]);

            DB::table('tbl_orders')
                ->where('order_id', $orderId)
                ->update([
                    'order_payment_status' => AdminOrderHelper::ISPAID,
                    'order_status' => AdminOrderHelper::STATUS_COMPLETED,
                    'order_pmethod_id' => $pmethodId,
                ]);

            $this->completeSubOrderOnPayment((int) $order['order_type'], $orderId, (int) $order['order_user_id'], $amount);
        });
    }

    public function updateBankTransferStatus(int $payId, int $status): void
    {
        if (! in_array($status, [AdminOrderHelper::BANK_APPROVED, AdminOrderHelper::BANK_DECLINED], true)) {
            throw new RuntimeException('Invalid request', 422);
        }

        $payment = DB::table('tbl_bank_transfers')->where('bnktras_id', $payId)->first();
        if (! $payment) {
            throw new RuntimeException('Invalid request', 422);
        }

        $payment = (array) $payment;

        if ($status === AdminOrderHelper::BANK_APPROVED) {
            if ((int) $payment['bnktras_status'] === AdminOrderHelper::BANK_DECLINED) {
                throw new RuntimeException('Already declined', 422);
            }
            $this->addPayment((int) $payment['bnktras_order_id'], [
                'ordpay_txn_id' => (string) $payment['bnktras_txn_id'],
                'ordpay_amount' => (float) $payment['bnktras_amount'],
                'ordpay_pmethod_id' => (int) ($payment['bnktras_pmethod_id'] ?? 0),
                'ordpay_response' => (string) ($payment['bnktras_comments'] ?? 'Bank transfer approved'),
            ]);
        } elseif ((int) $payment['bnktras_status'] === AdminOrderHelper::BANK_APPROVED) {
            throw new RuntimeException('Already approved', 422);
        }

        DB::table('tbl_bank_transfers')
            ->where('bnktras_id', $payId)
            ->update(['bnktras_status' => $status]);
    }

    /** @return array<string, mixed>|null */
    public function showLesson(int $lessonId, int $langId = 1): ?array
    {
        $row = DB::table('tbl_order_lessons as ordles')
            ->join('tbl_orders as orders', 'orders.order_id', '=', 'ordles.ordles_order_id')
            ->join('tbl_users as learner', 'learner.user_id', '=', 'orders.order_user_id')
            ->join('tbl_users as teacher', 'teacher.user_id', '=', 'ordles.ordles_teacher_id')
            ->leftJoin('tbl_teach_languages as tlang', 'tlang.tlang_id', '=', 'ordles.ordles_tlang_id')
            ->leftJoin('tbl_teach_languages_lang as tlanglang', function ($join) use ($langId) {
                $join->on('tlanglang.tlanglang_tlang_id', '=', 'tlang.tlang_id')
                    ->where('tlanglang.tlanglang_lang_id', '=', $langId);
            })
            ->where('ordles.ordles_id', $lessonId)
            ->select([
                'ordles.*',
                'orders.order_id',
                'orders.order_payment_status',
                DB::raw('TRIM(CONCAT(COALESCE(learner.user_first_name, ""), " ", COALESCE(learner.user_last_name, ""))) as learner_name'),
                DB::raw('TRIM(CONCAT(COALESCE(teacher.user_first_name, ""), " ", COALESCE(teacher.user_last_name, ""))) as teacher_name'),
                DB::raw('IFNULL(tlanglang.tlang_name, IFNULL(tlang.tlang_identifier, "")) as ordles_tlang_name'),
            ])
            ->first();

        if (! $row) {
            return null;
        }

        $row = (array) $row;
        $learnerName = (string) $row['learner_name'];
        $teacherName = (string) $row['teacher_name'];
        $orderId = (int) $row['order_id'];
        $net = AdminOrderHelper::lessonNetAmount(
            (float) $row['ordles_amount'],
            (float) $row['ordles_discount'],
            (float) $row['ordles_reward_discount'],
        );
        $repissId = (int) DB::table('tbl_reported_issues')
            ->where('repiss_record_type', 1)
            ->where('repiss_record_id', $lessonId)
            ->value('repiss_id');

        return [
            'type' => 'lesson',
            'order_id' => $orderId,
            'order_id_formatted' => AdminOrderHelper::formatOrderId($orderId),
            'learner_name' => $learnerName,
            'teacher_name' => $teacherName,
            'ordles_tlang_name' => (string) $row['ordles_tlang_name'],
            'ordles_offline' => (int) $row['ordles_offline'],
            'service_type_label' => AdminOrderHelper::serviceTypeLabel((int) $row['ordles_offline']),
            'ordles_status' => (int) $row['ordles_status'],
            'ordles_status_label' => AdminOrderHelper::lessonStatusLabel((int) $row['ordles_status']),
            'ordles_lesson_starttime' => (string) ($row['ordles_lesson_starttime'] ?? ''),
            'ordles_lesson_endtime' => (string) ($row['ordles_lesson_endtime'] ?? ''),
            'ordles_teacher_starttime' => (string) ($row['ordles_teacher_starttime'] ?? ''),
            'ordles_teacher_endtime' => (string) ($row['ordles_teacher_endtime'] ?? ''),
            'ordles_student_starttime' => (string) ($row['ordles_student_starttime'] ?? ''),
            'ordles_student_endtime' => (string) ($row['ordles_student_endtime'] ?? ''),
            'ordles_amount' => (float) $row['ordles_amount'],
            'ordles_discount' => (float) $row['ordles_discount'],
            'ordles_reward_discount' => (float) $row['ordles_reward_discount'],
            'ordles_net_amount' => $net,
            'ordles_commission_amount' => (float) ($row['ordles_commission_amount'] ?? 0),
            'ordles_affiliate_commission' => (float) ($row['ordles_affiliate_commission'] ?? 0),
            'ordles_duration' => (int) ($row['ordles_duration'] ?? 0),
            'ordles_teacher_paid' => (float) ($row['ordles_teacher_paid'] ?? 0),
            'ordles_reviewed' => (int) ($row['ordles_reviewed'] ?? 0),
            'ordles_refund' => (float) ($row['ordles_refund'] ?? 0),
            'ordles_ended_by' => (int) ($row['ordles_ended_by'] ?? 0),
            'ordles_ended_by_label' => $this->endedByLabel((int) ($row['ordles_ended_by'] ?? 0), $learnerName, $teacherName),
            'issue_reported' => $repissId > 0,
            'repiss_id' => $repissId,
        ];
    }

    /** @return array<string, mixed>|null */
    public function showClass(int $classId, int $langId = 1): ?array
    {
        $row = DB::table('tbl_order_classes as ordcls')
            ->join('tbl_orders as orders', 'orders.order_id', '=', 'ordcls.ordcls_order_id')
            ->join('tbl_users as learner', 'learner.user_id', '=', 'orders.order_user_id')
            ->join('tbl_group_classes as grpcls', 'grpcls.grpcls_id', '=', 'ordcls.ordcls_grpcls_id')
            ->join('tbl_users as teacher', 'teacher.user_id', '=', 'grpcls.grpcls_teacher_id')
            ->leftJoin('tbl_teach_languages as tlang', 'tlang.tlang_id', '=', 'grpcls.grpcls_tlang_id')
            ->leftJoin('tbl_teach_languages_lang as tlanglang', function ($join) use ($langId) {
                $join->on('tlanglang.tlanglang_tlang_id', '=', 'tlang.tlang_id')
                    ->where('tlanglang.tlanglang_lang_id', '=', $langId);
            })
            ->where('ordcls.ordcls_id', $classId)
            ->select([
                'ordcls.*',
                'orders.order_id',
                'orders.order_payment_status',
                'grpcls.grpcls_title',
                'grpcls.grpcls_start_datetime',
                'grpcls.grpcls_end_datetime',
                'grpcls.grpcls_offline',
                DB::raw('TRIM(CONCAT(COALESCE(learner.user_first_name, ""), " ", COALESCE(learner.user_last_name, ""))) as learner_name'),
                DB::raw('TRIM(CONCAT(COALESCE(teacher.user_first_name, ""), " ", COALESCE(teacher.user_last_name, ""))) as teacher_name'),
                DB::raw('IFNULL(tlanglang.tlang_name, IFNULL(tlang.tlang_identifier, "")) as grpcls_language_name'),
            ])
            ->first();

        if (! $row) {
            return null;
        }

        $row = (array) $row;
        $learnerName = (string) $row['learner_name'];
        $teacherName = (string) $row['teacher_name'];
        $classId = (int) $row['ordcls_id'];
        $orderId = (int) $row['order_id'];
        $repissId = (int) DB::table('tbl_reported_issues')
            ->where('repiss_record_type', 2)
            ->where('repiss_record_id', $classId)
            ->value('repiss_id');
        $net = (float) ($row['ordcls_amount'] ?? 0)
            - (float) ($row['ordcls_discount'] ?? 0)
            - (float) ($row['ordcls_reward_discount'] ?? 0);

        return [
            'type' => 'class',
            'order_id' => $orderId,
            'order_id_formatted' => AdminOrderHelper::formatOrderId($orderId),
            'learner_name' => $learnerName,
            'teacher_name' => $teacherName,
            'grpcls_title' => (string) ($row['grpcls_title'] ?? ''),
            'grpcls_language_name' => (string) $row['grpcls_language_name'],
            'service_type_label' => AdminOrderHelper::serviceTypeLabel((int) $row['grpcls_offline']),
            'ordcls_status' => (int) $row['ordcls_status'],
            'ordcls_status_label' => match ((int) $row['ordcls_status']) {
                1 => 'Scheduled', 2 => 'Completed', 3 => 'Cancelled', default => '—',
            },
            'order_payment_status' => (int) ($row['order_payment_status'] ?? 0),
            'order_payment_status_label' => AdminOrderHelper::paymentStatusLabel((int) ($row['order_payment_status'] ?? 0)),
            'grpcls_start_datetime' => (string) ($row['grpcls_start_datetime'] ?? ''),
            'grpcls_end_datetime' => (string) ($row['grpcls_end_datetime'] ?? ''),
            'ordcls_teacher_starttime' => (string) ($row['ordcls_teacher_starttime'] ?? ''),
            'ordcls_teacher_endtime' => (string) ($row['ordcls_teacher_endtime'] ?? ''),
            'ordcls_format_starttime' => (string) ($row['ordcls_format_starttime'] ?? ''),
            'ordcls_format_endtime' => (string) ($row['ordcls_format_endtime'] ?? ''),
            'ordcls_amount' => (float) ($row['ordcls_amount'] ?? 0),
            'ordcls_discount' => (float) ($row['ordcls_discount'] ?? 0),
            'ordcls_reward_discount' => (float) ($row['ordcls_reward_discount'] ?? 0),
            'ordcls_net_amount' => $net,
            'ordcls_commission_amount' => (float) ($row['ordcls_commission_amount'] ?? 0),
            'ordcls_affiliate_commission' => (float) ($row['ordcls_affiliate_commission'] ?? 0),
            'ordcls_teacher_paid' => (float) ($row['ordcls_teacher_paid'] ?? 0),
            'ordcls_reviewed' => (int) ($row['ordcls_reviewed'] ?? 0),
            'ordcls_refund' => (float) ($row['ordcls_refund'] ?? 0),
            'ordcls_ended_by' => (int) ($row['ordcls_ended_by'] ?? 0),
            'ordcls_ended_by_label' => $this->endedByLabel((int) ($row['ordcls_ended_by'] ?? 0), $learnerName, $teacherName),
            'issue_reported' => $repissId > 0,
            'repiss_id' => $repissId,
        ];
    }

    /** @return array<string, mixed>|null */
    public function showPackage(int $packageId, int $langId = 1): ?array
    {
        $row = DB::table('tbl_order_packages as ordpkg')
            ->join('tbl_orders as orders', 'orders.order_id', '=', 'ordpkg.ordpkg_order_id')
            ->join('tbl_users as learner', 'learner.user_id', '=', 'orders.order_user_id')
            ->join('tbl_group_classes as grpcls', 'grpcls.grpcls_id', '=', 'ordpkg.ordpkg_package_id')
            ->leftJoin('tbl_users as teacher', 'teacher.user_id', '=', 'grpcls.grpcls_teacher_id')
            ->leftJoin('tbl_teach_languages as tlang', 'tlang.tlang_id', '=', 'grpcls.grpcls_tlang_id')
            ->leftJoin('tbl_teach_languages_lang as tlanglang', function ($join) use ($langId) {
                $join->on('tlanglang.tlanglang_tlang_id', '=', 'tlang.tlang_id')
                    ->where('tlanglang.tlanglang_lang_id', '=', $langId);
            })
            ->leftJoin('tbl_group_classes_lang as gclang', function ($join) use ($langId) {
                $join->on('gclang.gclang_grpcls_id', '=', 'grpcls.grpcls_id')
                    ->where('gclang.gclang_lang_id', '=', $langId);
            })
            ->where('ordpkg.ordpkg_id', $packageId)
            ->select([
                'ordpkg.*',
                'orders.order_id',
                'orders.order_payment_status',
                DB::raw('IFNULL(gclang.grpcls_title, grpcls.grpcls_title) as grpcls_title'),
                'grpcls.grpcls_start_datetime',
                'grpcls.grpcls_end_datetime',
                'grpcls.grpcls_offline',
                DB::raw('TRIM(CONCAT(COALESCE(learner.user_first_name, ""), " ", COALESCE(learner.user_last_name, ""))) as learner_name'),
                DB::raw('TRIM(CONCAT(COALESCE(teacher.user_first_name, ""), " ", COALESCE(teacher.user_last_name, ""))) as teacher_name'),
                DB::raw('IFNULL(tlanglang.tlang_name, IFNULL(tlang.tlang_identifier, "")) as grpcls_language_name'),
            ])
            ->first();

        if (! $row) {
            return null;
        }

        $row = (array) $row;

        return [
            'type' => 'package',
            'order_id' => (int) $row['order_id'],
            'order_id_formatted' => AdminOrderHelper::formatOrderId((int) $row['order_id']),
            'learner_name' => (string) $row['learner_name'],
            'teacher_name' => (string) $row['teacher_name'],
            'grpcls_title' => (string) ($row['grpcls_title'] ?? ''),
            'grpcls_language_name' => (string) $row['grpcls_language_name'],
            'service_type_label' => AdminOrderHelper::serviceTypeLabel((int) ($row['grpcls_offline'] ?? $row['ordpkg_offline'] ?? 0)),
            'ordpkg_status' => (int) $row['ordpkg_status'],
            'ordpkg_status_label' => match ((int) $row['ordpkg_status']) {
                1 => 'Active', 2 => 'Completed', 3 => 'Cancelled', default => '—',
            },
            'order_payment_status_label' => AdminOrderHelper::paymentStatusLabel((int) ($row['order_payment_status'] ?? 0)),
            'grpcls_start_datetime' => (string) ($row['grpcls_start_datetime'] ?? ''),
            'grpcls_end_datetime' => (string) ($row['grpcls_end_datetime'] ?? ''),
            'ordpkg_lessons' => (int) ($row['ordpkg_lessons'] ?? 0),
            'ordpkg_duration' => (int) ($row['ordpkg_duration'] ?? 0),
            'ordpkg_amount' => (float) ($row['ordpkg_amount'] ?? 0),
            'ordpkg_discount' => (float) ($row['ordpkg_discount'] ?? 0),
            'ordpkg_reward_discount' => (float) ($row['ordpkg_reward_discount'] ?? 0),
            'order_net_amount' => (float) ($row['ordpkg_amount'] ?? 0)
                - (float) ($row['ordpkg_discount'] ?? 0)
                - (float) ($row['ordpkg_reward_discount'] ?? 0),
        ];
    }

    /** @return array<string, mixed>|null */
    public function showCourse(int $courseOrderId, int $langId = 1): ?array
    {
        $row = DB::table('tbl_order_courses as ordcrs')
            ->join('tbl_orders as orders', 'orders.order_id', '=', 'ordcrs.ordcrs_order_id')
            ->join('tbl_users as learner', 'learner.user_id', '=', 'orders.order_user_id')
            ->join('tbl_courses as course', 'course.course_id', '=', 'ordcrs.ordcrs_course_id')
            ->join('tbl_users as teacher', 'teacher.user_id', '=', 'course.course_user_id')
            ->leftJoin('tbl_course_details as crsdetail', 'crsdetail.course_id', '=', 'course.course_id')
            ->leftJoin('tbl_course_languages as clang', 'clang.clang_id', '=', 'course.course_clang_id')
            ->leftJoin('tbl_course_languages_lang as clanglang', function ($join) use ($langId) {
                $join->on('clanglang.clanglang_clang_id', '=', 'clang.clang_id')
                    ->where('clanglang.clanglang_lang_id', '=', $langId);
            })
            ->leftJoin('tbl_course_progresses as crspro', 'crspro.crspro_ordcrs_id', '=', 'ordcrs.ordcrs_id')
            ->where('ordcrs.ordcrs_id', $courseOrderId)
            ->select([
                'ordcrs.*',
                'orders.order_id',
                'orders.order_payment_status',
                'orders.order_reward_value',
                'orders.order_addedon',
                DB::raw('IFNULL(crsdetail.course_title, course.course_slug) as course_title'),
                DB::raw('IFNULL(clanglang.clang_name, IFNULL(clang.clang_identifier, "")) as clang_name'),
                'course.course_duration',
                'teacher.user_email as teacher_email',
                DB::raw('TRIM(CONCAT(COALESCE(learner.user_first_name, ""), " ", COALESCE(learner.user_last_name, ""))) as learner_name'),
                DB::raw('TRIM(CONCAT(COALESCE(teacher.user_first_name, ""), " ", COALESCE(teacher.user_last_name, ""))) as teacher_name'),
                DB::raw('IFNULL(crspro.crspro_progress, 0) as crspro_progress'),
            ])
            ->first();

        if (! $row) {
            return null;
        }

        $row = (array) $row;
        $courseOrderId = (int) $row['ordcrs_id'];
        $orderId = (int) $row['order_id'];
        $repissId = (int) DB::table('tbl_reported_issues')
            ->where('repiss_record_type', 3)
            ->where('repiss_record_id', $courseOrderId)
            ->value('repiss_id');
        $net = (float) ($row['ordcrs_amount'] ?? 0)
            - (float) ($row['ordcrs_discount'] ?? 0)
            - (float) ($row['order_reward_value'] ?? 0);
        $duration = (int) ($row['course_duration'] ?? 0);

        return [
            'type' => 'course',
            'ordcrs_id' => $courseOrderId,
            'order_id' => $orderId,
            'order_id_formatted' => AdminOrderHelper::formatOrderId($orderId),
            'learner_name' => (string) $row['learner_name'],
            'teacher_name' => (string) $row['teacher_name'],
            'teacher_email' => (string) ($row['teacher_email'] ?? ''),
            'course_title' => (string) $row['course_title'],
            'clang_name' => (string) $row['clang_name'],
            'course_duration' => $duration,
            'course_duration_label' => $duration > 0 ? $duration.' Minutes' : '',
            'ordcrs_status' => (int) ($row['ordcrs_status'] ?? 0),
            'ordcrs_status_label' => AdminOrderHelper::courseOrderStatusLabel((int) ($row['ordcrs_status'] ?? 0)),
            'order_payment_status' => (int) ($row['order_payment_status'] ?? 0),
            'order_payment_status_label' => AdminOrderHelper::paymentStatusLabel((int) ($row['order_payment_status'] ?? 0)),
            'order_addedon' => (string) ($row['order_addedon'] ?? ''),
            'ordcrs_amount' => (float) ($row['ordcrs_amount'] ?? 0),
            'ordcrs_discount' => (float) ($row['ordcrs_discount'] ?? 0),
            'order_reward_value' => (float) ($row['order_reward_value'] ?? 0),
            'ordcrs_net_amount' => $net,
            'ordcrs_commission_amount' => (float) ($row['ordcrs_commission_amount'] ?? 0),
            'ordcrs_affiliate_commission' => (float) ($row['ordcrs_affiliate_commission'] ?? 0),
            'ordcrs_teacher_paid' => (float) ($row['ordcrs_teacher_paid'] ?? 0),
            'ordcrs_reviewed' => (int) ($row['ordcrs_reviewed'] ?? 0),
            'ordcrs_refund' => (float) ($row['ordcrs_refund'] ?? 0),
            'crspro_progress' => (int) ($row['crspro_progress'] ?? 0),
            'issue_reported' => $repissId > 0,
            'repiss_id' => $repissId,
        ];
    }

    /** @return array<string, mixed>|null */
    public function showGiftcard(int $orderId): ?array
    {
        $row = DB::table('tbl_order_giftcards as ordgift')
            ->join('tbl_orders as orders', 'orders.order_id', '=', 'ordgift.ordgift_order_id')
            ->join('tbl_users as learner', 'learner.user_id', '=', 'orders.order_user_id')
            ->where('orders.order_id', $orderId)
            ->select([
                'ordgift.*',
                'orders.order_id',
                'orders.order_payment_status',
                'orders.order_total_amount',
                DB::raw('TRIM(CONCAT(COALESCE(learner.user_first_name, ""), " ", COALESCE(learner.user_last_name, ""))) as user_full_name'),
            ])
            ->first();

        if (! $row) {
            return null;
        }

        $row = (array) $row;

        return [
            'type' => 'giftcard',
            'order_id' => (int) $row['order_id'],
            'order_id_formatted' => AdminOrderHelper::formatOrderId((int) $row['order_id']),
            'order_payment_status' => (int) ($row['order_payment_status'] ?? 0),
            'order_payment_status_label' => AdminOrderHelper::paymentStatusLabel((int) ($row['order_payment_status'] ?? 0)),
            'user_full_name' => (string) $row['user_full_name'],
            'ordgift_receiver_name' => (string) ($row['ordgift_receiver_name'] ?? ''),
            'ordgift_receiver_email' => (string) ($row['ordgift_receiver_email'] ?? ''),
            'ordgift_code' => (string) ($row['ordgift_code'] ?? ''),
            'ordgift_status' => (int) $row['ordgift_status'],
            'ordgift_status_label' => match ((int) $row['ordgift_status']) {
                0 => 'Unused', 1 => 'Used', 2 => 'Cancelled', default => '—',
            },
            'order_total_amount' => (float) $row['order_total_amount'],
            'ordgift_expiry' => (string) ($row['ordgift_expiry'] ?? ''),
        ];
    }

    /** @return array<string, mixed>|null */
    public function showOrderSubscriptionPlan(int $planOrderId, int $langId = 1): ?array
    {
        $row = DB::table('tbl_order_subscription_plans as ordsplan')
            ->join('tbl_orders as orders', 'orders.order_id', '=', 'ordsplan.ordsplan_order_id')
            ->join('tbl_users as learner', 'learner.user_id', '=', 'orders.order_user_id')
            ->join('tbl_subscription_plans as sp', 'sp.subplan_id', '=', 'ordsplan.ordsplan_plan_id')
            ->leftJoin('tbl_subscription_plans_lang as splang', function ($join) use ($langId) {
                $join->on('splang.subplang_subplan_id', '=', 'sp.subplan_id')
                    ->where('splang.subplang_lang_id', '=', $langId);
            })
            ->where('ordsplan.ordsplan_id', $planOrderId)
            ->select([
                'ordsplan.*',
                'orders.order_id',
                'orders.order_net_amount',
                DB::raw('IFNULL(splang.subplang_subplan_title, sp.subplan_title) as plan_name'),
                DB::raw('TRIM(CONCAT(COALESCE(learner.user_first_name, ""), " ", COALESCE(learner.user_last_name, ""))) as learner_name'),
            ])
            ->first();

        if (! $row) {
            return null;
        }

        $row = (array) $row;

        return [
            'type' => 'subscription_plan',
            'order_id' => (int) $row['order_id'],
            'order_id_formatted' => AdminOrderHelper::formatOrderId((int) $row['order_id']),
            'learner_name' => (string) $row['learner_name'],
            'plan_name' => (string) $row['plan_name'],
            'ordsplan_start_date' => (string) ($row['ordsplan_start_date'] ?? ''),
            'ordsplan_end_date' => (string) ($row['ordsplan_end_date'] ?? ''),
            'ordsplan_lessons' => (int) ($row['ordsplan_lessons'] ?? 0),
            'ordsplan_duration' => (int) ($row['ordsplan_duration'] ?? 0),
            'ordsplan_status' => (int) $row['ordsplan_status'],
            'ordsplan_status_label' => match ((int) $row['ordsplan_status']) {
                1 => 'Active', 2 => 'Expired', 3 => 'Cancelled', default => '—',
            },
            'order_net_amount' => (float) $row['order_net_amount'],
        ];
    }

    /** @return array<string, mixed>|null */
    private function getChildOrderDetails(int $orderType, int $orderId, int $langId): ?array
    {
        return match ($orderType) {
            AdminOrderHelper::TYPE_LESSON => $this->firstLessonChild($orderId, $langId),
            AdminOrderHelper::TYPE_SUBSCR => $this->firstSubscriptionChild($orderId, $langId),
            AdminOrderHelper::TYPE_GCLASS => $this->firstClassChild($orderId, $langId),
            AdminOrderHelper::TYPE_PACKGE => $this->showPackage(
                (int) DB::table('tbl_order_packages')->where('ordpkg_order_id', $orderId)->value('ordpkg_id'),
                $langId,
            ),
            AdminOrderHelper::TYPE_COURSE => $this->firstCourseChild($orderId, $langId),
            AdminOrderHelper::TYPE_WALLET => ['type' => 'wallet', 'order_id' => $orderId],
            AdminOrderHelper::TYPE_GFTCRD => $this->showGiftcard($orderId),
            AdminOrderHelper::TYPE_SUBPLAN => $this->showOrderSubscriptionPlan(
                (int) DB::table('tbl_order_subscription_plans')->where('ordsplan_order_id', $orderId)->value('ordsplan_id'),
                $langId,
            ),
            default => null,
        };
    }

    /** @return array<string, mixed>|null */
    private function firstLessonChild(int $orderId, int $langId): ?array
    {
        $id = (int) DB::table('tbl_order_lessons')->where('ordles_order_id', $orderId)->value('ordles_id');

        return $id > 0 ? $this->showLesson($id, $langId) : null;
    }

    /** @return array<string, mixed>|null */
    private function firstSubscriptionChild(int $orderId, int $langId): ?array
    {
        $row = DB::table('tbl_order_subscriptions as ordsub')
            ->join('tbl_users as teacher', 'teacher.user_id', '=', 'ordsub.ordsub_teacher_id')
            ->where('ordsub.ordsub_order_id', $orderId)
            ->select([
                'ordsub.*',
                DB::raw('TRIM(CONCAT(COALESCE(teacher.user_first_name, ""), " ", COALESCE(teacher.user_last_name, ""))) as teacher_name'),
            ])
            ->first();

        if (! $row) {
            return null;
        }

        $row = (array) $row;

        return [
            'type' => 'subscription',
            'ordsub_startdate' => (string) ($row['ordsub_startdate'] ?? ''),
            'ordsub_enddate' => (string) ($row['ordsub_enddate'] ?? ''),
            'teacher_name' => (string) $row['teacher_name'],
            'service_type_label' => AdminOrderHelper::serviceTypeLabel((int) ($row['ordsub_offline'] ?? 0)),
            'ordsub_lessons' => (int) ($row['ordsub_lessons'] ?? 0),
            'ordsub_duration' => (int) ($row['ordsub_duration'] ?? 0),
        ];
    }

    /** @return array<string, mixed>|null */
    private function firstClassChild(int $orderId, int $langId): ?array
    {
        $id = (int) DB::table('tbl_order_classes')->where('ordcls_order_id', $orderId)->value('ordcls_id');

        return $id > 0 ? $this->showClass($id, $langId) : null;
    }

    /** @return array<string, mixed>|null */
    private function firstCourseChild(int $orderId, int $langId): ?array
    {
        $row = DB::table('tbl_order_courses as ordcrs')
            ->join('tbl_courses as course', 'course.course_id', '=', 'ordcrs.ordcrs_course_id')
            ->leftJoin('tbl_course_details as crsdetail', 'crsdetail.course_id', '=', 'course.course_id')
            ->where('ordcrs.ordcrs_order_id', $orderId)
            ->select([
                'ordcrs.*',
                DB::raw('IFNULL(crsdetail.course_title, course.course_slug) as course_title'),
            ])
            ->first();

        if (! $row) {
            return null;
        }

        $row = (array) $row;

        return [
            'type' => 'course',
            'course_title' => (string) $row['course_title'],
            'ordcrs_net_amount' => (float) ($row['ordcrs_amount'] ?? 0)
                - (float) ($row['ordcrs_discount'] ?? 0),
            'ordcrs_status' => (int) ($row['ordcrs_status'] ?? 0),
        ];
    }

    private function cancelSubscriptionOrder(int $orderId): void
    {
        DB::table('tbl_order_lessons')
            ->where('ordles_order_id', $orderId)
            ->update(['ordles_status' => AdminOrderHelper::LESSON_CANCELLED]);
        DB::table('tbl_order_subscriptions')
            ->where('ordsub_order_id', $orderId)
            ->update(['ordsub_status' => 3]);
    }

    private function cancelPackageOrder(int $orderId): void
    {
        DB::table('tbl_order_classes')
            ->where('ordcls_order_id', $orderId)
            ->update(['ordcls_status' => 3]);
        DB::table('tbl_order_packages')
            ->where('ordpkg_order_id', $orderId)
            ->update(['ordpkg_status' => 3]);
    }

    private function cancelCourseOrder(int $orderId): void
    {
        $progressId = DB::table('tbl_order_courses as ordcrs')
            ->join('tbl_course_progress as crspro', 'crspro.crspro_ordcrs_id', '=', 'ordcrs.ordcrs_id')
            ->where('ordcrs.ordcrs_order_id', $orderId)
            ->value('crspro.crspro_id');

        if ($progressId) {
            DB::table('tbl_course_progress')
                ->where('crspro_id', $progressId)
                ->update(['crspro_status' => 3]);
        }

        DB::table('tbl_order_courses')
            ->where('ordcrs_order_id', $orderId)
            ->update(['ordcrs_status' => 3]);
    }

    private function completeSubOrderOnPayment(int $orderType, int $orderId, int $userId, float $amount): void
    {
        if ($orderType === AdminOrderHelper::TYPE_WALLET) {
            DB::table('tbl_user_transactions')->insert([
                'usrtxn_type' => AdminOrderHelper::TXN_MONEY_DEPOSIT,
                'usrtxn_user_id' => $userId,
                'usrtxn_amount' => abs($amount),
                'usrtxn_comment' => 'Wallet money added',
                'usrtxn_datetime' => now()->format('Y-m-d H:i:s'),
            ]);
            DB::table('tbl_user_settings')
                ->where('user_id', $userId)
                ->update(['user_wallet_balance' => DB::raw('user_wallet_balance + '.abs($amount))]);
        }
    }

    private function endedByLabel(int $endedBy, string $learnerName, string $teacherName): string
    {
        return match ($endedBy) {
            2 => $teacherName,
            1 => $learnerName,
            default => $endedBy > 0 ? 'System' : '',
        };
    }
}
