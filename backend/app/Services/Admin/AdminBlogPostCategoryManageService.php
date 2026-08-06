<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\DB;

class AdminBlogPostCategoryManageService
{
    /** @return array<string, mixed> */
    public function createForm(int $langId, int $defaultParentId = 0, int $excludeId = 0): array
    {
        return [
            'site_languages' => $this->siteLanguages(),
            'parent_categories' => $this->parentCategories($langId, $excludeId),
            'default_parent_id' => $defaultParentId,
        ];
    }

    /** @return array<string, mixed>|null */
    public function show(int $categoryId): ?array
    {
        $row = DB::table('tbl_blog_post_categories')
            ->where('bpcategory_id', $categoryId)
            ->where('bpcategory_deleted', 0)
            ->first([
                'bpcategory_id',
                'bpcategory_identifier',
                'bpcategory_parent',
                'bpcategory_active',
            ]);

        if (! $row) {
            return null;
        }

        return [
            'bpcategory_id' => (int) $row->bpcategory_id,
            'bpcategory_identifier' => (string) $row->bpcategory_identifier,
            'bpcategory_parent' => (int) $row->bpcategory_parent,
            'bpcategory_active' => (int) $row->bpcategory_active,
        ];
    }

    /** @param array<string, mixed> $payload @return array{ok: bool, message?: string, id?: int} */
    public function store(array $payload): array
    {
        $categoryId = (int) ($payload['bpcategory_id'] ?? 0);
        $identifier = trim((string) ($payload['bpcategory_identifier'] ?? ''));
        $parentId = (int) ($payload['bpcategory_parent'] ?? 0);
        $active = (int) ($payload['bpcategory_active'] ?? 1);

        if ($identifier === '') {
            return ['ok' => false, 'message' => 'Identifier is required'];
        }

        if ($categoryId > 0 && ! $this->exists($categoryId)) {
            return ['ok' => false, 'message' => 'Invalid request'];
        }

        if ($categoryId > 0 && $parentId > 0 && $this->hasSubcategories($categoryId)) {
            return ['ok' => false, 'message' => 'Cannot assign parent as this category has subcategories'];
        }

        $duplicate = DB::table('tbl_blog_post_categories')
            ->where('bpcategory_deleted', 0)
            ->where('bpcategory_parent', $parentId)
            ->whereRaw('LOWER(bpcategory_identifier) = ?', [strtolower($identifier)])
            ->where('bpcategory_id', '!=', $categoryId)
            ->exists();

        if ($duplicate) {
            return ['ok' => false, 'message' => 'Identifier is already in use'];
        }

        if ($categoryId > 0 && $active === 0 && $this->hasBlogPosts($categoryId)) {
            return ['ok' => false, 'message' => 'Categories attached with blogs cannot be marked inactive'];
        }

        if ($categoryId > 0) {
            DB::table('tbl_blog_post_categories')
                ->where('bpcategory_id', $categoryId)
                ->update([
                    'bpcategory_identifier' => $identifier,
                    'bpcategory_parent' => $parentId,
                    'bpcategory_active' => $active,
                ]);
        } else {
            $maxOrder = (int) DB::table('tbl_blog_post_categories')
                ->where('bpcategory_parent', $parentId)
                ->where('bpcategory_deleted', 0)
                ->max('bpcategory_order');

            $categoryId = (int) DB::table('tbl_blog_post_categories')->insertGetId([
                'bpcategory_identifier' => $identifier,
                'bpcategory_parent' => $parentId,
                'bpcategory_order' => $maxOrder + 1,
                'bpcategory_active' => $active,
                'bpcategory_deleted' => 0,
            ]);
        }

        return ['ok' => true, 'id' => $categoryId];
    }

    /** @return array<string, mixed>|null */
    public function langForm(int $categoryId, int $langId): ?array
    {
        if (! $this->exists($categoryId)) {
            return null;
        }

        $name = DB::table('tbl_blog_post_categories_lang')
            ->where('bpcategorylang_bpcategory_id', $categoryId)
            ->where('bpcategorylang_lang_id', $langId)
            ->value('bpcategory_name');

        return [
            'bpcategory_name' => (string) ($name ?? ''),
            'layout_direction' => $this->layoutDirection($langId),
        ];
    }

