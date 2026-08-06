<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminPrivilegeService;
use App\Support\Branding;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class AdminCertificatesController extends Controller
{
    private const TYPE_CERTIFICATE_BACKGROUND_IMAGE = 56;

    public function __construct(private AdminPrivilegeService $privileges)
    {
    }

    public function langForm(Request $request, string $code, int $langId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($code, $langId) {
            $row = $this->template($code, $langId) ?? $this->template($code, $this->defaultLangId());
            if (! $row) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['data' => $this->formPayload($row, $code, $langId)]);
        });
    }

    public function storeLang(Request $request, string $code, int $langId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $code, $langId) {
            $existing = $this->template($code, $langId) ?? $this->template($code, $this->defaultLangId());
            if (! $existing) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            $name = trim((string) $request->input('certpl_name', ''));
            if ($name === '') {
                return response()->json(['message' => 'Invalid request'], 422);
            }

            $body = [
                'heading' => (string) $request->input('heading', ''),
                'content_part_1' => (string) $request->input('content_part_1', ''),
                'learner' => (string) $request->input('learner', ''),
                'content_part_2' => (string) $request->input('content_part_2', ''),
                'trainer' => (string) $request->input('trainer', ''),
                'certificate_number' => (string) $request->input('certificate_number', ''),
            ];
            if (trim(implode('', $body)) === '') {
                return response()->json(['message' => 'Invalid request'], 422);
            }

            $status = (int) $request->input('certpl_status', $existing->certpl_status ?? 1) === 1 ? 1 : 0;
            $now = now()->format('Y-m-d H:i:s');
            $payload = [
                'certpl_type' => (int) $existing->certpl_type,
                'certpl_lang_id' => $langId,
                'certpl_code' => $code,
                'certpl_name' => $name,
                'certpl_body' => json_encode($body, JSON_UNESCAPED_UNICODE),
                'certpl_vars' => (string) ($existing->certpl_vars ?? ''),
                'certpl_status' => $status,
                'certpl_updated' => $now,
                'certpl_deleted' => null,
            ];

            DB::table('tbl_certificate_templates')->updateOrInsert(
                ['certpl_code' => $code, 'certpl_lang_id' => $langId],
                $payload + ['certpl_created' => (string) ($existing->certpl_created ?? $now)],
            );

            if ($request->boolean('update_langs_data') && $langId === $this->defaultLangId()) {
                foreach ($this->siteLanguages() as $language) {
                    if ($language['id'] === $langId) {
                        continue;
                    }
                    $other = $this->template($code, $language['id']) ?? $existing;
                    DB::table('tbl_certificate_templates')->updateOrInsert(
                        ['certpl_code' => $code, 'certpl_lang_id' => $language['id']],
                        array_merge($payload, [
                            'certpl_lang_id' => $language['id'],
                            'certpl_vars' => (string) ($other->certpl_vars ?? $existing->certpl_vars ?? ''),
                            'certpl_created' => (string) ($other->certpl_created ?? $now),
                        ]),
                    );
                }
            }

            return response()->json(['message' => 'Setup successful']);
        });
    }

    public function updateStatus(Request $request, string $code): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $code) {
            if (! $this->template($code, $this->defaultLangId())) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            DB::table('tbl_certificate_templates')->where('certpl_code', $code)->update([
                'certpl_status' => $request->boolean('active') ? 1 : 0,
                'certpl_updated' => now()->format('Y-m-d H:i:s'),
            ]);

            return response()->json(['message' => 'Status updated successfully']);
        });
    }

    public function uploadMedia(Request $request, string $code): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $code) {
            $template = $this->template($code, (int) $request->input('lang_id', $this->defaultLangId())) ?? $this->template($code, $this->defaultLangId());
            if (! $template) {
                return response()->json(['message' => 'Record not found'], 404);
            }
            if (! $request->hasFile('certpl_image')) {
                return response()->json(['message' => 'Invalid request'], 422);
            }
            $file = $request->file('certpl_image');
            if (! $file || ! $file->isValid()) {
                return response()->json(['message' => 'Invalid file'], 422);
            }
            $ext = strtolower($file->getClientOriginalExtension() ?: '');
            if (! in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp'], true)) {
                return response()->json(['message' => 'Invalid file type'], 422);
            }

            $uploadRoot = base_path('../user-uploads');
            $relativeDir = date('Y').'/'.date('m').'/';
            $absoluteDir = $uploadRoot.'/'.$relativeDir;
            if (! is_dir($absoluteDir)) {
                @mkdir($absoluteDir, 0755, true);
            }
            $original = preg_replace('/[^a-zA-Z0-9.]/', '', $file->getClientOriginalName()) ?: 'certificate.'.$ext;
            $fileName = $original;
            while (is_file($absoluteDir.$fileName)) {
                $fileName = time().'-'.$original;
            }
            $file->move($absoluteDir, $fileName);

            $recordId = (int) $template->certpl_type;
            $existing = DB::table('tbl_attached_files')
                ->where('file_type', self::TYPE_CERTIFICATE_BACKGROUND_IMAGE)
                ->where('file_record_id', $recordId)
                ->where('file_lang_id', 0)
                ->get(['file_id', 'file_path']);
            foreach ($existing as $row) {
                if ($row->file_path && is_file($uploadRoot.'/'.$row->file_path)) {
                    @unlink($uploadRoot.'/'.$row->file_path);
                }
                DB::table('tbl_attached_files')->where('file_id', $row->file_id)->delete();
            }

            DB::table('tbl_attached_files')->insert([
                'file_type' => self::TYPE_CERTIFICATE_BACKGROUND_IMAGE,
                'file_record_id' => $recordId,
                'file_lang_id' => 0,
                'file_path' => $relativeDir.$fileName,
                'file_name' => $fileName,
                'file_order' => 0,
                'file_added' => now()->format('Y-m-d H:i:s'),
            ]);

            return response()->json([
                'message' => 'Files uploaded successfully',
                'data' => ['background_url' => $this->imageUrl(self::TYPE_CERTIFICATE_BACKGROUND_IMAGE, $recordId, 'LARGE')],
            ]);
        });
    }

    public function preview(Request $request, string $code, int $langId): Response|JsonResponse
    {
        $admin = $request->user();
        if (! $admin || ! $this->privileges->canView($admin->admin_id, AdminPrivilegeService::SECTION_MANAGE_CERTIFICATES)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $template = $this->template($code, $langId) ?? $this->template($code, $this->defaultLangId());
        if (! $template) {
            return response()->json(['message' => 'Certificate template not found'], 404);
        }

        return response($this->previewHtml($template, $langId), 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    private function formPayload(object $row, string $code, int $langId): array
    {
        $body = json_decode((string) ($row->certpl_body ?? ''), true);
        if (! is_array($body)) {
            $body = [];
        }

        return [
            'certpl_id' => (int) $row->certpl_id,
            'certpl_type' => (int) $row->certpl_type,
            'certpl_code' => $code,
            'certpl_lang_id' => $langId,
            'certpl_name' => (string) ($row->certpl_name ?? ''),
            'certpl_status' => (int) ($row->certpl_status ?? 1),
            'certpl_vars' => (string) ($row->certpl_vars ?? ''),
            'heading' => (string) ($body['heading'] ?? ''),
            'content_part_1' => (string) ($body['content_part_1'] ?? ''),
            'learner' => (string) ($body['learner'] ?? ''),
            'content_part_2' => (string) ($body['content_part_2'] ?? ''),
            'trainer' => (string) ($body['trainer'] ?? ''),
            'certificate_number' => (string) ($body['certificate_number'] ?? ''),
            'site_languages' => $this->siteLanguages(),
            'layout_direction' => $this->layoutDirection($langId),
            'show_auto_translate' => count($this->siteLanguages()) > 1 && $langId === $this->defaultLangId(),
            'background_url' => $this->imageUrl(self::TYPE_CERTIFICATE_BACKGROUND_IMAGE, (int) $row->certpl_type, 'LARGE'),
            'logo_url' => $this->brandLogoUrl(),
        ];
    }

    private function previewHtml(object $template, int $langId): string
    {
        $body = json_decode((string) $template->certpl_body, true) ?: [];
        $html = strtr($this->certificateLayout((int) $template->certpl_type, $langId), [
            '{heading}' => '<b>'.($body['heading'] ?? '').'</b>',
            '{content-1}' => (string) ($body['content_part_1'] ?? ''),
            '{learner}' => '<b>'.($body['learner'] ?? '').'</b>',
            '{content-2}' => (string) ($body['content_part_2'] ?? ''),
            '{trainer}' => (string) ($body['trainer'] ?? ''),
            '{certificate-number}' => (string) ($body['certificate_number'] ?? ''),
        ]);
        $html = strtr($html, $this->previewData());

        return '<!doctype html><html><head><meta charset="utf-8"><title>'.e((string) $template->certpl_name).'</title></head><body>'.$html.'</body></html>';
    }

    private function certificateLayout(int $type, int $langId): string
    {
        $direction = $this->layoutDirection($langId);
        $background = $this->imageUrl(self::TYPE_CERTIFICATE_BACKGROUND_IMAGE, $type, 'LARGE');
        $logo = $this->brandLogoUrl();

        return <<<HTML
<div class="layout--{$direction}" style="width:100%;">
  <div class="certificate" style="width:2070px;height:1680px;background-image:url('{$background}');background-size:100% 100%;background-repeat:no-repeat;position:relative;font-family:Open Sans,Arial,sans-serif;">
    <div class="certificate-content" style="position:absolute;left:0;right:0;top:0;bottom:0;text-align:center;padding:8% 8% 4%;display:flex;flex-direction:column;">
      <h1 style="font-size:3.6rem;font-weight:900;margin-bottom:7%;font-style:italic;">{heading}</h1>
      <div style="font-size:2rem;margin-bottom:3%;font-style:italic;">{content-1}</div>
      <div style="font-size:2rem;margin-bottom:5%;">{learner}</div>
      <div style="font-size:1.5rem;line-height:1.6;max-width:90%;margin:0 auto 5%;font-style:italic;height:200px;">{content-2}</div>
      <div style="font-size:14px;">
        <table border="0" cellspacing="0" style="width:100%">
          <tr>
            <td width="33.3%" style="text-align:left;">{trainer}</td>
            <td width="33.3%" style="text-align:center;"><img width="140" height="47" src="{$logo}" alt=""></td>
            <td width="33.3%" style="text-align:right;">{certificate-number}</td>
          </tr>
        </table>
      </div>
    </div>
  </div>
</div>
HTML;
    }

    private function template(string $code, int $langId): ?object
    {
        return DB::table('tbl_certificate_templates')
            ->where('certpl_code', $code)
            ->where('certpl_lang_id', $langId)
            ->whereNull('certpl_deleted')
            ->first();
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

    private function layoutDirection(int $langId): string
    {
        $direction = strtolower((string) DB::table('tbl_languages')->where('language_id', $langId)->value('language_direction'));

        return in_array($direction, ['rtl', 'ltr'], true) ? $direction : 'ltr';
    }

    private function imageUrl(int $fileType, int $recordId, string $size = 'MEDIUM', int $langId = 0): string
    {
        $path = "/api/v1/image/show/{$fileType}/{$recordId}/{$size}";
        if ($langId > 0) {
            $path .= "/{$langId}";
        }

        return url($path).'?time='.time();
    }

    private function brandLogoUrl(): string
    {
        return url(Branding::LOGO_URL).'?time='.time();
    }

    /** @return array<string, string> */
    private function previewData(): array
    {
        return [
            '{learner_name}' => 'Martha Christopher',
            '{teacher_name}' => 'John Doe',
            '{course_name}' => '<span class="courseNameJs">English Language Learning - Beginners</span>',
            '{course_language}' => 'English',
            '{course_completed_date}' => date('Y-m-d'),
            '{certificate_number}' => 'h34uwh9e72w',
            '{course_duration}' => '15 minutes',
            '{quiz_name}' => '<span class="courseNameJs">English Language Learning - Beginners</span>',
            '{quiz_completed_date}' => date('Y-m-d'),
            '{quiz_duration}' => '15 minutes',
            '{quiz_score}' => '85%',
            '{course_score}' => '85%',
        ];
    }

    /**
     * @template T
     * @param callable(): T $callback
     * @return T|JsonResponse
     */
    private function guardEdit(Request $request, callable $callback): mixed
    {
        $admin = $request->user();
        if (! $admin || ! $this->privileges->canEdit($admin->admin_id, AdminPrivilegeService::SECTION_MANAGE_CERTIFICATES)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $callback();
    }
}
