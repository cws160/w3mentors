<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\DB;

class AdminMetaTagManageService
{
    public const META_GROUP_DEFAULT = -1;

    public const META_GROUP_OTHER = 0;

    public const META_GROUP_TEACHER = 1;

    public const META_GROUP_GRP_CLASS = 2;

    public const META_GROUP_CMS_PAGE = 3;

    public const META_GROUP_BLOG_CATEGORY = 4;

    public const META_GROUP_BLOG_POST = 5;

    public const META_GROUP_COURSE = 6;

    public const META_GROUP_TEACH_LANGUAGE = 10;

    /** @return array<string, mixed>|null */
    public function form(int $metaId, int $metaType, string $recordId): ?array
    {
        $this->assertMetaTypeEnabled($metaType);

        $data = [
            'meta_id' => $metaId,
            'meta_type' => $metaType,
            'meta_record_id' => $recordId,
            'meta_identifier' => '',
            'meta_slug' => '',
            'site_languages' => $this->siteLanguages(),
        ];

        if ($metaId > 0) {
            $row = DB::table('tbl_meta_tags')->where('meta_id', $metaId)->first();
            if (! $row) {
                return null;
            }

            $data['meta_record_id'] = (string) $row->meta_record_id;
            $data['meta_identifier'] = (string) $row->meta_identifier;
            $data['meta_type'] = (int) $row->meta_type;

            if ($metaType === self::META_GROUP_OTHER) {
                $data['meta_slug'] = $this->slugFromComponents([
                    'meta_controller' => $row->meta_controller,
                    'meta_action' => $row->meta_action,
                    'meta_record_id' => $row->meta_record_id,
                ]);
            }
        }

        return $data;
    }

    /** @return array<string, mixed>|null */
    public function langForm(int $metaId, int $langId): ?array
    {
        $row = DB::table('tbl_meta_tags')->where('meta_id', $metaId)->first();
        if (! $row) {
            return null;
        }

        $langRow = DB::table('tbl_meta_tags_lang')
            ->where('metalang_meta_id', $metaId)
            ->where('metalang_lang_id', $langId)
            ->first();

        return [
            'meta_id' => $metaId,
            'meta_type' => (int) $row->meta_type,
            'meta_record_id' => (string) $row->meta_record_id,
            'lang_id' => $langId,
            'meta_title' => (string) ($langRow->meta_title ?? ''),
            'meta_keywords' => (string) ($langRow->meta_keywords ?? ''),
            'meta_description' => (string) ($langRow->meta_description ?? ''),
            'meta_other_meta_tags' => (string) ($langRow->meta_other_meta_tags ?? ''),
            'meta_og_title' => (string) ($langRow->meta_og_title ?? ''),
            'meta_og_url' => (string) ($langRow->meta_og_url ?? ''),
            'meta_og_description' => (string) ($langRow->meta_og_description ?? ''),
            'layout_direction' => $this->layoutDirection($langId),
            'show_og_image' => ! in_array((int) $row->meta_type, [
                self::META_GROUP_GRP_CLASS,
                self::META_GROUP_TEACHER,
                self::META_GROUP_COURSE,
            ], true),
        ];
    }

    /** @param  array<string, mixed>  $payload */
    public function setup(int $metaId, array $payload): array
    {
        $metaType = (int) ($payload['meta_type'] ?? self::META_GROUP_DEFAULT);
        $this->assertMetaTypeEnabled($metaType);

        if (! isset($this->tabsArr()[$metaType])) {
            throw new \InvalidArgumentException('Invalid request.');
        }

        if ($metaId === 0 && $metaType === self::META_GROUP_DEFAULT) {
            throw new \InvalidArgumentException('Invalid request.');
        }

        if ($metaId > 0 && ! DB::table('tbl_meta_tags')->where('meta_id', $metaId)->exists()) {
            throw new \InvalidArgumentException('Invalid request.');
        }

        $identifier = trim((string) ($payload['meta_identifier'] ?? ''));
        if ($identifier === '') {
            throw new \InvalidArgumentException('Identifier is required.');
        }

        $duplicate = DB::table('tbl_meta_tags')
            ->whereRaw('LOWER(meta_identifier) = ?', [strtolower($identifier)])
            ->where('meta_id', '!=', $metaId)
            ->exists();

        if ($duplicate) {
            throw new \InvalidArgumentException('Identifier is already in use.');
        }

        $record = [
            'meta_type' => $metaType,
            'meta_identifier' => $identifier,
            'meta_record_id' => trim((string) ($payload['meta_record_id'] ?? '')),
        ];

        if (! $this->applyUrlComponents($metaType, $record, $payload)) {
            throw new \InvalidArgumentException('Invalid request.');
        }

        if ($metaId > 0) {
            DB::table('tbl_meta_tags')->where('meta_id', $metaId)->update([
                'meta_controller' => $record['meta_controller'],
                'meta_action' => $record['meta_action'],
                'meta_type' => $record['meta_type'],
                'meta_record_id' => $record['meta_record_id'],
                'meta_identifier' => $record['meta_identifier'],
            ]);
        } else {
            $metaId = (int) DB::table('tbl_meta_tags')->insertGetId([
                'meta_controller' => $record['meta_controller'],
                'meta_action' => $record['meta_action'],
                'meta_type' => $record['meta_type'],
                'meta_record_id' => $record['meta_record_id'],
                'meta_identifier' => $record['meta_identifier'],
            ]);
        }

        $nextLangId = 0;
        if ($metaId > 0) {
            foreach ($this->siteLanguages() as $language) {
                $exists = DB::table('tbl_meta_tags_lang')
                    ->where('metalang_meta_id', $metaId)
                    ->where('metalang_lang_id', $language['id'])
                    ->exists();
                if (! $exists) {
                    $nextLangId = $language['id'];
                    break;
                }
            }
        } else {
            $nextLangId = max(1, (int) DB::table('tbl_configurations')
                ->where('conf_name', 'CONF_ADMIN_DEFAULT_LANG')
                ->value('conf_val'));
        }

        return [
            'meta_id' => $metaId,
            'meta_type' => $metaType,
            'lang_id' => $nextLangId,
        ];
    }

