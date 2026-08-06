<?php

namespace App\Services\Admin\Listings;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminCommissionListingService
{
    use AdminListingSupport;

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int|bool>} */
    public function search(Request $request): array
    {
        $page = max(1, $request->integer('page', 1));
        $perPage = $this->adminPageSize($request);

        $query = DB::table('tbl_admin_commissions as comm')
            ->leftJoin('tbl_users as user', 'comm.comm_user_id', '=', 'user.user_id')
            ->orderByRaw('comm.comm_user_id IS NULL DESC')
            ->orderByDesc('comm.comm_id')
            ->select([
                'comm.comm_id as id',
                'comm.comm_lessons',
                'comm.comm_classes',
                'comm.comm_courses',
                'comm.comm_user_id as teacher_user_id',
                'user.user_id',
                'user.user_first_name',
                'user.user_last_name',
            ]);

        $keyword = trim((string) $request->query('keyword', ''));
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('user.user_first_name', 'like', "%{$keyword}%")
                    ->orWhere('user.user_last_name', 'like', "%{$keyword}%")
                    ->orWhereRaw('CONCAT(user.user_first_name, " ", user.user_last_name) like ?', ["%{$keyword}%"]);
            });
        }

        $total = (clone $query)->count('comm.comm_id');
        $flags = $this->featureFlags();

        $rows = $query->forPage($page, $perPage)->get()->map(function ($row) {
            $isGlobal = empty($row->teacher_user_id);
            $name = $isGlobal
                ? ''
                : trim((string) $row->user_first_name.' '.(string) ($row->user_last_name ?? ''));

            return [
                'id' => (int) $row->id,
                'user_id' => $isGlobal ? 0 : (int) ($row->user_id ?? 0),
                'is_global' => $isGlobal,
                'teacher_name' => $name,
                'comm_lessons' => $this->formatRate($row->comm_lessons),
                'comm_classes' => $this->formatRate($row->comm_classes),
                'comm_courses' => $this->formatRate($row->comm_courses),
            ];
        })->all();

        return [
            'data' => $rows,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage) ?: 1,
                'classes_enabled' => $flags['classes_enabled'],
                'courses_enabled' => $flags['courses_enabled'],
            ],
        ];
    }

    /** @return array{classes_enabled: bool, courses_enabled: bool} */
    private function featureFlags(): array
    {
        $courses = (int) DB::table('tbl_configurations')
            ->where('conf_name', 'CONF_ENABLE_COURSES')
            ->value('conf_val');

        $classes = (int) DB::table('tbl_configurations')
            ->where('conf_name', 'CONF_GROUP_CLASSES_DISABLED')
            ->value('conf_val');

        return [
            'classes_enabled' => $classes === 1,
            'courses_enabled' => $courses === 1,
        ];
    }

    private function formatRate(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        return number_format((float) $value, 2, '.', '');
    }
}
