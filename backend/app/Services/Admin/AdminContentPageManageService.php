<?php

namespace App\Services\Admin;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminContentPageManageService
{
    public const LAYOUT_BLOCKS = 1;

    public const LAYOUT_CONTENT = 2;

    public const LAYOUT_BLOCK_COUNT = 2;

    private const TYPE_CPAGE_BACKGROUND_IMAGE = 27;

    /** @return array<string, mixed> */
    public function createForm(): array
    {
        return [
            'layouts' => $this->layouts(),
            'site_languages' => $this->siteLanguages(),
        ];
    }

    /** @return array<string, mixed>|null */
    public function show(int $pageId): ?array
    {
        $row = DB::table('tbl_content_pages')
            ->where('cpage_id', $pageId)
            ->where('cpage_deleted', 0)
            ->first(['cpage_id', 'cpage_identifier', 'cpage_layout']);

        if (! $row) {
            return null;
        }

        return [
            'cpage_id' => (int) $row->cpage_id,
            'cpage_identifier' => (string) $row->cpage_identifier,
            'cpage_layout' => (int) $row->cpage_layout,
            'layouts' => $this->layouts(),
            'site_languages' => $this->siteLanguages(),
        ];
    }

    /** @param array<string, mixed> $data @return array{ok: bool, message?: string, id?: int, next_lang_id?: int} */
    public function saveGeneral(array $data): array
    {
        $pageId = (int) ($data['cpage_id'] ?? 0);
        $identifier = trim((string) ($data['cpage_identifier'] ?? ''));
        $layout = (int) ($data['cpage_layout'] ?? 0);

        if ($identifier === '' || ! isset($this->layouts()[$layout])) {
            return ['ok' => false, 'message' => 'Please fill all required fields.'];
        }

        $duplicate = DB::table('tbl_content_pages')
            ->where('cpage_identifier', $identifier)
            ->where('cpage_deleted', 0)
            ->when($pageId > 0, fn ($query) => $query->where('cpage_id', '!=', $pageId))
            ->exists();

        if ($duplicate) {
            return ['ok' => false, 'message' => 'Page identifier already exists.'];
        }

        $values = [
            'cpage_identifier' => $identifier,
            'cpage_layout' => $layout,
            'cpage_deleted' => 0,
        ];

        if ($pageId > 0) {
            DB::table('tbl_content_pages')->where('cpage_id', $pageId)->update($values);
        } else {
            $pageId = (int) DB::table('tbl_content_pages')->insertGetId($values);
        }

        return [
            'ok' => true,
            'id' => $pageId,
            'next_lang_id' => $this->nextMissingLangId($pageId),
        ];
    }

    /** @return array<string, mixed>|null */
    public function langForm(int $pageId, int $langId): ?array
    {
        $page = $this->show($pageId);
        if (! $page) {
            return null;
        }

        $lang = DB::table('tbl_content_pages_lang')
            ->where('cpagelang_cpage_id', $pageId)
            ->where('cpagelang_lang_id', $langId)
            ->first(['cpage_title', 'cpage_content', 'cpage_image_title']);

        $blocks = [];
        for ($i = 1; $i <= self::LAYOUT_BLOCK_COUNT; $i++) {
            $blocks[$i] = '';
        }

        $blockRows = DB::table('tbl_content_pages_block_lang')
            ->where('cpblocklang_cpage_id', $pageId)
            ->where('cpblocklang_lang_id', $langId)
            ->get(['cpblocklang_block_id', 'cpblocklang_text']);

        foreach ($blockRows as $row) {
            $blocks[(int) $row->cpblocklang_block_id] = (string) $row->cpblocklang_text;
        }

        return [
            ...$page,
            'lang_id' => $langId,
            'cpage_title' => (string) ($lang->cpage_title ?? ''),
            'cpage_content' => (string) ($lang->cpage_content ?? ''),
            'cpage_image_title' => (string) ($lang->cpage_image_title ?? ''),
            'blocks' => $blocks,
            'bg_image' => $this->backgroundImage($pageId, $langId),
        ];
    }

    /** @param array<string, mixed> $data @return array{ok: bool, message?: string, id?: int, next_lang_id?: int} */
    public function saveLang(int $pageId, int $langId, array $data): array
    {
        $page = $this->show($pageId);
        if (! $page) {
            return ['ok' => false, 'message' => 'Invalid request'];
        }

        $title = trim((string) ($data['cpage_title'] ?? ''));
        if ($title === '') {
            return ['ok' => false, 'message' => 'Page title is required.'];
        }

        $values = [
            'cpagelang_cpage_id' => $pageId,
            'cpagelang_lang_id' => $langId,
            'cpage_title' => $title,
            'cpage_content' => (string) ($data['cpage_content'] ?? ''),
            'cpage_image_title' => (string) ($data['cpage_image_title'] ?? ''),
        ];

        DB::table('tbl_content_pages_lang')->updateOrInsert(
            ['cpagelang_cpage_id' => $pageId, 'cpagelang_lang_id' => $langId],
            $values,
        );

        if ((int) $page['cpage_layout'] === self::LAYOUT_BLOCKS) {
            $blocks = (array) ($data['blocks'] ?? []);
            for ($i = 1; $i <= self::LAYOUT_BLOCK_COUNT; $i++) {
                DB::table('tbl_content_pages_block_lang')->updateOrInsert(
                    [
                        'cpblocklang_cpage_id' => $pageId,
                        'cpblocklang_lang_id' => $langId,
                        'cpblocklang_block_id' => $i,
                    ],
                    ['cpblocklang_text' => (string) ($blocks[$i] ?? $blocks[(string) $i] ?? '')],
                );
            }
        }

        return [
            'ok' => true,
            'id' => $pageId,
            'next_lang_id' => $this->nextMissingLangId($pageId),
        ];
    }