    /** @param  array<string, mixed>  $payload */
    public function langSetup(int $metaId, int $langId, array $payload): array
    {
        if ($metaId < 1 || $langId < 1) {
            throw new \InvalidArgumentException('Invalid request.');
        }

        $metaRow = DB::table('tbl_meta_tags')->where('meta_id', $metaId)->first();
        if (! $metaRow) {
            throw new \InvalidArgumentException('Invalid request.');
        }

        $this->assertMetaTypeEnabled((int) $metaRow->meta_type);

        $otherMetaTags = (string) ($payload['meta_other_meta_tags'] ?? '');
        if ($otherMetaTags !== '' && $otherMetaTags === strip_tags($otherMetaTags)) {
            throw new \InvalidArgumentException('Other meta tags must contain valid HTML markup.');
        }

        $ogUrl = trim((string) ($payload['meta_og_url'] ?? ''));
        if ($ogUrl !== '' && filter_var($ogUrl, FILTER_VALIDATE_URL) === false) {
            throw new \InvalidArgumentException('Open graph URL is invalid.');
        }

        $data = $this->langRowPayload($payload, $otherMetaTags, $ogUrl);
        $this->saveLanguageRow($metaId, $langId, $data);

        if (! empty($payload['update_langs_data'])) {
            foreach ($this->siteLanguages() as $language) {
                if ((int) $language['id'] === $langId) {
                    continue;
                }
                $this->saveLanguageRow($metaId, (int) $language['id'], $data);
            }
        }

        $nextLangId = 0;
        foreach ($this->siteLanguages() as $language) {
            $langExists = DB::table('tbl_meta_tags_lang')
                ->where('metalang_meta_id', $metaId)
                ->where('metalang_lang_id', $language['id'])
                ->exists();
            if (! $langExists) {
                $nextLangId = $language['id'];
                break;
            }
        }

        return [
            'meta_id' => $metaId,
            'lang_id' => $nextLangId,
        ];
    }

    /** @param  array<string, mixed>  $payload */
    private function langRowPayload(array $payload, string $otherMetaTags, string $ogUrl): array
    {
        return [
            'meta_title' => trim((string) ($payload['meta_title'] ?? '')),
            'meta_keywords' => trim((string) ($payload['meta_keywords'] ?? '')),
            'meta_description' => trim((string) ($payload['meta_description'] ?? '')),
            'meta_other_meta_tags' => $otherMetaTags,
            'meta_og_title' => trim((string) ($payload['meta_og_title'] ?? '')),
            'meta_og_url' => $ogUrl,
            'meta_og_description' => trim((string) ($payload['meta_og_description'] ?? '')),
        ];
    }

    /** @param  array<string, string>  $data */
    private function saveLanguageRow(int $metaId, int $langId, array $data): void
    {
        $exists = DB::table('tbl_meta_tags_lang')
            ->where('metalang_meta_id', $metaId)
            ->where('metalang_lang_id', $langId)
            ->exists();

        if ($exists) {
            DB::table('tbl_meta_tags_lang')
                ->where('metalang_meta_id', $metaId)
                ->where('metalang_lang_id', $langId)
                ->update($data);

            return;
        }

        DB::table('tbl_meta_tags_lang')->insert(array_merge($data, [
            'metalang_meta_id' => $metaId,
            'metalang_lang_id' => $langId,
        ]));
    }

    public function delete(int $metaId): bool
    {
        $exists = DB::table('tbl_meta_tags')->where('meta_id', $metaId)->exists();
        if (! $exists) {
            return false;
        }

        DB::table('tbl_meta_tags_lang')->where('metalang_meta_id', $metaId)->delete();
        DB::table('tbl_meta_tags')->where('meta_id', $metaId)->delete();

        return true;
    }

