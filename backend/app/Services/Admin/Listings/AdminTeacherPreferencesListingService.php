<?php

namespace App\Services\Admin\Listings;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminTeacherPreferencesListingService
{
    use AdminListingSupport;

    public const PREF_TYPE_ACCENTS = 1;

  public const PREF_TYPE_TEACHES_LEVEL = 2;

  public const PREF_TYPE_LEARNER_AGES = 3;

  public const PREF_TYPE_LESSONS = 4;

  public const PREF_TYPE_TEST_PREPARATIONS = 6;

  /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>}|null */
  public function search(string $module, Request $request): ?array
  {
    return match ($module) {
      'preferences' => $this->preferences($request),
      'speak-language' => $this->speakLanguages($request),
      'speak-language-levels' => $this->speakLanguageLevels($request),
      'teach-language' => $this->teachLanguages($request),
      'issue-report-options' => $this->issueReportOptions($request),
      default => null,
    };
  }

  /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
  public function preferences(Request $request): array
  {
    $langId = $this->langId($request);
    $type = $request->integer('type');
    if ($type < 1) {
      return $this->emptyResult();
    }

    $query = DB::table('tbl_preferences as prefer')
      ->leftJoin('tbl_preferences_lang as preferlang', function ($join) use ($langId) {
        $join->on('preferlang.preferlang_prefer_id', '=', 'prefer.prefer_id')
          ->where('preferlang.preferlang_lang_id', '=', $langId);
      })
      ->where('prefer.prefer_type', '=', $type)
      ->select([
        'prefer.prefer_id as id',
        'prefer.prefer_id as prefer_id',
        'prefer.prefer_identifier as identifier',
        'prefer.prefer_order as prefer_order',
        DB::raw(
            'COALESCE(
                preferlang.prefer_title,
                (SELECT pl2.prefer_title FROM tbl_preferences_lang pl2 WHERE pl2.preferlang_prefer_id = prefer.prefer_id LIMIT 1),
                prefer.prefer_identifier
            ) as title'
        ),
      ]);

    $this->applyKeyword($request, $query, ['prefer.prefer_identifier', 'preferlang.prefer_title']);

    $rows = $query
      ->orderBy('prefer.prefer_order')
      ->orderBy('prefer.prefer_id')
      ->get()
      ->map(fn ($row) => [
        'id' => (int) $row->id,
        'prefer_id' => (int) $row->prefer_id,
        'identifier' => trim((string) $row->identifier),
        'title' => trim((string) $row->title),
        'prefer_order' => (int) ($row->prefer_order ?? 0),
      ])
      ->all();

    return $this->allRowsResult($rows);
  }

  /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
  public function speakLanguages(Request $request): array
  {
    $langId = $this->langId($request);
    $query = DB::table('tbl_speak_languages as slang')
      ->leftJoin('tbl_speak_languages_lang as slanglang', function ($join) use ($langId) {
        $join->on('slanglang.slanglang_slang_id', '=', 'slang.slang_id')
          ->where('slanglang.slanglang_lang_id', '=', $langId);
      })
      ->select([
        'slang.slang_id as id',
        'slang.slang_id as slang_id',
        'slang.slang_identifier as identifier',
        DB::raw(
            'COALESCE(
                slanglang.slang_name,
                (SELECT ll2.slang_name FROM tbl_speak_languages_lang ll2 WHERE ll2.slanglang_slang_id = slang.slang_id LIMIT 1),
                slang.slang_identifier
            ) as title'
        ),
        'slang.slang_active as active',
        'slang.slang_order as sort_order',
      ]);

    $this->applyKeyword($request, $query, ['slang.slang_identifier', 'slanglang.slang_name']);

    $rows = $query
      ->orderByDesc('slang.slang_active')
      ->orderBy('slang.slang_order')
      ->orderBy('slang.slang_id')
      ->get()
      ->map(fn ($row) => $this->formatActiveRow($row, 'slang_id'))
      ->all();

    return $this->allRowsResult($rows);
  }

  /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
  public function speakLanguageLevels(Request $request): array
  {
    $langId = $this->langId($request);
    $query = DB::table('tbl_speak_language_levels as slanglvl')
      ->leftJoin('tbl_speak_language_levels_lang as slanglvllang', function ($join) use ($langId) {
        $join->on('slanglvllang.slanglvllang_slanglvl_id', '=', 'slanglvl.slanglvl_id')
          ->where('slanglvllang.slanglvllang_lang_id', '=', $langId);
      })
      ->select([
        'slanglvl.slanglvl_id as id',
        'slanglvl.slanglvl_id as slanglvl_id',
        'slanglvl.slanglvl_identifier as identifier',
        DB::raw(
            'COALESCE(
                slanglvllang.slanglvl_name,
                (SELECT ll2.slanglvl_name FROM tbl_speak_language_levels_lang ll2 WHERE ll2.slanglvllang_slanglvl_id = slanglvl.slanglvl_id LIMIT 1),
                slanglvl.slanglvl_identifier
            ) as title'
        ),
        'slanglvl.slanglvl_active as active',
        'slanglvl.slanglvl_order as sort_order',
      ]);

    $this->applyKeyword($request, $query, ['slanglvl.slanglvl_identifier', 'slanglvllang.slanglvl_name']);

    $rows = $query
      ->orderByDesc('slanglvl.slanglvl_active')
      ->orderBy('slanglvl.slanglvl_order')
      ->orderBy('slanglvl.slanglvl_id')
      ->get()
      ->map(fn ($row) => $this->formatActiveRow($row, 'slanglvl_id'))
      ->all();

    return $this->allRowsResult($rows);
  }

  /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
  public function teachLanguages(Request $request): array
  {
    $langId = $this->langId($request);
    $parentId = $request->integer('parent_id');

    $query = DB::table('tbl_teach_languages as tlang')
      ->leftJoin('tbl_teach_languages_lang as tlanglang', function ($join) use ($langId) {
        $join->on('tlanglang.tlanglang_tlang_id', '=', 'tlang.tlang_id')
          ->where('tlanglang.tlanglang_lang_id', '=', $langId);
      })
      ->where('tlang.tlang_parent', '=', $parentId)
      ->select([
        'tlang.tlang_id as id',
        'tlang.tlang_id as tlang_id',
        'tlang.tlang_parent as tlang_parent',
        'tlang.tlang_identifier as identifier',
        DB::raw(
            'COALESCE(
                tlanglang.tlang_name,
                (SELECT ll2.tlang_name FROM tbl_teach_languages_lang ll2 WHERE ll2.tlanglang_tlang_id = tlang.tlang_id LIMIT 1),
                tlang.tlang_identifier
            ) as title'
        ),
        'tlang.tlang_active as active',
        'tlang.tlang_featured as featured',
        'tlang.tlang_subcategories as subcategories',
        'tlang.tlang_min_price as min_price',
        'tlang.tlang_max_price as max_price',
        'tlang.tlang_hourly_price as hourly_price',
        'tlang.tlang_order as sort_order',
      ]);

    $this->applyKeyword($request, $query, ['tlang.tlang_identifier', 'tlanglang.tlang_name']);

    $rows = $query
      ->orderByDesc('tlang.tlang_active')
      ->orderBy('tlang.tlang_order')
      ->orderByDesc('tlang.tlang_id')
      ->get()
      ->map(function ($row) use ($parentId) {
        $subcategories = (int) ($row->subcategories ?? 0);

        return [
          'id' => (int) $row->tlang_id,
          'tlang_id' => (int) $row->tlang_id,
          'tlang_parent' => (int) ($row->tlang_parent ?? 0),
          'identifier' => (string) $row->identifier,
          'title' => (string) $row->title,
          'active' => (int) ($row->active ?? 0),
          'featured' => (int) ($row->featured ?? 0),
          'featured_label' => (int) ($row->featured ?? 0) === 1 ? 'Yes' : 'No',
          'subcategories' => $subcategories,
          'subcategories_label' => $subcategories > 0 ? (string) $subcategories : '—',
          'min_price' => $subcategories > 0 ? null : (float) ($row->min_price ?? 0),
          'max_price' => $subcategories > 0 ? null : (float) ($row->max_price ?? 0),
          'hourly_price' => $subcategories > 0 ? null : (float) ($row->hourly_price ?? 0),
          'min_price_label' => $subcategories > 0 ? '—' : $this->money($row->min_price),
          'max_price_label' => $subcategories > 0 ? '—' : $this->money($row->max_price),
          'hourly_price_label' => $subcategories > 0 ? '—' : $this->money($row->hourly_price),
          'is_root' => $parentId < 1,
        ];
      })
      ->all();

    return $this->allRowsResult($rows);
  }

  /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
  public function issueReportOptions(Request $request): array
  {
    $langId = $this->langId($request);
    $query = DB::table('tbl_issue_report_options as tissueopt')
      ->leftJoin('tbl_issue_report_options_lang as tissueoptlang', function ($join) use ($langId) {
        $join->on('tissueoptlang.tissueoptlang_tissueopt_id', '=', 'tissueopt.tissueopt_id')
          ->where('tissueoptlang.tissueoptlang_lang_id', '=', $langId);
      })
      ->select([
        'tissueopt.tissueopt_id as id',
        'tissueopt.tissueopt_id as tissueopt_id',
        'tissueopt.tissueopt_identifier as identifier',
        DB::raw('IFNULL(tissueoptlang.tissueoptlang_title, tissueopt.tissueopt_identifier) as title'),
        'tissueopt.tissueopt_active as active',
        'tissueopt.tissueopt_order as sort_order',
      ]);

    $this->applyKeyword($request, $query, ['tissueopt.tissueopt_identifier', 'tissueoptlang.tissueoptlang_title']);

    $rows = $query
      ->orderByDesc('tissueopt.tissueopt_active')
      ->orderBy('tissueopt.tissueopt_order')
      ->orderBy('tissueopt.tissueopt_id')
      ->get()
      ->map(fn ($row) => $this->formatActiveRow($row, 'tissueopt_id'))
      ->all();

    return $this->allRowsResult($rows);
  }

  /** @param  array<int, array<string, mixed>>  $rows */
  private function allRowsResult(array $rows): array
  {
    $total = count($rows);

    return [
      'data' => $rows,
      'meta' => [
        'current_page' => 1,
        'per_page' => $total > 0 ? $total : 1,
        'total' => $total,
        'last_page' => 1,
      ],
    ];
  }

  /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
  private function emptyResult(): array
  {
    return $this->allRowsResult([]);
  }

  private function money(mixed $value): string
  {
    $amount = (float) ($value ?? 0);

    return number_format($amount, 2, '.', '');
  }

  /** @return array<string, mixed> */
  private function formatActiveRow(object $row, string $idKey): array
  {
    return [
      'id' => (int) $row->id,
      $idKey => (int) $row->id,
      'identifier' => (string) $row->identifier,
      'title' => (string) $row->title,
      'active' => (int) ($row->active ?? 0),
    ];
  }
}
