<?php

namespace App\Services\Admin\Listings;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminPageLangDataListingService
{
    use AdminListingSupport;

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    public function search(Request $request): array
    {
        $page = max(1, $request->integer('page', 1));
        $perPage = $this->adminPageSize($request);
        $defaultLang = $this->defaultLangId();

        $query = DB::table('tbl_pages_language_data')
            ->where('plang_lang_id', $defaultLang)
            ->orderBy('plang_key')
            ->select([
                'plang_id as id',
                'plang_key as page_key',
                'plang_title as title',
                'plang_lang_id as lang_id',
            ]);

        $keyword = trim((string) $request->query('keyword', ''));
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('plang_key', 'like', "%{$keyword}%")
                    ->orWhere('plang_title', 'like', "%{$keyword}%");
            });
        }

        $total = (clone $query)->count('plang_id');
        $rows = $query->forPage($page, $perPage)->get()->map(fn ($row) => [
            'id' => (int) $row->id,
            'page_key' => (string) $row->page_key,
            'title' => (string) ($row->title ?? ''),
            'lang_id' => (int) $row->lang_id,
        ])->all();

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

    private function defaultLangId(): int
    {
        $configured = (int) DB::table('tbl_configurations')
            ->where('conf_name', 'CONF_DEFAULT_LANG')
            ->value('conf_val');

        return $configured > 0 ? $configured : 1;
    }
}
