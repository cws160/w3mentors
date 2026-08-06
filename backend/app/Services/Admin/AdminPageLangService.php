<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\DB;

class AdminPageLangService
{
    /** @return array<string, mixed> */
    public function getByKey(string $pageKey, int $langId = 1): array
    {
        $row = DB::table('tbl_pages_language_data')
            ->where('plang_key', $pageKey)
            ->where('plang_lang_id', $langId)
            ->first();

        if (! $row) {
            return [];
        }

        return [
            'plang_id' => (int) $row->plang_id,
            'title' => html_entity_decode((string) ($row->plang_title ?? ''), ENT_QUOTES, 'UTF-8'),
            'summary' => html_entity_decode((string) ($row->plang_summary ?? ''), ENT_QUOTES, 'UTF-8'),
            'warning' => html_entity_decode((string) ($row->plang_warring_msg ?? ''), ENT_QUOTES, 'UTF-8'),
            'recommendations' => html_entity_decode((string) ($row->plang_recommendations ?? ''), ENT_QUOTES, 'UTF-8'),
            'helping_text' => html_entity_decode((string) ($row->plang_helping_text ?? ''), ENT_QUOTES, 'UTF-8'),
        ];
    }
}
