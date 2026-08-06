<?php

namespace App\Services\Admin\Listings;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminCategoriesListingService
{
    use AdminListingSupport;

    private const TYPE_COURSE = 1;

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    public function search(Request $request): array
    {
        $langId = $this->langId($request);
        $parentId = max(0, $request->integer('parent_id', 0));
        $cateType = max(1, $request->integer('cate_type', self::TYPE_COURSE));

        $query = DB::table('tbl_categories as catg')
            ->leftJoin('tbl_categories_lang as catg_l', function ($join) use ($langId) {
                $join->on('catg_l.catelang_cate_id', '=', 'catg.cate_id')
                    ->where('catg_l.catelang_lang_id', '=', $langId);
            })
            ->whereNull('catg.cate_deleted')
            ->where('catg.cate_parent', '=', $parentId)
            ->where('catg.cate_type', '=', $cateType)
            ->orderByDesc('catg.cate_status')
            ->orderBy('catg.cate_order')
            ->select([
                'catg.cate_id as id',
                'catg.cate_identifier as identifier',
                DB::raw('IFNULL(catg_l.cate_name, catg.cate_identifier) as title'),
                'catg.cate_subcategories as subcategories',
                'catg.cate_records as records',
                'catg.cate_featured as featured',
                'catg.cate_status as active',
                'catg.cate_updated as updated_at',
                'catg.cate_parent as parent_id',
            ]);

        $this->applyKeyword($request, $query, ['catg.cate_identifier', 'catg_l.cate_name']);

        $rows = $query->get()->map(fn ($row) => [
            'id' => (int) $row->id,
            'identifier' => (string) $row->identifier,
            'title' => (string) $row->title,
            'subcategories' => (int) $row->subcategories,
            'records' => (int) $row->records,
            'featured' => (int) $row->featured,
            'active' => (int) $row->active,
            'updated_at' => (string) ($row->updated_at ?? ''),
            'parent_id' => (int) $row->parent_id,
        ])->all();

        $total = count($rows);
        $parentName = null;
        if ($parentId > 0) {
            $parentName = DB::table('tbl_categories as catg')
                ->leftJoin('tbl_categories_lang as catg_l', function ($join) use ($langId) {
                    $join->on('catg_l.catelang_cate_id', '=', 'catg.cate_id')
                        ->where('catg_l.catelang_lang_id', '=', $langId);
                })
                ->where('catg.cate_id', '=', $parentId)
                ->whereNull('catg.cate_deleted')
                ->value(DB::raw('IFNULL(catg_l.cate_name, catg.cate_identifier)'));
        }

        return [
            'data' => $rows,
            'meta' => [
                'current_page' => 1,
                'per_page' => $total > 0 ? $total : 10,
                'total' => $total,
                'last_page' => 1,
                'parent_id' => $parentId,
                'parent_name' => $parentName ? (string) $parentName : null,
            ],
        ];
    }
}
