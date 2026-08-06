<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\DB;

class AdminCourseLanguageManageService
{
    private const STATUS_PUBLISHED = 3;

    /** @return array<string, mixed> */
    public function createForm(): array
    {
        return [
            'site_languages' => $this->siteLanguages(),
            'status_options' => [
                ['value' => 1, 'label' => 'Active'],
                ['value' => 0, 'label' => 'Inactive'],
            ],
        ];
    }

    /** @return array<string, mixed>|null */
    public function show(int $clangId): ?array
    {
        $row = DB::table('tbl_course_languages')
            ->where('clang_id', $clangId)
            ->whereNull('clang_deleted')
            ->first(['clang_id', 'clang_identifier', 'clang_active']);

        if (! $row) {
            return null;
        }

        return [
            'clang_id' => (int) $row->clang_id,
            'clang_identifier' => (string) $row->clang_identifier,
            'clang_active' => (int) $row->clang_active,
        ];
    }

    /** @param array<string, mixed> $payload @return array{ok: bool, message?: string, id?: int} */
    public function store(array $payload): array
    {
        $clangId = (int) ($payload['clang_id'] ?? 0);
        $identifier = trim((string) ($payload['clang_identifier'] ?? ''));
        $status = (int) ($payload['clang_active'] ?? 1);

        if ($identifier === '') {
            return ['ok' => false, 'message' => 'Course language identifier is required'];
        }

        if ($clangId > 0 && ! $this->exists($clangId)) {
            return ['ok' => false, 'message' => 'Invalid request'];
        }

        if ($this->identifierInUse($identifier, $clangId)) {
            return ['ok' => false, 'message' => 'Language identifier is not available'];
        }

        if ($status === 0 && $clangId > 0 && $this->hasPublishedCourses($clangId)) {
            return ['ok' => false, 'message' => 'Course attached language cannot be deactivated'];
        }

        if ($clangId > 0) {
            DB::table('tbl_course_languages')
                ->where('clang_id', $clangId)
                ->update([
                    'clang_identifier' => $identifier,
                    'clang_active' => $status,
                ]);
        } else {
            $maxOrder = (int) DB::table('tbl_course_languages')->max('clang_order');
            $clangId = (int) DB::table('tbl_course_languages')->insertGetId([
                'clang_identifier' => $identifier,
                'clang_active' => $status,
                'clang_order' => $maxOrder + 1,
            ]);
        }

        return ['ok' => true, 'id' => $clangId];
    }

    /** @return array<string, mixed>|null */
    public function langForm(int $clangId, int $langId): ?array
    {
        if (! $this->exists($clangId)) {
            return null;
        }

        $languages = $this->siteLanguages();
        if (! collect($languages)->contains(fn ($lang) => (int) $lang['id'] === $langId)) {
            return null;
        }

        $row = DB::table('tbl_course_languages_lang')
            ->where('clanglang_clang_id', $clangId)
            ->where('clanglang_lang_id', $langId)
            ->first(['clang_name']);

        return [
            'clanglang_clang_id' => $clangId,
            'clanglang_lang_id' => $langId,
            'clang_name' => (string) ($row->clang_name ?? ''),
            'layout_direction' => $this->layoutDirection($langId),
        ];
    }

    /** @param array<string, mixed> $payload @return array{ok: bool, message?: string} */
    public function storeLang(array $payload): array
    {
        $clangId = (int) ($payload['clanglang_clang_id'] ?? 0);
        $langId = (int) ($payload['clanglang_lang_id'] ?? 0);
        $name = trim((string) ($payload['clang_name'] ?? ''));

        if ($clangId < 1 || $langId < 1 || ! $this->exists($clangId)) {
            return ['ok' => false, 'message' => 'Invalid request'];
        }

        if ($name === '') {
            return ['ok' => false, 'message' => 'Course language name is required'];
        }

        $exists = DB::table('tbl_course_languages_lang')
            ->where('clanglang_clang_id', $clangId)
            ->where('clanglang_lang_id', $langId)
            ->exists();

        if ($exists) {
            DB::table('tbl_course_languages_lang')
                ->where('clanglang_clang_id', $clangId)
                ->where('clanglang_lang_id', $langId)
                ->update(['clang_name' => $name]);
        } else {
            DB::table('tbl_course_languages_lang')->insert([
                'clanglang_clang_id' => $clangId,
                'clanglang_lang_id' => $langId,
                'clang_name' => $name,
            ]);
        }

        return ['ok' => true];
    }

    /** @return array{ok: bool, message?: string} */
    public function changeStatus(int $clangId, int $status): array
    {
        if (! $this->exists($clangId)) {
            return ['ok' => false, 'message' => 'Invalid request'];
        }

        if ($status === 0 && $this->hasPublishedCourses($clangId)) {
            return ['ok' => false, 'message' => 'Course attached language cannot be deactivated'];
        }

        DB::table('tbl_course_languages')
            ->where('clang_id', $clangId)
            ->update(['clang_active' => $status]);

        return ['ok' => true];
    }

    /** @return array{ok: bool, message?: string} */
    public function delete(int $clangId): array
    {
        if (! $this->exists($clangId)) {
            return ['ok' => false, 'message' => 'Invalid request'];
        }

        if ($this->hasPublishedCourses($clangId)) {
            return ['ok' => false, 'message' => 'Course attached language cannot be deleted'];
        }

        DB::table('tbl_course_languages')
            ->where('clang_id', $clangId)
            ->update(['clang_deleted' => now()]);

        return ['ok' => true];
    }

    /** @param array<int, int|string> $order @return array{ok: bool, message?: string} */
    public function updateOrder(array $order): array
    {
        if ($order === []) {
            return ['ok' => false, 'message' => 'Invalid request'];
        }

        foreach (array_values($order) as $index => $id) {
            $clangId = (int) $id;
            if ($clangId < 1) {
                continue;
            }
            DB::table('tbl_course_languages')
                ->where('clang_id', $clangId)
                ->update(['clang_order' => $index]);
        }

        return ['ok' => true];
    }

    private function exists(int $clangId): bool
    {
        return DB::table('tbl_course_languages')
            ->where('clang_id', $clangId)
            ->whereNull('clang_deleted')
            ->exists();
    }

    private function identifierInUse(string $identifier, int $excludeId): bool
    {
        $query = DB::table('tbl_course_languages')
            ->where('clang_identifier', $identifier)
            ->whereNull('clang_deleted');

        if ($excludeId > 0) {
            $query->where('clang_id', '!=', $excludeId);
        }

        return $query->exists();
    }

    private function hasPublishedCourses(int $clangId): bool
    {
        return DB::table('tbl_courses')
            ->where('course_clang_id', $clangId)
            ->where('course_status', self::STATUS_PUBLISHED)
            ->whereNull('course_deleted')
            ->exists();
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
