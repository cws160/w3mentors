<?php

namespace App\Services\Admin\Listings;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminCourseLanguageListingService
{
    use AdminListingSupport;

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    public function search(Request $request): array
    {
        $langId = $this->langId($request);

        $query = DB::table('tbl_course_languages as clang')
            ->leftJoin('tbl_course_languages_lang as clanglang', function ($join) use ($langId) {
                $join->on('clanglang.clanglang_clang_id', '=', 'clang.clang_id')
                    ->where('clanglang.clanglang_lang_id', '=', $langId);
            })
            ->whereNull('clang.clang_deleted')
            ->orderByDesc('clang.clang_active')
            ->orderBy('clang.clang_order')
            ->select([
                'clang.clang_id as id',
                'clang.clang_identifier as identifier',
                DB::raw('IFNULL(clanglang.clang_name, clang.clang_identifier) as title'),
                'clang.clang_active as active',
                'clang.clang_order as sort_order',
            ]);

        $rows = $query->get()->map(fn ($row) => [
            'id' => (int) $row->id,
            'identifier' => (string) $row->identifier,
            'title' => (string) $row->title,
            'active' => (int) $row->active,
            'sort_order' => (int) $row->sort_order,
        ])->all();

        $total = count($rows);

        return [
            'data' => $rows,
            'meta' => [
                'current_page' => 1,
                'per_page' => $total > 0 ? $total : 10,
                'total' => $total,
                'last_page' => 1,
            ],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function exportRows(Request $request): array
    {
        return $this->search($request)['data'];
    }
}