    /** @param  array<string, mixed>  $record */
    private function applyUrlComponents(int $metaType, array &$record, array $payload): bool
    {
        $tabs = $this->tabsArr();

        switch ($metaType) {
            case self::META_GROUP_TEACHER:
                $user = DB::table('tbl_users')
                    ->where('user_username', $record['meta_record_id'])
                    ->whereNull('user_deleted')
                    ->first(['user_username']);
                if (! $user) {
                    return false;
                }
                $record['meta_controller'] = $tabs[$metaType]['controller'];
                $record['meta_action'] = $tabs[$metaType]['action'];
                $record['meta_record_id'] = (string) $user->user_username;
                break;

            case self::META_GROUP_OTHER:
                $slug = trim((string) ($payload['meta_slug'] ?? ''));
                if ($slug === '') {
                    return false;
                }
                $parts = explode('/', $slug);
                $record['meta_controller'] = $this->dashedToCamel($parts[0] ?? '', true);
                $record['meta_action'] = $this->dashedToCamel($parts[1] ?? 'index');
                $record['meta_record_id'] = (string) ($parts[2] ?? '0');
                break;

            case self::META_GROUP_GRP_CLASS:
                $group = DB::table('tbl_group_classes')
                    ->where('grpcls_slug', $record['meta_record_id'])
                    ->first(['grpcls_slug']);
                if (! $group) {
                    return false;
                }
                $record['meta_controller'] = $tabs[$metaType]['controller'];
                $record['meta_action'] = $tabs[$metaType]['action'];
                $record['meta_record_id'] = (string) $group->grpcls_slug;
                break;

            case self::META_GROUP_COURSE:
                $course = DB::table('tbl_courses')
                    ->where('course_slug', $record['meta_record_id'])
                    ->whereNull('course_deleted')
                    ->first(['course_slug']);
                if (! $course) {
                    return false;
                }
                $record['meta_controller'] = $tabs[$metaType]['controller'];
                $record['meta_action'] = $tabs[$metaType]['action'];
                $record['meta_record_id'] = (string) $course->course_slug;
                break;

            default:
                $record['meta_controller'] = $tabs[$metaType]['controller'];
                $record['meta_action'] = $tabs[$metaType]['action'];
                break;
        }

        return true;
    }

    /** @param  array<string, mixed>  $row */
    private function slugFromComponents(array $row): string
    {
        $parts = [];
        foreach (['meta_controller', 'meta_action', 'meta_record_id'] as $key) {
            $value = $row[$key] ?? '';
            if ($value !== '' && $value !== '0') {
                $parts[] = $value;
            }
        }

        return implode('/', $parts);
    }

    private function dashedToCamel(string $value, bool $capitalizeFirst = false): string
    {
        $value = str_replace('-', ' ', $value);
        $value = ucwords($value);
        $value = str_replace(' ', '', $value);

        return $capitalizeFirst ? $value : lcfirst($value);
    }

    /** @return array<int, array{controller: string, action: string}> */
    private function tabsArr(): array
    {
        $tabs = [
            self::META_GROUP_DEFAULT => ['controller' => 'Default', 'action' => 'Default'],
            self::META_GROUP_OTHER => ['controller' => '', 'action' => ''],
            self::META_GROUP_TEACHER => ['controller' => 'Teachers', 'action' => 'view'],
            self::META_GROUP_CMS_PAGE => ['controller' => 'Cms', 'action' => 'view'],
            self::META_GROUP_BLOG_CATEGORY => ['controller' => 'Blog', 'action' => 'category'],
            self::META_GROUP_BLOG_POST => ['controller' => 'Blog', 'action' => 'postDetail'],
            self::META_GROUP_TEACH_LANGUAGE => ['controller' => 'teachers', 'action' => 'languages'],
        ];

        if ($this->coursesEnabled()) {
            $tabs[self::META_GROUP_COURSE] = ['controller' => 'Courses', 'action' => 'view'];
        }

        if ($this->groupClassesEnabled()) {
            $tabs[self::META_GROUP_GRP_CLASS] = ['controller' => 'GroupClasses', 'action' => 'view'];
        }

        return $tabs;
    }

    private function assertMetaTypeEnabled(int $metaType): void
    {
        if ($metaType === self::META_GROUP_COURSE && ! $this->coursesEnabled()) {
            throw new \InvalidArgumentException('Course module is not available.');
        }

        if ($metaType === self::META_GROUP_GRP_CLASS && ! $this->groupClassesEnabled()) {
            throw new \InvalidArgumentException('Group class module is not available.');
        }
    }

    private function coursesEnabled(): bool
    {
        return (int) DB::table('tbl_configurations')->where('conf_name', 'CONF_ENABLE_COURSES')->value('conf_val') === 1;
    }

    private function groupClassesEnabled(): bool
    {
        return (int) DB::table('tbl_configurations')->where('conf_name', 'CONF_GROUP_CLASSES_DISABLED')->value('conf_val') === 1;
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
