<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\DB;

class AdminSpeakLanguageManageService
{
    public function show(int $slangId, int $langId): ?array
    {
        $row = DB::table('tbl_speak_languages as slang')
            ->leftJoin('tbl_speak_languages_lang as slanglang', function ($join) use ($langId) {
                $join->on('slanglang.slanglang_slang_id', '=', 'slang.slang_id')
                    ->where('slanglang.slanglang_lang_id', '=', $langId);
            })
            ->where('slang.slang_id', $slangId)
            ->first([
                'slang.slang_id',
                'slang.slang_identifier',
                'slang.slang_active',
                DB::raw('IFNULL(slanglang.slang_name, slang.slang_identifier) as slang_name'),
            ]);

        if (! $row) {
            return null;
        }

        return [
            'slang_id' => (int) $row->slang_id,
            'slang_identifier' => (string) $row->slang_identifier,
            'slang_name' => (string) $row->slang_name,
            'slang_active' => (int) ($row->slang_active ?? 0),
        ];
    }

    /** @param  array<string, mixed>  $payload */
    public function save(int $slangId, array $payload, int $langId): int
    {
        $identifier = trim((string) ($payload['slang_identifier'] ?? ''));
        $name = trim((string) ($payload['slang_name'] ?? ''));
        $active = (int) ($payload['slang_active'] ?? 1);

        if ($identifier === '') {
            throw new \InvalidArgumentException('Speak language identifier is required.');
        }

        $duplicate = DB::table('tbl_speak_languages')
            ->whereRaw('LOWER(slang_identifier) = ?', [strtolower($identifier)])
            ->where('slang_id', '!=', $slangId)
            ->exists();

        if ($duplicate) {
            throw new \InvalidArgumentException('Identifier is already in use.');
        }

        if ($slangId > 0) {
            DB::table('tbl_speak_languages')
                ->where('slang_id', $slangId)
                ->update([
                    'slang_identifier' => $identifier,
                    'slang_active' => $active,
                ]);
        } else {
            $maxOrder = (int) DB::table('tbl_speak_languages')->max('slang_order');
            $slangId = (int) DB::table('tbl_speak_languages')->insertGetId([
                'slang_identifier' => $identifier,
                'slang_active' => $active,
                'slang_order' => $maxOrder + 1,
            ]);
        }

        if ($name !== '') {
            $this->saveLanguageRow($slangId, $langId, $name);

            if (! empty($payload['update_langs_data']) && $this->isAutoTranslateSourceLang($langId)) {
                foreach ($this->siteLanguages() as $language) {
                    if ($language['id'] === $langId) {
                        continue;
                    }
                    $this->saveLanguageRow($slangId, $language['id'], $name);
                }
            }
        }

        if ($active === 0) {
            $this->removeUserSpeakLanguages([$slangId]);
        }

        return $slangId;
    }

    public function changeStatus(int $slangId, int $status): bool
    {
        if (! $this->exists($slangId)) {
            return false;
        }

        DB::table('tbl_speak_languages')
            ->where('slang_id', $slangId)
            ->update(['slang_active' => $status]);

        if ($status === 0) {
            $this->removeUserSpeakLanguages([$slangId]);
        }

        return true;
    }

    public function delete(int $slangId): bool
    {
        if (! $this->exists($slangId)) {
            return false;
        }

        $this->removeUserSpeakLanguages([$slangId]);
        DB::table('tbl_speak_languages_lang')->where('slanglang_slang_id', $slangId)->delete();
        DB::table('tbl_speak_languages')->where('slang_id', $slangId)->delete();

        return true;
    }

    /** @param  array<int, int|string>  $ids */
    public function updateOrder(array $ids): bool
    {
        if ($ids === []) {
            return false;
        }

        foreach (array_values($ids) as $order => $id) {
            $slangId = (int) $id;
            if ($slangId < 1) {
                continue;
            }
            DB::table('tbl_speak_languages')
                ->where('slang_id', $slangId)
                ->update(['slang_order' => $order]);
        }

        return true;
    }

    /** @param  array<int, int>  $slangIds */
    private function removeUserSpeakLanguages(array $slangIds): void
    {
        if ($slangIds === []) {
            return;
        }

        DB::table('tbl_user_speak_languages')
            ->whereIn('uslang_slang_id', $slangIds)
            ->delete();
    }

    private function exists(int $slangId): bool
    {
        return DB::table('tbl_speak_languages')->where('slang_id', $slangId)->exists();
    }

    private function saveLanguageRow(int $slangId, int $langId, string $name): void
    {
        DB::table('tbl_speak_languages_lang')->updateOrInsert(
            ['slanglang_slang_id' => $slangId, 'slanglang_lang_id' => $langId],
            [
                'slanglang_slang_id' => $slangId,
                'slanglang_lang_id' => $langId,
                'slang_name' => $name,
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
