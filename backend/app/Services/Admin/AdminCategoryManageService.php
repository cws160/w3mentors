<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\DB;

class AdminCategoryManageService
{
    public const TYPE_COURSE = 1;

    public const TYPE_QUESTION = 2;

    /** @return array<string, mixed> */
    public function createForm(int $langId, int $cateType, int $defaultParentId = 0): array
    {
        return [
            'site_languages' => $this->siteLanguages(),
            'parent_categories' => $this->parentCategories($langId, $cateType),
            'default_parent_id' => $defaultParentId,
            'cate_type' => $cateType,
            'show_featured' => $cateType === self::TYPE_COURSE && $defaultParentId === 0,
        ];
    }

    /** @return array<string, mixed>|null */
    public function show(int $cateId): ?array
    {
        $row = DB::table('tbl_categories')
            ->where('cate_id', $cateId)
            ->whereNull('cate_deleted')
            ->first([
                'cate_id',
                'cate_identifier',
                'cate_parent',
                'cate_featured',
                'cate_status',
                'cate_type',
                'cate_subcategories',
            ]);

        if (! $row) {
            return null;
        }

        return [
            'cate_id' => (int) $row->cate_id,
            'cate_identifier' => (string) $row->cate_identifier,
            'cate_parent' => (int) $row->cate_parent,
            'cate_featured' => (int) $row->cate_featured,
            'cate_status' => (int) $row->cate_status,
            'cate_type' => (int) $row->cate_type,
            'cate_subcategories' => (int) $row->cate_subcategories,
            'show_featured' => (int) $row->cate_type === self::TYPE_COURSE && (int) $row->cate_parent === 0,
        ];
    }

    /** @param array<string, mixed> $payload @return array{ok: bool, message?: string, id?: int} */
    public function store(array $payload, int $langId = 1): array
    {
        $cateId = (int) ($payload['cate_id'] ?? 0);
        $cateType = (int) ($payload['cate_type'] ?? self::TYPE_COURSE);
        $identifier = trim((string) ($payload['cate_identifier'] ?? ''));
        $parentId = (int) ($payload['cate_parent'] ?? 0);
        $status = (int) ($payload['cate_status'] ?? 1);
        $featured = (int) ($payload['cate_featured'] ?? 0);

        if ($identifier === '') {
            return ['ok' => false, 'message' => 'Identifier is required'];
        }

        if ($cateId > 0 && ! $this->exists($cateId)) {
            return ['ok' => false, 'message' => 'Invalid request'];
        }

        if ($cateId > 0) {
            $existing = $this->show($cateId);
            if ($parentId > 0 && (int) ($existing['cate_subcategories'] ?? 0) > 0) {
                return ['ok' => false, 'message' => 'Cannot assign parent as this category has subcategories'];
            }
        }

        if ($parentId > 0 || $cateType !== self::TYPE_COURSE) {
            $featured = 0;
        }

        if ($cateId > 0) {
            DB::table('tbl_categories')
                ->where('cate_id', $cateId)
                ->update([
                    'cate_identifier' => $identifier,
                    'cate_parent' => $parentId,
                    'cate_featured' => $featured,
                    'cate_status' => $status,
                    'cate_updated' => now(),
                ]);
        } else {
            $maxOrder = (int) DB::table('tbl_categories')->where('cate_type', $cateType)->max('cate_order');
            $cateId = (int) DB::table('tbl_categories')->insertGetId([
                'cate_identifier' => $identifier,
                'cate_type' => $cateType,
                'cate_parent' => $parentId,
                'cate_subcategories' => 0,
                'cate_records' => 0,
                'cate_featured' => $featured,
                'cate_order' => $maxOrder + 1,
                'cate_status' => $status,
                'cate_created' => now(),
            ]);
            $this->refreshSubcategoryCounts();
        }

        return ['ok' => true, 'id' => $cateId];
    }

    /** @return array<string, mixed>|null */
    public function langForm(int $cateId, int $langId): ?array
    {
        if (! $this->exists($cateId)) {
            return null;
        }

        $row = DB::table('tbl_categories_lang')
            ->where('catelang_cate_id', $cateId)
            ->where('catelang_lang_id', $langId)
            ->first(['cate_name', 'cate_details']);

        return [
            'catelang_cate_id' => $cateId,
            'catelang_lang_id' => $langId,
            'cate_name' => (string) ($row->cate_name ?? ''),
            'cate_details' => (string) ($row->cate_details ?? ''),
            'layout_direction' => $this->layoutDirection($langId),
        ];
    }

