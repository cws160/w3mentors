<?php

namespace App\Services\Admin;

use App\Services\BlogService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class AdminBlogPostManageService
{
    /** @return array<string, mixed> */
    public function createForm(int $langId, int $postId = 0): array
    {
        $selected = $postId > 0 ? $this->selectedCategoryIds($postId) : [];

        return [
            'site_languages' => $this->siteLanguages(),
            'categories' => $this->categoryOptions($langId),
            'selected_categories' => $selected,
            'frontend_base_url' => rtrim((string) config('app.frontend_url', config('app.url')), '/'),
        ];
    }

    /** @return array<string, mixed>|null */
    public function show(int $postId): ?array
    {
        $row = DB::table('tbl_blog_post')
            ->where('post_id', $postId)
            ->where('post_deleted', 0)
            ->first([
                'post_id',
                'post_identifier',
                'post_published',
                'post_comment_opened',
            ]);

        if (! $row) {
            return null;
        }

        $seoUrl = DB::table('tbl_seo_urls')
            ->where('seourl_original', 'blog/post-detail/'.$postId)
            ->value('seourl_custom');

        return [
            'post_id' => (int) $row->post_id,
            'post_identifier' => (string) $row->post_identifier,
            'post_published' => (int) $row->post_published,
            'post_comment_opened' => (int) $row->post_comment_opened,
            'seourl_custom' => (string) ($seoUrl ?? ''),
            'categories' => $this->selectedCategoryIds($postId),
        ];
    }

    /** @param array<string, mixed> $payload @return array{ok: bool, message?: string, id?: int} */
    public function store(array $payload, int $langId = 1): array
    {
        $postId = (int) ($payload['post_id'] ?? 0);
        $identifier = trim((string) ($payload['post_identifier'] ?? ''));
        $published = (int) ($payload['post_published'] ?? 0);
        $commentOpened = (int) ($payload['post_comment_opened'] ?? 0);
        $seoCustom = trim((string) ($payload['seourl_custom'] ?? ''));
        $categories = array_map('intval', (array) ($payload['categories'] ?? []));

        if ($identifier === '') {
            return ['ok' => false, 'message' => 'Post identifier is required'];
        }
        if ($seoCustom === '') {
            return ['ok' => false, 'message' => 'SEO friendly URL is required'];
        }
        if ($categories === []) {
            return ['ok' => false, 'message' => 'Please select at least one category'];
        }

        $duplicate = DB::table('tbl_blog_post')
            ->where('post_deleted', 0)
            ->whereRaw('LOWER(post_identifier) = ?', [strtolower($identifier)])
            ->where('post_id', '!=', $postId)
            ->exists();
        if ($duplicate) {
            return ['ok' => false, 'message' => 'Identifier is already in use'];
        }

        $now = now()->format('Y-m-d H:i:s');
        $publishedOn = $published === 1 ? $now : null;

        if ($postId > 0) {
            $existing = DB::table('tbl_blog_post')->where('post_id', $postId)->first(['post_published', 'post_published_on']);
            if (! $existing) {
                return ['ok' => false, 'message' => 'Invalid request'];
            }
            if ((int) $existing->post_published === $published) {
                $publishedOn = $existing->post_published_on;
            }

            DB::table('tbl_blog_post')
                ->where('post_id', $postId)
                ->update([
                    'post_identifier' => $identifier,
                    'post_published' => $published,
                    'post_comment_opened' => $commentOpened,
                    'post_published_on' => $publishedOn,
                    'post_updated_on' => $now,
                ]);
        } else {
            $postId = (int) DB::table('tbl_blog_post')->insertGetId([
                'post_identifier' => $identifier,
                'post_published' => $published,
                'post_comment_opened' => $commentOpened,
                'post_added_on' => $now,
                'post_published_on' => $publishedOn,
                'post_updated_on' => $now,
                'post_view_count' => 0,
                'post_deleted' => 0,
            ]);
        }

        $this->syncCategories($postId, $categories);
        $this->syncSeoUrl($postId, $seoCustom, $langId);

        return ['ok' => true, 'id' => $postId];
    }

    /** @return array<string, mixed>|null */
    public function langForm(int $postId, int $langId): ?array
    {
        if (! $this->exists($postId)) {
            return null;
        }

        $row = DB::table('tbl_blog_post_lang')
            ->where('postlang_post_id', $postId)
            ->where('postlang_lang_id', $langId)
            ->first(['post_title', 'post_author_name', 'post_description']);

        return [
            'post_title' => (string) ($row->post_title ?? ''),
            'post_author_name' => (string) ($row->post_author_name ?? ''),
            'post_description' => (string) ($row->post_description ?? ''),
            'layout_direction' => $this->layoutDirection($langId),
        ];
    }

    /** @param array<string, mixed> $payload @return array{ok: bool, message?: string} */
    public function storeLang(int $postId, int $langId, array $payload): array
    {
        if (! $this->exists($postId) || $langId < 1) {
            return ['ok' => false, 'message' => 'Invalid request'];
        }

        $title = trim((string) ($payload['post_title'] ?? ''));
        $author = trim((string) ($payload['post_author_name'] ?? ''));
        $description = trim((string) ($payload['post_description'] ?? ''));

        if ($title === '' || $author === '' || $description === '') {
            return ['ok' => false, 'message' => 'Title, author, and description are required'];
        }

        $exists = DB::table('tbl_blog_post_lang')
            ->where('postlang_post_id', $postId)
            ->where('postlang_lang_id', $langId)
            ->exists();

        $data = [
            'postlang_post_id' => $postId,
            'postlang_lang_id' => $langId,
            'post_title' => $title,
            'post_author_name' => $author,
            'post_description' => $description,
            'post_short_description' => '',
        ];

        if ($exists) {
            DB::table('tbl_blog_post_lang')
                ->where('postlang_post_id', $postId)
                ->where('postlang_lang_id', $langId)
                ->update([
                    'post_title' => $title,
                    'post_author_name' => $author,
                    'post_description' => $description,
                ]);
        } else {
            DB::table('tbl_blog_post_lang')->insert($data);
        }

        return ['ok' => true];
    }

    /** @return array<string, mixed>|null */
    public function imagesForm(int $postId): ?array
    {
        if (! $this->exists($postId)) {
            return null;
        }

        return [
            'post_id' => $postId,
            'language_options' => $this->imageLanguageOptions(),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function listImages(int $postId, int $langId = 0): array
    {
        if (! $this->exists($postId)) {
            return [];
        }

        $langNames = collect($this->siteLanguages())->keyBy('id');

        return DB::table('tbl_attached_files')
            ->where('file_type', BlogService::BLOG_POST_IMAGE)
            ->where('file_record_id', $postId)
            ->where('file_lang_id', $langId)
            ->orderBy('file_order')
            ->orderBy('file_id')
            ->get(['file_id', 'file_name', 'file_lang_id', 'file_record_id'])
            ->map(function ($row) use ($langNames) {
                $fileLangId = (int) $row->file_lang_id;

                return [
                    'file_id' => (int) $row->file_id,
                    'file_name' => (string) $row->file_name,
                    'file_lang_id' => $fileLangId,
                    'file_record_id' => (int) $row->file_record_id,
                    'language_label' => $fileLangId > 0
                        ? (string) ($langNames->get($fileLangId)['name'] ?? '')
                        : 'All Languages',
                    'image_url' => '/api/v1/image/show-by-id/'.$row->file_id.'/MEDIUM',
                ];
            })
            ->all();
    }

    /** @return array{ok: bool, message?: string} */
    public function uploadImage(int $postId, int $langId, UploadedFile $file): array
    {
        if (! $this->exists($postId)) {
            return ['ok' => false, 'message' => 'Invalid request'];
        }

        $ext = strtolower($file->getClientOriginalExtension());
        $allowed = ['png', 'jpg', 'jpeg'];
        if (! in_array($ext, $allowed, true)) {
            return ['ok' => false, 'message' => 'Invalid file type'];
        }

        if ($file->getSize() > 4 * 1024 * 1024) {
            return ['ok' => false, 'message' => 'File is too large'];
        }

        $uploadRoot = base_path('../user-uploads');
        if (! is_dir($uploadRoot)) {
            @mkdir($uploadRoot, 0755, true);
        }

        $baseName = preg_replace('/[^a-zA-Z0-9.]/', '', $file->getClientOriginalName()) ?: 'blog-image.'.$ext;
        $relativePath = date('Y/m').'/'.$baseName;
        $targetDir = $uploadRoot.'/'.dirname($relativePath);
        if (! is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }

        while (is_file($uploadRoot.'/'.$relativePath)) {
            $relativePath = date('Y/m').'/'.time().'-'.$baseName;
        }

        $file->move($targetDir, basename($relativePath));

        DB::table('tbl_attached_files')->insert([
            'file_type' => BlogService::BLOG_POST_IMAGE,
            'file_lang_id' => max(0, $langId),
            'file_record_id' => $postId,
            'file_name' => basename($relativePath),
            'file_path' => $relativePath,
            'file_order' => 0,
            'file_added' => now()->format('Y-m-d H:i:s'),
        ]);

        return ['ok' => true];
    }

    /** @return array{ok: bool, message?: string} */
    public function deleteImage(int $postId, int $fileId): array
    {
        if (! $this->exists($postId)) {
            return ['ok' => false, 'message' => 'Invalid request'];
        }

        $row = DB::table('tbl_attached_files')
            ->where('file_id', $fileId)
            ->where('file_type', BlogService::BLOG_POST_IMAGE)
            ->where('file_record_id', $postId)
            ->first(['file_path']);

        if (! $row) {
            return ['ok' => false, 'message' => 'Image not found'];
        }

        DB::table('tbl_attached_files')->where('file_id', $fileId)->delete();

        if (! empty($row->file_path)) {
            $path = base_path('../user-uploads/'.$row->file_path);
            if (is_file($path)) {
                @unlink($path);
            }
        }

        return ['ok' => true];
    }

    /** @return array{ok: bool, message?: string} */
    public function delete(int $postId): array
    {
        $row = DB::table('tbl_blog_post')
            ->where('post_id', $postId)
            ->where('post_deleted', 0)
            ->first(['post_identifier']);

        if (! $row) {
            return ['ok' => false, 'message' => 'Post not found'];
        }

        DB::table('tbl_blog_post')
            ->where('post_id', $postId)
            ->update([
                'post_deleted' => 1,
                'post_identifier' => $row->post_identifier.'-'.$postId,
            ]);

        DB::table('tbl_seo_urls')->where('seourl_original', 'blog/post-detail/'.$postId)->delete();

        return ['ok' => true];
    }

    /** @return array<int, int> */
    private function selectedCategoryIds(int $postId): array
    {
        return DB::table('tbl_blog_post_to_category')
            ->where('ptc_post_id', $postId)
            ->pluck('ptc_bpcategory_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /** @param array<int, int> $categoryIds */
    private function syncCategories(int $postId, array $categoryIds): void
    {
        DB::table('tbl_blog_post_to_category')->where('ptc_post_id', $postId)->delete();
        foreach (array_values(array_unique(array_filter($categoryIds))) as $categoryId) {
            if ($categoryId < 1) {
                continue;
            }
            DB::table('tbl_blog_post_to_category')->insert([
                'ptc_post_id' => $postId,
                'ptc_bpcategory_id' => $categoryId,
            ]);
        }
    }

    private function syncSeoUrl(int $postId, string $custom, int $langId): void
    {
        $original = 'blog/post-detail/'.$postId;
        DB::table('tbl_seo_urls')->where('seourl_original', $original)->delete();

        if ($custom === '') {
            return;
        }

        $langIds = DB::table('tbl_languages')->where('language_active', 1)->pluck('language_id');
        foreach ($langIds as $languageId) {
            DB::table('tbl_seo_urls')->insert([
                'seourl_original' => $original,
                'seourl_custom' => ltrim($custom, '/'),
                'seourl_lang_id' => (int) $languageId,
                'seourl_httpcode' => 301,
            ]);
        }
    }

    private function exists(int $postId): bool
    {
        return DB::table('tbl_blog_post')->where('post_id', $postId)->where('post_deleted', 0)->exists();
    }

    /** @return array<int, array{id: int, name: string}> */
    private function categoryOptions(int $langId): array
    {
        $rows = DB::table('tbl_blog_post_categories as bpc')
            ->leftJoin('tbl_blog_post_categories_lang as bpc_l', function ($join) use ($langId) {
                $join->on('bpc_l.bpcategorylang_bpcategory_id', '=', 'bpc.bpcategory_id')
                    ->where('bpc_l.bpcategorylang_lang_id', '=', $langId);
            })
            ->where('bpc.bpcategory_deleted', 0)
            ->orderBy('bpc.bpcategory_order')
            ->get([
                'bpc.bpcategory_id as id',
                'bpc.bpcategory_parent as parent_id',
                DB::raw('IFNULL(bpc_l.bpcategory_name, bpc.bpcategory_identifier) as name'),
            ])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'parent_id' => (int) $row->parent_id,
                'name' => (string) $row->name,
            ])
            ->all();

        return $this->buildTreeOptions($rows, 0, '');
    }

    /**
     * @param  array<int, array{id: int, parent_id: int, name: string}>  $rows
     * @return array<int, array{id: int, name: string}>
     */
    private function buildTreeOptions(array $rows, int $parentId, string $prefix): array
    {
        $options = [];
        foreach ($rows as $row) {
            if ($row['parent_id'] !== $parentId) {
                continue;
            }
            $label = $prefix === '' ? $row['name'] : $prefix.' » '.$row['name'];
            $options[] = ['id' => $row['id'], 'name' => $label];
            $options = array_merge($options, $this->buildTreeOptions($rows, $row['id'], $label));
        }

        return $options;
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

    /** @return array<int, array{id: int, name: string}> */
    private function imageLanguageOptions(): array
    {
        return array_merge(
            [['id' => 0, 'name' => 'All Languages']],
            $this->siteLanguages(),
        );
    }
}
