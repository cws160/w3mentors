<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\DB;

class AdminPreferenceManageService
{
    public function show(int $preferId, int $langId): ?array
    {
        $row = DB::table('tbl_preferences as prefer')
            ->leftJoin('tbl_preferences_lang as preferlang', function ($join) use ($langId) {
                $join->on('preferlang.preferlang_prefer_id', '=', 'prefer.prefer_id')
                    ->where('preferlang.preferlang_lang_id', '=', $langId);
            })
            ->where('prefer.prefer_id', $preferId)
            ->first([
                'prefer.prefer_id',
                'prefer.prefer_type',
                'prefer.prefer_identifier',
                'prefer.prefer_order',
                DB::raw(
                    'COALESCE(
                        preferlang.prefer_title,
                        (SELECT pl2.prefer_title FROM tbl_preferences_lang pl2 WHERE pl2.preferlang_prefer_id = prefer.prefer_id LIMIT 1),
                        prefer.prefer_identifier
                    ) as prefer_title'
                ),
            ]);

        if (! $row) {
            return null;
        }

        return [
            'prefer_id' => (int) $row->prefer_id,
            'prefer_type' => (int) $row->prefer_type,
            'prefer_identifier' => (string) $row->prefer_identifier,
            'prefer_title' => (string) $row->prefer_title,
            'prefer_order' => (int) ($row->prefer_order ?? 0),
        ];
    }

    /** @param  array{prefer_identifier: string, prefer_title?: string, prefer_type: int, update_langs_data?: mixed}  $data */
    public function save(int $preferId, array $data, int $langId): int
    {
        $identifier = trim((string) ($data['prefer_identifier'] ?? ''));
        $type = (int) ($data['prefer_type'] ?? 0);
        $title = trim((string) ($data['prefer_title'] ?? ''));

        if ($identifier === '' || $type < 1) {
            throw new \InvalidArgumentException('Invalid preference data.');
        }

        $duplicate = DB::table('tbl_preferences')
            ->whereRaw('LOWER(prefer_identifier) = ?', [strtolower($identifier)])
            ->where('prefer_type', $type)
            ->where('prefer_id', '!=', $preferId)
            ->exists();

        if ($duplicate) {
            throw new \InvalidArgumentException('Identifier is already in use.');
        }

        if ($preferId > 0) {
            DB::table('tbl_preferences')
                ->where('prefer_id', $preferId)
                ->update([
                    'prefer_identifier' => $identifier,
                    'prefer_type' => $type,
                ]);
        } else {
            $maxOrder = (int) DB::table('tbl_preferences')->where('prefer_type', $type)->max('prefer_order');
            $preferId = (int) DB::table('tbl_preferences')->insertGetId([
                'prefer_identifier' => $identifier,
                'prefer_type' => $type,
                'prefer_order' => $maxOrder + 1,
            ]);
        }

        if ($title !== '') {
            $this->saveLanguageRow($preferId, $langId, $title);

            if (! empty($data['update_langs_data']) && $this->isAutoTranslateSourceLang($langId)) {
                foreach ($this->siteLanguages() as $language) {
                    if ($language['id'] === $langId) {
                        continue;
                    }
                    $this->saveLanguageRow($preferId, $language['id'], $title);
                }
            }
        }

        return $preferId;
    }

    public function delete(int $preferId): bool
    {
        $exists = DB::table('tbl_preferences')->where('prefer_id', $preferId)->exists();
        if (! $exists) {
            return false;
        }

        DB::table('tbl_user_preferences')->where('uprefer_prefer_id', $preferId)->delete();
        DB::table('tbl_preferences_lang')->where('preferlang_prefer_id', $preferId)->delete();
        DB::table('tbl_preferences')->where('prefer_id', $preferId)->delete();

        return true;
    }

    /** @param  array<int, int>  $ids */
    public function updateOrder(array $ids): bool
    {
        if ($ids === []) {
            return false;
        }

        DB::transaction(function () use ($ids) {
            foreach ($ids as $order => $id) {
                $preferId = (int) $id;
                if ($preferId < 1) {
                    continue;
                }
                DB::table('tbl_preferences')
                    ->where('prefer_id', $preferId)
                    ->update(['prefer_order' => $order]);
            }
        });

        return true;
    }

    private function saveLanguageRow(int $preferId, int $langId, string $title): void
    {
        DB::table('tbl_preferences_lang')->updateOrInsert(
            ['preferlang_prefer_id' => $preferId, 'preferlang_lang_id' => $langId],
            [
                'preferlang_prefer_id' => $preferId,
                'preferlang_lang_id' => $langId,
                'prefer_title' => $title,
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

    private function defaultLangId(): int
    {
        $configured = (int) DB::table('tbl_configurations')
            ->where('conf_name', 'CONF_DEFAULT_LANG')
            ->value('conf_val');

        return $configured > 0 ? $configured : 1;
    }

    private function isAutoTranslateSourceLang(int $langId): bool
    {
        if ($langId === $this->defaultLangId()) {
            return true;
        }

        $language = DB::table('tbl_languages')
            ->where('language_id', $langId)
            ->first(['language_code', 'language_name']);

        $code = strtolower((string) ($language->language_code ?? ''));
        $name = strtolower((string) ($language->language_name ?? ''));

        return in_array($code, ['en', 'es'], true) || in_array($name, ['english', 'spanish'], true);
    }
}
