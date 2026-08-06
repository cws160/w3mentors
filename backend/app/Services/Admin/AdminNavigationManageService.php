<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\DB;

class AdminNavigationManageService
{
    /** @return array<string, mixed>|null */
    public function show(int $navigationId): ?array
    {
        $row = DB::table('tbl_navigations')
            ->where('nav_id', $navigationId)
            ->where('nav_deleted', 0)
            ->first(['nav_id', 'nav_identifier', 'nav_active', 'nav_type']);

        if (! $row) {
            return null;
        }

        return [
            'nav_id' => (int) $row->nav_id,
            'nav_identifier' => (string) $row->nav_identifier,
            'nav_active' => (int) $row->nav_active,
            'nav_type' => (int) $row->nav_type,
            'site_languages' => $this->siteLanguages(),
        ];
    }

    /** @return array{navigation: array<string, mixed>, links: list<array<string, mixed>>}|null */
    public function pages(int $navigationId, int $langId): ?array
    {
        $navigation = DB::table('tbl_navigations as n')
            ->leftJoin('tbl_navigations_lang as nl', function ($join) use ($langId) {
                $join->on('nl.navlang_nav_id', '=', 'n.nav_id')
                    ->where('nl.navlang_lang_id', '=', $langId);
            })
            ->where('n.nav_id', $navigationId)
            ->where('n.nav_deleted', 0)
            ->first([
                'n.nav_id',
                'n.nav_identifier',
                DB::raw('IFNULL(nl.nav_name, n.nav_identifier) as nav_name'),
            ]);

        if (! $navigation) {
            return null;
        }

        $linkColumns = DB::getSchemaBuilder()->getColumnListing('tbl_navigation_links');
        $hasActiveColumn = in_array('nlink_active', $linkColumns, true);

        $select = [
            'link.nlink_id',
            'link.nlink_nav_id',
            'link.nlink_identifier',
            DB::raw('IFNULL(link_l.nlink_caption, link.nlink_identifier) as nlink_caption'),
            'link.nlink_order',
        ];
        if ($hasActiveColumn) {
            $select[] = 'link.nlink_active';
        }

        $links = DB::table('tbl_navigation_links as link')
            ->leftJoin('tbl_navigation_links_lang as link_l', function ($join) use ($langId) {
                $join->on('link_l.nlinklang_nlink_id', '=', 'link.nlink_id')
                    ->where('link_l.nlinklang_lang_id', '=', $langId);
            })
            ->where('link.nlink_nav_id', $navigationId)
            ->orderBy('link.nlink_order')
            ->orderByDesc('link.nlink_id')
            ->get($select)
            ->map(fn ($row) => [
                'id' => (int) $row->nlink_id,
                'navigation_id' => (int) $row->nlink_nav_id,
                'identifier' => (string) $row->nlink_identifier,
                'caption' => (string) $row->nlink_caption,
                'display_order' => (int) $row->nlink_order,
                'active' => $hasActiveColumn ? (int) $row->nlink_active : 1,
                'can_update_status' => $hasActiveColumn,
            ])
            ->all();

        return [
            'navigation' => [
                'id' => (int) $navigation->nav_id,
                'identifier' => (string) $navigation->nav_identifier,
                'title' => (string) $navigation->nav_name,
            ],
            'links' => $links,
        ];
    }

    public function changeLinkStatus(int $linkId, int $status): bool
    {
        $columns = DB::getSchemaBuilder()->getColumnListing('tbl_navigation_links');
        if (! in_array('nlink_active', $columns, true)) {
            return false;
        }

        return DB::table('tbl_navigation_links')
            ->where('nlink_id', $linkId)
            ->update(['nlink_active' => $status === 1 ? 1 : 0]) > 0;
    }

