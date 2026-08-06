<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminPrivilegeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminVideoContentController extends Controller
{
    public function __construct(private AdminPrivilegeService $privileges)
    {
    }

    public function show(Request $request, int $contentId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($contentId) {
            $row = $contentId > 0 ? DB::table('tbl_bible_content')->where('biblecontent_id', $contentId)->first() : null;
            if ($contentId > 0 && ! $row) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['data' => [
                'biblecontent_id' => (int) ($row->biblecontent_id ?? 0),
                'biblecontent_title' => (string) ($row->biblecontent_title ?? ''),
                'biblecontent_url' => (string) ($row->biblecontent_url ?? ''),
                'biblecontent_active' => (int) ($row->biblecontent_active ?? 1),
                'site_languages' => $this->siteLanguages(),
            ]]);
        });
    }

    public function update(Request $request, int $contentId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $contentId) {
            $title = trim((string) $request->input('biblecontent_title', ''));
            $url = trim((string) $request->input('biblecontent_url', ''));
            if ($title === '' || $url === '') {
                return response()->json(['message' => 'Invalid request'], 422);
            }
            if (! $this->isYoutubeUrl($url)) {
                return response()->json(['message' => 'Please enter a valid YouTube URL'], 422);
            }
            if ($this->duplicateTitle($title, $contentId)) {
                return response()->json(['message' => 'Title already exists.'], 422);
            }

            $data = [
                'biblecontent_title' => $title,
                'biblecontent_url' => $url,
                'biblecontent_active' => (int) $request->input('biblecontent_active', 1) === 1 ? 1 : 0,
            ];

            if ($contentId > 0) {
                DB::table('tbl_bible_content')->where('biblecontent_id', $contentId)->update($data);
            } else {
                $data['biblecontent_order'] = ((int) DB::table('tbl_bible_content')->max('biblecontent_order')) + 1;
                $contentId = (int) DB::table('tbl_bible_content')->insertGetId($data);
            }

            return response()->json([
                'message' => 'Setup successful',
                'data' => [
                    'biblecontent_id' => $contentId,
                    'next_lang_id' => $this->nextMissingLangId($contentId),
                ],
            ]);
        });
    }

    public function langForm(Request $request, int $contentId, int $langId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($contentId, $langId) {
            $content = DB::table('tbl_bible_content')->where('biblecontent_id', $contentId)->first(['biblecontent_id']);
            if (! $content) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            $lang = DB::table('tbl_bible_content_lang')
                ->where('biblecontentlang_biblecontent_id', $contentId)
                ->where('biblecontentlang_lang_id', $langId)
                ->first(['biblecontentlang_biblecontent_title']);
            $languages = $this->siteLanguages();
            $defaultLang = $this->defaultLangId();

            return response()->json(['data' => [
                'biblecontent_id' => $contentId,
                'lang_id' => $langId,
                'biblecontentlang_biblecontent_title' => (string) ($lang->biblecontentlang_biblecontent_title ?? ''),
                'site_languages' => $languages,
                'default_lang_id' => $defaultLang,
                'show_auto_translate' => count($languages) > 1 && $langId === $defaultLang,
                'layout_direction' => $this->layoutDirection($langId),
            ]]);
        });
    }

    public function storeLang(Request $request, int $contentId, int $langId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $contentId, $langId) {
            if (! DB::table('tbl_bible_content')->where('biblecontent_id', $contentId)->exists()) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            $title = trim((string) $request->input('biblecontentlang_biblecontent_title', ''));
            if ($title === '') {
                return response()->json(['message' => 'Title is required.'], 422);
            }

            DB::table('tbl_bible_content_lang')->updateOrInsert(
                ['biblecontentlang_biblecontent_id' => $contentId, 'biblecontentlang_lang_id' => $langId],
                [
                    'biblecontentlang_biblecontent_id' => $contentId,
                    'biblecontentlang_lang_id' => $langId,
                    'biblecontentlang_biblecontent_title' => $title,
                ],
            );

            if ($request->boolean('update_langs_data') && $langId === $this->defaultLangId()) {
                $this->syncOtherLanguageRows($contentId, $langId, $title);
            }

            return response()->json([
                'message' => 'Setup successful',
                'data' => [
                    'biblecontent_id' => $contentId,
                    'next_lang_id' => $this->nextMissingLangId($contentId),
                ],
            ]);
        });
    }

    public function updateStatus(Request $request, int $contentId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $contentId) {
            if (! DB::table('tbl_bible_content')->where('biblecontent_id', $contentId)->exists()) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            DB::table('tbl_bible_content')->where('biblecontent_id', $contentId)->update([
                'biblecontent_active' => $request->boolean('active') ? 1 : 0,
            ]);

            return response()->json(['message' => 'Action performed successfully']);
        });
    }

    public function destroy(Request $request, int $contentId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($contentId) {
            DB::table('tbl_bible_content_lang')->where('biblecontentlang_biblecontent_id', $contentId)->delete();
            $deleted = DB::table('tbl_bible_content')->where('biblecontent_id', $contentId)->delete();
            if ($deleted < 1) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['message' => 'Record deleted successfully']);
        });
    }

    public function updateOrder(Request $request): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request) {
            $ids = array_values(array_unique(array_map('intval', (array) $request->input('ids', []))));
            DB::transaction(function () use ($ids) {
                foreach ($ids as $index => $id) {
                    DB::table('tbl_bible_content')->where('biblecontent_id', $id)->update([
                        'biblecontent_order' => $index + 1,
                    ]);
                }
            });

            return response()->json(['message' => 'Order updated successfully']);
        });
    }

    private function duplicateTitle(string $title, int $contentId): bool
    {
        return DB::table('tbl_bible_content')
            ->where('biblecontent_title', $title)
            ->where('biblecontent_id', '!=', $contentId)
            ->exists();
    }

    private function isYoutubeUrl(string $url): bool
    {
        return preg_match('#^(https?://)?(www\.)?(youtube\.com|youtu\.be)/#i', $url) === 1;
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

    private function nextMissingLangId(int $contentId): int
    {
        foreach ($this->siteLanguages() as $language) {
            $exists = DB::table('tbl_bible_content_lang')
                ->where('biblecontentlang_biblecontent_id', $contentId)
                ->where('biblecontentlang_lang_id', $language['id'])
                ->exists();
            if (! $exists) {
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

    private function syncOtherLanguageRows(int $contentId, int $sourceLangId, string $title): void
    {
        foreach ($this->siteLanguages() as $language) {
            if ($language['id'] === $sourceLangId) {
                continue;
            }

            DB::table('tbl_bible_content_lang')->updateOrInsert(
                ['biblecontentlang_biblecontent_id' => $contentId, 'biblecontentlang_lang_id' => $language['id']],
                [
                    'biblecontentlang_biblecontent_id' => $contentId,
                    'biblecontentlang_lang_id' => $language['id'],
                    'biblecontentlang_biblecontent_title' => $title,
                ],
            );
        }
    }

    private function guardEdit(Request $request, callable $callback): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canEdit($admin->admin_id, AdminPrivilegeService::SECTION_VIDEO_CONTENT)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $callback();
    }
}
