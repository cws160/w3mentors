<?php

namespace App\Services\Admin\Listings;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminBlogPostsListingService
{
    use AdminListingSupport;

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    public function search(Request $request): array
    {
        $langId = $this->langId($request);
        $page = max(1, $request->integer('page', 1));
        $perPage = $this->adminPageSize($request);

        $query = DB::table('tbl_blog_post as post')
            ->leftJoin('tbl_blog_post_lang as plang', function ($join) use ($langId) {
                $join->on('plang.postlang_post_id', '=', 'post.post_id')
                    ->where('plang.postlang_lang_id', '=', $langId);
            })
            ->leftJoin('tbl_blog_post_to_category as ptc', 'ptc.ptc_post_id', '=', 'post.post_id')
            ->leftJoin('tbl_blog_post_categories as bpc', function ($join) {
                $join->on('bpc.bpcategory_id', '=', 'ptc.ptc_bpcategory_id')
                    ->where('bpc.bpcategory_deleted', '=', 0);
            })
            ->leftJoin('tbl_blog_post_categories_lang as bpcl', function ($join) use ($langId) {
                $join->on('bpcl.bpcategorylang_bpcategory_id', '=', 'bpc.bpcategory_id')
                    ->where('bpcl.bpcategorylang_lang_id', '=', $langId);
            })
            ->where('post.post_deleted', '=', 0)
            ->groupBy(
                'post.post_id',
                'post.post_identifier',
                'post.post_published',
                'post.post_added_on',
                'post.post_published_on',
                'plang.post_title',
            )
            ->orderByDesc('post.post_added_on')
            ->select([
                'post.post_id as id',
                'post.post_identifier as identifier',
                DB::raw('IFNULL(plang.post_title, post.post_identifier) as title'),
                DB::raw('GROUP_CONCAT(DISTINCT IFNULL(bpcl.bpcategory_name, bpc.bpcategory_identifier) ORDER BY bpc.bpcategory_order SEPARATOR ", ") as categories'),
                'post.post_published as published',
                'post.post_added_on as posted_on',
                'post.post_published_on as published_on',
            ]);

        $this->applyKeyword($request, $query, ['post.post_identifier', 'plang.post_title']);

        $published = $request->query('post_published', $request->query('published'));
        if ($published !== null && $published !== '') {
            $query->where('post.post_published', '=', (int) $published);
        }

        $total = DB::query()->fromSub((clone $query), 'blog_posts')->count();
        $rows = $query->forPage($page, $perPage)->get()->map(fn ($row) => [
            'id' => (int) $row->id,
            'identifier' => (string) $row->identifier,
            'title' => (string) $row->title,
            'categories' => (string) ($row->categories ?? ''),
            'published' => (int) $row->published,
            'posted_on' => (string) ($row->posted_on ?? ''),
            'published_on' => (string) ($row->published_on ?? ''),
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
}
