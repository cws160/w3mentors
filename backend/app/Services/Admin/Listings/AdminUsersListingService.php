<?php

namespace App\Services\Admin\Listings;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminUsersListingService
{
    private const USER_LEARNER = 1;

    private const USER_TEACHER = 2;

    private const USER_AFFILIATE = 5;

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    public function search(Request $request): array
    {
        $page = max(1, $request->integer('page', 1));
        $perPage = (int) DB::table('tbl_configurations')
            ->where('conf_name', 'CONF_ADMIN_PAGESIZE')
            ->value('conf_val') ?: 10;
        $perPage = min(50, max(1, $perPage));
        if ($request->boolean('export')) {
            $page = 1;
            $perPage = 5000;
        }

        $query = DB::table('tbl_users as user')
            ->leftJoin('tbl_user_settings as uset', 'uset.user_id', '=', 'user.user_id')
            ->whereNull('user.user_deleted')
            ->select([
                'user.user_id as id',
                'user.user_email as email',
                'user.user_username as username',
                'user.user_verified as verified',
                'user.user_active as active',
                'user.user_created as created_at',
                'user.user_is_teacher as is_teacher',
                'user.user_is_affiliate as is_affiliate',
                'uset.user_registered_as as registered_as',
                'user.user_featured as featured',
                'uset.user_phone_code as phone_code',
                'uset.user_phone_number as phone_number',
                DB::raw('CONCAT(user.user_first_name, " ", COALESCE(user.user_last_name, "")) as full_name'),
            ]);

        $keyword = trim((string) $request->query('keyword', ''));
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->whereRaw('CONCAT(user.user_first_name, " ", COALESCE(user.user_last_name, "")) LIKE ?', ["%{$keyword}%"])
                    ->orWhere('user.user_username', 'like', "%{$keyword}%")
                    ->orWhere('user.user_email', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('user_id')) {
            $query->where('user.user_id', $request->integer('user_id'));
        }

        if ($request->has('user_active') && $request->query('user_active') !== '') {
            $query->where('user.user_active', $request->integer('user_active'));
        }

        if ($request->has('user_verified') && $request->query('user_verified') !== '') {
            if ($request->integer('user_verified') === 1) {
                $query->whereNotNull('user.user_verified');
            } else {
                $query->whereNull('user.user_verified');
            }
        }

        if ($request->has('user_featured') && $request->query('user_featured') !== '') {
            $query->where('user.user_featured', $request->integer('user_featured'));
        }

        $type = $request->query('type', '');
        if ($type !== '' && $type !== '0') {
            $this->applyTypeFilter($query, $type);
        }

        if ($request->filled('user_regdate_from')) {
            $query->where('user.user_created', '>=', $request->query('user_regdate_from'));
        }
        if ($request->filled('user_regdate_to')) {
            $query->where('user.user_created', '<=', $request->query('user_regdate_to').' 23:59:59');
        }

        $total = (clone $query)->count();
        $rawRows = $query
            ->orderByDesc('user.user_active')
            ->orderByDesc('user.user_id')
            ->forPage($page, $perPage)
            ->get();

        $dialCodes = $this->resolveDialCodes(
            $rawRows->pluck('phone_code')->filter()->map(fn ($id) => (int) $id)->unique()->all()
        );

        $rows = $rawRows
            ->map(function ($row) use ($dialCodes) {
                $phoneCodeId = $row->phone_code !== null ? (int) $row->phone_code : 0;
                $dialCode = $phoneCodeId > 0 ? (string) ($dialCodes[$phoneCodeId] ?? '') : '';
                $phoneNumber = (string) ($row->phone_number ?? '');

                return [
                    'id' => (int) $row->id,
                    'full_name' => trim((string) $row->full_name),
                    'email' => (string) $row->email,
                    'username' => (string) $row->username,
                    'phone_code' => $phoneCodeId > 0 ? $phoneCodeId : null,
                    'phone_dial_code' => $dialCode,
                    'phone_number' => $phoneNumber,
                    'phone_display' => trim($dialCode.' '.$phoneNumber),
                    'verified' => $row->verified !== null,
                    'active' => (bool) $row->active,
                    'featured' => (bool) $row->featured,
                    'is_teacher' => (bool) $row->is_teacher,
                    'is_affiliate' => (bool) $row->is_affiliate,
                    'registered_as' => $row->registered_as !== null ? (int) $row->registered_as : null,
                    'created_at' => (string) $row->created_at,
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
            ],
        ];
    }

    /** @return array<int, array{id: int, full_name: string, email: string, username: string}> */
    public function autocomplete(string $keyword, int $limit = 20): array
    {
        $keyword = trim($keyword);
        if ($keyword === '') {
            return [];
        }

        return DB::table('tbl_users')
            ->whereNull('user_deleted')
            ->where(function ($q) use ($keyword) {
                $q->whereRaw('CONCAT(user_first_name, " ", COALESCE(user_last_name, "")) LIKE ?', ["%{$keyword}%"])
                    ->orWhere('user_username', 'like', "%{$keyword}%")
                    ->orWhere('user_email', 'like', "%{$keyword}%");
            })
            ->orderByRaw('CONCAT(user_first_name, " ", COALESCE(user_last_name, "")) ASC')
            ->limit(min(20, max(1, $limit)))
            ->get([
                'user_id as id',
                'user_email as email',
                'user_username as username',
                DB::raw('CONCAT(user_first_name, " ", COALESCE(user_last_name, "")) as full_name'),
            ])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'full_name' => trim((string) $row->full_name),
                'email' => (string) $row->email,
                'username' => (string) ($row->username ?? ''),
            ])
            ->all();
    }

    public function updateStatus(int $userId, int $active): bool
    {
        return DB::table('tbl_users')
            ->where('user_id', $userId)
            ->whereNull('user_deleted')
            ->update(['user_active' => $active ? 1 : 0]) > 0;
    }

    /**
     * @param  \Illuminate\Database\Query\Builder  $query
     */
    private function applyTypeFilter($query, mixed $type): void
    {
        $normalized = is_numeric($type) ? (int) $type : (string) $type;

        switch ($normalized) {
            case self::USER_TEACHER:
            case 'teacher':
                $query->where('user.user_is_teacher', 1);
                break;
            case self::USER_LEARNER:
            case 'learner':
                $query->where('user.user_is_affiliate', 0);
                break;
            case self::USER_AFFILIATE:
            case 'affiliate':
                $query->where('user.user_is_affiliate', 1);
                break;
        }
    }

    /**
     * @param  array<int, int>  $countryIds
     * @return array<int, string>
     */
    private function resolveDialCodes(array $countryIds): array
    {
        if ($countryIds === []) {
            return [];
        }

        return DB::table('tbl_countries')
            ->whereIn('country_id', $countryIds)
            ->pluck('country_dial_code', 'country_id')
            ->map(fn ($code) => (string) $code)
            ->all();
    }
}
