<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\DB;

class AdminContentBlockManageService
{
    private const TYPE_HOMEPAGE = 1;
    private const TYPE_APPLY_TO_TEACH = 2;
    private const TYPE_CONTACT_US = 3;
    private const TYPE_AVAILABILITY = 5;
    private const TYPE_AFFILIATE_REGISTRATION = 6;

    /** @return array<int, string> */
    public function types(): array
    {
        return [
            self::TYPE_HOMEPAGE => 'Homepage',
            self::TYPE_APPLY_TO_TEACH => 'Apply To Teach',
            self::TYPE_CONTACT_US => 'Contact Us',
            self::TYPE_AVAILABILITY => 'Availability',
            self::TYPE_AFFILIATE_REGISTRATION => 'Affiliate Registration',
        ];
    }

    /** @return array<string, mixed>|null */
    public function show(int $blockId): ?array
    {
        $row = DB::table('tbl_extra_pages')
            ->where('epage_id', $blockId)
            ->first(['epage_id', 'epage_identifier', 'epage_active', 'epage_type', 'epage_editable']);

        if (! $row) {
            return null;
        }

        return [
            'epage_id' => (int) $row->epage_id,
            'epage_identifier' => (string) $row->epage_identifier,
            'epage_active' => (int) $row->epage_active,
            'epage_type' => (int) $row->epage_type,
            'epage_editable' => (int) $row->epage_editable,
            'site_languages' => $this->siteLanguages(),
        ];
    }

    /** @param array<string, mixed> $data @return array{ok: bool, message?: string, id?: int, next_lang_id?: int} */
    public function saveGeneral(int $blockId, array $data): array
    {
        $block = $this->show($blockId);
        if (! $block) {
            return ['ok' => false, 'message' => 'Invalid request'];
        }

        $identifier = trim((string) ($data['epage_identifier'] ?? ''));
        if ($identifier === '') {
            return ['ok' => false, 'message' => 'Page identifier is required.'];
        }

        $duplicate = DB::table('tbl_extra_pages')
            ->where('epage_identifier', $identifier)
            ->where('epage_type', (int) $block['epage_type'])
            ->where('epage_id', '!=', $blockId)
            ->exists();

        if ($duplicate) {
            return ['ok' => false, 'message' => 'Page identifier already exists.'];
        }

        DB::table('tbl_extra_pages')->where('epage_id', $blockId)->update([
            'epage_identifier' => $identifier,
            'epage_active' => (int) ($data['epage_active'] ?? 0) === 1 ? 1 : 0,
        ]);

        return ['ok' => true, 'id' => $blockId, 'next_lang_id' => $this->nextMissingLangId($blockId)];
    }

    /** @return array<string, mixed>|null */
    public function langForm(int $blockId, int $langId): ?array
    {
        $block = DB::table('tbl_extra_pages')
            ->where('epage_id', $blockId)
            ->first([
                'epage_id',
                'epage_identifier',
                'epage_active',
                'epage_type',
                'epage_editable',
                'epage_default_content',
            ]);

        if (! $block) {
            return null;
        }

        $lang = DB::table('tbl_extra_pages_lang')
            ->where('epagelang_epage_id', $blockId)
            ->where('epagelang_lang_id', $langId)
            ->first(['epage_label', 'epage_content']);

        return [
            'epage_id' => (int) $block->epage_id,
            'lang_id' => $langId,
            'epage_identifier' => (string) $block->epage_identifier,
            'epage_active' => (int) $block->epage_active,
            'epage_type' => (int) $block->epage_type,
            'epage_editable' => (int) $block->epage_editable,
            'epage_label' => (string) ($lang->epage_label ?? ''),
            'epage_content' => (string) ($lang->epage_content ?? ''),
            'epage_default_content' => (string) ($block->epage_default_content ?? ''),
            'site_languages' => $this->siteLanguages(),
        ];
    }

    /** @param array<string, mixed> $data @return array{ok: bool, message?: string, id?: int, next_lang_id?: int} */
    public function saveLang(int $blockId, int $langId, array $data): array
    {
        $block = $this->show($blockId);
        if (! $block) {
            return ['ok' => false, 'message' => 'Invalid request'];
        }

        $label = trim((string) ($data['epage_label'] ?? ''));
        if ($label === '') {
            return ['ok' => false, 'message' => 'Page title is required.'];
        }

        DB::table('tbl_extra_pages_lang')->updateOrInsert(
            ['epagelang_epage_id' => $blockId, 'epagelang_lang_id' => $langId],
            [
                'epagelang_epage_id' => $blockId,
                'epagelang_lang_id' => $langId,
                'epage_label' => $label,
                'epage_content' => (int) $block['epage_editable'] === 1 ? (string) ($data['epage_content'] ?? '') : '',
            ],
        );

        return ['ok' => true, 'id' => $blockId, 'next_lang_id' => $this->nextMissingLangId($blockId)];
    }

    public function changeStatus(int $blockId, int $status): void
    {
        if (! DB::table('tbl_extra_pages')->where('epage_id', $blockId)->exists()) {
            throw new \InvalidArgumentException('Invalid request');
        }

        DB::table('tbl_extra_pages')->where('epage_id', $blockId)->update([
            'epage_active' => $status === 1 ? 1 : 0,
        ]);
    }

    /** @param list<int> $blockIds */
    public function updateOrder(array $blockIds): void
    {
        DB::transaction(function () use ($blockIds) {
            foreach (array_values(array_unique($blockIds)) as $index => $blockId) {
                DB::table('tbl_extra_pages')->where('epage_id', (int) $blockId)->update([
                    'epage_order' => $index,
                ]);
            }
        });
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

    private function nextMissingLangId(int $blockId): int
    {
        foreach ($this->siteLanguages() as $language) {
            $exists = DB::table('tbl_extra_pages_lang')
                ->where('epagelang_epage_id', $blockId)
                ->where('epagelang_lang_id', $language['id'])
                ->exists();
            if (! $exists) {
                return $language['id'];
            }
        }

        return 0;
    }
}
