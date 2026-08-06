<?php

namespace App\Services\Admin\Listings;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

trait AdminListingSupport
{
    protected function adminPageSize(Request $request): int
    {
        if ($request->boolean('export')) {
            return 5000;
        }

        $configured = (int) DB::table('tbl_configurations')
            ->where('conf_name', 'CONF_ADMIN_PAGESIZE')
            ->value('conf_val');

        $perPage = $request->integer('per_page', $configured > 0 ? $configured : 10);

        return min(50, max(1, $perPage));
    }

    protected function langId(Request $request): int
    {
        $requested = $request->integer('lang_id', 0);
        if ($requested > 0) {
            return $requested;
        }

        $default = (int) DB::table('tbl_configurations')
            ->where('conf_name', 'CONF_DEFAULT_LANG')
            ->value('conf_val');

        return $default > 0 ? $default : 1;
    }

    /** @param array<int, array<string, mixed>> $data */
    protected function paginateResult(Request $request, array $data, int $total): array
    {
        $page = max(1, $request->integer('page', 1));
        $perPage = $this->adminPageSize($request);

        return [
            'data' => $data,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage) ?: 1,
            ],
        ];
    }

    protected function applyKeyword(Request $request, Builder $query, array $columns): Builder
    {
        $keyword = trim((string) $request->query('keyword', ''));
        if ($keyword === '') {
            return $query;
        }

        $query->where(function (Builder $q) use ($keyword, $columns) {
            foreach ($columns as $index => $column) {
                if ($index === 0) {
                    $q->where($column, 'like', "%{$keyword}%");
                } else {
                    $q->orWhere($column, 'like', "%{$keyword}%");
                }
            }
        });

        return $query;
    }

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    protected function runQuery(Request $request, Builder $query, string $orderColumn = 'id', string $direction = 'desc'): array
    {
        $page = max(1, $request->integer('page', 1));
        $perPage = $this->adminPageSize($request);
        $total = (clone $query)->count();
        $rows = $query
            ->orderBy($orderColumn, $direction)
            ->forPage($page, $perPage)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        return $this->paginateResult($request, $rows, $total);
    }
}