    /** @param array<string, mixed> $payload @return array{ok: bool, message?: string} */
    public function storeLang(int $categoryId, int $langId, array $payload): array
    {
        if (! $this->exists($categoryId) || $langId < 1) {
            return ['ok' => false, 'message' => 'Invalid request'];
        }

        $name = trim((string) ($payload['bpcategory_name'] ?? ''));
        if ($name === '') {
            return ['ok' => false, 'message' => 'Category name is required'];
        }

        $this->saveLanguageRow($categoryId, $langId, $name);

        if (! empty($payload['update_langs_data'])) {
            foreach ($this->siteLanguages() as $language) {
                if ((int) $language['id'] === $langId) {
                    continue;
                }
                $this->saveLanguageRow($categoryId, (int) $language['id'], $name);
            }
        }

        return ['ok' => true];
    }

    private function saveLanguageRow(int $categoryId, int $langId, string $name): void
    {
        $exists = DB::table('tbl_blog_post_categories_lang')
            ->where('bpcategorylang_bpcategory_id', $categoryId)
            ->where('bpcategorylang_lang_id', $langId)
            ->exists();

        if ($exists) {
            DB::table('tbl_blog_post_categories_lang')
                ->where('bpcategorylang_bpcategory_id', $categoryId)
                ->where('bpcategorylang_lang_id', $langId)
                ->update(['bpcategory_name' => $name]);

            return;
        }

        DB::table('tbl_blog_post_categories_lang')->insert([
            'bpcategorylang_bpcategory_id' => $categoryId,
            'bpcategorylang_lang_id' => $langId,
            'bpcategory_name' => $name,
        ]);
    }

    /** @return array{ok: bool, message?: string} */
    public function changeStatus(int $categoryId, int $status): array
    {
        if (! $this->exists($categoryId)) {
            return ['ok' => false, 'message' => 'Invalid request'];
        }

        if ($status === 0 && $this->hasBlogPosts($categoryId)) {
            return ['ok' => false, 'message' => 'Categories attached with blogs cannot be marked inactive'];
        }

        DB::table('tbl_blog_post_categories')
            ->where('bpcategory_id', $categoryId)
            ->update(['bpcategory_active' => $status === 1 ? 1 : 0]);

        return ['ok' => true];
    }

    /** @return array{ok: bool, message?: string} */
    public function delete(int $categoryId): array
    {
        $row = DB::table('tbl_blog_post_categories')
            ->where('bpcategory_id', $categoryId)
            ->where('bpcategory_deleted', 0)
            ->first(['bpcategory_identifier']);

        if (! $row) {
            return ['ok' => false, 'message' => 'Category not found'];
        }

        if ($this->hasBlogPosts($categoryId, true)) {
            return ['ok' => false, 'message' => 'Categories attached with blogs cannot be deleted'];
        }

        DB::table('tbl_blog_post_categories')
            ->where('bpcategory_id', $categoryId)
            ->update([
                'bpcategory_deleted' => 1,
                'bpcategory_identifier' => $row->bpcategory_identifier.'-'.$categoryId,
            ]);

        return ['ok' => true];
    }

    /** @param array<int, int|string> $order @return array{ok: bool, message?: string} */
    public function updateOrder(array $order): array
    {
        if ($order === []) {
            return ['ok' => false, 'message' => 'Invalid request'];
        }

        foreach (array_values($order) as $index => $id) {
            $categoryId = (int) $id;
            if ($categoryId < 1) {
                continue;
            }
            DB::table('tbl_blog_post_categories')
                ->where('bpcategory_id', $categoryId)
                ->where('bpcategory_deleted', 0)
                ->update(['bpcategory_order' => $index + 1]);
        }

        return ['ok' => true];
    }

    private function exists(int $categoryId): bool
    {
        return DB::table('tbl_blog_post_categories')
            ->where('bpcategory_id', $categoryId)
            ->where('bpcategory_deleted', 0)
            ->exists();
    }