    /** @param array<string, mixed> $payload @return array{ok: bool, message?: string} */
    public function storeLang(array $payload): array
    {
        $cateId = (int) ($payload['catelang_cate_id'] ?? 0);
        $langId = (int) ($payload['catelang_lang_id'] ?? 0);
        $name = trim((string) ($payload['cate_name'] ?? ''));
        $details = trim((string) ($payload['cate_details'] ?? ''));

        if ($cateId < 1 || $langId < 1 || ! $this->exists($cateId)) {
            return ['ok' => false, 'message' => 'Invalid request'];
        }

        if ($name === '' || $details === '') {
            return ['ok' => false, 'message' => 'Name and description are required'];
        }

        $exists = DB::table('tbl_categories_lang')
            ->where('catelang_cate_id', $cateId)
            ->where('catelang_lang_id', $langId)
            ->exists();

        if ($exists) {
            DB::table('tbl_categories_lang')
                ->where('catelang_cate_id', $cateId)
                ->where('catelang_lang_id', $langId)
                ->update([
                    'cate_name' => $name,
                    'cate_details' => $details,
                ]);
        } else {
            DB::table('tbl_categories_lang')->insert([
                'catelang_lang_id' => $langId,
                'catelang_cate_id' => $cateId,
                'cate_name' => $name,
                'cate_details' => $details,
            ]);
        }

        return ['ok' => true];
    }

    /** @return array{ok: bool, message?: string} */
    public function changeStatus(int $cateId, int $status): array
    {
        if (! $this->exists($cateId)) {
            return ['ok' => false, 'message' => 'Invalid request'];
        }

        if ($status === 0) {
            $row = DB::table('tbl_categories')->where('cate_id', $cateId)->first(['cate_records', 'cate_type']);
            if ($row && (int) $row->cate_records > 0) {
                return [
                    'ok' => false,
                    'message' => 'Categories attached with courses cannot be marked inactive',
                ];
            }
        }

        DB::table('tbl_categories')
            ->where('cate_id', $cateId)
            ->update([
                'cate_status' => $status,
                'cate_updated' => now(),
            ]);

        return ['ok' => true];
    }

    /** @return array{ok: bool, message?: string} */
    public function delete(int $cateId): array
    {
        $row = DB::table('tbl_categories')
            ->where('cate_id', $cateId)
            ->whereNull('cate_deleted')
            ->first(['cate_records', 'cate_subcategories', 'cate_type', 'cate_parent']);

        if (! $row) {
            return ['ok' => false, 'message' => 'Invalid request'];
        }

        if ((int) $row->cate_records > 0) {
            $message = (int) $row->cate_type === self::TYPE_COURSE
                ? 'Categories attached with courses cannot be deleted'
                : 'Categories attached with questions cannot be deleted';

            return ['ok' => false, 'message' => $message];
        }

        if ((int) $row->cate_subcategories > 0) {
            return ['ok' => false, 'message' => 'Categories attached with subcategories cannot be deleted'];
        }

        DB::table('tbl_categories')
            ->where('cate_id', $cateId)
            ->update(['cate_deleted' => now()]);

        $this->refreshSubcategoryCounts();

        return ['ok' => true];
    }

    private const TYPE_CATEGORY_IMAGE = 64;

    /** @return array<string, mixed>|null */
    public function mediaForm(int $cateId): ?array
    {
        if (! $this->exists($cateId)) {
            return null;
        }

        return [
            'category_id' => $cateId,
            'has_image' => $this->hasImage($cateId),
        ];
    }