    /** @return array{ok: bool, message?: string, id?: int, bg_image?: array<string, mixed>|null} */
    public function uploadBackgroundImage(int $pageId, int $langId, ?UploadedFile $file): array
    {
        $page = $this->show($pageId);
        if (! $page || (int) $page['cpage_layout'] !== self::LAYOUT_BLOCKS) {
            return ['ok' => false, 'message' => 'Invalid request'];
        }
        if (! $file instanceof UploadedFile) {
            return ['ok' => false, 'message' => 'Please upload image'];
        }

        $ext = strtolower($file->getClientOriginalExtension());
        if (! in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp'], true)) {
            return ['ok' => false, 'message' => 'Invalid file type'];
        }
        if ($file->getSize() > 4 * 1024 * 1024) {
            return ['ok' => false, 'message' => 'File is too large'];
        }

        $uploadRoot = base_path('../user-uploads');
        if (! is_dir($uploadRoot)) {
            @mkdir($uploadRoot, 0755, true);
        }

        $oldFiles = DB::table('tbl_attached_files')
            ->where('file_type', self::TYPE_CPAGE_BACKGROUND_IMAGE)
            ->where('file_record_id', $pageId)
            ->where('file_lang_id', $langId)
            ->get();

        $fileName = 'content_page_'.$pageId.'_'.$langId.'_'.time().'_'.Str::random(6).'.'.$ext;
        $relativePath = date('Y/m').'/'.$fileName;
        $targetDir = $uploadRoot.'/'.dirname($relativePath);
        if (! is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }
        $file->move($targetDir, $fileName);

        DB::transaction(function () use ($pageId, $langId, $fileName, $relativePath, $oldFiles, $uploadRoot) {
            DB::table('tbl_attached_files')
                ->where('file_type', self::TYPE_CPAGE_BACKGROUND_IMAGE)
                ->where('file_record_id', $pageId)
                ->where('file_lang_id', $langId)
                ->delete();

            DB::table('tbl_attached_files')->insert([
                'file_type' => self::TYPE_CPAGE_BACKGROUND_IMAGE,
                'file_lang_id' => $langId,
                'file_record_id' => $pageId,
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
        });

        return ['ok' => true, 'id' => $pageId, 'bg_image' => $this->backgroundImage($pageId, $langId)];
    }

    /** @return array{ok: bool, message?: string} */
    public function removeBackgroundImage(int $pageId, int $langId): array
    {
        if (! $this->show($pageId)) {
            return ['ok' => false, 'message' => 'Invalid request'];
        }

        $oldFiles = DB::table('tbl_attached_files')
            ->where('file_type', self::TYPE_CPAGE_BACKGROUND_IMAGE)
            ->where('file_record_id', $pageId)
            ->where('file_lang_id', $langId)
            ->get();

        DB::table('tbl_attached_files')
            ->where('file_type', self::TYPE_CPAGE_BACKGROUND_IMAGE)
            ->where('file_record_id', $pageId)
            ->where('file_lang_id', $langId)
            ->delete();

        $uploadRoot = base_path('../user-uploads');
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
    public function delete(int $pageId): array
    {
        $identifier = DB::table('tbl_content_pages')
            ->where('cpage_id', $pageId)
            ->where('cpage_deleted', 0)
            ->value('cpage_identifier');

        if (! is_string($identifier)) {
            return ['ok' => false, 'message' => 'Invalid request'];
        }

        DB::table('tbl_content_pages')->where('cpage_id', $pageId)->update([
            'cpage_deleted' => 1,
            'cpage_identifier' => $identifier.'-'.$pageId,
        ]);

        return ['ok' => true];
    }

    /** @return array<int, string> */
    private function layouts(): array
    {
        return [
            self::LAYOUT_BLOCKS => 'Content Page Layout1',
            self::LAYOUT_CONTENT => 'Content Page Layout2',
        ];
    }

    /** @return list<array{id: int, name: string}> */
    private function siteLanguages(): array
    {
        if (! DB::getSchemaBuilder()->hasTable('tbl_languages')) {
            return [];
        }

        $columns = DB::getSchemaBuilder()->getColumnListing('tbl_languages');
        $idColumn = in_array('language_id', $columns, true) ? 'language_id' : 'lang_id';
        $nameColumn = in_array('language_name', $columns, true) ? 'language_name' : 'lang_name';

        return DB::table('tbl_languages')
            ->orderBy($idColumn)
            ->get([$idColumn.' as id', $nameColumn.' as name'])
            ->map(fn ($row) => ['id' => (int) $row->id, 'name' => (string) $row->name])
            ->all();
    }

    private function nextMissingLangId(int $pageId): int
    {
        foreach ($this->siteLanguages() as $language) {
            $exists = DB::table('tbl_content_pages_lang')
                ->where('cpagelang_cpage_id', $pageId)
                ->where('cpagelang_lang_id', $language['id'])
                ->exists();
            if (! $exists) {
                return $language['id'];
            }
        }

        return 0;
    }

    /** @return array<string, mixed>|null */
    private function backgroundImage(int $pageId, int $langId): ?array
    {
        $row = DB::table('tbl_attached_files')
            ->where('file_type', self::TYPE_CPAGE_BACKGROUND_IMAGE)
            ->where('file_record_id', $pageId)
            ->where('file_lang_id', $langId)
            ->where('file_path', '!=', '')
            ->first(['file_id', 'file_name', 'file_added']);

        if (! $row) {
            return null;
        }

        return [
            'file_id' => (int) $row->file_id,
            'file_name' => (string) $row->file_name,
            'file_added' => (string) $row->file_added,
        ];
    }
}
