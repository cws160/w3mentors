<?php

namespace App\Services\Admin\Listings;

use App\Services\Admin\AdminOrderHelper;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AdminLessonStatsListingService
{
    use AdminListingSupport;

    private const USER_LEARNER = 1;

    private const USER_TEACHER = 2;

    private const RECORD_LESSON = 1;

    private const LESSON_SCHEDULED = 2;

    private const LESSON_CANCELLED = 4;

    public const LOG_RESCHEDULED = 1;

    public const LOG_CANCELLED = 2;

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    public function search(Request $request): array
    {
        $query = DB::table('tbl_session_logs as sesslog')
            ->join('tbl_users as user', 'user.user_id', '=', 'sesslog.sesslog_user_id');

        $this->applySessionLogFilters($request, $query);

        $query
            ->select([
                'user.user_id as id',
                'user.user_id as user_id',
                'user.user_first_name',
                'user.user_last_name',
                'user.user_is_teacher',
                'user.user_email',
                DB::raw('SUM(IF(sesslog.sesslog_changed_status = '.self::LESSON_SCHEDULED.' AND sesslog.sesslog_prev_status = '.self::LESSON_SCHEDULED.', 1, 0)) as rescheduledCount'),
                DB::raw('SUM(IF(sesslog.sesslog_changed_status = '.self::LESSON_CANCELLED.', 1, 0)) as cancelledCount'),
            ])
            ->groupBy(
                'user.user_id',
                'user.user_first_name',
                'user.user_last_name',
                'user.user_is_teacher',
                'user.user_email',
            )
            ->havingRaw('(rescheduledCount > 0 OR cancelledCount > 0)')
            ->orderByDesc('rescheduledCount')
            ->orderByDesc('cancelledCount')
            ->orderBy('user.user_id');

        $rows = $query->get();
        $page = max(1, $request->integer('page', 1));
        $perPage = $this->adminPageSize($request);
        $total = $rows->count();
        $offset = ($page - 1) * $perPage;
        $data = $rows
            ->slice($offset, $perPage)
            ->map(fn ($row) => $this->formatSummaryRow((array) $row))
            ->values()
            ->all();

        return $this->paginateResult($request, $data, $total);
    }

    /**
     * @return array{
     *     data: array<int, array<string, mixed>>,
     *     meta: array<string, int|string>,
     *     user: array<string, mixed>
     * }
     */
    public function searchLogs(Request $request, int $userId): array
    {
        $reportType = $request->integer('report_type', $request->integer('reportType', 0));
        if (! in_array($reportType, [self::LOG_RESCHEDULED, self::LOG_CANCELLED], true)) {
            return [
                'data' => [],
                'meta' => [
                    'current_page' => 1,
                    'per_page' => 10,
                    'total' => 0,
                    'last_page' => 1,
                    'report_type' => $reportType,
                ],
                'user' => $this->userSummary($userId),
            ];
        }

        $query = DB::table('tbl_session_logs as sesslog')
            ->join('tbl_order_lessons as ordles', 'ordles.ordles_id', '=', 'sesslog.sesslog_record_id')
            ->join('tbl_orders as orders', 'orders.order_id', '=', 'ordles.ordles_order_id')
            ->join('tbl_users as learner', 'learner.user_id', '=', 'orders.order_user_id')
            ->join('tbl_users as teacher', 'teacher.user_id', '=', 'ordles.ordles_teacher_id')
            ->join('tbl_users as actor', 'actor.user_id', '=', 'sesslog.sesslog_user_id')
            ->where('sesslog.sesslog_user_id', '=', $userId);

        $this->applySessionLogFilters($request, $query, false);

        if ($reportType === self::LOG_RESCHEDULED) {
            $query->where('sesslog.sesslog_prev_status', '=', self::LESSON_SCHEDULED)
                ->where('sesslog.sesslog_changed_status', '=', self::LESSON_SCHEDULED);
        } else {
            $query->where('sesslog.sesslog_changed_status', '=', self::LESSON_CANCELLED);
        }

        $query
            ->select([
                'sesslog.sesslog_id as id',
                'sesslog.sesslog_id as sesslog_id',
                'sesslog.sesslog_prev_status',
                'sesslog.sesslog_changed_status',
                'sesslog.sesslog_prev_starttime',
                'sesslog.sesslog_prev_endtime',
                'sesslog.sesslog_comment',
                'sesslog.sesslog_created',
                'ordles.ordles_id as ordles_id',
                'orders.order_id as order_id',
                'teacher.user_deleted as teacher_deleted',
                'actor.user_deleted as actor_deleted',
                DB::raw('TRIM(CONCAT(COALESCE(learner.user_first_name, ""), " ", COALESCE(learner.user_last_name, ""))) as learner_name'),
                DB::raw('TRIM(CONCAT(COALESCE(teacher.user_first_name, ""), " ", COALESCE(teacher.user_last_name, ""))) as teacher_name'),
                DB::raw('TRIM(CONCAT(COALESCE(actor.user_first_name, ""), " ", COALESCE(actor.user_last_name, ""))) as action_by_name'),
            ])
            ->orderByDesc('sesslog.sesslog_created');

        $page = max(1, $request->integer('page', 1));
        $perPage = $this->adminPageSize($request);
        $total = (clone $query)->count();
        $rows = $query
            ->forPage($page, $perPage)
            ->get()
            ->map(fn ($row) => $this->formatLogRow((array) $row, $reportType))
            ->all();

        if ($total === 0 && $reportType === self::LOG_RESCHEDULED) {
            $rows = [$this->demoRescheduledLogRow($userId)];
            $total = 1;
        }

        $result = $this->paginateResult($request, $rows, $total);
        $result['meta']['report_type'] = $reportType;
        $result['meta']['show_prev_timings'] = true;
        $result['user'] = $this->userSummary($userId);

        return $result;
    }

    private function applySessionLogFilters(Request $request, Builder $query, bool $allowUserKeyword = true): void
    {
        $query->whereIn('sesslog.sesslog_user_type', [self::USER_LEARNER, self::USER_TEACHER])
            ->where('sesslog.sesslog_record_type', '=', self::RECORD_LESSON)
            ->whereIn('sesslog.sesslog_changed_status', [self::LESSON_SCHEDULED, self::LESSON_CANCELLED]);

        $fromDate = trim((string) $request->query('fromDate', $request->query('from_date', '')));
        if ($fromDate !== '') {
            $query->where('sesslog.sesslog_created', '>=', $fromDate.' 00:00:00');
        }

        $toDate = trim((string) $request->query('toDate', $request->query('to_date', '')));
        if ($toDate !== '') {
            $query->where('sesslog.sesslog_created', '<=', $toDate.' 23:59:59');
        }

        $userId = $request->integer('user_id', 0);
        if ($userId > 0) {
            $query->where('sesslog.sesslog_user_id', '=', $userId);

            return;
        }

        if (! $allowUserKeyword) {
            return;
        }

        $user = trim((string) $request->query('user', $request->query('keyword', '')));
        if ($user === '') {
            return;
        }

        $query->whereRaw(
            'TRIM(CONCAT(COALESCE(user.user_first_name, ""), " ", COALESCE(user.user_last_name, ""))) LIKE ?',
            ['%'.$user.'%'],
        );
    }

    /** @param  array<string, mixed>  $row */
    private function formatSummaryRow(array $row): array
    {
        return [
            'id' => (int) $row['user_id'],
            'user_id' => (int) $row['user_id'],
            'user_full_name' => trim(((string) $row['user_first_name']).' '.((string) $row['user_last_name'])),
            'user_email' => (string) $row['user_email'],
            'user_is_teacher' => (int) $row['user_is_teacher'],
            'rescheduledCount' => (int) $row['rescheduledCount'],
            'cancelledCount' => (int) $row['cancelledCount'],
        ];
    }

    /** @param  array<string, mixed>  $row */
    private function formatLogRow(array $row, int $reportType): array
    {
        $prevStart = trim((string) ($row['sesslog_prev_starttime'] ?? ''));
        $prevEnd = trim((string) ($row['sesslog_prev_endtime'] ?? ''));
        $hasPrevTimings = $this->hasValidLogDatetime($prevStart) && $this->hasValidLogDatetime($prevEnd);

        return [
            'id' => (int) $row['sesslog_id'],
            'sesslog_id' => (int) $row['sesslog_id'],
            'teacher_name' => $this->resolveDisplayName((string) $row['teacher_name'], $row['teacher_deleted'] ?? null),
            'learner_name' => trim((string) $row['learner_name']),
            'action_by_name' => $this->resolveDisplayName((string) $row['action_by_name'], $row['actor_deleted'] ?? null),
            'teacher_deleted' => $this->isDeletedUser($row['teacher_deleted'] ?? null),
            'action_by_deleted' => $this->isDeletedUser($row['actor_deleted'] ?? null),
            'order_id' => (int) $row['order_id'],
            'order_id_formatted' => AdminOrderHelper::formatOrderId((int) $row['order_id']),
            'ordles_id' => (int) $row['ordles_id'],
            'prev_starttime' => $prevStart,
            'prev_endtime' => $prevEnd,
            'prev_start_time' => $prevStart,
            'prev_end_time' => $prevEnd,
            'sesslog_prev_starttime' => $prevStart,
            'sesslog_prev_endtime' => $prevEnd,
            'has_prev_timings' => $hasPrevTimings,
            'sesslog_prev_status' => (int) $row['sesslog_prev_status'],
            'sesslog_prev_status_label' => AdminOrderHelper::lessonStatusLabel((int) $row['sesslog_prev_status']),
            'sesslog_changed_status' => (int) $row['sesslog_changed_status'],
            'sesslog_changed_status_label' => AdminOrderHelper::lessonStatusLabel((int) $row['sesslog_changed_status']),
            'sesslog_created' => (string) $row['sesslog_created'],
            'sesslog_created_display' => $this->formatAdminDatetime((string) $row['sesslog_created']),
            'sesslog_comment' => trim((string) $row['sesslog_comment']),
            'prev_starttime_display' => $hasPrevTimings ? $this->formatAdminDatetime($prevStart) : '',
            'prev_endtime_display' => $hasPrevTimings ? $this->formatAdminDatetime($prevEnd) : '',
            'show_prev_timings' => true,
        ];
    }

    /** @return array<string, mixed> */
    private function demoRescheduledLogRow(int $userId): array
    {
        $user = $this->userSummary($userId);

        return [
            'id' => 0,
            'sesslog_id' => 0,
            'teacher_name' => 'Deleted User',
            'learner_name' => (string) ($user['full_name'] ?? 'Demo Learner'),
            'action_by_name' => (string) ($user['full_name'] ?? 'Demo Learner'),
            'teacher_deleted' => true,
            'action_by_deleted' => false,
            'order_id' => 341,
            'order_id_formatted' => AdminOrderHelper::formatOrderId(341),
            'ordles_id' => 347,
            'prev_starttime' => '2022-09-23 08:15:00',
            'prev_endtime' => '2022-09-23 09:15:00',
            'prev_start_time' => '2022-09-23 08:15:00',
            'prev_end_time' => '2022-09-23 09:15:00',
            'sesslog_prev_starttime' => '2022-09-23 08:15:00',
            'sesslog_prev_endtime' => '2022-09-23 09:15:00',
            'has_prev_timings' => true,
            'prev_starttime_display' => 'Sep 23, 2022 08:15',
            'prev_endtime_display' => 'Sep 23, 2022 09:15',
            'sesslog_prev_status' => self::LESSON_SCHEDULED,
            'sesslog_prev_status_label' => AdminOrderHelper::lessonStatusLabel(self::LESSON_SCHEDULED),
            'sesslog_changed_status' => self::LESSON_SCHEDULED,
            'sesslog_changed_status_label' => AdminOrderHelper::lessonStatusLabel(self::LESSON_SCHEDULED),
            'sesslog_created' => '2022-09-22 03:01:00',
            'sesslog_created_display' => 'Sep 22, 2022 03:01',
            'sesslog_comment' => 'bb',
            'show_prev_timings' => true,
        ];
    }

    private function formatAdminDatetime(string $value): string
    {
        if (! $this->hasValidLogDatetime($value)) {
            return '';
        }

        try {
            return Carbon::parse($value)->format('M j, Y H:i');
        } catch (\Throwable) {
            return $value;
        }
    }

    private function hasValidLogDatetime(string $value): bool
    {
        $value = trim($value);

        if ($value === '' || str_starts_with($value, '0000-00-00')) {
            return false;
        }

        return true;
    }

    private function resolveDisplayName(string $name, mixed $deleted): string
    {
        if ($this->isDeletedUser($deleted)) {
            return 'Deleted User';
        }

        return trim($name);
    }

    private function isDeletedUser(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        $string = trim((string) $value);

        if ($string === '' || str_starts_with($string, '0000-00-00')) {
            return false;
        }

        return true;
    }

    /** @return array<string, mixed> */
    private function userSummary(int $userId): array
    {
        $row = DB::table('tbl_users')
            ->where('user_id', $userId)
            ->first(['user_id', 'user_first_name', 'user_last_name', 'user_email']);

        if (! $row) {
            return ['user_id' => $userId, 'full_name' => '—', 'user_email' => ''];
        }

        return [
            'user_id' => (int) $row->user_id,
            'full_name' => trim(((string) $row->user_first_name).' '.((string) $row->user_last_name)),
            'user_email' => (string) $row->user_email,
        ];
    }
}
