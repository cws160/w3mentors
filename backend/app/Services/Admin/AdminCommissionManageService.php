<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\DB;

class AdminCommissionManageService
{
    /** @return array<string, mixed>|null */
    public function show(int $commissionId): ?array
    {
        if ($commissionId < 1) {
            return [
                'comm_id' => 0,
                'comm_user_id' => 0,
                'comm_lessons' => '',
                'comm_classes' => '',
                'comm_courses' => '',
                'user_name' => '',
                'is_global' => false,
                'classes_enabled' => $this->featureFlags()['classes_enabled'],
                'courses_enabled' => $this->featureFlags()['courses_enabled'],
            ];
        }

        $row = DB::table('tbl_admin_commissions as comm')
            ->leftJoin('tbl_users as user', 'comm.comm_user_id', '=', 'user.user_id')
            ->where('comm.comm_id', $commissionId)
            ->first([
                'comm.comm_id',
                'comm.comm_user_id',
                'comm.comm_lessons',
                'comm.comm_classes',
                'comm.comm_courses',
                'user.user_first_name',
                'user.user_last_name',
            ]);

        if (! $row) {
            return null;
        }

        $flags = $this->featureFlags();
        $isGlobal = empty($row->comm_user_id);
        $userName = $isGlobal
            ? ''
            : trim((string) $row->user_first_name.' '.(string) ($row->user_last_name ?? ''));

        return [
            'comm_id' => (int) $row->comm_id,
            'comm_user_id' => $isGlobal ? 0 : (int) $row->comm_user_id,
            'comm_lessons' => (float) $row->comm_lessons,
            'comm_classes' => (float) $row->comm_classes,
            'comm_courses' => (float) $row->comm_courses,
            'user_name' => $userName,
            'is_global' => $isGlobal,
            'classes_enabled' => $flags['classes_enabled'],
            'courses_enabled' => $flags['courses_enabled'],
        ];
    }

