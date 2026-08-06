<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait PaginatesJson
{
    protected function paginatedResponse(Request $request, Builder $query, string $orderColumn = 'id', string $direction = 'desc'): JsonResponse
    {
        $page = max(1, $request->integer('page', 1));
        $perPage = min(50, max(1, $request->integer('per_page', 20)));

        $total = (clone $query)->count();
        $rows = $query
            ->orderBy($orderColumn, $direction)
            ->forPage($page, $perPage)
            ->get();

        return response()->json([
            'data' => $rows,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage) ?: 1,
            ],
        ]);
    }
}
