<?php

namespace App\Services\Admin\Listings;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminTeacherPerformanceListingService
{
    use AdminListingSupport;

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    public function search(Request $request): array
    {
        $groupClassesEnabled = $this->groupClassesEnabled();
        $coursesEnabled = $this->coursesEnabled();

        $query = DB::table('tbl_users as teacher')
            ->join('tbl_teacher_stats as testat', 'testat.testat_user_id', '=', 'teacher.user_id')
            ->where('teacher.user_is_teacher', '=', 1)
            ->select([
                'teacher.user_id as id',
                'teacher.user_id as user_id',
                DB::raw('TRIM(CONCAT(COALESCE(teacher.user_first_name, ""), " ", COALESCE(teacher.user_last_name, ""))) as teacher_name'),
                'testat.testat_ratings',
                'testat.testat_reviewes',
                'testat.testat_students',
                'testat.testat_lessons',
                'testat.testat_classes',
                'testat.testat_courses',
            ]);

        $this->applyFilters($request, $query, $groupClassesEnabled, $coursesEnabled);

        $query->orderByDesc('testat.testat_ratings')
            ->orderByDesc('testat.testat_students')
            ->orderByDesc('testat.testat_lessons')
            ->orderBy('teacher.user_id');

        $page = max(1, $request->integer('page', 1));
        $perPage = $this->adminPageSize($request);
        $total = (clone $query)->count();
        $rows = $query
            ->forPage($page, $perPage)
            ->get()
            ->map(fn ($row) => $this->formatRow((array) $row))
            ->all();

        return $this->paginateResult($request, $rows, $total);
    }

    private function applyFilters(
        Request $request,
        Builder $query,
        bool $groupClassesEnabled,
        bool $coursesEnabled,
    ): void {
        $userId = $request->integer('user_id', 0);
        if ($userId > 0) {
            $query->where('teacher.user_id', '=', $userId);
        } else {
            $keyword = trim((string) $request->query('keyword', ''));
            if ($keyword !== '') {
                $query->whereRaw(
                    'TRIM(CONCAT(COALESCE(teacher.user_first_name, ""), " ", COALESCE(teacher.user_last_name, ""))) LIKE ?',
                    ['%'.$keyword.'%'],
                );
            }
        }

        $query->where(function (Builder $q) use ($groupClassesEnabled, $coursesEnabled) {
            $q->where('testat.testat_students', '>', 0)
                ->orWhere('testat.testat_lessons', '>', 0);

            if ($groupClassesEnabled) {
                $q->orWhere('testat.testat_classes', '>', 0);
            }

            if ($coursesEnabled) {
                $q->orWhere('testat.testat_courses', '>', 0);
            }
        });
    }

    /** @param  array<string, mixed>  $row */
    private function formatRow(array $row): array
    {
        return [
            'id' => (int) $row['user_id'],
            'user_id' => (int) $row['user_id'],
            'teacher_name' => (string) $row['teacher_name'],
            'testat_lessons' => (int) $row['testat_lessons'],
            'testat_classes' => (int) $row['testat_classes'],
            'testat_courses' => (int) $row['testat_courses'],
            'testat_students' => (int) $row['testat_students'],
            'testat_reviewes' => (int) $row['testat_reviewes'],
            'testat_ratings' => number_format((float) $row['testat_ratings'], 2, '.', ''),
        ];
    }

    private function groupClassesEnabled(): bool
    {
        return (int) DB::table('tbl_configurations')
            ->where('conf_name', 'CONF_GROUP_CLASSES_DISABLED')
            ->value('conf_val') === 1;
    }

    private function coursesEnabled(): bool
    {
        return (int) DB::table('tbl_configurations')
            ->where('conf_name', 'CONF_ENABLE_COURSES')
            ->value('conf_val') === 1;
    }
}
