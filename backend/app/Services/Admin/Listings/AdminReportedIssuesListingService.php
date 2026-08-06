<?php

namespace App\Services\Admin\Listings;

use App\Services\Admin\AdminOrderHelper;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminReportedIssuesListingService
{
    use AdminListingSupport;

    public const TYPE_LESSON = 1;

    public const TYPE_GCLASS = 2;

    public const STATUS_PROGRESS = 1;

    public const STATUS_RESOLVED = 2;

    public const STATUS_ESCALATED = 3;

    public const STATUS_CLOSED = 4;

    private const USER_TYPE_LEARNER = 1;

    private const USER_TYPE_TEACHER = 2;

    private const USER_TYPE_SUPPORT = 3;

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    public function search(Request $request): array
    {
        $query = DB::table('tbl_reported_issues as repiss')
            ->join('tbl_users as learner', 'learner.user_id', '=', 'repiss.repiss_reported_by')
            ->leftJoin('tbl_order_lessons as ordles', function ($join) {
                $join->on('repiss.repiss_record_type', '=', DB::raw((string) self::TYPE_LESSON))
                    ->on('repiss.repiss_record_id', '=', 'ordles.ordles_id');
            })
            ->leftJoin('tbl_order_classes as ordcls', function ($join) {
                $join->on('repiss.repiss_record_type', '=', DB::raw((string) self::TYPE_GCLASS))
                    ->on('repiss.repiss_record_id', '=', 'ordcls.ordcls_id');
            })
            ->leftJoin('tbl_group_classes as grpcls', 'grpcls.grpcls_id', '=', 'ordcls.ordcls_grpcls_id')
            ->leftJoin('tbl_users as ordlestch', function ($join) {
                $join->on('repiss.repiss_record_type', '=', DB::raw((string) self::TYPE_LESSON))
                    ->on('ordlestch.user_id', '=', 'ordles.ordles_teacher_id');
            })
            ->leftJoin('tbl_users as ordclstch', function ($join) {
                $join->on('repiss.repiss_record_type', '=', DB::raw((string) self::TYPE_GCLASS))
                    ->on('ordclstch.user_id', '=', 'grpcls.grpcls_teacher_id');
            })
            ->select([
                'repiss.repiss_id as id',
                'repiss.repiss_id as repiss_id',
                'repiss.repiss_title as repiss_title',
                'repiss.repiss_record_id as repiss_record_id',
                'repiss.repiss_record_type as repiss_record_type',
                'repiss.repiss_status as repiss_status',
                'repiss.repiss_comment as repiss_comment',
                'repiss.repiss_reported_on as repiss_reported_on',
                DB::raw('TRIM(CONCAT(COALESCE(learner.user_first_name, ""), " ", COALESCE(learner.user_last_name, ""))) as reporter_name'),
                'ordles.ordles_order_id as ordles_order_id',
                'ordcls.ordcls_order_id as ordcls_order_id',
            ]);

        $this->applyFilters($request, $query);

        $page = max(1, $request->integer('page', 1));
        $perPage = $this->adminPageSize($request);
        $total = (clone $query)->count('repiss.repiss_id');
        $rows = $query
            ->orderBy('repiss.repiss_status')
            ->orderByDesc('repiss.repiss_reported_on')
            ->forPage($page, $perPage)
            ->get()
            ->map(fn ($row) => $this->formatRow((array) $row))
            ->all();

        return $this->paginateResult($request, $rows, $total);
    }

    /** @return array<string, mixed>|null */
    public function detail(int $issueId): ?array
    {
        $row = DB::table('tbl_reported_issues as repiss')
            ->join('tbl_users as learner', 'learner.user_id', '=', 'repiss.repiss_reported_by')
            ->leftJoin('tbl_order_lessons as ordles', function ($join) {
                $join->on('repiss.repiss_record_type', '=', DB::raw((string) self::TYPE_LESSON))
                    ->on('repiss.repiss_record_id', '=', 'ordles.ordles_id');
            })
            ->leftJoin('tbl_order_classes as ordcls', function ($join) {
                $join->on('repiss.repiss_record_type', '=', DB::raw((string) self::TYPE_GCLASS))
                    ->on('repiss.repiss_record_id', '=', 'ordcls.ordcls_id');
            })
            ->leftJoin('tbl_group_classes as grpcls', 'grpcls.grpcls_id', '=', 'ordcls.ordcls_grpcls_id')
            ->leftJoin('tbl_orders as orders', function ($join) {
                $join->on('orders.order_id', '=', 'ordles.ordles_order_id')
                    ->orOn('orders.order_id', '=', 'ordcls.ordcls_order_id');
            })
            ->leftJoin('tbl_users as ordlestch', function ($join) {
                $join->on('repiss.repiss_record_type', '=', DB::raw((string) self::TYPE_LESSON))
                    ->on('ordlestch.user_id', '=', 'ordles.ordles_teacher_id');
            })
            ->leftJoin('tbl_users as ordclstch', function ($join) {
                $join->on('repiss.repiss_record_type', '=', DB::raw((string) self::TYPE_GCLASS))
                    ->on('ordclstch.user_id', '=', 'grpcls.grpcls_teacher_id');
            })
            ->leftJoin('tbl_teach_languages_lang as lesson_tlang', function ($join) {
                $join->on('lesson_tlang.tlanglang_tlang_id', '=', 'ordles.ordles_tlang_id')
                    ->where('lesson_tlang.tlanglang_lang_id', '=', 1);
            })
            ->leftJoin('tbl_teach_languages_lang as class_tlang', function ($join) {
                $join->on('class_tlang.tlanglang_tlang_id', '=', 'grpcls.grpcls_tlang_id')
                    ->where('class_tlang.tlanglang_lang_id', '=', 1);
            })
            ->where('repiss.repiss_id', $issueId)
            ->select([
                'repiss.repiss_id',
                'repiss.repiss_title',
                'repiss.repiss_record_id',
                'repiss.repiss_record_type',
                'repiss.repiss_status',
                'repiss.repiss_comment',
                'repiss.repiss_reported_on',
                'orders.order_id',
                'orders.order_item_count',
                'orders.order_discount_value',
                'orders.order_reward_value',
                'orders.order_currency_code',
                DB::raw('TRIM(CONCAT(COALESCE(learner.user_first_name, ""), " ", COALESCE(learner.user_last_name, ""))) as learner_full_name'),
                DB::raw('TRIM(CONCAT(COALESCE(ordlestch.user_first_name, ""), " ", COALESCE(ordlestch.user_last_name, ""))) as lesson_teacher_full_name'),
                DB::raw('TRIM(CONCAT(COALESCE(ordclstch.user_first_name, ""), " ", COALESCE(ordclstch.user_last_name, ""))) as class_teacher_full_name'),
                'ordles.ordles_amount',
                'ordles.ordles_discount',
                'ordles.ordles_reward_discount',
                'ordles.ordles_teacher_starttime',
                'ordles.ordles_teacher_endtime',
                'ordles.ordles_student_starttime',
                'ordles.ordles_student_endtime',
                'ordles.ordles_ended_by',
                'ordcls.ordcls_amount',
                'ordcls.ordcls_discount',
                'ordcls.ordcls_reward_discount',
                'ordcls.ordcls_starttime',
                'ordcls.ordcls_endtime',
                'ordcls.ordcls_ended_by',
                'grpcls.grpcls_teacher_starttime',
                'grpcls.grpcls_teacher_endtime',
                DB::raw('COALESCE(lesson_tlang.tlang_name, class_tlang.tlang_name, "") as language_name'),
            ])
            ->first();

        if (! $row) {
            return null;
        }

        $issue = (array) $row;
        $logs = DB::table('tbl_reported_issues_log as reislo')
            ->leftJoin('tbl_users as user', function ($join) {
                $join->on('user.user_id', '=', 'reislo.reislo_added_by')
                    ->whereIn('reislo.reislo_added_by_type', [self::USER_TYPE_LEARNER, self::USER_TYPE_TEACHER]);
            })
            ->leftJoin('tbl_admin as admin', function ($join) {
                $join->on('admin.admin_id', '=', 'reislo.reislo_added_by')
                    ->where('reislo.reislo_added_by_type', '=', self::USER_TYPE_SUPPORT);
            })
            ->where('reislo.reislo_repiss_id', $issueId)
            ->orderByDesc('reislo.reislo_id')
            ->get([
                'reislo.reislo_action',
                'reislo.reislo_comment',
                'reislo.reislo_added_on',
                'reislo.reislo_added_by_type',
                DB::raw('CASE WHEN reislo.reislo_added_by_type = 3 THEN admin.admin_name ELSE TRIM(CONCAT(COALESCE(user.user_first_name, ""), " ", COALESCE(user.user_last_name, ""))) END as user_fullname'),
            ])
            ->map(fn ($log) => [
                'date' => (string) ($log->reislo_added_on ?? ''),
                'author' => (string) ($log->user_fullname ?? ''),
                'role' => $this->userTypeLabel((int) ($log->reislo_added_by_type ?? 0)),
                'message' => $this->actionLabel((int) ($log->reislo_action ?? 0)),
                'comments' => (string) ($log->reislo_comment ?? ''),
                'class_name' => $this->logClassName((int) ($log->reislo_added_by_type ?? 0)),
                'icon' => $this->logIcon((int) ($log->reislo_added_by_type ?? 0)),
            ])
            ->all();

        $logs[] = [
            'date' => (string) ($issue['repiss_reported_on'] ?? ''),
            'author' => (string) ($issue['learner_full_name'] ?? ''),
            'role' => $this->userTypeLabel(self::USER_TYPE_LEARNER),
            'message' => (string) ($issue['repiss_title'] ?? ''),
            'comments' => (string) ($issue['repiss_comment'] ?? ''),
            'class_name' => $this->logClassName(self::USER_TYPE_LEARNER),
            'icon' => $this->logIcon(self::USER_TYPE_LEARNER),
        ];

        return [
            'logs' => $logs,
            'record_details' => $this->recordDetails($issue),
        ];
    }

    private function applyFilters(Request $request, Builder $query): void
    {
        if ($request->integer('escalated') === 1) {
            $query->where('repiss.repiss_status', '=', self::STATUS_ESCALATED);
        }

        $status = $request->query('repiss_status');
        if ($status !== null && $status !== '') {
            $query->where('repiss.repiss_status', '=', (int) $status);
        }

        $recordType = $request->query('repiss_record_type');
        if ($recordType !== null && $recordType !== '') {
            $query->where('repiss.repiss_record_type', '=', (int) $recordType);
        }

        $recordId = $request->query('repiss_record_id');
        if ($recordId !== null && $recordId !== '') {
            $query->where('repiss.repiss_record_id', '=', (int) $recordId);
        }

        $orderIdRaw = trim((string) $request->query('order_id', ''));
        if ($orderIdRaw !== '') {
            $orderId = (int) preg_replace('/\D/', '', $orderIdRaw);
            if ($orderId > 0) {
                $query->where(function (Builder $q) use ($orderId) {
                    $q->where('ordcls.ordcls_order_id', '=', $orderId)
                        ->orWhere('ordles.ordles_order_id', '=', $orderId);
                });
            }
        }

        $learnerId = $request->integer('learner_id');
        if ($learnerId > 0) {
            $query->where('learner.user_id', '=', $learnerId);
        } else {
            $learner = trim((string) $request->query('learner', ''));
            if ($learner !== '') {
                $query->whereRaw(
                    'TRIM(CONCAT(COALESCE(learner.user_first_name, ""), " ", COALESCE(learner.user_last_name, ""))) LIKE ?',
                    ['%'.$learner.'%']
                );
            }
        }

        $teacherId = $request->integer('teacher_id');
        if ($teacherId > 0) {
            $query->where(function (Builder $q) use ($teacherId) {
                $q->where('ordles.ordles_teacher_id', '=', $teacherId)
                    ->orWhere('grpcls.grpcls_teacher_id', '=', $teacherId);
            });
        } else {
            $teacher = trim((string) $request->query('teacher', ''));
            if ($teacher !== '') {
                $query->where(function (Builder $q) use ($teacher) {
                    $q->whereRaw(
                        'TRIM(CONCAT(COALESCE(ordclstch.user_first_name, ""), " ", COALESCE(ordclstch.user_last_name, ""))) LIKE ?',
                        ['%'.$teacher.'%']
                    )->orWhereRaw(
                        'TRIM(CONCAT(COALESCE(ordlestch.user_first_name, ""), " ", COALESCE(ordlestch.user_last_name, ""))) LIKE ?',
                        ['%'.$teacher.'%']
                    );
                });
            }
        }

        $keyword = trim((string) $request->query('keyword', ''));
        if ($keyword !== '') {
            $query->where(function (Builder $q) use ($keyword) {
                $q->where('repiss.repiss_title', 'like', '%'.$keyword.'%')
                    ->orWhereRaw(
                        'TRIM(CONCAT(COALESCE(learner.user_first_name, ""), " ", COALESCE(learner.user_last_name, ""))) LIKE ?',
                        ['%'.$keyword.'%']
                    );

                $numeric = (int) preg_replace('/\D/', '', $keyword);
                if ($numeric > 0) {
                    $q->orWhere('repiss.repiss_id', '=', $numeric)
                        ->orWhere('repiss.repiss_record_id', '=', $numeric);
                }
            });
        }
    }

    /** @param  array<string, mixed>  $row */
    private function formatRow(array $row): array
    {
        $recordType = (int) ($row['repiss_record_type'] ?? 0);
        $orderId = $recordType === self::TYPE_GCLASS
            ? (int) ($row['ordcls_order_id'] ?? 0)
            : (int) ($row['ordles_order_id'] ?? 0);
        $status = (int) ($row['repiss_status'] ?? 0);

        return [
            'id' => (int) $row['repiss_id'],
            'repiss_id' => (int) $row['repiss_id'],
            'repiss_title' => (string) ($row['repiss_title'] ?? ''),
            'repiss_record_id' => (int) ($row['repiss_record_id'] ?? 0),
            'repiss_record_type' => $recordType,
            'record_type_label' => $this->recordTypeLabel($recordType),
            'order_id' => $orderId,
            'order_id_formatted' => $orderId > 0 ? AdminOrderHelper::formatOrderId($orderId) : '—',
            'reporter_name' => (string) ($row['reporter_name'] ?? ''),
            'repiss_comment' => (string) ($row['repiss_comment'] ?? ''),
            'repiss_status' => $status,
            'status_label' => $this->statusLabel($status),
            'reported_on' => (string) ($row['repiss_reported_on'] ?? ''),
        ];
    }

    private function recordTypeLabel(int $type): string
    {
        return match ($type) {
            self::TYPE_LESSON => 'One-on-one lesson',
            self::TYPE_GCLASS => 'Group classes/packages',
            default => 'N/A',
        };
    }

    private function statusLabel(int $status): string
    {
        return match ($status) {
            self::STATUS_PROGRESS => 'In progress',
            self::STATUS_RESOLVED => 'Resolved',
            self::STATUS_ESCALATED => 'Escalated',
            self::STATUS_CLOSED => 'Closed',
            default => 'N/A',
        };
    }

    /** @param array<string, mixed> $issue */
    private function recordDetails(array $issue): array
    {
        $isClass = (int) ($issue['repiss_record_type'] ?? 0) === self::TYPE_GCLASS;
        $teacherName = $isClass ? (string) ($issue['class_teacher_full_name'] ?? '') : (string) ($issue['lesson_teacher_full_name'] ?? '');
        $orderId = (int) ($issue['order_id'] ?? 0);
        $amount = (float) ($isClass ? ($issue['ordcls_amount'] ?? 0) : ($issue['ordles_amount'] ?? 0));
        $discount = (float) ($isClass ? ($issue['ordcls_discount'] ?? 0) : ($issue['ordles_discount'] ?? 0));
        $rewardDiscount = (float) ($isClass ? ($issue['ordcls_reward_discount'] ?? 0) : ($issue['ordles_reward_discount'] ?? 0));
        $endedBy = (int) ($isClass ? ($issue['ordcls_ended_by'] ?? 0) : ($issue['ordles_ended_by'] ?? 0));

        $rows = [
            ['label' => 'Language', 'value' => (string) ($issue['language_name'] ?? '')],
            ['label' => 'Order id', 'value' => $orderId > 0 ? AdminOrderHelper::formatOrderId($orderId) : 'N/A'],
            ['label' => 'Record ID', 'value' => (string) ($issue['repiss_record_id'] ?? '')],
            ['label' => 'Total item', 'value' => (string) ($issue['order_item_count'] ?? '')],
            ['label' => 'Price', 'value' => $this->money($amount, (string) ($issue['order_currency_code'] ?? ''))],
            ['label' => 'Discount', 'value' => $this->money($discount, (string) ($issue['order_currency_code'] ?? ''))],
            ['label' => 'Reward discount', 'value' => $this->money($rewardDiscount, (string) ($issue['order_currency_code'] ?? ''))],
            ['label' => 'Net amount', 'value' => $this->money($amount - ($discount + $rewardDiscount), (string) ($issue['order_currency_code'] ?? ''))],
            ['label' => 'Teacher name', 'value' => $teacherName],
            ['label' => 'Teacher join time', 'value' => (string) ($isClass ? ($issue['grpcls_teacher_starttime'] ?? '') : ($issue['ordles_teacher_starttime'] ?? ''))],
            ['label' => 'Teacher end time', 'value' => (string) ($isClass ? ($issue['grpcls_teacher_endtime'] ?? '') : ($issue['ordles_teacher_endtime'] ?? ''))],
            ['label' => 'Learner name', 'value' => (string) ($issue['learner_full_name'] ?? '')],
            ['label' => 'Learner join time', 'value' => (string) ($isClass ? ($issue['ordcls_starttime'] ?? '') : ($issue['ordles_student_starttime'] ?? ''))],
            ['label' => 'Learner end time', 'value' => (string) ($isClass ? ($issue['ordcls_endtime'] ?? '') : ($issue['ordles_student_endtime'] ?? ''))],
            ['label' => 'Ended by', 'value' => $this->endedByLabel($endedBy, $teacherName, (string) ($issue['learner_full_name'] ?? ''))],
        ];

        if ((float) ($issue['order_discount_value'] ?? 0) > 0 || (float) ($issue['order_reward_value'] ?? 0) > 0) {
            $rows[] = [
                'label' => '',
                'value' => 'Note: refund amount may be affected by order discounts or reward points.',
                'is_note' => true,
            ];
        }

        return $rows;
    }

    private function actionLabel(int $action): string
    {
        return match ($action) {
            1 => 'Reset and unscheduled',
            2 => 'Complete and zero refund',
            3 => 'Complete and 50% refund',
            4 => 'Complete and 100% refund',
            5 => 'Escalate to support team',
            default => 'N/A',
        };
    }

    private function userTypeLabel(int $type): string
    {
        return match ($type) {
            self::USER_TYPE_LEARNER => 'Learner',
            self::USER_TYPE_TEACHER => 'Teacher',
            self::USER_TYPE_SUPPORT => 'Support',
            default => 'N/A',
        };
    }

    private function logClassName(int $type): string
    {
        return match ($type) {
            self::USER_TYPE_TEACHER => 'is-inprogress',
            self::USER_TYPE_SUPPORT => 'is-approved',
            default => 'is-rejected',
        };
    }

    private function logIcon(int $type): string
    {
        return match ($type) {
            self::USER_TYPE_TEACHER => 'sync',
            self::USER_TYPE_SUPPORT => 'check',
            default => 'warning',
        };
    }

    private function endedByLabel(int $endedBy, string $teacherName, string $learnerName): string
    {
        return match ($endedBy) {
            self::USER_TYPE_TEACHER => $teacherName,
            self::USER_TYPE_LEARNER => $learnerName,
            0 => 'N/A',
            default => 'System',
        };
    }

    private function money(float $amount, string $currencyCode): string
    {
        $symbol = strtoupper($currencyCode) === 'USD' || $currencyCode === '' ? '$' : $currencyCode.' ';

        return $symbol.AdminOrderHelper::formatMoney($amount);
    }
}
