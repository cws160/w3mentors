<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminPrivilegeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminFaqController extends Controller
{
    public function __construct(private AdminPrivilegeService $privileges)
    {
    }

    public function show(Request $request, int $faqId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($faqId) {
            $row = $faqId > 0 ? DB::table('tbl_faq')->where('faq_id', $faqId)->first() : null;
            if ($faqId > 0 && ! $row) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['data' => [
                'faq_id' => (int) ($row->faq_id ?? 0),
                'faq_identifier' => (string) ($row->faq_identifier ?? ''),
                'faq_category' => (int) ($row->faq_category ?? 0),
                'faq_active' => (int) ($row->faq_active ?? 1),
                'categories' => $this->categories(),
                'site_languages' => $this->siteLanguages(),
            ]]);
        });
    }

    public function update(Request $request, int $faqId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $faqId) {
            $identifier = trim((string) $request->input('faq_identifier', ''));
            $categoryId = (int) $request->input('faq_category', 0);
            if ($identifier === '' || $categoryId < 1) {
                return response()->json(['message' => 'Invalid request'], 422);
            }
            if ($this->duplicateIdentifier($identifier, $faqId)) {
                return response()->json(['message' => 'FAQ identifier already exists.'], 422);
            }
            if (! $this->categoryExists($categoryId)) {
                return response()->json(['message' => 'FAQ category is required.'], 422);
            }

            $data = [
                'faq_identifier' => $identifier,
                'faq_category' => $categoryId,
                'faq_active' => (int) $request->input('faq_active', 1) === 1 ? 1 : 0,
            ];

            if ($faqId > 0) {
                $updated = DB::table('tbl_faq')->where('faq_id', $faqId)->update($data);
                if ($updated < 1 && ! DB::table('tbl_faq')->where('faq_id', $faqId)->exists()) {
                    return response()->json(['message' => 'Record not found'], 404);
                }
            } else {
                $data['faq_added_on'] = now();
                $faqId = (int) DB::table('tbl_faq')->insertGetId($data);
            }

            return response()->json([
                'message' => 'Setup successful',
                'data' => [
                    'faq_id' => $faqId,
                    'next_lang_id' => $this->nextMissingLangId($faqId),
                ],
            ]);
        });
    }

    public function langForm(Request $request, int $faqId, int $langId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($faqId, $langId) {
            if (! $this->exists($faqId)) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            $lang = DB::table('tbl_faq_lang')
                ->where('faqlang_faq_id', $faqId)
                ->where('faqlang_lang_id', $langId)
                ->first(['faq_title', 'faq_description']);
            $languages = $this->siteLanguages();
            $defaultLang = $this->defaultLangId();

            return response()->json(['data' => [
                'faq_id' => $faqId,
                'lang_id' => $langId,
                'faq_title' => (string) ($lang->faq_title ?? ''),
                'faq_description' => (string) ($lang->faq_description ?? ''),
                'site_languages' => $languages,
                'default_lang_id' => $defaultLang,
                'show_auto_translate' => count($languages) > 1 && $langId === $defaultLang,
                'layout_direction' => $this->layoutDirection($langId),
            ]]);
        });
    }

    public function storeLang(Request $request, int $faqId, int $langId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $faqId, $langId) {
            if (! $this->exists($faqId)) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            $title = trim((string) $request->input('faq_title', ''));
            $description = trim((string) $request->input('faq_description', ''));
            if ($title === '') {
                return response()->json(['message' => 'FAQ title is required.'], 422);
            }

            DB::table('tbl_faq_lang')->updateOrInsert(
                ['faqlang_faq_id' => $faqId, 'faqlang_lang_id' => $langId],
                [
                    'faqlang_faq_id' => $faqId,
                    'faqlang_lang_id' => $langId,
                    'faq_title' => $title,
                    'faq_description' => $description,
                ],
            );

            if ($request->boolean('update_langs_data') && $langId === $this->defaultLangId()) {
                foreach ($this->siteLanguages() as $language) {
                    if ($language['id'] === $langId) {
                        continue;
                    }
                    DB::table('tbl_faq_lang')->updateOrInsert(
                        ['faqlang_faq_id' => $faqId, 'faqlang_lang_id' => $language['id']],
                        [
                            'faqlang_faq_id' => $faqId,
                            'faqlang_lang_id' => $language['id'],
                            'faq_title' => $title,
                            'faq_description' => $description,
                        ],
                    );
                }
            }

            return response()->json([
                'message' => 'Setup successful',
                'data' => [
                    'faq_id' => $faqId,
                    'next_lang_id' => $this->nextMissingLangId($faqId),
                ],
            ]);
        });
    }

    public function updateStatus(Request $request, int $faqId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $faqId) {
            if (! $this->exists($faqId)) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            DB::table('tbl_faq')->where('faq_id', $faqId)->update([
                'faq_active' => $request->boolean('active') ? 1 : 0,
            ]);

            return response()->json(['message' => 'Action performed successfully']);
        });
    }

    public function destroy(Request $request, int $faqId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($faqId) {
            DB::table('tbl_faq_lang')->where('faqlang_faq_id', $faqId)->delete();
            $deleted = DB::table('tbl_faq')->where('faq_id', $faqId)->delete();
            if ($deleted < 1) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['message' => 'Record deleted successfully']);
        });
    }

    public function export(Request $request): StreamedResponse|JsonResponse
    {
        $admin = $request->user();
        if (! $admin || ! $this->privileges->canView($admin->admin_id, AdminPrivilegeService::SECTION_FAQ)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $langId = (int) $request->query('lang_id', $this->defaultLangId());
        $query = DB::table('tbl_faq as f')
            ->leftJoin('tbl_faq_lang as fl', function ($join) use ($langId) {
                $join->on('fl.faqlang_faq_id', '=', 'f.faq_id')
                    ->where('fl.faqlang_lang_id', '=', $langId);
            })
            ->leftJoin('tbl_faq_categories as fc', 'fc.faqcat_id', '=', 'f.faq_category')
            ->leftJoin('tbl_faq_categories_lang as fcl', function ($join) use ($langId) {
                $join->on('fcl.faqcatlang_faqcat_id', '=', 'fc.faqcat_id')
                    ->where('fcl.faqcatlang_lang_id', '=', $langId);
            })
            ->select([
                'f.faq_identifier',
                'fl.faq_title',
                DB::raw('IFNULL(fcl.faqcat_name, fc.faqcat_identifier) as faqcat_name'),
                'f.faq_active',
            ])
            ->orderByDesc('f.faq_active');

        $keyword = trim((string) $request->query('keyword', ''));
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('f.faq_identifier', 'like', "%{$keyword}%")
                    ->orWhere('fl.faq_title', 'like', "%{$keyword}%");
            });
        }

        $rows = $query->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['FAQ Identifier', 'FAQ Title', 'Category', 'Status']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->faq_identifier,
                    $row->faq_title,
                    $row->faqcat_name,
                    ((int) $row->faq_active === 1) ? 'Active' : 'Inactive',
                ]);
            }
            fclose($out);
        }, 'faq.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function duplicateIdentifier(string $identifier, int $faqId): bool
    {
        return DB::table('tbl_faq')
            ->where('faq_identifier', $identifier)
            ->where('faq_id', '!=', $faqId)
            ->exists();
    }

    private function exists(int $faqId): bool
    {
        return DB::table('tbl_faq')->where('faq_id', $faqId)->exists();
    }

    private function categoryExists(int $categoryId): bool
    {
        return DB::table('tbl_faq_categories')
            ->where('faqcat_id', $categoryId)
            ->where('faqcat_active', 1)
            ->where('faqcat_deleted', 0)
            ->exists();
    }

    /** @return list<array{id: int, name: string}> */
    private function categories(): array
    {
        $langId = $this->defaultLangId();

        return DB::table('tbl_faq_categories as fc')
            ->leftJoin('tbl_faq_categories_lang as fcl', function ($join) use ($langId) {
                $join->on('fcl.faqcatlang_faqcat_id', '=', 'fc.faqcat_id')
                    ->where('fcl.faqcatlang_lang_id', '=', $langId);
            })
            ->where('fc.faqcat_active', 1)
            ->where('fc.faqcat_deleted', 0)
            ->orderBy('fc.faqcat_order')
            ->get([
                'fc.faqcat_id as id',
                DB::raw('IFNULL(fcl.faqcat_name, fc.faqcat_identifier) as name'),
            ])
            ->map(fn ($row) => ['id' => (int) $row->id, 'name' => (string) $row->name])
            ->all();
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

    private function nextMissingLangId(int $faqId): int
    {
        foreach ($this->siteLanguages() as $language) {
            if (! DB::table('tbl_faq_lang')
                ->where('faqlang_faq_id', $faqId)
                ->where('faqlang_lang_id', $language['id'])
                ->exists()) {
                return $language['id'];
            }
        }

        return 0;
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

    private function guardEdit(Request $request, callable $callback): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $admin || ! $this->privileges->canEdit($admin->admin_id, AdminPrivilegeService::SECTION_FAQ)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $callback();
    }
}
