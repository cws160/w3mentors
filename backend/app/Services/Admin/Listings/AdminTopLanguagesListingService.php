<?php

namespace App\Services\Admin\Listings;

use App\Services\Admin\AdminOrderHelper;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminTopLanguagesListingService
{
    use AdminListingSupport;

    private const LESSON_FREE_TRIAL = 1;

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    public function lessonLanguages(Request $request): array
    {
        $langId = $this->langId($request);

        $query = DB::table('tbl_orders as orders')
            ->join('tbl_order_lessons as ordles', 'orders.order_id', '=', 'ordles.ordles_order_id')
            ->join('tbl_teach_languages as tlang', 'tlang.tlang_id', '=', 'ordles.ordles_tlang_id')
            ->leftJoin('tbl_teach_languages_lang as tlanglang', function ($join) use ($langId) {
                $join->on('tlanglang.tlanglang_tlang_id', '=', 'tlang.tlang_id')
                    ->where('tlanglang.tlanglang_lang_id', '=', $langId);
            })
            ->whereIn('orders.order_type', [AdminOrderHelper::TYPE_LESSON, AdminOrderHelper::TYPE_SUBSCR])
            ->where('orders.order_payment_status', '=', AdminOrderHelper::ISPAID)
            ->where('ordles.ordles_type', '!=', self::LESSON_FREE_TRIAL)
            ->select([
                'ordles.ordles_tlang_id as tlang_id',
                DB::raw('COUNT(ordles.ordles_tlang_id) AS totalsold'),
                DB::raw('COUNT(IF(ordles.ordles_status = '.AdminOrderHelper::LESSON_UNSCHEDULED.', 1, NULL)) AS unscheduled'),
                DB::raw('COUNT(IF(ordles.ordles_status = '.AdminOrderHelper::LESSON_SCHEDULED.', 1, NULL)) AS scheduled'),
                DB::raw('COUNT(IF(ordles.ordles_status = '.AdminOrderHelper::LESSON_COMPLETED.', 1, NULL)) AS completed'),
                DB::raw('COUNT(IF(ordles.ordles_status = '.AdminOrderHelper::LESSON_CANCELLED.', 1, NULL)) AS cancelled'),
            ])
            ->groupBy('ordles.ordles_tlang_id')
            ->orderByDesc('totalsold')
            ->orderBy('ordles.ordles_tlang_id');

        $this->applyLessonLanguageFilters($request, $query, $langId);

        return $this->paginateGroupedRows($request, $query, $langId);
    }

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    public function classLanguages(Request $request): array
    {
        $langId = $this->langId($request);

        $query = DB::table('tbl_orders as orders')
            ->join('tbl_order_classes as ordcls', 'orders.order_id', '=', 'ordcls.ordcls_order_id')
            ->join('tbl_group_classes as grpcls', 'grpcls.grpcls_id', '=', 'ordcls.ordcls_grpcls_id')
            ->join('tbl_teach_languages as tlang', 'tlang.tlang_id', '=', 'grpcls.grpcls_tlang_id')
            ->leftJoin('tbl_teach_languages_lang as tlanglang', function ($join) use ($langId) {
                $join->on('tlanglang.tlanglang_tlang_id', '=', 'tlang.tlang_id')
                    ->where('tlanglang.tlanglang_lang_id', '=', $langId);
            })
            ->whereIn('orders.order_type', [AdminOrderHelper::TYPE_GCLASS, AdminOrderHelper::TYPE_PACKGE])
            ->where('orders.order_payment_status', '=', AdminOrderHelper::ISPAID)
            ->select([
                'grpcls.grpcls_tlang_id as tlang_id',
                DB::raw('COUNT(grpcls.grpcls_tlang_id) AS totalsold'),
                DB::raw('COUNT(IF(ordcls.ordcls_status = 1, 1, NULL)) AS scheduled'),
                DB::raw('COUNT(IF(ordcls.ordcls_status = 2, 1, NULL)) AS completed'),
                DB::raw('COUNT(IF(ordcls.ordcls_status = 3, 1, NULL)) AS cancelled'),
            ])
            ->groupBy('grpcls.grpcls_tlang_id')
            ->orderByDesc('totalsold')
            ->orderBy('grpcls.grpcls_tlang_id');

        $this->applyClassLanguageFilters($request, $query, $langId);

        return $this->paginateGroupedRows($request, $query, $langId);
    }

    private function applyLessonLanguageFilters(Request $request, Builder $query, int $langId): void
    {
        $this->applyTeachLanguageFilter(
            $query,
            'ordles.ordles_tlang_id',
            (int) $request->integer('ordles_tlang_id', 0),
            trim((string) $request->query('ordles_tlang', '')),
            $langId,
        );
        $this->applyOrderDateFilters($request, $query);
    }

    private function applyClassLanguageFilters(Request $request, Builder $query, int $langId): void
    {
        $this->applyTeachLanguageFilter(
            $query,
            'grpcls.grpcls_tlang_id',
            (int) $request->integer('grpcls_tlang_id', 0),
            trim((string) $request->query('grpcls_tlang', '')),
            $langId,
        );
        $this->applyOrderDateFilters($request, $query);
    }

    private function applyTeachLanguageFilter(
        Builder $query,
        string $tlangColumn,
        int $tlangId,
        string $keyword,
        int $langId,
    ): void {
        if ($tlangId > 0) {
            $query->where(function (Builder $q) use ($tlangColumn, $tlangId) {
                $q->where($tlangColumn, '=', $tlangId)
                    ->orWhereRaw('FIND_IN_SET(?, tlang.tlang_parentids)', [$tlangId]);
            });

            return;
        }

        if ($keyword === '') {
            return;
        }

        $ids = $this->searchTeachLanguageIds($keyword, $langId);

        $query->where(function (Builder $q) use ($keyword, $ids) {
            $q->where('tlanglang.tlang_name', 'like', "%{$keyword}%")
                ->orWhere('tlang.tlang_identifier', 'like', "%{$keyword}%");

            if ($ids !== []) {
                $q->orWhereIn('tlang.tlang_id', $ids);
                foreach ($ids as $id) {
                    $q->orWhereRaw('FIND_IN_SET(?, tlang.tlang_parentids)', [$id]);
                }
            }
        });
    }

    private function applyOrderDateFilters(Request $request, Builder $query): void
    {
        $from = trim((string) $request->query('order_addedon_from', ''));
        if ($from !== '') {
            $query->where('orders.order_addedon', '>=', $from.' 00:00:00');
        }

        $to = trim((string) $request->query('order_addedon_to', ''));
        if ($to !== '') {
            $query->where('orders.order_addedon', '<=', $to.' 23:59:59');
        }
    }

    /** @return array<int, int> */
    private function searchTeachLanguageIds(string $keyword, int $langId): array
    {
        return DB::table('tbl_teach_languages as tl')
            ->leftJoin('tbl_teach_languages_lang as tll', function ($join) use ($langId) {
                $join->on('tll.tlanglang_tlang_id', '=', 'tl.tlang_id')
                    ->where('tll.tlanglang_lang_id', '=', $langId);
            })
            ->where(function (Builder $q) use ($keyword) {
                $q->where('tll.tlang_name', 'like', "%{$keyword}%")
                    ->orWhere('tl.tlang_identifier', 'like', "%{$keyword}%");
            })
            ->pluck('tl.tlang_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    private function paginateGroupedRows(Request $request, Builder $query, int $langId): array
    {
        $rows = $query->get();
        $ids = $rows->pluck('tlang_id')->map(fn ($id) => (int) $id)->all();
        $names = $this->teachLanguageNames($ids, $langId);

        $mapped = [];
        foreach ($rows as $row) {
            $tlangId = (int) $row->tlang_id;
            $mapped[] = [
                'id' => $tlangId,
                'tlang_id' => $tlangId,
                'language' => $names[$tlangId] ?? '—',
                'unscheduled' => (int) ($row->unscheduled ?? 0),
                'scheduled' => (int) ($row->scheduled ?? 0),
                'completed' => (int) ($row->completed ?? 0),
                'cancelled' => (int) ($row->cancelled ?? 0),
                'totalsold' => (int) $row->totalsold,
            ];
        }

        $page = max(1, $request->integer('page', 1));
        $perPage = $this->adminPageSize($request);
        $total = count($mapped);
        $offset = ($page - 1) * $perPage;
        $data = array_slice($mapped, $offset, $perPage);

        return $this->paginateResult($request, $data, $total);
    }

    /** @param  array<int, int>  $ids */
    private function teachLanguageNames(array $ids, int $langId): array
    {
        if ($ids === []) {
            return [];
        }

        $cache = [];
        $names = [];

        foreach (array_unique($ids) as $id) {
            $tlangId = (int) $id;
            $names[$tlangId] = $this->resolveTeachLanguageName($tlangId, $langId, $cache);
        }

        return $names;
    }

    /** @param  array<int, string>  $cache */
    private function resolveTeachLanguageName(int $tlangId, int $langId, array &$cache): string
    {
        if ($tlangId < 1) {
            return '—';
        }

        if (isset($cache[$tlangId])) {
            return $cache[$tlangId];
        }

        $row = DB::table('tbl_teach_languages as tl')
            ->leftJoin('tbl_teach_languages_lang as tll', function ($join) use ($langId) {
                $join->on('tll.tlanglang_tlang_id', '=', 'tl.tlang_id')
                    ->where('tll.tlanglang_lang_id', '=', $langId);
            })
            ->where('tl.tlang_id', $tlangId)
            ->first([
                'tl.tlang_parent',
                DB::raw('IFNULL(tll.tlang_name, tl.tlang_identifier) as name'),
            ]);

        if (! $row) {
            $cache[$tlangId] = '—';

            return '—';
        }

        $name = (string) $row->name;
        $parentId = (int) ($row->tlang_parent ?? 0);
        if ($parentId > 0) {
            $parentName = $this->resolveTeachLanguageName($parentId, $langId, $cache);
            if ($parentName !== '—') {
                $name = $parentName.' » '.$name;
            }
        }

        $cache[$tlangId] = $name;

        return $name;
    }
}