    private function hasSubcategories(int $categoryId): bool
    {
        return DB::table('tbl_blog_post_categories')
            ->where('bpcategory_parent', $categoryId)
            ->where('bpcategory_deleted', 0)
            ->exists();
    }

    private function hasBlogPosts(int $categoryId, bool $includeChildren = false): bool
    {
        $ids = [$categoryId];
        if ($includeChildren) {
            $childIds = DB::table('tbl_blog_post_categories')
                ->where('bpcategory_parent', $categoryId)
                ->where('bpcategory_deleted', 0)
                ->pluck('bpcategory_id')
                ->all();
            $ids = array_merge($ids, $childIds);
        }

        return DB::table('tbl_blog_post_to_category')
            ->whereIn('ptc_bpcategory_id', $ids)
            ->exists();
    }

    /** @return array<int, array{id: int, name: string}> */
    private function parentCategories(int $langId, int $excludeId = 0): array
    {
        $rows = DB::table('tbl_blog_post_categories as bpc')
            ->leftJoin('tbl_blog_post_categories_lang as bpc_l', function ($join) use ($langId) {
                $join->on('bpc_l.bpcategorylang_bpcategory_id', '=', 'bpc.bpcategory_id')
                    ->where('bpc_l.bpcategorylang_lang_id', '=', $langId);
            })
            ->where('bpc.bpcategory_deleted', 0)
            ->orderBy('bpc.bpcategory_order')
            ->get([
                'bpc.bpcategory_id',
                'bpc.bpcategory_parent',
                DB::raw('IFNULL(bpc_l.bpcategory_name, bpc.bpcategory_identifier) as name'),
            ])
            ->map(fn ($row) => [
                'id' => (int) $row->bpcategory_id,
                'parent_id' => (int) $row->bpcategory_parent,
                'name' => (string) $row->name,
            ])
            ->all();

        $exclude = $excludeId > 0 ? array_merge([$excludeId], $this->descendantIds($excludeId, $rows)) : [];

        return $this->buildTreeOptions($rows, 0, '', $exclude);
    }

    /**
     * @param  array<int, array{id: int, parent_id: int, name: string}>  $rows
     * @param  array<int, int>  $exclude
     * @return array<int, array{id: int, name: string}>
     */
    private function buildTreeOptions(array $rows, int $parentId, string $prefix, array $exclude): array
    {
        $options = [];
        foreach ($rows as $row) {
            if ($row['parent_id'] !== $parentId) {
                continue;
            }
            if (in_array($row['id'], $exclude, true)) {
                continue;
            }
            $label = $prefix === '' ? $row['name'] : $prefix.' » '.$row['name'];
            $options[] = ['id' => $row['id'], 'name' => $label];
            $options = array_merge($options, $this->buildTreeOptions($rows, $row['id'], $label, $exclude));
        }

        return $options;
    }

    /**
     * @param  array<int, array{id: int, parent_id: int, name: string}>  $rows
     * @return array<int, int>
     */
    private function descendantIds(int $categoryId, array $rows): array
    {
        $ids = [];
        foreach ($rows as $row) {
            if ($row['parent_id'] === $categoryId) {
                $ids[] = $row['id'];
                $ids = array_merge($ids, $this->descendantIds($row['id'], $rows));
            }
        }

        return $ids;
    }

    /** @return array<int, array{id: int, name: string}> */
    private function siteLanguages(): array
    {
        return DB::table('tbl_languages')
            ->where('language_active', 1)
            ->orderBy('language_id')
            ->get(['language_id as id', 'language_name as name'])
            ->map(fn ($row) => ['id' => (int) $row->id, 'name' => (string) $row->name])
            ->all();
    }

    private function layoutDirection(int $langId): string
    {
        $code = (string) DB::table('tbl_languages')->where('language_id', $langId)->value('language_code');

        return in_array(strtolower($code), ['ar', 'he', 'fa', 'ur'], true) ? 'rtl' : 'ltr';
    }
}
