<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminPrivilegeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminFaqCategoriesController extends Controller
{
    public function __construct(private AdminPrivilegeService $privileges)
    {
    }

    public function show(Request $request, int $categoryId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($categoryId) {
            $row = $categoryId > 0
                ? DB::table('tbl_faq_categories')
                    ->where('faqcat_id', $categoryId)
                    ->where('faqcat_deleted', 0)
                    ->first()
                : null;
            if ($categoryId > 0 && ! $row) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['data' => [
                'faqcat_id' => (int) ($row->faqcat_id ?? 0),
                'faqcat_identifier' => (string) ($row->faqcat_identifier ?? ''),
                'faqcat_active' => (int) ($row->faqcat_active ?? 1),
                'site_languages' => $this->siteLanguages(),
            ]]);
        });
    }

    public function update(Request $request, int $categoryId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $categoryId) {
            $identifier = trim((string) $request->input('faqcat_identifier', ''));
            if ($identifier === '') {
                return response()->json(['message' => 'Category identifier is required.'], 422);
            }
            if ($this->duplicateIdentifier($identifier, $categoryId)) {
                return response()->json(['message' => 'Category identifier already exists.'], 422);
            }

            $data = [
                'faqcat_identifier' => $identifier,
                'faqcat_active' => (int) $request->input('faqcat_active', 1) === 1 ? 1 : 0,
            ];

            if ($categoryId > 0) {
                $updated = DB::table('tbl_faq_categories')
                    ->where('faqcat_id', $categoryId)
                    ->where('faqcat_deleted', 0)
                    ->update($data);
                if ($updated < 1) {
                    return response()->json(['message' => 'Record not found'], 404);
                }
            } else {
                $data['faqcat_deleted'] = 0;
                $data['faqcat_featured'] = 0;
                $data['faqcat_order'] = ((int) DB::table('tbl_faq_categories')->max('faqcat_order')) + 1;
                $categoryId = (int) DB::table('tbl_faq_categories')->insertGetId($data);
            }

            return response()->json([
                'message' => 'Category setup successful',
                'data' => [
                    'faqcat_id' => $categoryId,
                    'next_lang_id' => $this->nextMissingLangId($categoryId),
                ],
            ]);
        });
    }

    public function langForm(Request $request, int $categoryId, int $langId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($categoryId, $langId) {
            if (! $this->exists($categoryId)) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            $lang = DB::table('tbl_faq_categories_lang')
                ->where('faqcatlang_faqcat_id', $categoryId)
                ->where('faqcatlang_lang_id', $langId)
                ->first(['faqcat_name']);
            $languages = $this->siteLanguages();
            $defaultLang = $this->defaultLangId();

            return response()->json(['data' => [
                'faqcat_id' => $categoryId,
                'lang_id' => $langId,
                'faqcat_name' => (string) ($lang->faqcat_name ?? ''),
                'site_languages' => $languages,
                'default_lang_id' => $defaultLang,
                'show_auto_translate' => count($languages) > 1 && $langId === $defaultLang,
                'layout_direction' => $this->layoutDirection($langId),
            ]]);
        });
    }

    public function storeLang(Request $request, int $categoryId, int $langId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $categoryId, $langId) {
            if (! $this->exists($categoryId)) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            $name = trim((string) $request->input('faqcat_name', ''));
            if ($name === '') {
                return response()->json(['message' => 'Category name is required.'], 422);
            }

            DB::table('tbl_faq_categories_lang')->updateOrInsert(
                ['faqcatlang_faqcat_id' => $categoryId, 'faqcatlang_lang_id' => $langId],
                [
                    'faqcatlang_faqcat_id' => $categoryId,
                    'faqcatlang_lang_id' => $langId,
                    'faqcat_name' => $name,
                ],
            );

            if ($request->boolean('update_langs_data') && $langId === $this->defaultLangId()) {
                foreach ($this->siteLanguages() as $language) {
                    if ($language['id'] === $langId) {
                        continue;
                    }
                    DB::table('tbl_faq_categories_lang')->updateOrInsert(
                        ['faqcatlang_faqcat_id' => $categoryId, 'faqcatlang_lang_id' => $language['id']],
                        [
                            'faqcatlang_faqcat_id' => $categoryId,
                            'faqcatlang_lang_id' => $language['id'],
                            'faqcat_name' => $name,
                        ],
                    );
                }
            }

            return response()->json([
                'message' => 'Category setup successful',
                'data' => [
                    'faqcat_id' => $categoryId,
                    'next_lang_id' => $this->nextMissingLangId($categoryId),
                ],
            ]);
        });
    }

    public function updateStatus(Request $request, int $categoryId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $categoryId) {
            if (! $this->exists($categoryId)) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            DB::table('tbl_faq_categories')->where('faqcat_id', $categoryId)->update([
                'faqcat_active' => $request->boolean('active') ? 1 : 0,
            ]);

            return response()->json(['message' => 'Action performed successfully']);
        });
    }

    public function destroy(Request $request, int $categoryId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($categoryId) {
            $identifier = DB::table('tbl_faq_categories')
                ->where('faqcat_id', $categoryId)
                ->where('faqcat_deleted', 0)
                ->value('faqcat_identifier');
            if (! $identifier) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            DB::table('tbl_faq_categories')->where('faqcat_id', $categoryId)->update([
                'faqcat_deleted' => 1,
                'faqcat_identifier' => $identifier.'-'.$categoryId,
            ]);

            return response()->json(['message' => 'Record deleted successfully']);
        });
    }

    public function updateOrder(Request $request): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request) {
            $ids = array_values(array_unique(array_map('intval', (array) $request->input('ids', []))));
            foreach ($ids as $index => $id) {
                DB::table('tbl_faq_categories')
                    ->where('faqcat_id', $id)
                    ->where('faqcat_deleted', 0)
                    ->update(['faqcat_order' => $index + 1]);
            }

            return response()->json(['message' => 'Order updated successfully']);
        });
    }

    public function export(Request $request): StreamedResponse|JsonResponse
    {
        $admin = $request->user();
        if (! $admin || ! $this->privileges->canView($admin->admin_id, AdminPrivilegeService::SECTION_FAQ_CATEGORY)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $langId = (int) $request->query('lang_id', $this->defaultLangId());
        $rows = DB::table('tbl_faq_categories as fc')
            ->leftJoin('tbl_faq_categories_lang as fcl', function ($join) use ($langId) {
                $join->on('fcl.faqcatlang_faqcat_id', '=', 'fc.faqcat_id')
                    ->where('fcl.faqcatlang_lang_id', '=', $langId);
            })
            ->where('fc.faqcat_deleted', 0)
            ->orderByDesc('fc.faqcat_active')
            ->orderBy('fc.faqcat_order')
            ->get([
                'fc.faqcat_identifier',
                DB::raw('IFNULL(fcl.faqcat_name, fc.faqcat_identifier) as faqcat_name'),
                'fc.faqcat_active',
            ]);

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Category identifier', 'Category name', 'Status']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->faqcat_identifier,
                    $row->faqcat_name,
                    ((int) $row->faqcat_active === 1) ? 'Active' : 'Inactive',
                ]);
            }
            fclose($out);
        }, 'faq-categories.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function duplicateIdentifier(string $identifier, int $categoryId): bool
    {
        return DB::table('tbl_faq_categories')
            ->where('faqcat_identifier', $identifier)
            ->where('faqcat_id', '!=', $categoryId)
            ->where('faqcat_deleted', 0)
            ->exists();
    }

    private function exists(int $categoryId): bool
    {
        return DB::table('tbl_faq_categories')
            ->where('faqcat_id', $categoryId)
            ->where('faqcat_deleted', 0)
            ->exists();
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

    private function nextMissingLangId(int $categoryId): int
    {
        foreach ($this->siteLanguages() as $language) {
            if (! DB::table('tbl_faq_categories_lang')
                ->where('faqcatlang_faqcat_id', $categoryId)
                ->where('faqcatlang_lang_id', $language['id'])
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
        if (! $admin || ! $this->privileges->canEdit($admin->admin_id, AdminPrivilegeService::SECTION_FAQ_CATEGORY)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $callback();
    }
}
