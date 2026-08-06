<?php

namespace App\Services\Admin\Listings;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminHoursTaughtReportListingService
{
    use AdminListingSupport;

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    public function search(Request $request): array
    {
        $baseQuery = DB::table('tbl_hours_taught_stats as hts')
            ->join('tbl_users as user', 'hts.hts_teacher_id', '=', 'user.user_id')
            ->where('user.user_is_teacher', '=', 1);

        $this->applyFilters($request, $baseQuery);

        $total = (clone $baseQuery)->distinct()->count('hts.hts_teacher_id');

        $page = max(1, $request->integer('page', 1));
        $perPage = $this->adminPageSize($request);

        $rows = (clone $baseQuery)
            ->select([
                'hts.hts_teacher_id as user_id',
                DB::raw('TRIM(CONCAT(COALESCE(user.user_first_name, ""), " ", COALESCE(user.user_last_name, ""))) as user_name'),
                DB::raw('SUM(IFNULL(hts.hts_lesson_duration, 0)) as hts_lesson_duration'),
                DB::raw('SUM(IFNULL(hts.hts_class_duration, 0)) as hts_class_duration'),
                DB::raw('SUM(IFNULL(hts.hts_lesson_duration, 0) + IFNULL(hts.hts_class_duration, 0)) as total_duration'),
            ])
            ->groupBy('hts.hts_teacher_id', 'user.user_first_name', 'user.user_last_name')
            ->orderBy('user.user_first_name')
            ->orderBy('hts.hts_teacher_id')
            ->forPage($page, $perPage)
            ->get()
            ->map(fn ($row) => $this->formatRow((array) $row))
            ->all();

        return $this->paginateResult($request, $rows, $total);
    }

    private function applyFilters(Request $request, Builder $query): void
    {
        $userId = $request->integer('user_id', 0);
        if ($userId > 0) {
            $query->where('user.user_id', '=', $userId);

            return;
        }

        $keyword = trim((string) $request->query('keyword', ''));
        if ($keyword !== '') {
            $query->whereRaw(
                'TRIM(CONCAT(COALESCE(user.user_first_name, ""), " ", COALESCE(user.user_last_name, ""))) LIKE ?',
                ['%'.$keyword.'%'],
            );
        }

        $fromDate = trim((string) $request->query('fromDate', $request->query('from_date', '')));
        if ($fromDate !== '') {
            $query->where('hts.hts_date', '>=', $fromDate.' 00:00:00');
        }

        $toDate = trim((string) $request->query('toDate', $request->query('to_date', '')));
        if ($toDate !== '') {
            $query->where('hts.hts_date', '<=', $toDate.' 23:59:59');
        }
    }

    /** @param  array<string, mixed>  $row */
    private function formatRow(array $row): array
    {
        $lessonMinutes = (int) ($row['hts_lesson_duration'] ?? 0);
        $classMinutes = (int) ($row['hts_class_duration'] ?? 0);
        $totalMinutes = (int) ($row['total_duration'] ?? 0);

        return [
            'id' => (int) $row['user_id'],
            'user_id' => (int) $row['user_id'],
            'user_name' => (string) $row['user_name'],
            'hts_lesson_duration' => $this->formatDurationLabel($lessonMinutes),
            'hts_class_duration' => $this->formatDurationLabel($classMinutes),
            'total_duration' => $this->formatDurationLabel($totalMinutes),
        ];
    }

    private function formatDurationLabel(int $minutes): string
    {
        if ($minutes <= 0) {
            return 'N/A';
        }

        return $this->formatDuration($minutes * 60, true);
    }

    private function formatDuration(int $seconds, bool $withSeconds = false): string
    {
        if ($seconds <= 0) {
            return '00m';
        }

        $parts = [];
        $hrs = intdiv($seconds, 3600);
        if ($hrs > 0) {
            $parts[] = $hrs.'h';
        }

        $min = (int) gmdate('i', $seconds);
        if ($min > 0) {
            $parts[] = $min.'m';
        }

        if ($withSeconds) {
            $sec = (int) gmdate('s', $seconds);
            if ($sec > 0) {
                $parts[] = $sec.'s';
            }
        }

        return count($parts) > 0 ? implode(' ', $parts) : '00m';
    }
}
