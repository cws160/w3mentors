<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class IssueListingService
{
    public const ISSUE_TYPE_LESSON = 1;

    public const ISSUE_TYPE_GCLASS = 2;

    public const ISSUE_STATUS_PROGRESS = 1;

    public const LESSON_UNSCHEDULED = 1;

    public const LESSON_SCHEDULED = 2;

    public const LESSON_COMPLETED = 3;

    public const LESSON_CANCELLED = 4;

    public const CLASS_SCHEDULED = 1;

    public const CLASS_COMPLETED = 2;

    public const CLASS_CANCELLED = 3;

    /**
     * @param  array{keyword?: string, status?: int, class_type?: int, page?: int, per_page?: int}  $filters
     * @return array{items: array<int, array<string, mixed>>, meta: array<string, int>}
     */
    public function list(int $userId, bool $isTeacher, int $langId, array $filters): array
    {
        $perPage = max(1, min(50, (int) ($filters['per_page'] ?? 20)));
        $page = max(1, (int) ($filters['page'] ?? 1));

        $query = DB::table('tbl_reported_issues as repiss')
            ->join('tbl_users as reporter', 'reporter.user_id', '=', 'repiss.repiss_reported_by')
            ->leftJoin('tbl_order_lessons as ordles', function ($join) {
                $join->on('repiss.repiss_record_type', '=', DB::raw((string) self::ISSUE_TYPE_LESSON))
                    ->on('repiss.repiss_record_id', '=', 'ordles.ordles_id');
            })
            ->leftJoin('tbl_orders as les_order', 'les_order.order_id', '=', 'ordles.ordles_order_id')
            ->leftJoin('tbl_users as les_learner', 'les_learner.user_id', '=', 'les_order.order_user_id')
            ->leftJoin('tbl_order_classes as ordcls', function ($join) {
                $join->on('repiss.repiss_record_type', '=', DB::raw((string) self::ISSUE_TYPE_GCLASS))
                    ->on('repiss.repiss_record_id', '=', 'ordcls.ordcls_id');
            })
            ->leftJoin('tbl_group_classes as grpcls', 'grpcls.grpcls_id', '=', 'ordcls.ordcls_grpcls_id')
            ->leftJoin('tbl_orders as cls_order', 'cls_order.order_id', '=', 'ordcls.ordcls_order_id')
            ->leftJoin('tbl_users as cls_learner', 'cls_learner.user_id', '=', 'cls_order.order_user_id')
            ->leftJoin('tbl_users as les_teacher', 'les_teacher.user_id', '=', 'ordles.ordles_teacher_id')
            ->leftJoin('tbl_users as cls_teacher', 'cls_teacher.user_id', '=', 'grpcls.grpcls_teacher_id')
            ->leftJoin('tbl_teach_languages_lang as tlanglang', function ($join) use ($langId) {
                $join->on('tlanglang.tlanglang_tlang_id', '=', 'ordles.ordles_tlang_id')
                    ->where('tlanglang.tlanglang_lang_id', '=', $langId);
            })
            ->leftJoin('tbl_countries_lang as les_learner_country', function ($join) use ($langId) {
                $join->on('les_learner_country.countrylang_country_id', '=', 'les_learner.user_country_id')
                    ->where('les_learner_country.countrylang_lang_id', '=', $langId);
            })
            ->leftJoin('tbl_countries_lang as cls_learner_country', function ($join) use ($langId) {
                $join->on('cls_learner_country.countrylang_country_id', '=', 'cls_learner.user_country_id')
                    ->where('cls_learner_country.countrylang_lang_id', '=', $langId);
            })
            ->leftJoin('tbl_countries_lang as les_teacher_country', function ($join) use ($langId) {
                $join->on('les_teacher_country.countrylang_country_id', '=', 'les_teacher.user_country_id')
                    ->where('les_teacher_country.countrylang_lang_id', '=', $langId);
            })
            ->leftJoin('tbl_countries_lang as cls_teacher_country', function ($join) use ($langId) {
                $join->on('cls_teacher_country.countrylang_country_id', '=', 'cls_teacher.user_country_id')
                    ->where('cls_teacher_country.countrylang_lang_id', '=', $langId);
            });

        if ($isTeacher) {
            $query->where(function ($q) use ($userId) {
                $q->where('ordles.ordles_teacher_id', $userId)
                    ->orWhere('grpcls.grpcls_teacher_id', $userId);
            });
        } else {
            $query->where('repiss.repiss_reported_by', $userId);
        }

        if (! empty($filters['class_type'])) {
            $query->where('repiss.repiss_record_type', (int) $filters['class_type']);
        }

        if (! empty($filters['status'])) {
            $query->where('repiss.repiss_status', (int) $filters['status']);
        }

        $keyword = trim((string) ($filters['keyword'] ?? ''));
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword, $isTeacher) {
                if ($isTeacher) {
                    $q->whereRaw(
                        "CONCAT(COALESCE(les_learner.user_first_name, cls_learner.user_first_name, reporter.user_first_name), ' ', COALESCE(les_learner.user_last_name, cls_learner.user_last_name, reporter.user_last_name)) LIKE ?",
                        ['%'.$keyword.'%']
                    );
                } else {
                    $q->whereRaw(
                        "CONCAT(COALESCE(les_teacher.user_first_name, cls_teacher.user_first_name), ' ', COALESCE(les_teacher.user_last_name, cls_teacher.user_last_name)) LIKE ?",
                        ['%'.$keyword.'%']
                    );
                }
            });
        }

        $total = (clone $query)->count('repiss.repiss_id');

        $rows = $query
            ->orderByDesc('repiss.repiss_id')
            ->forPage($page, $perPage)
            ->get([
                'repiss.repiss_id',
                'repiss.repiss_title',
                'repiss.repiss_status',
                'repiss.repiss_record_type',
                'repiss.repiss_reported_on',
                'ordles.ordles_status',
                'ordles.ordles_lesson_starttime',
                'ordcls.ordcls_status',
                'ordcls.ordcls_starttime',
                'tlanglang.tlang_name',
                'les_learner.user_id as lesson_learner_id',
                'les_learner.user_first_name as lesson_learner_first',
                'les_learner.user_last_name as lesson_learner_last',
                'cls_learner.user_id as cls_learner_id',
                'cls_learner.user_first_name as cls_learner_first',
                'cls_learner.user_last_name as cls_learner_last',
                'les_teacher.user_id as les_teacher_id',
                'les_teacher.user_first_name as les_teacher_first',
                'les_teacher.user_last_name as les_teacher_last',
                'cls_teacher.user_id as cls_teacher_id',
                'cls_teacher.user_first_name as cls_teacher_first',
                'cls_teacher.user_last_name as cls_teacher_last',
                'les_learner_country.country_name as lesson_learner_country',
                'cls_learner_country.country_name as cls_learner_country',
                'les_teacher_country.country_name as les_teacher_country',
                'cls_teacher_country.country_name as cls_teacher_country',
            ]);

        $items = $rows->map(function ($row) use ($isTeacher) {
            $recordType = (int) $row->repiss_record_type;
            $sessionTime = $row->ordles_lesson_starttime ?? $row->ordcls_starttime;
            $sessionStatus = $row->ordles_status ?? $row->ordcls_status;
            $issueStatus = (int) $row->repiss_status;

            if ($isTeacher) {
                $counterpartyId = (int) ($row->lesson_learner_id ?? $row->cls_learner_id ?? 0);
                $first = $row->lesson_learner_first ?? $row->cls_learner_first;
                $last = $row->lesson_learner_last ?? $row->cls_learner_last;
                $country = (string) ($row->lesson_learner_country ?? $row->cls_learner_country ?? '');
            } else {
                $counterpartyId = (int) ($row->les_teacher_id ?? $row->cls_teacher_id ?? 0);
                $first = $row->les_teacher_first ?? $row->cls_teacher_first;
                $last = $row->les_teacher_last ?? $row->cls_teacher_last;
                $country = (string) ($row->les_teacher_country ?? $row->cls_teacher_country ?? '');
            }

            return [
                'id' => (int) $row->repiss_id,
                'title' => (string) ($row->repiss_title ?? ''),
                'status' => $issueStatus,
                'status_label' => $this->issueStatusLabel($issueStatus),
                'record_type' => $recordType,
                'reported_at' => $row->repiss_reported_on ? (string) $row->repiss_reported_on : null,
                'language' => (string) ($row->tlang_name ?? ''),
                'session_time' => $sessionTime ? (string) $sessionTime : null,
                'session_status' => $sessionStatus !== null ? (int) $sessionStatus : null,
                'session_status_label' => $this->sessionStatusLabel($recordType, $sessionStatus !== null ? (int) $sessionStatus : null),
                'counterparty_id' => $counterpartyId,
                'counterparty_name' => trim((string) $first.' '.(string) ($last ?? '')),
                'counterparty_country' => $country,
                'can_resolve' => $isTeacher && $issueStatus === self::ISSUE_STATUS_PROGRESS,
            ];
        })->all();

        return [
            'items' => $items,
            'meta' => [
                'current_page' => $page,
                'last_page' => (int) max(1, ceil($total / $perPage)),
                'per_page' => $perPage,
                'total' => $total,
            ],
        ];
    }

    private function issueStatusLabel(int $status): string
    {
        return match ($status) {
            1 => 'In progress',
            2 => 'Resolved',
            3 => 'Escalated',
            4 => 'Closed',
            default => 'N/A',
        };
    }

    private function sessionStatusLabel(int $recordType, ?int $status): string
    {
        if ($status === null) {
            return 'N/A';
        }

        if ($recordType === self::ISSUE_TYPE_GCLASS) {
            return match ($status) {
                self::CLASS_SCHEDULED => 'Scheduled',
                self::CLASS_COMPLETED => 'Completed',
                self::CLASS_CANCELLED => 'Cancelled',
                default => 'N/A',
            };
        }

        return match ($status) {
            self::LESSON_UNSCHEDULED => 'Unscheduled',
            self::LESSON_SCHEDULED => 'Scheduled',
            self::LESSON_COMPLETED => 'Completed',
            self::LESSON_CANCELLED => 'Cancelled',
            default => 'N/A',
        };
    }
}
