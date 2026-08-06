<?php

namespace App\Services\Admin\Listings;

use App\Services\Admin\AdminGroupClassManageService;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminGroupClassListingService
{
    use AdminListingSupport;

    public const TYPE_REGULAR = 1;

    public const TYPE_PACKAGE = 2;

    public const STATUS_SCHEDULED = 1;

    public const STATUS_COMPLETED = 2;

    public const STATUS_CANCELLED = 3;

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    public function groupClasses(Request $request): array
    {
        $query = $this->baseQuery($request);
        $query->where('gc.grpcls_type', self::TYPE_REGULAR);
        $this->applyGroupClassFilters($request, $query);

        return $this->runGroupClassQuery($request, $query);
    }

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    public function packageClasses(Request $request): array
    {
        $query = $this->baseQuery($request);
        $query->where('gc.grpcls_type', self::TYPE_PACKAGE)
            ->where('gc.grpcls_parent', 0);
        $this->applyGroupClassFilters($request, $query, false);

        return $this->runGroupClassQuery($request, $query);
    }

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    public function learners(Request $request, int $classId): array
    {
        $class = DB::table('tbl_group_classes')
            ->where('grpcls_id', $classId)
            ->first(['grpcls_id', 'grpcls_type']);

        if (! $class) {
            return $this->paginateResult($request, [], 0);
        }

        $page = max(1, $request->integer('page', 1));
        $perPage = $this->adminPageSize($request);

        if ((int) $class->grpcls_type === self::TYPE_PACKAGE) {
            $query = DB::table('tbl_order_packages as ordpkg')
                ->join('tbl_orders as o', 'o.order_id', '=', 'ordpkg.ordpkg_order_id')
                ->join('tbl_users as u', 'u.user_id', '=', 'o.order_user_id')
                ->where('ordpkg.ordpkg_package_id', $classId)
                ->where('ordpkg.ordpkg_status', '!=', 3)
                ->where('o.order_status', 2)
                ->where('o.order_payment_status', 1)
                ->select([
                    'u.user_id as id',
                    'u.user_first_name',
                    'u.user_last_name',
                    DB::raw('CONCAT(u.user_first_name, " ", COALESCE(u.user_last_name, "")) as full_name'),
                    'u.user_email as email',
                ])
                ->groupBy('u.user_id', 'u.user_first_name', 'u.user_last_name', 'u.user_email');
        } else {
            $query = DB::table('tbl_order_classes as oc')
                ->join('tbl_orders as o', 'o.order_id', '=', 'oc.ordcls_order_id')
                ->join('tbl_users as u', 'u.user_id', '=', 'o.order_user_id')
                ->where('oc.ordcls_grpcls_id', $classId)
                ->where('oc.ordcls_status', '!=', 3)
                ->where('o.order_status', 2)
                ->where('o.order_payment_status', 1)
                ->select([
                    'u.user_id as id',
                    'u.user_first_name',
                    'u.user_last_name',
                    DB::raw('CONCAT(u.user_first_name, " ", COALESCE(u.user_last_name, "")) as full_name'),
                    'u.user_email as email',
                ])
                ->groupBy('u.user_id', 'u.user_first_name', 'u.user_last_name', 'u.user_email');
        }

        $total = (clone $query)->get()->count();
        $rows = $query
            ->orderBy('u.user_first_name')
            ->forPage($page, $perPage)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        return $this->paginateResult($request, $rows, $total);
    }

    private function baseQuery(Request $request): Builder
    {
        $langId = $this->langId($request);

        return DB::table('tbl_group_classes as gc')
            ->join('tbl_users as t', 't.user_id', '=', 'gc.grpcls_teacher_id')
            ->leftJoin('tbl_group_classes_lang as gcl', function ($join) use ($langId) {
                $join->on('gcl.gclang_grpcls_id', '=', 'gc.grpcls_id')
                    ->where('gcl.gclang_lang_id', '=', $langId);
            })
            ->select([
                'gc.grpcls_id as id',
                DB::raw('IFNULL(gcl.grpcls_title, gc.grpcls_title) as title'),
                'gc.grpcls_type as class_type',
                'gc.grpcls_parent as parent_id',
                'gc.grpcls_offline as offline',
                DB::raw('CONCAT(t.user_first_name, " ", COALESCE(t.user_last_name, "")) as teacher_name'),
                'gc.grpcls_entry_fee as entry_fee',
                'gc.grpcls_start_datetime as start_at',
                'gc.grpcls_end_datetime as end_at',
                'gc.grpcls_added_on as created_at',
                'gc.grpcls_status as status',
                DB::raw('1 as created_by_type'),
            ]);
    }

    private function applyGroupClassFilters(Request $request, Builder $query, bool $includeClassType = true): void
    {
        $keyword = trim((string) $request->query('keyword', ''));
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where(DB::raw('CONCAT(t.user_first_name, " ", COALESCE(t.user_last_name, ""))'), 'like', "%{$keyword}%")
                    ->orWhere('t.user_username', 'like', "%{$keyword}%")
                    ->orWhere('t.user_email', 'like', "%{$keyword}%")
                    ->orWhere('gcl.grpcls_title', 'like', "%{$keyword}%")
                    ->orWhere('gc.grpcls_title', 'like', "%{$keyword}%");
            });
        }

        $teacher = trim((string) $request->query('teacher', ''));
        if ($teacher !== '') {
            $query->where(function ($q) use ($teacher) {
                $q->where(DB::raw('CONCAT(t.user_first_name, " ", COALESCE(t.user_last_name, ""))'), 'like', "%{$teacher}%")
                    ->orWhere('t.user_username', 'like', "%{$teacher}%")
                    ->orWhere('t.user_email', 'like', "%{$teacher}%");
            });
        }

        $teacherId = $request->query('teacher_id', '');
        if ($teacherId !== '' && $teacherId !== null) {
            $query->where('t.user_id', (int) $teacherId);
        }

        $startFrom = trim((string) $request->query('grpcls_start_datetime', $request->query('start_from', '')));
        if ($startFrom !== '') {
            $query->where('gc.grpcls_start_datetime', '>=', $startFrom.' 00:00:00');
        }

        $endTo = trim((string) $request->query('grpcls_end_datetime', $request->query('end_to', '')));
        if ($endTo !== '') {
            $query->where('gc.grpcls_end_datetime', '<=', $endTo.' 23:59:59');
        }

        $parent = $request->query('grpcls_parent', '');
        if ($parent !== '' && $parent !== null) {
            $query->where('gc.grpcls_parent', (int) $parent);
        }

        $status = $request->query('grpcls_status', $request->query('status', ''));
        if ($status !== '' && $status !== null) {
            $query->where('gc.grpcls_status', (int) $status);
        }

        $offline = $request->query('grpcls_offline', '');
        if ($offline !== '' && $offline !== null) {
            $query->where('gc.grpcls_offline', (int) $offline);
        }

        $createdByType = $request->query('created_by_type', '');
        if ($createdByType !== '' && $createdByType !== null) {
            $query->whereRaw('1 = ?', [(int) $createdByType]);
        }

        if ($includeClassType) {
            $classType = $request->query('grpcls_type', '');
            if ($classType !== '' && $classType !== null) {
                if ((int) $classType === self::TYPE_PACKAGE) {
                    $query->where('gc.grpcls_parent', '>', 0);
                } else {
                    $query->where('gc.grpcls_type', (int) $classType)
                        ->where('gc.grpcls_parent', 0);
                }
            }
        }
    }

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    private function runGroupClassQuery(Request $request, Builder $query): array
    {
        $page = max(1, $request->integer('page', 1));
        $perPage = $this->adminPageSize($request);
        $total = (clone $query)->count();
        $rows = $query
            ->orderByDesc('gc.grpcls_start_datetime')
            ->orderByDesc('gc.grpcls_id')
            ->forPage($page, $perPage)
            ->get()
            ->map(function ($row) {
                $data = (array) $row;
                $data['status_label'] = $this->statusLabel((int) $data['status']);
                $data['class_type_label'] = $this->classTypeLabel((int) $data['class_type'], (int) ($data['parent_id'] ?? 0));
                $data['service_type_label'] = (int) ($data['offline'] ?? 0) === 1 ? 'Offline' : 'Online';
                $addedBy = (int) ($data['created_by_type'] ?? AdminGroupClassManageService::ADDED_BY_TEACHER);
                $data['created_by_type'] = $addedBy;
                $data['created_by_label'] = $addedBy === AdminGroupClassManageService::ADDED_BY_ADMIN ? 'Admin' : 'Teacher';

                return $data;
            })
            ->all();

        return $this->paginateResult($request, $rows, $total);
    }

    public function statusLabel(int $status): string
    {
        return match ($status) {
            self::STATUS_SCHEDULED => 'Scheduled',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
            default => (string) $status,
        };
    }

    public function classTypeLabel(int $type, int $parentId = 0): string
    {
        if ($parentId > 0) {
            return 'Package';
        }

        return match ($type) {
            self::TYPE_REGULAR => 'Regular',
            self::TYPE_PACKAGE => 'Package',
            default => (string) $type,
        };
    }
}