    /** @return array<string, mixed>|null */
    public function linkForm(int $navigationId, int $linkId, int $langId): ?array
    {
        if (! $this->show($navigationId)) {
            return null;
        }

        $columns = DB::getSchemaBuilder()->getColumnListing('tbl_navigation_links');
        $hasActiveColumn = in_array('nlink_active', $columns, true);

        $row = null;
        if ($linkId > 0) {
            $query = DB::table('tbl_navigation_links')
                ->where('nlink_id', $linkId)
                ->where('nlink_nav_id', $navigationId);
            $row = $query->first();
            if (! $row) {
                return null;
            }
        }

        return [
            'nlink_id' => (int) ($row->nlink_id ?? 0),
            'nlink_nav_id' => $navigationId,
            'nlink_identifier' => (string) ($row->nlink_identifier ?? ''),
            'nlink_type' => (int) ($row->nlink_type ?? 0),
            'nlink_target' => (string) ($row->nlink_target ?? '_self'),
            'nlink_login_protected' => (int) ($row->nlink_login_protected ?? 0),
            'nlink_cpage_id' => (int) ($row->nlink_cpage_id ?? 0),
            'nlink_url' => (string) ($row->nlink_url ?? ''),
            'nlink_active' => $hasActiveColumn ? (int) ($row->nlink_active ?? 1) : 1,
            'has_active_column' => $hasActiveColumn,
            'site_languages' => $this->siteLanguages(),
            'content_pages' => $this->contentPages($langId),
            'type_options' => [
                ['id' => 0, 'name' => 'CMS Page'],
                ['id' => 2, 'name' => 'External Page'],
            ],
            'target_options' => [
                ['id' => '_self', 'name' => 'Same Window'],
                ['id' => '_blank', 'name' => 'New Window'],
            ],
            'login_options' => [
                ['id' => 0, 'name' => 'Both'],
                ['id' => 1, 'name' => 'Yes'],
                ['id' => 2, 'name' => 'No'],
            ],
        ];
    }

    /** @param array<string, mixed> $data @return array{ok: bool, message?: string, id?: int, next_lang_id?: int} */
    public function saveLink(int $navigationId, int $linkId, array $data): array
    {
        if (! $this->show($navigationId)) {
            return ['ok' => false, 'message' => 'Invalid request'];
        }

        $identifier = trim((string) ($data['nlink_identifier'] ?? ''));
        if ($identifier === '') {
            return ['ok' => false, 'message' => 'Caption identifier is required.'];
        }

        $type = (int) ($data['nlink_type'] ?? 0);
        $values = [
            'nlink_nav_id' => $navigationId,
            'nlink_cpage_id' => $type === 0 ? (int) ($data['nlink_cpage_id'] ?? 0) : 0,
            'nlink_category_id' => 0,
            'nlink_identifier' => $identifier,
            'nlink_target' => (string) ($data['nlink_target'] ?? '_self'),
            'nlink_type' => in_array($type, [0, 2], true) ? $type : 2,
            'nlink_parent_id' => 0,
            'nlink_login_protected' => (int) ($data['nlink_login_protected'] ?? 0),
            'nlink_deleted' => 0,
            'nlink_url' => $type === 2 ? trim((string) ($data['nlink_url'] ?? '')) : '',
        ];

        $columns = DB::getSchemaBuilder()->getColumnListing('tbl_navigation_links');
        if (in_array('nlink_active', $columns, true)) {
            $values['nlink_active'] = (int) ($data['nlink_active'] ?? 1) === 1 ? 1 : 0;
        }

        if ($linkId > 0) {
            $updated = DB::table('tbl_navigation_links')
                ->where('nlink_id', $linkId)
                ->where('nlink_nav_id', $navigationId)
                ->update($values);
            if ($updated === 0 && ! DB::table('tbl_navigation_links')->where('nlink_id', $linkId)->exists()) {
                return ['ok' => false, 'message' => 'Invalid request'];
            }
        } else {
            $values['nlink_order'] = ((int) DB::table('tbl_navigation_links')
                ->where('nlink_nav_id', $navigationId)
                ->max('nlink_order')) + 1;
            $linkId = (int) DB::table('tbl_navigation_links')->insertGetId($values);
        }

        return ['ok' => true, 'id' => $linkId, 'next_lang_id' => $this->nextMissingLinkLangId($linkId)];
    }

