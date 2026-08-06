<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BlogService
{
    public const BLOG_POST_IMAGE = 23;

    public const COMMENT_APPROVED = 1;

    /** @var array<int, Collection<int, object>> */
    private array $categoryRowsByLang = [];

    public function categoryTree(int $langId): array
    {
        return $this->buildCategoryNodes($langId, 0);
    }

    public function categoryName(int $langId, int $categoryId): string
    {
        $row = $this->allCategories($langId)->firstWhere('id', $categoryId);
        if (!$row) {
            return '';
        }

        $parts = [];
        $current = $row;
        while ($current) {
            array_unshift($parts, $current->name);
            $current = $current->parent_id
                ? $this->allCategories($langId)->firstWhere('id', $current->parent_id)
                : null;
        }

        return implode(' » ', $parts);
    }

    /** @return list<int> */
    public function categoryIdsWithDescendants(int $categoryId, int $langId = 1): array
    {
        $ids = [$categoryId];
        $children = $this->allCategories($langId)->where('parent_id', $categoryId);
        foreach ($children as $child) {
            $ids = array_merge($ids, $this->categoryIdsWithDescendants((int) $child->id, $langId));
        }

        return array_values(array_unique($ids));
    }

    /** @return list<array{id: int, url: string}> */
    public function postImages(int $postId, string $size = 'LARGE'): array
    {
        return DB::table('tbl_attached_files')
            ->where('file_type', self::BLOG_POST_IMAGE)
            ->where('file_record_id', $postId)
            ->orderBy('file_order')
            ->orderBy('file_id')
            ->get(['file_id'])
            ->map(fn ($row) => [
                'id' => (int) $row->file_id,
                'url' => '/image/show-by-id/'.$row->file_id.'/'.$size,
            ])
            ->values()
            ->all();
    }

    public function attachCategoriesToPosts(array $posts, int $langId): array
    {
        if ($posts === []) {
            return [];
        }

        $postIds = array_map(fn ($p) => (int) $p->id, $posts);
        $links = DB::table('tbl_blog_post_to_category as ptc')
            ->join('tbl_blog_post_categories as c', 'c.bpcategory_id', '=', 'ptc.ptc_bpcategory_id')
            ->leftJoin('tbl_blog_post_categories_lang as cl', function ($join) use ($langId) {
                $join->on('c.bpcategory_id', '=', 'cl.bpcategorylang_bpcategory_id')
                    ->where('cl.bpcategorylang_lang_id', '=', $langId);
            })
            ->whereIn('ptc.ptc_post_id', $postIds)
            ->where('c.bpcategory_active', 1)
            ->where('c.bpcategory_deleted', 0)
            ->orderBy('c.bpcategory_order')
            ->get([
                'ptc.ptc_post_id as post_id',
                'c.bpcategory_id as id',
                DB::raw('COALESCE(cl.bpcategory_name, c.bpcategory_identifier) as name'),
            ])
            ->groupBy('post_id');

        return array_map(function ($post) use ($links) {
            $cats = ($links[(int) $post->id] ?? collect())->map(fn ($c) => [
                'id' => (int) $c->id,
                'name' => $c->name,
            ])->values()->all();

            return [
                'id' => (int) $post->id,
                'title' => $post->title,
                'excerpt' => $post->excerpt ?? '',
                'published_at' => $post->published_at,
                'categories' => $cats,
            ];
        }, $posts);
    }

    /** @return list<array<string, mixed>> */
    public function approvedComments(int $postId, int $page = 1, int $perPage = 10): array
    {
        $rows = DB::table('tbl_blog_post_comments')
            ->where('bpcomment_post_id', $postId)
            ->where('bpcomment_approved', self::COMMENT_APPROVED)
            ->where('bpcomment_deleted', 0)
            ->orderByDesc('bpcomment_added_on')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => collect($rows->items())->map(fn ($c) => [
                'id' => (int) $c->bpcomment_id,
                'author_name' => $c->bpcomment_author_name,
                'author_email' => $c->bpcomment_author_email,
                'user_id' => (int) $c->bpcomment_user_id,
                'content' => $c->bpcomment_content,
                'added_on' => $c->bpcomment_added_on,
            ])->values()->all(),
            'meta' => [
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
                'per_page' => $rows->perPage(),
                'total' => $rows->total(),
            ],
        ];
    }

    public function commentsCount(int $postId): int
    {
        return (int) DB::table('tbl_blog_post_comments')
            ->where('bpcomment_post_id', $postId)
            ->where('bpcomment_approved', self::COMMENT_APPROVED)
            ->where('bpcomment_deleted', 0)
            ->count();
    }

    private function allCategories(int $langId): Collection
    {
        if (!isset($this->categoryRowsByLang[$langId])) {
            $this->categoryRowsByLang[$langId] = DB::table('tbl_blog_post_categories as c')
                ->leftJoin('tbl_blog_post_categories_lang as cl', function ($join) use ($langId) {
                    $join->on('c.bpcategory_id', '=', 'cl.bpcategorylang_bpcategory_id')
                        ->where('cl.bpcategorylang_lang_id', '=', $langId);
                })
                ->where('c.bpcategory_active', 1)
                ->where('c.bpcategory_deleted', 0)
                ->orderBy('c.bpcategory_order')
                ->get([
                    'c.bpcategory_id as id',
                    'c.bpcategory_parent as parent_id',
                    DB::raw('COALESCE(cl.bpcategory_name, c.bpcategory_identifier) as name'),
                ]);
        }

        return $this->categoryRowsByLang[$langId];
    }

    /** @return list<array<string, mixed>> */
    private function buildCategoryNodes(int $langId, int $parentId): array
    {
        $nodes = [];
        foreach ($this->allCategories($langId)->where('parent_id', $parentId) as $row) {
            $children = $this->buildCategoryNodes($langId, (int) $row->id);
            $ownCount = $this->postsCountForCategory($langId, (int) $row->id);
            $childSum = array_sum(array_map(
                fn ($c) => (int) ($c['post_count'] ?? 0) + (int) ($c['children_post_count'] ?? 0),
                $children
            ));
            $total = $ownCount + $childSum;
            if ($total <= 0) {
                continue;
            }
            $nodes[] = [
                'id' => (int) $row->id,
                'name' => $row->name,
                'post_count' => $ownCount,
                'children_post_count' => $childSum,
                'children' => $children,
            ];
        }

        return $nodes;
    }

    private function postsCountForCategory(int $langId, int $categoryId): int
    {
        return (int) DB::table('tbl_blog_post as p')
            ->join('tbl_blog_post_lang as pl', function ($join) use ($langId) {
                $join->on('p.post_id', '=', 'pl.postlang_post_id')
                    ->where('pl.postlang_lang_id', '=', $langId);
            })
            ->join('tbl_blog_post_to_category as ptc', 'ptc.ptc_post_id', '=', 'p.post_id')
            ->where('p.post_published', 1)
            ->where('p.post_deleted', 0)
            ->where('ptc.ptc_bpcategory_id', $categoryId)
            ->distinct('p.post_id')
            ->count('p.post_id');
    }
}