    public function uploadImage(int $cateId, \Illuminate\Http\UploadedFile $file): array
    {
        if (! $this->exists($cateId)) {
            return ['ok' => false, 'message' => 'Invalid request'];
        }

        $ext = strtolower($file->getClientOriginalExtension());
        $allowed = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
        if (! in_array($ext, $allowed, true)) {
            return ['ok' => false, 'message' => 'Invalid file type'];
        }

        if ($file->getSize() > 4 * 1024 * 1024) {
            return ['ok' => false, 'message' => 'File is too large'];
        }

        $uploadRoot = public_path('user-uploads');
        if (! is_dir($uploadRoot)) {
            @mkdir($uploadRoot, 0755, true);
        }

        $fileName = 'category_'.$cateId.'_'.time().'.'.$ext;
        $relativePath = date('Y/m').'/'.$fileName;
        $targetDir = $uploadRoot.'/'.dirname($relativePath);
        if (! is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }

        $file->move($targetDir, $fileName);

        $oldFiles = DB::table('tbl_attached_files')
            ->where('file_type', self::TYPE_CATEGORY_IMAGE)
            ->where('file_record_id', $cateId)
            ->get();

        DB::table('tbl_attached_files')
            ->where('file_type', self::TYPE_CATEGORY_IMAGE)
            ->where('file_record_id', $cateId)
            ->delete();

        DB::table('tbl_attached_files')->insert([
            'file_type' => self::TYPE_CATEGORY_IMAGE,
            'file_lang_id' => 0,
            'file_record_id' => $cateId,
            'file_name' => $fileName,
            'file_path' => $relativePath,
            'file_order' => 0,
            'file_added' => now()->format('Y-m-d H:i:s'),
        ]);

        foreach ($oldFiles as $old) {
            if (! empty($old->file_path)) {
                $oldPath = $uploadRoot.'/'.$old->file_path;
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }
        }

        return ['ok' => true];
    }

    /** @return array{ok: bool, message?: string} */
    public function removeImage(int $cateId): array
    {
        if (! $this->exists($cateId)) {
            return ['ok' => false, 'message' => 'Invalid request'];
        }

        $oldFiles = DB::table('tbl_attached_files')
            ->where('file_type', self::TYPE_CATEGORY_IMAGE)
            ->where('file_record_id', $cateId)
            ->get();

        DB::table('tbl_attached_files')
            ->where('file_type', self::TYPE_CATEGORY_IMAGE)
            ->where('file_record_id', $cateId)
            ->delete();

        $uploadRoot = public_path('user-uploads');
        foreach ($oldFiles as $old) {
            if (! empty($old->file_path)) {
                $oldPath = $uploadRoot.'/'.$old->file_path;
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }
        }

        return ['ok' => true];
    }

    private function hasImage(int $cateId): bool
    {
        return DB::table('tbl_attached_files')
            ->where('file_type', self::TYPE_CATEGORY_IMAGE)
            ->where('file_record_id', $cateId)
            ->exists();
    }

    /** @param array<int, int|string> $order @return array{ok: bool, message?: string} */
    public function updateOrder(array $order, int $cateType = self::TYPE_COURSE): array
    {
        if ($order === []) {
            return ['ok' => false, 'message' => 'Invalid request'];
        }

        foreach (array_values($order) as $index => $id) {
            $cateId = (int) $id;
            if ($cateId < 1) {
                continue;
            }
            DB::table('tbl_categories')
                ->where('cate_id', $cateId)
                ->where('cate_type', $cateType)
                ->whereNull('cate_deleted')
                ->update(['cate_order' => $index]);
        }

        return ['ok' => true];
    }

    private function exists(int $cateId): bool
    {
        return DB::table('tbl_categories')
            ->where('cate_id', $cateId)
            ->whereNull('cate_deleted')
            ->exists();
    }

    private function refreshSubcategoryCounts(): void
    {
        DB::statement('
            UPDATE tbl_categories cate
            LEFT JOIN (
                SELECT COUNT(cate_id) AS cate_subcategories, cate_parent
                FROM tbl_categories
                WHERE cate_deleted IS NULL
                GROUP BY cate_parent
            ) c ON cate.cate_id = c.cate_parent
            SET cate.cate_subcategories = IFNULL(c.cate_subcategories, 0)
        ');
    }

    /** @return array<int, array{id: int, name: string}> */
    private function parentCategories(int $langId, int $cateType): array
    {
        return DB::table('tbl_categories as c')
            ->leftJoin('tbl_categories_lang as cl', function ($join) use ($langId) {
                $join->on('cl.catelang_cate_id', '=', 'c.cate_id')
                    ->where('cl.catelang_lang_id', '=', $langId);
            })
            ->whereNull('c.cate_deleted')
            ->where('c.cate_parent', 0)
            ->where('c.cate_type', $cateType)
            ->orderBy('c.cate_order')
            ->get([
                'c.cate_id as id',
                DB::raw('IFNULL(cl.cate_name, c.cate_identifier) as name'),
            ])
            ->map(fn ($row) => ['id' => (int) $row->id, 'name' => (string) $row->name])
            ->all();
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
