<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\DB;

class AdminSpeakLanguageLevelManageService
{
    public function show(int $slanglvlId, int $langId): ?array
    {
        $row = DB::table('tbl_speak_language_levels as slanglvl')
            ->leftJoin('tbl_speak_language_levels_lang as slanglvllang', function ($join) use ($langId) {
                $join->on('slanglvllang.slanglvllang_slanglvl_id', '=', 'slanglvl.slanglvl_id')
                    ->where('slanglvllang.slanglvllang_lang_id', '=', $langId);
            })
            ->where('slanglvl.slanglvl_id', $slanglvlId)
            ->first([
                'slanglvl.slanglvl_id',
                'slanglvl.slanglvl_identifier',
                'slanglvl.slanglvl_active',
                DB::raw('IFNULL(slanglvllang.slanglvl_name, slanglvl.slanglvl_identifier) as slanglvl_name'),
            ]);

        if (! $row) {
            return null;
        }

        return [
            'slanglvl_id' => (int) $row->slanglvl_id,
            'slanglvl_identifier' => (string) $row->slanglvl_identifier,
            'slanglvl_name' => (string) $row->slanglvl_name,
            'slanglvl_active' => (int) ($row->slanglvl_active ?? 0),
        ];
    }

    /** @param  array<string, mixed>  $payload */
    public function save(int $slanglvlId, array $payload, int $langId): int
    {
        $identifier = trim((string) ($payload['slanglvl_identifier'] ?? ''));
        $name = trim((string) ($payload['slanglvl_name'] ?? ''));
        $active = (int) ($payload['slanglvl_active'] ?? 1);

        if ($identifier === '') {
            throw new \InvalidArgumentException('Language level identifier is required.');
        }

        $duplicate = DB::table('tbl_speak_language_levels')
            ->whereRaw('LOWER(slanglvl_identifier) = ?', [strtolower($identifier)])
            ->where('slanglvl_id', '!=', $slanglvlId)
            ->exists();

        if ($duplicate) {
            throw new \InvalidArgumentException('Identifier is already in use.');
        }

        if ($slanglvlId > 0) {
            DB::table('tbl_speak_language_levels')
                ->where('slanglvl_id', $slanglvlId)
                ->update([
                    'slanglvl_identifier' => $identifier,
                    'slanglvl_active' => $active,
                ]);
        } else {
            $maxOrder = (int) DB::table('tbl_speak_language_levels')->max('slanglvl_order');
            $slanglvlId = (int) DB::table('tbl_speak_language_levels')->insertGetId([
                'slanglvl_identifier' => $identifier,
                'slanglvl_active' => $active,
                'slanglvl_order' => $maxOrder + 1,
            ]);
        }

        if ($name !== '') {
            $this->saveLanguageRow($slanglvlId, $langId, $name);

            if (! empty($payload['update_langs_data']) && $this->isAutoTranslateSourceLang($langId)) {
                foreach ($this->siteLanguages() as $language) {
                    if ($language['id'] === $langId) {
                        continue;
                    }
                    $this->saveLanguageRow($slanglvlId, $language['id'], $name);
                }
            }
        }

        if ($active === 0) {
            $this->clearUserProficiency([$slanglvlId]);
        }

        return $slanglvlId;
    }

    public function changeStatus(int $slanglvlId, int $status): bool
    {
        if (! $this->exists($slanglvlId)) {
            return false;
        }

        DB::table('tbl_speak_language_levels')
            ->where('slanglvl_id', $slanglvlId)
            ->update(['slanglvl_active' => $status]);

        if ($status === 0) {
            $this->clearUserProficiency([$slanglvlId]);
        }

        return true;
    }

    public function delete(int $slanglvlId): bool
    {
        if (! $this->exists($slanglvlId)) {
            return false;
        }

        $this->clearUserProficiency([$slanglvlId]);
        DB::table('tbl_speak_language_levels_lang')->where('slanglvllang_slanglvl_id', $slanglvlId)->delete();
        DB::table('tbl_speak_language_levels')->where('slanglvl_id', $slanglvlId)->delete();

        return true;
    }

    /** @param  array<int, int|string>  $ids */
    public function updateOrder(array $ids): bool
    {
        if ($ids === []) {
            return false;
        }

        foreach (array_values($ids) as $order => $id) {
            $slanglvlId = (int) $id;
            if ($slanglvlId < 1) {
                continue;
            }
            DB::table('tbl_speak_language_levels')
                ->where('slanglvl_id', $slanglvlId)
                ->update(['slanglvl_order' => $order]);
        }

        return true;
    }

    /** @param  array<int, int>  $levelIds */
    private function clearUserProficiency(array $levelIds): void
    {
        if ($levelIds === []) {
            return;
        }

        DB::table('tbl_user_speak_languages')
            ->whereIn('uslang_proficiency', $levelIds)
            ->update(['uslang_proficiency' => 0]);
    }

    private function exists(int $slanglvlId): bool
    {
        return DB::table('tbl_speak_language_levels')->where('slanglvl_id', $slanglvlId)->exists();
    }

    private function saveLanguageRow(int $slanglvlId, int $langId, string $name): void
    {
        DB::table('tbl_speak_language_levels_lang')->updateOrInsert(
            ['slanglvllang_slanglvl_id' => $slanglvlId, 'slanglvllang_lang_id' => $langId],
            [
                'slanglvllang_slanglvl_id' => $slanglvlId,
                'slanglvllang_lang_id' => $langId,
                'slanglvl_name' => $name,
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
