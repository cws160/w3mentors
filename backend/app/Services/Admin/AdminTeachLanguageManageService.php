<?php

namespace App\Services\Admin;

use App\Models\Configuration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminTeachLanguageManageService
{
    private const MAX_LEVEL = 2;

    private const TYPE_TEACHING_LANGUAGE_IMAGE = 42;

    /** @return array<string, mixed> */
    public function context(int $parentId, int $langId, int $excludeId = 0): array
    {
        $managePrices = (int) Configuration::getValue('CONF_MANAGE_PRICES', 0) === 1;
        $parentRow = $parentId > 0
            ? DB::table('tbl_teach_languages')->where('tlang_id', $parentId)->first()
            : null;
        $level = $parentRow ? (int) ($parentRow->tlang_level ?? 0) : 0;
        $backId = $parentRow ? (int) ($parentRow->tlang_parent ?? 0) : 0;

        return [
            'parent_id' => $parentId,
            'back_id' => $backId,
            'level' => $level,
            'manage_prices' => $managePrices,
            'show_subcategories' => ($level + 1) < self::MAX_LEVEL,
            'show_featured' => $parentId < 1,
            'can_upload_media' => $parentId < 1,
            'breadcrumb' => $this->breadcrumb($parentId, $langId),
            'parent_options' => $this->parentOptions($langId, $excludeId),
        ];
    }

    /** @return array<int, array{id: int, name: string}> */
    public function parentOptions(int $langId, int $excludeId = 0): array
    {
        $ignoreIds = $excludeId > 0 ? [$excludeId] : [];
        $teachLanguages = $this->getTeachLangNames($langId, 0, false);

        foreach ($teachLanguages as $key => $langs) {
            if (
                in_array($langs['tlang_parent'], $ignoreIds, true)
                || in_array($langs['tlang_id'], $ignoreIds, true)
                || (int) $langs['tlang_level'] >= self::MAX_LEVEL
            ) {
                $ignoreIds[] = $langs['tlang_id'];
                unset($teachLanguages[$key]);
            }
        }

        $options = [];
        foreach ($teachLanguages as $lang) {
            $options[] = [
                'id' => (int) $lang['tlang_id'],
                'name' => (string) $lang['tlang_name'],
            ];
        }

        return $options;
    }

    /** @return array<int, array{id: int, name: string}> */
    public function autocomplete(string $keyword, int $langId, int $limit = 20): array
    {
        $keyword = trim($keyword);
        if ($keyword === '') {
            return [];
        }

        $options = [];
        foreach ($this->getTeachLangNames($langId, 0, true) as $lang) {
            $name = (string) $lang['tlang_name'];
            if (stripos($name, $keyword) === false) {
                continue;
            }
            $options[] = [
                'id' => (int) $lang['tlang_id'],
                'name' => $name,
            ];
            if (count($options) >= $limit) {
                break;
            }
        }

        return $options;
    }

    public function show(int $tlangId, int $langId): ?array
    {
        $row = DB::table('tbl_teach_languages as tlang')
            ->leftJoin('tbl_teach_languages_lang as tlanglang', function ($join) use ($langId) {
                $join->on('tlanglang.tlanglang_tlang_id', '=', 'tlang.tlang_id')
                    ->where('tlanglang.tlanglang_lang_id', '=', $langId);
            })
            ->where('tlang.tlang_id', $tlangId)
            ->first([
                'tlang.tlang_id',
                'tlang.tlang_identifier',
                'tlang.tlang_slug',
                'tlang.tlang_parent',
                'tlang.tlang_featured',
                'tlang.tlang_active',
                'tlang.tlang_subcategories',
                'tlang.tlang_min_price',
                'tlang.tlang_max_price',
                'tlang.tlang_hourly_price',
                DB::raw('IFNULL(tlanglang.tlang_name, tlang.tlang_identifier) as tlang_name'),
                DB::raw('IFNULL(tlanglang.tlang_description, \'\') as tlang_description'),
            ]);

        if (! $row) {
            return null;
        }

        return [
            'tlang_id' => (int) $row->tlang_id,
            'tlang_identifier' => (string) $row->tlang_identifier,
            'tlang_slug' => (string) ($row->tlang_slug ?? ''),
            'tlang_parent' => (int) ($row->tlang_parent ?? 0),
            'tlang_featured' => (int) ($row->tlang_featured ?? 0),
            'tlang_active' => (int) ($row->tlang_active ?? 0),
            'tlang_subcategories' => (int) ($row->tlang_subcategories ?? 0),
            'tlang_min_price' => (float) ($row->tlang_min_price ?? 0),
            'tlang_max_price' => (float) ($row->tlang_max_price ?? 0),
            'tlang_hourly_price' => (float) ($row->tlang_hourly_price ?? 0),
            'tlang_name' => (string) $row->tlang_name,
            'tlang_description' => (string) ($row->tlang_description ?? ''),
        ];
    }

    /** @param  array<string, mixed>  $payload */
    public function save(int $tlangId, array $payload, int $langId): int
    {
        $identifier = trim((string) ($payload['tlang_identifier'] ?? ''));
        $slug = trim((string) ($payload['tlang_slug'] ?? ''));
        $name = trim((string) ($payload['tlang_name'] ?? ''));
        $description = trim((string) ($payload['tlang_description'] ?? ''));
        $saveLang = array_key_exists('tlang_name', $payload) || array_key_exists('tlang_description', $payload);
        $parentId = (int) ($payload['tlang_parent'] ?? 0);
        $featured = (int) ($payload['tlang_featured'] ?? 0) === 1 ? 1 : 0;
        $active = (int) ($payload['tlang_active'] ?? 1);
        $minPrice = (float) ($payload['tlang_min_price'] ?? 0);
        $maxPrice = (float) ($payload['tlang_max_price'] ?? 0);
        $hourlyPrice = (float) ($payload['tlang_hourly_price'] ?? 0);
        $managePrices = (int) Configuration::getValue('CONF_MANAGE_PRICES', 0) === 1;

        if ($identifier === '') {
            throw new \InvalidArgumentException('Language identifier is required.');
        }

        if ($slug === '') {
            $slug = Str::slug($identifier, '-');
        }

        $duplicate = DB::table('tbl_teach_languages')
            ->where('tlang_parent', $parentId)
            ->whereRaw('LOWER(tlang_identifier) = ?', [strtolower($identifier)])
            ->where('tlang_id', '!=', $tlangId)
            ->exists();

        if ($duplicate) {
            throw new \InvalidArgumentException('Identifier is already in use.');
        }

        if ($parentId > 0 && $featured === 1) {
            throw new \InvalidArgumentException('Sub languages cannot be marked as featured.');
        }

        if ($tlangId > 0 && $active === 0 && $this->hasActiveChildren($tlangId)) {
            throw new \InvalidArgumentException('Cannot mark inactive as there are active sub languages attached.');
        }

        $existing = $tlangId > 0
            ? DB::table('tbl_teach_languages')->where('tlang_id', $tlangId)->first()
            : null;

        $hasSubcategories = (int) ($existing->tlang_subcategories ?? 0);

        $data = [
            'tlang_identifier' => $identifier,
            'tlang_slug' => $slug,
            'tlang_parent' => $parentId,
            'tlang_featured' => $parentId < 1 ? $featured : 0,
            'tlang_active' => $active,
        ];

        if ($hasSubcategories < 1) {
            if ($managePrices) {
                $data['tlang_hourly_price'] = $hourlyPrice;
            } else {
                $data['tlang_min_price'] = $minPrice;
                $data['tlang_max_price'] = $maxPrice;
            }
        }

        if ($tlangId > 0) {
            DB::table('tbl_teach_languages')->where('tlang_id', $tlangId)->update($data);
        } else {
            $maxOrder = (int) DB::table('tbl_teach_languages')->max('tlang_order');
            $data['tlang_order'] = $maxOrder + 1;
            $data['tlang_subcategories'] = 0;
            $data['tlang_level'] = 0;
            $data['tlang_parentids'] = '';
            $tlangId = (int) DB::table('tbl_teach_languages')->insertGetId($data);
        }

        if ($saveLang) {
            $this->saveLanguageRow($tlangId, $langId, $name, $description);

            if (! empty($payload['update_langs_data'])) {
                foreach ($this->siteLanguages() as $language) {
                    if ($language['id'] === $langId) {
                        continue;
                    }
                    $this->saveLanguageRow($tlangId, $language['id'], $name, $description);
                }
            }
        }

        $this->syncParentsAndLevels($tlangId, $parentId);
        $this->updateSubCatCount();

        if ($parentId > 0) {
            $this->removeUserTeachLanguages([$parentId]);
        }
        if ($active === 0) {
            $this->removeUserTeachLanguages([$tlangId]);
        } elseif ($managePrices && $hasSubcategories < 1) {
            DB::table('tbl_user_teach_languages')
                ->where('utlang_tlang_id', $tlangId)
                ->update(['utlang_price' => $hourlyPrice]);
        }

        return $tlangId;
    }

    public function changeStatus(int $tlangId, int $status): bool
    {
        if (! $this->exists($tlangId)) {
            return false;
        }

        if ($status === 0 && $this->hasActiveChildren($tlangId)) {
            throw new \InvalidArgumentException('Cannot mark inactive as there are active sub languages attached.');
        }

        DB::table('tbl_teach_languages')
            ->where('tlang_id', $tlangId)
            ->update(['tlang_active' => $status]);

        if ($status === 0) {
            $this->removeUserTeachLanguages([$tlangId]);
        }

        return true;
    }

    public function delete(int $tlangId): bool
    {
        $row = DB::table('tbl_teach_languages')->where('tlang_id', $tlangId)->first();
        if (! $row) {
            return false;
        }

        if ((int) ($row->tlang_subcategories ?? 0) > 0) {
            throw new \InvalidArgumentException('Languages attached with sub languages cannot be deleted.');
        }

        $this->removeUserTeachLanguages([$tlangId]);
        DB::table('tbl_teach_languages_lang')->where('tlanglang_tlang_id', $tlangId)->delete();
        DB::table('tbl_teach_languages')->where('tlang_id', $tlangId)->delete();
        $this->updateSubCatCount();

        return true;
    }

    /** @param  array<int, int|string>  $ids */
    public function updateOrder(array $ids): bool
    {
        if ($ids === []) {
            return false;
        }

        foreach (array_values($ids) as $order => $id) {
            $tlangId = (int) $id;
            if ($tlangId < 1) {
                continue;
            }
            DB::table('tbl_teach_languages')
                ->where('tlang_id', $tlangId)
                ->update(['tlang_order' => $order]);
        }

        return true;
    }

    /** @return array<int, array<string, mixed>> */
    private function getTeachLangNames(int $langId, int $parentId = 0, bool $activeOnly = true, array &$array = []): array
    {
        $query = DB::table('tbl_teach_languages as tlang')
            ->leftJoin('tbl_teach_languages_lang as tlanglang', function ($join) use ($langId) {
                $join->on('tlanglang.tlanglang_tlang_id', '=', 'tlang.tlang_id')
                    ->where('tlanglang.tlanglang_lang_id', '=', $langId);
            })
            ->where('tlang.tlang_parent', $parentId)
            ->whereNotNull('tlang.tlang_slug')
            ->orderBy('tlang.tlang_order')
            ->orderByDesc('tlang.tlang_id');

        if ($activeOnly) {
            $query->where('tlang.tlang_active', 1);
        }

        $rows = $query->get([
            'tlang.tlang_id',
            'tlang.tlang_parent',
            'tlang.tlang_level',
            'tlang.tlang_subcategories',
            DB::raw('IFNULL(tlanglang.tlang_name, tlang.tlang_identifier) as tlang_name'),
        ]);

        foreach ($rows as $langs) {
            $name = (string) $langs->tlang_name;
            $parentKey = (int) ($langs->tlang_parent ?? 0);
            if (! empty($array[$parentKey])) {
                $name = $array[$parentKey]['tlang_name'].' » '.$name;
            }
            $tlangId = (int) $langs->tlang_id;
            $array[$tlangId] = [
                'tlang_id' => $tlangId,
                'tlang_parent' => $parentKey,
                'tlang_level' => (int) ($langs->tlang_level ?? 0),
                'tlang_name' => $name,
                'tlang_subcategories' => (int) ($langs->tlang_subcategories ?? 0),
            ];
            if ((int) ($langs->tlang_subcategories ?? 0) > 0) {
                $this->getTeachLangNames($langId, $tlangId, $activeOnly, $array);
            }
        }

        return $array;
    }

    /** @return array<int, array<string, mixed>> */
    private function breadcrumb(int $parentId, int $langId): array
    {
        $nodes = [
            ['id' => 0, 'title' => 'Root languages', 'path' => '/admin/teach-language'],
        ];

        if ($parentId < 1) {
            return $nodes;
        }

        $parent = DB::table('tbl_teach_languages as tlang')
            ->leftJoin('tbl_teach_languages_lang as tlanglang', function ($join) use ($langId) {
                $join->on('tlanglang.tlanglang_tlang_id', '=', 'tlang.tlang_id')
                    ->where('tlanglang.tlanglang_lang_id', '=', $langId);
            })
            ->where('tlang.tlang_id', $parentId)
            ->first([
                'tlang.tlang_id',
                'tlang.tlang_parent',
                'tlang.tlang_parentids',
                DB::raw('IFNULL(tlanglang.tlang_name, tlang.tlang_identifier) as title'),
            ]);

        if (! $parent) {
            return $nodes;
        }

        $chainIds = array_values(array_filter(array_unique(array_merge(
            array_reverse(array_filter(array_map('intval', explode(',', (string) ($parent->tlang_parentids ?? ''))))),
            [(int) $parent->tlang_id],
        ))));

        if ($chainIds === []) {
            return $nodes;
        }

        $titles = DB::table('tbl_teach_languages as tlang')
            ->leftJoin('tbl_teach_languages_lang as tlanglang', function ($join) use ($langId) {
                $join->on('tlanglang.tlanglang_tlang_id', '=', 'tlang.tlang_id')
                    ->where('tlanglang.tlanglang_lang_id', '=', $langId);
            })
            ->whereIn('tlang.tlang_id', $chainIds)
            ->get([
                'tlang.tlang_id as id',
                DB::raw('IFNULL(tlanglang.tlang_name, tlang.tlang_identifier) as title'),
            ])
            ->keyBy('id');

        foreach ($chainIds as $id) {
            $title = $titles->get($id)?->title ?? (string) $id;
            $nodes[] = [
                'id' => $id,
                'title' => (string) $title,
                'path' => '/admin/teach-language?parent_id='.$id,
            ];
        }

        return $nodes;
    }

    private function syncParentsAndLevels(int $tlangId, int $parentId): void
    {
        $subIds = DB::table('tbl_teach_languages')
            ->where('tlang_id', $tlangId)
            ->orWhere('tlang_parent', $tlangId)
            ->orWhereRaw('FIND_IN_SET(?, tlang_parentids)', [$tlangId])
            ->pluck('tlang_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $ids = array_values(array_unique(array_merge([$tlangId, $parentId], $subIds)));

        foreach ($ids as $id) {
            if ($id < 1) {
                continue;
            }
            [$level, $parentIds] = $this->langLevel($id);
            if ($level > self::MAX_LEVEL) {
                throw new \InvalidArgumentException('Maximum category depth exceeded.');
            }
            DB::table('tbl_teach_languages')
                ->where('tlang_id', $id)
                ->update([
                    'tlang_level' => $level,
                    'tlang_parentids' => implode(',', array_values($parentIds)),
                ]);
        }
    }

    /** @return array{0: int, 1: array<int, int>} */
    private function langLevel(int $tlangId, array $parentIds = [], int $level = 0): array
    {
        if ($level > 0) {
            $parentIds[$tlangId] = $tlangId;
        }

        $parentId = (int) (DB::table('tbl_teach_languages')->where('tlang_id', $tlangId)->value('tlang_parent') ?? 0);
        if ($parentId > 0) {
            $level++;

            return $this->langLevel($parentId, $parentIds, $level);
        }

        return [$level, $parentIds];
    }

    private function updateSubCatCount(): void
    {
        DB::statement(
            'UPDATE tbl_teach_languages tmp
            LEFT JOIN (
                SELECT COUNT(*) AS ttl, tlang_parent
                FROM tbl_teach_languages
                GROUP BY tlang_parent
            ) tmp1 ON tmp.tlang_id = tmp1.tlang_parent
            SET tmp.tlang_subcategories = IFNULL(tmp1.ttl, 0)
            WHERE tmp.tlang_level < ?',
            [self::MAX_LEVEL],
        );
    }

    private function hasActiveChildren(int $tlangId): bool
    {
        return DB::table('tbl_teach_languages')
            ->where('tlang_parent', $tlangId)
            ->where('tlang_active', 1)
            ->exists();
    }

    /** @param  array<int, int>  $tlangIds */
    private function removeUserTeachLanguages(array $tlangIds): void
    {
        if ($tlangIds === []) {
            return;
        }

        DB::table('tbl_user_teach_languages')
            ->whereIn('utlang_tlang_id', $tlangIds)
            ->delete();
    }

    private function exists(int $tlangId): bool
    {
        return DB::table('tbl_teach_languages')->where('tlang_id', $tlangId)->exists();
    }

    private function saveLanguageRow(int $tlangId, int $langId, string $name, string $description): void
    {
        DB::table('tbl_teach_languages_lang')->updateOrInsert(
            ['tlanglang_tlang_id' => $tlangId, 'tlanglang_lang_id' => $langId],
            [
                'tlanglang_tlang_id' => $tlangId,
                'tlanglang_lang_id' => $langId,
                'tlang_name' => $name,
                'tlang_description' => $description,
            ],
        );
    }

    /** @return list<array{id: int, name: string}> */
    private function siteLanguages(): array
    {
        return DB::table('tbl_languages')
            ->where('language_active', 1)
            ->orderBy('language_id')
            ->get(['language_id as id', 'language_name as name'])
            ->map(fn ($row) => ['id' => (int) $row->id, 'name' => (string) $row->name])
            ->all();
    }

    /** @return array<string, mixed>|null */
    public function mediaForm(int $tlangId): ?array
    {
        $row = DB::table('tbl_teach_languages')->where('tlang_id', $tlangId)->first(['tlang_parent']);
        if (! $row || (int) ($row->tlang_parent ?? 0) > 0) {
            return null;
        }

        return [
            'tlang_id' => $tlangId,
            'has_image' => $this->hasImage($tlangId),
            'dimensions' => '60x60',
            'allowed_extensions' => 'png, jpg, jpeg',
        ];
    }

    public function uploadImage(int $tlangId, \Illuminate\Http\UploadedFile $file): array
    {
        $row = DB::table('tbl_teach_languages')->where('tlang_id', $tlangId)->first(['tlang_parent']);
        if (! $row || (int) ($row->tlang_parent ?? 0) > 0) {
            return ['ok' => false, 'message' => 'Request not authorised to upload'];
        }

        $ext = strtolower($file->getClientOriginalExtension());
        $allowed = ['png', 'jpg', 'jpeg'];
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

        $fileName = 'teach_lang_'.$tlangId.'_'.time().'.'.$ext;
        $relativePath = date('Y/m').'/'.$fileName;
        $targetDir = $uploadRoot.'/'.dirname($relativePath);
        if (! is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }

        $file->move($targetDir, $fileName);

        $oldFiles = DB::table('tbl_attached_files')
            ->where('file_type', self::TYPE_TEACHING_LANGUAGE_IMAGE)
            ->where('file_record_id', $tlangId)
            ->get();

        DB::table('tbl_attached_files')
            ->where('file_type', self::TYPE_TEACHING_LANGUAGE_IMAGE)
            ->where('file_record_id', $tlangId)
            ->delete();

        DB::table('tbl_attached_files')->insert([
            'file_type' => self::TYPE_TEACHING_LANGUAGE_IMAGE,
            'file_lang_id' => 0,
            'file_record_id' => $tlangId,
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
    public function removeImage(int $tlangId): array
    {
        $row = DB::table('tbl_teach_languages')->where('tlang_id', $tlangId)->first(['tlang_parent']);
        if (! $row || (int) ($row->tlang_parent ?? 0) > 0) {
            return ['ok' => false, 'message' => 'Request not authorised to upload'];
        }

        $oldFiles = DB::table('tbl_attached_files')
            ->where('file_type', self::TYPE_TEACHING_LANGUAGE_IMAGE)
            ->where('file_record_id', $tlangId)
            ->get();

        DB::table('tbl_attached_files')
            ->where('file_type', self::TYPE_TEACHING_LANGUAGE_IMAGE)
            ->where('file_record_id', $tlangId)
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

    private function hasImage(int $tlangId): bool
    {
        return DB::table('tbl_attached_files')
            ->where('file_type', self::TYPE_TEACHING_LANGUAGE_IMAGE)
            ->where('file_record_id', $tlangId)
            ->exists();
    }
}
