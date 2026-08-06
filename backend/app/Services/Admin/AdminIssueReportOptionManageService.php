<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\DB;

class AdminIssueReportOptionManageService
{
    public function show(int $optId, int $langId): ?array
    {
        $row = DB::table('tbl_issue_report_options as tissueopt')
            ->leftJoin('tbl_issue_report_options_lang as tissueoptlang', function ($join) use ($langId) {
                $join->on('tissueoptlang.tissueoptlang_tissueopt_id', '=', 'tissueopt.tissueopt_id')
                    ->where('tissueoptlang.tissueoptlang_lang_id', '=', $langId);
            })
            ->where('tissueopt.tissueopt_id', $optId)
            ->first([
                'tissueopt.tissueopt_id',
                'tissueopt.tissueopt_identifier',
                'tissueopt.tissueopt_active',
                DB::raw('IFNULL(tissueoptlang.tissueoptlang_title, tissueopt.tissueopt_identifier) as tissueoptlang_title'),
            ]);

        if (! $row) {
            return null;
        }

        return [
            'tissueopt_id' => (int) $row->tissueopt_id,
            'tissueopt_identifier' => (string) $row->tissueopt_identifier,
            'tissueoptlang_title' => (string) $row->tissueoptlang_title,
            'tissueopt_active' => (int) ($row->tissueopt_active ?? 0),
        ];
    }

    /** @param  array<string, mixed>  $payload */
    public function save(int $optId, array $payload, int $langId): int
    {
        $identifier = trim((string) ($payload['tissueopt_identifier'] ?? ''));
        $title = trim((string) ($payload['tissueoptlang_title'] ?? ''));
        $active = (int) ($payload['tissueopt_active'] ?? 1);

        if ($identifier === '') {
            throw new \InvalidArgumentException('Option identifier is required.');
        }

        $duplicate = DB::table('tbl_issue_report_options')
            ->whereRaw('LOWER(tissueopt_identifier) = ?', [strtolower($identifier)])
            ->where('tissueopt_id', '!=', $optId)
            ->exists();

        if ($duplicate) {
            throw new \InvalidArgumentException('Identifier is already in use.');
        }

        if ($optId > 0) {
            DB::table('tbl_issue_report_options')
                ->where('tissueopt_id', $optId)
                ->update([
                    'tissueopt_identifier' => $identifier,
                    'tissueopt_active' => $active,
                ]);
        } else {
            $maxOrder = (int) DB::table('tbl_issue_report_options')->max('tissueopt_order');
            $optId = (int) DB::table('tbl_issue_report_options')->insertGetId([
                'tissueopt_identifier' => $identifier,
                'tissueopt_active' => $active,
                'tissueopt_order' => $maxOrder + 1,
            ]);
        }

        if ($title !== '') {
            $this->saveLanguageRow($optId, $langId, $title);

            if (! empty($payload['update_langs_data'])) {
                foreach ($this->siteLanguages() as $language) {
                    if ($language['id'] === $langId) {
                        continue;
                    }
                    $this->saveLanguageRow($optId, $language['id'], $title);
                }
            }
        }

        return $optId;
    }

    public function changeStatus(int $optId, int $status): bool
    {
        if (! $this->exists($optId)) {
            return false;
        }

        DB::table('tbl_issue_report_options')
            ->where('tissueopt_id', $optId)
            ->update(['tissueopt_active' => $status]);

        return true;
    }

    public function delete(int $optId): bool
    {
        if (! $this->exists($optId)) {
            return false;
        }

        DB::table('tbl_issue_report_options_lang')->where('tissueoptlang_tissueopt_id', $optId)->delete();
        DB::table('tbl_issue_report_options')->where('tissueopt_id', $optId)->delete();

        return true;
    }

    /** @param  array<int, int|string>  $ids */
    public function updateOrder(array $ids): bool
    {
        if ($ids === []) {
            return false;
        }

        foreach (array_values($ids) as $order => $id) {
            $optId = (int) $id;
            if ($optId < 1) {
                continue;
            }
            DB::table('tbl_issue_report_options')
                ->where('tissueopt_id', $optId)
                ->update(['tissueopt_order' => $order]);
        }

        return true;
    }

    private function exists(int $optId): bool
    {
        return DB::table('tbl_issue_report_options')->where('tissueopt_id', $optId)->exists();
    }

    private function saveLanguageRow(int $optId, int $langId, string $title): void
    {
        DB::table('tbl_issue_report_options_lang')->updateOrInsert(
            ['tissueoptlang_tissueopt_id' => $optId, 'tissueoptlang_lang_id' => $langId],
            [
                'tissueoptlang_tissueopt_id' => $optId,
                'tissueoptlang_lang_id' => $langId,
                'tissueoptlang_title' => $title,
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
}