    /** @return array<string, mixed>|null */
    public function linkLangForm(int $navigationId, int $linkId, int $langId): ?array
    {
        $link = DB::table('tbl_navigation_links')
            ->where('nlink_id', $linkId)
            ->where('nlink_nav_id', $navigationId)
            ->first(['nlink_id', 'nlink_nav_id', 'nlink_identifier']);
        if (! $link) {
            return null;
        }

        $lang = DB::table('tbl_navigation_links_lang')
            ->where('nlinklang_nlink_id', $linkId)
            ->where('nlinklang_lang_id', $langId)
            ->first(['nlink_caption']);

        $languages = $this->siteLanguages();
        $defaultLang = $this->defaultLangId();

        return [
            'nlink_id' => (int) $link->nlink_id,
            'nlink_nav_id' => (int) $link->nlink_nav_id,
            'lang_id' => $langId,
            'nlink_identifier' => (string) $link->nlink_identifier,
            'nlink_caption' => (string) ($lang->nlink_caption ?? ''),
            'site_languages' => $languages,
            'default_lang_id' => $defaultLang,
            'show_auto_translate' => count($languages) > 1 && $langId === $defaultLang,
        ];
    }

    /** @param array<string, mixed> $data @return array{ok: bool, message?: string, id?: int, next_lang_id?: int} */
    public function saveLinkLang(int $navigationId, int $linkId, int $langId, array $data): array
    {
        if (! DB::table('tbl_navigation_links')->where('nlink_id', $linkId)->where('nlink_nav_id', $navigationId)->exists()) {
            return ['ok' => false, 'message' => 'Invalid request'];
        }

        $caption = trim((string) ($data['nlink_caption'] ?? ''));
        if ($caption === '') {
            return ['ok' => false, 'message' => 'Caption is required.'];
        }

        DB::table('tbl_navigation_links_lang')->updateOrInsert(
            ['nlinklang_nlink_id' => $linkId, 'nlinklang_lang_id' => $langId],
            [
                'nlinklang_nlink_id' => $linkId,
                'nlinklang_lang_id' => $langId,
                'nlink_caption' => $caption,
            ],
        );

        if (! empty($data['update_langs_data']) && $langId === $this->defaultLangId()) {
            $this->syncOtherLinkLanguageRows($linkId, $langId, $caption);
        }

        return ['ok' => true, 'id' => $linkId, 'next_lang_id' => $this->nextMissingLinkLangId($linkId)];
    }

    private function syncOtherLinkLanguageRows(int $linkId, int $sourceLangId, string $caption): void
    {
        foreach ($this->siteLanguages() as $language) {
            if ($language['id'] === $sourceLangId) {
                continue;
            }

            DB::table('tbl_navigation_links_lang')->updateOrInsert(
                ['nlinklang_nlink_id' => $linkId, 'nlinklang_lang_id' => $language['id']],
                [
                    'nlinklang_nlink_id' => $linkId,
                    'nlinklang_lang_id' => $language['id'],
                    'nlink_caption' => $caption,
                ],
            );
        }
    }

    /** @param list<int> $linkIds */
    public function updateLinkOrder(int $navigationId, array $linkIds): void
    {
        DB::transaction(function () use ($navigationId, $linkIds) {
            foreach (array_values(array_unique($linkIds)) as $index => $linkId) {
                DB::table('tbl_navigation_links')
                    ->where('nlink_nav_id', $navigationId)
                    ->where('nlink_id', (int) $linkId)
                    ->update(['nlink_order' => $index]);
            }
        });
    }

    public function deleteLink(int $linkId): bool
    {
        return DB::transaction(function () use ($linkId) {
            DB::table('tbl_navigation_links_lang')->where('nlinklang_nlink_id', $linkId)->delete();

            return DB::table('tbl_navigation_links')->where('nlink_id', $linkId)->delete() > 0;
        });
    }

    /** @param array<string, mixed> $data @return array{ok: bool, message?: string, id?: int, next_lang_id?: int} */
    public function saveGeneral(int $navigationId, array $data): array
    {
        if (! $this->show($navigationId)) {
            return ['ok' => false, 'message' => 'Invalid request'];
        }

        $identifier = trim((string) ($data['nav_identifier'] ?? ''));
        if ($identifier === '') {
            return ['ok' => false, 'message' => 'Identifier is required.'];
        }

        $duplicate = DB::table('tbl_navigations')
            ->where('nav_identifier', $identifier)
            ->where('nav_id', '!=', $navigationId)
            ->where('nav_deleted', 0)
            ->exists();

        if ($duplicate) {
            return ['ok' => false, 'message' => 'Identifier already exists.'];
        }

        DB::table('tbl_navigations')->where('nav_id', $navigationId)->update([
            'nav_identifier' => $identifier,
            'nav_active' => (int) ($data['nav_active'] ?? 0) === 1 ? 1 : 0,
        ]);

        return ['ok' => true, 'id' => $navigationId, 'next_lang_id' => $this->nextMissingLangId($navigationId)];
    }

