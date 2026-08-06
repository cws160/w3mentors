<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\DB;

class AdminPageLangDataManageService
{
    /** @return array<string, mixed>|null */
    public function langForm(int $plangId, int $langId): ?array
    {
        if ($plangId < 1 || $langId < 1) {
            return null;
        }

        $base = DB::table('tbl_pages_language_data')->where('plang_id', $plangId)->first();
        if (! $base) {
            return null;
        }

        $pageKey = (string) $base->plang_key;
        $langData = DB::table('tbl_pages_language_data')
            ->where('plang_key', $pageKey)
            ->where('plang_lang_id', $langId)
            ->first();

        $defaultHelpingText = $this->decodeHtml((string) (DB::table('tbl_pages_data')
            ->where('pdata_key', $pageKey)
            ->value('pdata_helping_text') ?? ''));

        $languages = $this->siteLanguages();
        $defaultLang = $this->defaultLangId();

        return [
            'plang_id' => (int) ($langData->plang_id ?? $plangId),
            'plang_lang_id' => $langId,
            'plang_key' => $pageKey,
            'plang_title' => $this->fieldValue($langData, 'plang_title'),
            'plang_summary' => $this->fieldValue($langData, 'plang_summary'),
            'plang_warring_msg' => $this->fieldValue($langData, 'plang_warring_msg'),
            'plang_recommendations' => $this->fieldValue($langData, 'plang_recommendations'),
            'plang_helping_text' => $this->fieldValue($langData, 'plang_helping_text'),
            'default_helping_text' => $defaultHelpingText,
            'layout_direction' => $this->layoutDirection($langId),
            'site_languages' => $languages,
            'default_lang_id' => $defaultLang,
            'show_auto_translate' => $this->isTranslatorActive() && count($languages) > 1 && $langId === $defaultLang,
        ];
    }

    private function fieldValue(?object $row, string $column): string
    {
        if (! $row) {
            return '';
        }

        return $this->decodeHtml((string) ($row->{$column} ?? ''));
    }

    private function decodeHtml(string $value): string
    {
        if ($value === '') {
            return '';
        }

        return html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /** @param array<string, mixed> $payload @return array{ok: bool, message?: string, plang_id?: int, next_lang_id?: int} */
    public function langSetup(array $payload): array
    {
        $plangKey = trim((string) ($payload['plang_key'] ?? ''));
        $langId = (int) ($payload['plang_lang_id'] ?? 0);
        $title = trim((string) ($payload['plang_title'] ?? ''));

        if ($plangKey === '' || $langId < 1 || $title === '') {
            return ['ok' => false, 'message' => 'Invalid request'];
        }

        $defaultLang = $this->defaultLangId();
        $defaultExists = DB::table('tbl_pages_language_data')
            ->where('plang_key', $plangKey)
            ->where('plang_lang_id', $defaultLang)
            ->exists();

        if (! $defaultExists) {
            return ['ok' => false, 'message' => 'Identifier not found'];
        }

        $existing = DB::table('tbl_pages_language_data')
            ->where('plang_key', $plangKey)
            ->where('plang_lang_id', $langId)
            ->first();

        $data = [
            'plang_lang_id' => $langId,
            'plang_key' => $plangKey,
            'plang_title' => $title,
            'plang_summary' => (string) ($payload['plang_summary'] ?? ''),
            'plang_warring_msg' => (string) ($payload['plang_warring_msg'] ?? ''),
            'plang_recommendations' => (string) ($payload['plang_recommendations'] ?? ''),
            'plang_helping_text' => (string) ($payload['plang_helping_text'] ?? ''),
        ];

        if ($existing) {
            DB::table('tbl_pages_language_data')
                ->where('plang_id', $existing->plang_id)
                ->update($data);
            $plangId = (int) $existing->plang_id;
        } else {
            $plangId = (int) DB::table('tbl_pages_language_data')->insertGetId($data);
        }

        if (! empty($payload['update_langs_data']) && $langId === $defaultLang) {
            $this->syncOtherLanguageRows($plangKey, $data);
        }

        return [
            'ok' => true,
            'plang_id' => $plangId,
            'next_lang_id' => $this->nextMissingLangId($plangKey),
        ];
    }

    /** @param array<string, mixed> $data */
    private function syncOtherLanguageRows(string $pageKey, array $data): void
    {
        foreach ($this->siteLanguages() as $language) {
            if ($language['id'] === (int) $data['plang_lang_id']) {
                continue;
            }

            $existing = DB::table('tbl_pages_language_data')
                ->where('plang_key', $pageKey)
                ->where('plang_lang_id', $language['id'])
                ->first();

            $row = [
                'plang_lang_id' => $language['id'],
                'plang_key' => $pageKey,
                'plang_title' => (string) ($data['plang_title'] ?? ''),
                'plang_summary' => (string) ($data['plang_summary'] ?? ''),
                'plang_warring_msg' => (string) ($data['plang_warring_msg'] ?? ''),
                'plang_recommendations' => (string) ($data['plang_recommendations'] ?? ''),
                'plang_helping_text' => (string) ($data['plang_helping_text'] ?? ''),
            ];

            if ($existing) {
                DB::table('tbl_pages_language_data')
                    ->where('plang_id', $existing->plang_id)
                    ->update($row);
                continue;
            }

            DB::table('tbl_pages_language_data')->insert($row);
        }
    }

    private function isTranslatorActive(): bool
    {
        $key = (string) DB::table('tbl_configurations')
            ->where('conf_name', 'CONF_MICROSOFT_TRANSLATOR_SUBSCRIPTION_KEY')
            ->value('conf_val');

        return trim($key) !== '';
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

    private function defaultLangId(): int
    {
        $configured = (int) DB::table('tbl_configurations')
            ->where('conf_name', 'CONF_DEFAULT_LANG')
            ->value('conf_val');

        return $configured > 0 ? $configured : 1;
    }

    private function layoutDirection(int $langId): string
    {
        $code = strtolower((string) DB::table('tbl_languages')->where('language_id', $langId)->value('language_code'));

        return in_array($code, ['ar', 'he', 'ur'], true) ? 'rtl' : 'ltr';
    }

    private function nextMissingLangId(string $pageKey): int
    {
        foreach ($this->siteLanguages() as $language) {
            $exists = DB::table('tbl_pages_language_data')
                ->where('plang_key', $pageKey)
                ->where('plang_lang_id', $language['id'])
                ->exists();
            if (! $exists) {
                return $language['id'];
            }
        }

        return 0;
    }
}
