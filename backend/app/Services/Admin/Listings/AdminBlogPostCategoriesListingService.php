<?php

namespace App\Services\Admin\Listings;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminBlogPostCategoriesListingService
{
    use AdminListingSupport;

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, mixed>} */
    public function search(Request $request): array
    {
        $langId = $this->langId($request);
        $parentId = max(0, $request->integer('parent_id', 0));

        $childCounts = DB::table('tbl_blog_post_categories')
            ->select('bpcategory_parent', DB::raw('COUNT(*) as child_count'))
            ->where('bpcategory_deleted', '=', 0)
            ->groupBy('bpcategory_parent');

        $query = DB::table('tbl_blog_post_categories as bpc')
            ->leftJoin('tbl_blog_post_categories_lang as bpc_l', function ($join) use ($langId) {
                $join->on('bpc_l.bpcategorylang_bpcategory_id', '=', 'bpc.bpcategory_id')
                    ->where('bpc_l.bpcategorylang_lang_id', '=', $langId);
            })
            ->leftJoinSub($childCounts, 'child', function ($join) {
                $join->on('child.bpcategory_parent', '=', 'bpc.bpcategory_id');
            })
            ->where('bpc.bpcategory_deleted', '=', 0)
            ->where('bpc.bpcategory_parent', '=', $parentId)
            ->orderByDesc('bpc.bpcategory_active')
            ->orderBy('bpc.bpcategory_order')
            ->select([
                'bpc.bpcategory_id as id',
                'bpc.bpcategory_identifier as identifier',
                DB::raw('IFNULL(bpc_l.bpcategory_name, bpc.bpcategory_identifier) as title'),
                DB::raw('IFNULL(child.child_count, 0) as subcategories'),
                'bpc.bpcategory_active as active',
                'bpc.bpcategory_parent as parent_id',
            ]);

        $this->applyKeyword($request, $query, ['bpc.bpcategory_identifier', 'bpc_l.bpcategory_name']);

        $rows = $query->get()->map(fn ($row) => [
            'id' => (int) $row->id,
            'identifier' => (string) $row->identifier,
            'title' => (string) $row->title,
            'subcategories' => (int) $row->subcategories,
            'active' => (int) $row->active,
            'parent_id' => (int) $row->parent_id,
        ])->all();

        $total = count($rows);
        $parentName = null;
        if ($parentId > 0) {
            $parentName = DB::table('tbl_blog_post_categories as bpc')
                ->leftJoin('tbl_blog_post_categories_lang as bpc_l', function ($join) use ($langId) {
                    $join->on('bpc_l.bpcategorylang_bpcategory_id', '=', 'bpc.bpcategory_id')
                        ->where('bpc_l.bpcategorylang_lang_id', '=', $langId);
                })
                ->where('bpc.bpcategory_id', '=', $parentId)
                ->where('bpc.bpcategory_deleted', '=', 0)
                ->value(DB::raw('IFNULL(bpc_l.bpcategory_name, bpc.bpcategory_identifier)'));
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