    /** @param array<string, mixed> $payload @return array{ok: bool, message?: string, comm_id?: int} */
    public function setup(array $payload): array
    {
        $commissionId = (int) ($payload['comm_id'] ?? 0);
        $userId = (int) ($payload['comm_user_id'] ?? 0);
        $lessons = (float) ($payload['comm_lessons'] ?? 0);
        $classes = (float) ($payload['comm_classes'] ?? 0);
        $courses = (float) ($payload['comm_courses'] ?? 0);
        $flags = $this->featureFlags();

        foreach ([
            'lessons' => $lessons,
            'classes' => $flags['classes_enabled'] ? $classes : 0,
            'courses' => $flags['courses_enabled'] ? $courses : 0,
        ] as $label => $rate) {
            if ($rate < 1 || $rate > 100) {
                return ['ok' => false, 'message' => ucfirst($label).' commission must be between 1 and 100'];
            }
        }

        if ($commissionId < 1 && $userId < 1) {
            $existingGlobal = $this->findCommissionForUser(0);
            if ($existingGlobal) {
                return ['ok' => false, 'message' => 'Global commission already exists'];
            }
        }

        $existing = $this->findCommissionForUser($userId);
        if ($existing) {
            $commissionId = (int) $existing->comm_id;
        }

        $data = [
            'comm_user_id' => $userId > 0 ? $userId : null,
            'comm_lessons' => $lessons,
            'comm_classes' => $flags['classes_enabled'] ? $classes : 0,
            'comm_courses' => $flags['courses_enabled'] ? $courses : 0,
            'comm_created' => now()->format('Y-m-d H:i:s'),
        ];

        if ($commissionId > 0) {
            if (! DB::table('tbl_admin_commissions')->where('comm_id', $commissionId)->exists()) {
                return ['ok' => false, 'message' => 'Invalid request'];
            }
            DB::table('tbl_admin_commissions')->where('comm_id', $commissionId)->update($data);
        } else {
            $commissionId = (int) DB::table('tbl_admin_commissions')->insertGetId($data);
        }

        DB::table('tbl_commission_history')->insert([
            'comhis_user_id' => $userId > 0 ? $userId : null,
            'comhis_lessons' => $lessons,
            'comhis_classes' => $flags['classes_enabled'] ? $classes : 0,
            'comhis_courses' => $flags['courses_enabled'] ? $courses : 0,
            'comhis_created' => now()->format('Y-m-d H:i:s'),
        ]);

        return ['ok' => true, 'comm_id' => $commissionId];
    }

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int|bool>} */
    public function history(int $userId, int $page = 1): array
    {
        $perPage = (int) DB::table('tbl_configurations')
            ->where('conf_name', 'CONF_ADMIN_PAGESIZE')
            ->value('conf_val');
        $perPage = $perPage > 0 ? $perPage : 10;
        $page = max(1, $page);
        $flags = $this->featureFlags();

        $query = DB::table('tbl_commission_history as h')
            ->leftJoin('tbl_users as user', 'h.comhis_user_id', '=', 'user.user_id')
            ->orderByDesc('h.comhis_id')
            ->select([
                'h.comhis_lessons',
                'h.comhis_classes',
                'h.comhis_courses',
                'h.comhis_created as created_at',
                'h.comhis_user_id as teacher_user_id',
                'user.user_id',
                'user.user_first_name',
                'user.user_last_name',
            ]);

        if ($userId < 1) {
            $query->whereNull('h.comhis_user_id');
        } else {
            $query->where('h.comhis_user_id', $userId);
        }

        $total = (clone $query)->count('h.comhis_id');

        $rows = $query
            ->forPage($page, $perPage)
            ->get()
            ->map(function ($row) {
                $isGlobal = empty($row->teacher_user_id);

                return [
                    'user_id' => $isGlobal ? 0 : (int) ($row->user_id ?? 0),
                    'is_global' => $isGlobal,
                    'teacher_name' => $isGlobal
                        ? ''
                        : trim((string) $row->user_first_name.' '.(string) ($row->user_last_name ?? '')),
                    'comm_lessons' => number_format((float) $row->comhis_lessons, 2, '.', ''),
                    'comm_classes' => number_format((float) $row->comhis_classes, 2, '.', ''),
                    'comm_courses' => number_format((float) $row->comhis_courses, 2, '.', ''),
                    'created_at' => (string) ($row->created_at ?? ''),
                ];
            })
            ->all();

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

    /** @return array<int, array<string, mixed>> */
    public function autocomplete(string $keyword): array
    {
        $keyword = trim($keyword);
        if ($keyword === '') {
            return [];
        }

        return DB::table('tbl_users as user')
            ->leftJoin('tbl_admin_commissions as comm', 'user.user_id', '=', 'comm.comm_user_id')
            ->where('user.user_is_teacher', 1)
            ->whereNull('user.user_deleted')
            ->whereNull('comm.comm_user_id')
            ->where(function ($q) use ($keyword) {
                $q->where('user.user_username', 'like', "%{$keyword}%")
                    ->orWhere('user.user_email', 'like', "%{$keyword}%")
                    ->orWhereRaw('CONCAT(user.user_first_name, " ", user.user_last_name) like ?', ["%{$keyword}%"]);
            })
            ->orderByRaw('CONCAT(user.user_first_name, " ", user.user_last_name) ASC')
            ->limit(30)
            ->get([
                'user.user_id as id',
                'user.user_email as email',
                'user.user_username as username',
                DB::raw('TRIM(CONCAT(user.user_first_name, " ", COALESCE(user.user_last_name, ""))) as full_name'),
            ])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'email' => (string) ($row->email ?? ''),
                'username' => (string) ($row->username ?? ''),
                'full_name' => (string) ($row->full_name ?? ''),
            ])
            ->all();
    }

    private function findCommissionForUser(int $userId): ?object
    {
        $query = DB::table('tbl_admin_commissions');
        if ($userId > 0) {
            $query->where('comm_user_id', $userId);
        } else {
            $query->whereNull('comm_user_id');
        }

        return $query->first(['comm_id', 'comm_user_id']);
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
}
