<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminPrivilegeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class AdminTestimonialsController extends Controller
{
    private const FILE_TYPE_TESTIMONIAL_IMAGE = 26;

    public function __construct(private AdminPrivilegeService $privileges)
    {
    }

    public function show(Request $request, int $testimonialId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($testimonialId) {
            $row = $testimonialId > 0
                ? DB::table('tbl_testimonials')
                    ->where('testimonial_id', $testimonialId)
                    ->where('testimonial_deleted', '0')
                    ->first()
                : null;

            if ($testimonialId > 0 && ! $row) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['data' => [
                'testimonial_id' => (int) ($row->testimonial_id ?? 0),
                'testimonial_identifier' => (string) ($row->testimonial_identifier ?? ''),
                'testimonial_user_name' => (string) ($row->testimonial_user_name ?? ''),
                'testimonial_active' => (int) ($row->testimonial_active ?? 1),
                'site_languages' => $this->siteLanguages(),
            ]]);
        });
    }

    public function update(Request $request, int $testimonialId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $testimonialId) {
            $identifier = trim((string) $request->input('testimonial_identifier', ''));
            $userName = trim((string) $request->input('testimonial_user_name', ''));
            if ($identifier === '' || $userName === '') {
                return response()->json(['message' => 'Invalid request'], 422);
            }
            if ($this->duplicateIdentifier($identifier, $testimonialId)) {
                return response()->json(['message' => 'Identifier already exists.'], 422);
            }

            $data = [
                'testimonial_identifier' => $identifier,
                'testimonial_user_name' => $userName,
                'testimonial_active' => (int) $request->input('testimonial_active', 1) === 1 ? 1 : 0,
                'testimonial_deleted' => '0',
            ];

            if ($testimonialId > 0) {
                $updated = DB::table('tbl_testimonials')
                    ->where('testimonial_id', $testimonialId)
                    ->where('testimonial_deleted', '0')
                    ->update($data);
                if ($updated < 1) {
                    return response()->json(['message' => 'Record not found'], 404);
                }
            } else {
                $data['testimonial_added_on'] = now()->format('Y-m-d H:i:s');
                $testimonialId = (int) DB::table('tbl_testimonials')->insertGetId($data);
            }

            return response()->json([
                'message' => 'Setup successful',
                'data' => [
                    'testimonial_id' => $testimonialId,
                    'next_lang_id' => $this->nextMissingLangId($testimonialId),
                ],
            ]);
        });
    }

    public function langForm(Request $request, int $testimonialId, int $langId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($testimonialId, $langId) {
            if (! $this->exists($testimonialId)) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            $lang = DB::table('tbl_testimonials_lang')
                ->where('testimoniallang_testimonial_id', $testimonialId)
                ->where('testimoniallang_lang_id', $langId)
                ->first(['testimonial_text']);
            $languages = $this->siteLanguages();
            $defaultLang = $this->defaultLangId();

            return response()->json(['data' => [
                'testimonial_id' => $testimonialId,
                'lang_id' => $langId,
                'testimonial_text' => (string) ($lang->testimonial_text ?? ''),
                'site_languages' => $languages,
                'default_lang_id' => $defaultLang,
                'show_auto_translate' => count($languages) > 1 && $langId === $defaultLang,
                'layout_direction' => $this->layoutDirection($langId),
            ]]);
        });
    }

    public function storeLang(Request $request, int $testimonialId, int $langId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $testimonialId, $langId) {
            if (! $this->exists($testimonialId)) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            $text = trim((string) $request->input('testimonial_text', ''));
            if (mb_strlen($text) < 10 || mb_strlen($text) > 300) {
                return response()->json(['message' => 'Testimonial text must be between 10 and 300 characters.'], 422);
            }

            DB::table('tbl_testimonials_lang')->updateOrInsert(
                ['testimoniallang_testimonial_id' => $testimonialId, 'testimoniallang_lang_id' => $langId],
                [
                    'testimoniallang_testimonial_id' => $testimonialId,
                    'testimoniallang_lang_id' => $langId,
                    'testimonial_text' => $text,
                ],
            );

            if ($request->boolean('update_langs_data') && $langId === $this->defaultLangId()) {
                $this->syncOtherLanguageRows($testimonialId, $langId, $text);
            }

            return response()->json([
                'message' => 'Setup successful',
                'data' => [
                    'testimonial_id' => $testimonialId,
                    'next_lang_id' => $this->nextMissingLangId($testimonialId),
                ],
            ]);
        });
    }

    public function media(Request $request, int $testimonialId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($testimonialId) {
            if (! $this->exists($testimonialId)) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            $file = DB::table('tbl_attached_files')
                ->where('file_type', self::FILE_TYPE_TESTIMONIAL_IMAGE)
                ->where('file_record_id', $testimonialId)
                ->orderByDesc('file_id')
                ->first(['file_id', 'file_record_id', 'file_lang_id']);

            return response()->json(['data' => [
                'testimonial_id' => $testimonialId,
                'preferred_dimensions' => '275 x 275',
                'image' => $file ? [
                    'file_id' => (int) $file->file_id,
                    'file_lang_id' => (int) $file->file_lang_id,
                    'url' => '/api/v1/image/show-by-id/'.(int) $file->file_id.'/SMALL',
                ] : null,
                'site_languages' => $this->siteLanguages(),
            ]]);
        });
    }

    public function uploadMedia(Request $request, int $testimonialId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $testimonialId) {
            if (! $this->exists($testimonialId)) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            $file = $request->file('file', $request->file('testimonial_image'));
            if (! $file instanceof UploadedFile) {
                return response()->json(['message' => 'Invalid request or file not supported'], 422);
            }

            $result = $this->saveImage($testimonialId, $file);
            if (! ($result['ok'] ?? false)) {
                return response()->json(['message' => $result['message'] ?? 'Unable to upload image'], 422);
            }

            return response()->json(['message' => 'File uploaded successfully']);
        });
    }

    public function removeMedia(Request $request, int $testimonialId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($testimonialId) {
            if (! $this->exists($testimonialId)) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            $this->deleteImages($testimonialId);

            return response()->json(['message' => 'Deleted successfully']);
        });
    }

    public function updateStatus(Request $request, int $testimonialId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $testimonialId) {
            if (! $this->exists($testimonialId)) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            DB::table('tbl_testimonials')->where('testimonial_id', $testimonialId)->update([
                'testimonial_active' => $request->boolean('active') ? 1 : 0,
            ]);

            return response()->json(['message' => 'Action performed successfully']);
        });
    }

    public function destroy(Request $request, int $testimonialId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($testimonialId) {
            $identifier = DB::table('tbl_testimonials')
                ->where('testimonial_id', $testimonialId)
                ->where('testimonial_deleted', '0')
                ->value('testimonial_identifier');
            if (! $identifier) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            DB::table('tbl_testimonials')->where('testimonial_id', $testimonialId)->update([
                'testimonial_deleted' => '1',
                'testimonial_identifier' => $identifier.'-'.$testimonialId,
            ]);
            $this->deleteImages($testimonialId);

            return response()->json(['message' => 'Record deleted successfully']);
        });
    }

    private function saveImage(int $testimonialId, UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: '');
        if (! in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp'], true)) {
            return ['ok' => false, 'message' => 'Invalid file type'];
        }

        $uploadRoot = public_path('user-uploads');
        if (! is_dir($uploadRoot)) {
            @mkdir($uploadRoot, 0755, true);
        }

        $fileName = 'testimonial_'.$testimonialId.'_'.time().'.'.$ext;
        $relativePath = date('Y/m').'/'.$fileName;
        $targetDir = $uploadRoot.'/'.dirname($relativePath);
        if (! is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }

        $file->move($targetDir, $fileName);
        $oldFiles = $this->testimonialFiles($testimonialId);
        DB::table('tbl_attached_files')
            ->where('file_type', self::FILE_TYPE_TESTIMONIAL_IMAGE)
            ->where('file_record_id', $testimonialId)
            ->delete();
        DB::table('tbl_attached_files')->insert([
            'file_type' => self::FILE_TYPE_TESTIMONIAL_IMAGE,
            'file_lang_id' => 0,
            'file_record_id' => $testimonialId,
            'file_name' => $fileName,
            'file_path' => $relativePath,
            'file_order' => 0,
            'file_added' => now()->format('Y-m-d H:i:s'),
        ]);
        $this->unlinkFiles($oldFiles);

        return ['ok' => true];
    }

    private function deleteImages(int $testimonialId): void
    {
        $oldFiles = $this->testimonialFiles($testimonialId);
        DB::table('tbl_attached_files')
            ->where('file_type', self::FILE_TYPE_TESTIMONIAL_IMAGE)
            ->where('file_record_id', $testimonialId)
            ->delete();
        $this->unlinkFiles($oldFiles);
    }

    private function testimonialFiles(int $testimonialId)
    {
        return DB::table('tbl_attached_files')
            ->where('file_type', self::FILE_TYPE_TESTIMONIAL_IMAGE)
            ->where('file_record_id', $testimonialId)
            ->get(['file_path']);
    }

    private function unlinkFiles($files): void
    {
        $uploadRoot = public_path('user-uploads');
        foreach ($files as $file) {
            if (! empty($file->file_path)) {
                $path = $uploadRoot.'/'.$file->file_path;
                if (is_file($path)) {
                    @unlink($path);
                }
            }
        }
    }

    private function duplicateIdentifier(string $identifier, int $testimonialId): bool
    {
        return DB::table('tbl_testimonials')
            ->where('testimonial_identifier', $identifier)
            ->where('testimonial_id', '!=', $testimonialId)
            ->where('testimonial_deleted', '0')
            ->exists();
    }

    private function exists(int $testimonialId): bool
    {
        return DB::table('tbl_testimonials')
            ->where('testimonial_id', $testimonialId)
            ->where('testimonial_deleted', '0')
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

    private function nextMissingLangId(int $testimonialId): int
    {
        foreach ($this->siteLanguages() as $language) {
            $exists = DB::table('tbl_testimonials_lang')
                ->where('testimoniallang_testimonial_id', $testimonialId)
                ->where('testimoniallang_lang_id', $language['id'])
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

    private function syncOtherLanguageRows(int $testimonialId, int $sourceLangId, string $text): void
    {
        foreach ($this->siteLanguages() as $language) {
            if ($language['id'] === $sourceLangId) {
                continue;
            }

            DB::table('tbl_testimonials_lang')->updateOrInsert(
                ['testimoniallang_testimonial_id' => $testimonialId, 'testimoniallang_lang_id' => $language['id']],
                [
                    'testimoniallang_testimonial_id' => $testimonialId,
                    'testimoniallang_lang_id' => $language['id'],
                    'testimonial_text' => $text,
                ],
            );
        }
    }

    private function guardEdit(Request $request, callable $callback): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canEdit($admin->admin_id, AdminPrivilegeService::SECTION_TESTIMONIAL)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $callback();
    }
}