    /** @return array<string, mixed>|null */
    public function langForm(int $navigationId, int $langId): ?array
    {
        $navigation = $this->show($navigationId);
        if (! $navigation) {
            return null;
        }

        $lang = DB::table('tbl_navigations_lang')
            ->where('navlang_nav_id', $navigationId)
            ->where('navlang_lang_id', $langId)
            ->first(['nav_name']);

        return [
            ...$navigation,
            'lang_id' => $langId,
            'nav_name' => (string) ($lang->nav_name ?? ''),
        ];
    }

    /** @param array<string, mixed> $data @return array{ok: bool, message?: string, id?: int, next_lang_id?: int} */
    public function saveLang(int $navigationId, int $langId, array $data): array
    {
        if (! $this->show($navigationId)) {
            return ['ok' => false, 'message' => 'Invalid request'];
        }

        $name = trim((string) ($data['nav_name'] ?? ''));
        if ($name === '') {
            return ['ok' => false, 'message' => 'Title is required.'];
        }

        DB::table('tbl_navigations_lang')->updateOrInsert(
            ['navlang_nav_id' => $navigationId, 'navlang_lang_id' => $langId],
            [
                'navlang_nav_id' => $navigationId,
                'navlang_lang_id' => $langId,
                'nav_name' => $name,
            ],
        );

        return ['ok' => true, 'id' => $navigationId, 'next_lang_id' => $this->nextMissingLangId($navigationId)];
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
            ->where('language_active', 1)
            ->orderBy($idColumn)
            ->get([$idColumn.' as id', $nameColumn.' as name'])
            ->map(fn ($row) => ['id' => (int) $row->id, 'name' => (string) $row->name])
            ->all();
    }

    private function nextMissingLangId(int $navigationId): int
    {
        foreach ($this->siteLanguages() as $language) {
            $exists = DB::table('tbl_navigations_lang')
                ->where('navlang_nav_id', $navigationId)
                ->where('navlang_lang_id', $language['id'])
                ->exists();
            if (! $exists) {
                return $language['id'];
            }
        }

        return 0;
    }

    private function defaultLangId(): int
    {
        $configured = (int) DB::table('tbl_configurations')
            ->where('conf_name', 'CONF_DEFAULT_LANG')
            ->value('conf_val');

        return $configured > 0 ? $configured : 1;
    }

    private function isTranslatorActive(): bool
    {
        $key = (string) DB::table('tbl_configurations')
            ->where('conf_name', 'CONF_MICROSOFT_TRANSLATOR_SUBSCRIPTION_KEY')
            ->value('conf_val');

        return trim($key) !== '';
    }

    /** @return list<array{id: int, name: string}> */
    private function contentPages(int $langId): array
    {
        return DB::table('tbl_content_pages as p')
            ->leftJoin('tbl_content_pages_lang as pl', function ($join) use ($langId) {
                $join->on('pl.cpagelang_cpage_id', '=', 'p.cpage_id')
                    ->where('pl.cpagelang_lang_id', '=', $langId);
            })
            ->where('p.cpage_deleted', 0)
            ->orderBy('p.cpage_id')
            ->get([
                'p.cpage_id as id',
                DB::raw('IFNULL(pl.cpage_title, p.cpage_identifier) as name'),
            ])
            ->map(fn ($row) => ['id' => (int) $row->id, 'name' => (string) $row->name])
            ->all();
    }

    private function nextMissingLinkLangId(int $linkId): int
    {
        foreach ($this->siteLanguages() as $language) {
            $exists = DB::table('tbl_navigation_links_lang')
                ->where('nlinklang_nlink_id', $linkId)
                ->where('nlinklang_lang_id', $language['id'])
                ->exists();
            if (! $exists) {
                return $language['id'];
            }
        }

        return 0;
    }
}
