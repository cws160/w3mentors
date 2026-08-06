<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminPrivilegeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminLanguageLabelsController extends Controller
{
    public function __construct(private AdminPrivilegeService $privileges)
    {
    }

    public function show(Request $request, int $labelId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($labelId) {
            $labelKey = (string) DB::table('tbl_language_labels')->where('label_id', $labelId)->value('label_key');
            if ($labelKey === '') {
                return response()->json(['message' => 'Record not found'], 404);
            }

            $captions = DB::table('tbl_language_labels')
                ->where('label_key', $labelKey)
                ->pluck('label_caption', 'label_lang_id')
                ->mapWithKeys(fn ($caption, $langId) => [(int) $langId => (string) $caption])
                ->all();

            return response()->json(['data' => [
                'label_id' => $labelId,
                'label_key' => $labelKey,
                'captions' => $captions,
                'site_languages' => $this->siteLanguages(false),
            ]]);
        });
    }

    public function update(Request $request, int $labelId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $labelId) {
            $labelKey = (string) DB::table('tbl_language_labels')->where('label_id', $labelId)->value('label_key');
            if ($labelKey === '') {
                return response()->json(['message' => 'Record not found'], 404);
            }

            $captions = (array) $request->input('captions', []);
            foreach ($this->siteLanguages(false) as $language) {
                $langId = (int) $language['id'];
                $caption = trim((string) ($captions[$langId] ?? $captions[(string) $langId] ?? ''));
                if ($caption === '') {
                    return response()->json(['message' => 'Caption is required.'], 422);
                }

                DB::table('tbl_language_labels')->updateOrInsert(
                    ['label_key' => $labelKey, 'label_lang_id' => $langId],
                    [
                        'label_key' => $labelKey,
                        'label_lang_id' => $langId,
                        'label_caption' => $caption,
                    ],
                );
            }

            return response()->json(['message' => 'Setup successful']);
        });
    }

    public function export(Request $request): StreamedResponse|JsonResponse
    {
        $admin = $request->user();
        if (! $admin || ! $this->privileges->canView($admin->admin_id, AdminPrivilegeService::SECTION_LANGUAGE_LABELS)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $keyword = trim((string) $request->query('keyword', ''));
        $languages = $this->siteLanguages(false);
        $rows = DB::table('tbl_language_labels as lbl')
            ->join('tbl_languages as lang', 'lang.language_id', '=', 'lbl.label_lang_id')
            ->where('lang.language_active', 1)
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('lbl.label_key', 'like', "%{$keyword}%")
                        ->orWhere('lbl.label_caption', 'like', "%{$keyword}%");
                });
            })
            ->orderBy('lbl.label_key')
            ->orderByDesc('lbl.label_id')
            ->get(['lbl.label_key', 'lbl.label_lang_id', 'lbl.label_caption']);

        $filename = 'Labels_'.date('d-M-Y').'.csv';

        return response()->streamDownload(function () use ($languages, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, array_merge(['Key'], array_map(fn ($lang) => $lang['code'], $languages)));
            $grouped = [];
            foreach ($rows as $row) {
                $key = (string) $row->label_key;
                $grouped[$key] ??= array_fill(0, count($languages), '');
                foreach ($languages as $index => $language) {
                    if ((int) $language['id'] === (int) $row->label_lang_id) {
                        $grouped[$key][$index] = html_entity_decode((string) $row->label_caption, ENT_QUOTES, 'UTF-8');
                    }
                }
            }
            foreach ($grouped as $key => $captions) {
                fputcsv($out, array_merge([$key], $captions));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function import(Request $request): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request) {
            $file = $request->file('import_file');
            if (! $file) {
                return response()->json(['message' => 'Please select a CSV file.'], 422);
            }

            $handle = fopen($file->getRealPath(), 'r');
            $header = $handle ? fgetcsv($handle) : false;
            if (! $handle || empty($header)) {
                return response()->json(['message' => 'Not a valid CSV file.'], 422);
            }

            array_shift($header);
            $languagesByCode = collect($this->siteLanguages(false))->keyBy('code');
            $indexToLangId = [];
            foreach ($header as $index => $code) {
                $code = (string) $code;
                if ($code === '') {
                    continue;
                }
                if (! isset($languagesByCode[$code])) {
                    return response()->json(['message' => 'Invalid language code "'.$code.'"'], 422);
                }
                $indexToLangId[$index] = (int) $languagesByCode[$code]['id'];
            }
            if ($indexToLangId === []) {
                return response()->json(['message' => 'Please add a valid language code.'], 422);
            }

            while (($line = fgetcsv($handle)) !== false) {
                $labelKey = trim((string) array_shift($line));
                if ($labelKey === '') {
                    continue;
                }
                foreach ($line as $index => $caption) {
                    if (! isset($indexToLangId[$index])) {
                        continue;
                    }
                    DB::table('tbl_language_labels')->updateOrInsert(
                        ['label_key' => $labelKey, 'label_lang_id' => $indexToLangId[$index]],
                        [
                            'label_key' => $labelKey,
                            'label_lang_id' => $indexToLangId[$index],
                            'label_caption' => (string) $caption,
                        ],
                    );
                }
            }
            fclose($handle);

            return response()->json(['message' => 'Labels data imported successfully']);
        });
    }

    /** @return list<array{id: int, code: string, name: string}> */
    private function siteLanguages(bool $activeOnly = true): array
    {
        $query = DB::table('tbl_languages')->orderBy('language_id');
        if ($activeOnly) {
            $query->where('language_active', 1);
        }

        return $query
            ->get(['language_id as id', 'language_code as code', 'language_name as name'])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'code' => (string) $row->code,
                'name' => (string) $row->name,
            ])
            ->all();
    }

    private function guardEdit(Request $request, callable $callback): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $admin || ! $this->privileges->canEdit($admin->admin_id, AdminPrivilegeService::SECTION_LANGUAGE_LABELS)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $callback();
    }
}
