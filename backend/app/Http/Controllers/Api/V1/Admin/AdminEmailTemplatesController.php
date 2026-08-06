<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminPrivilegeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminEmailTemplatesController extends Controller
{
    public function __construct(private AdminPrivilegeService $privileges)
    {
    }

    public function langForm(Request $request, string $code, int $langId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($code, $langId) {
            $row = $this->template($code, $langId) ?? $this->template($code);
            if (! $row) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['data' => [
                'etpl_code' => $code,
                'etpl_lang_id' => $langId,
                'etpl_name' => (string) ($row->etpl_name ?? ''),
                'etpl_subject' => (string) ($row->etpl_subject ?? ''),
                'etpl_body' => (string) ($row->etpl_body ?? ''),
                'etpl_vars' => (string) ($row->etpl_vars ?? ''),
                'etpl_status' => (int) ($row->etpl_status ?? 1),
                'site_languages' => $this->siteLanguages(),
                'layout_direction' => $this->layoutDirection($langId),
                'show_auto_translate' => count($this->siteLanguages()) > 1 && $langId === $this->defaultLangId(),
            ]]);
        });
    }

    public function storeLang(Request $request, string $code, int $langId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $code, $langId) {
            $existing = $this->template($code, $langId) ?? $this->template($code);
            if (! $existing) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            $name = trim((string) $request->input('etpl_name', ''));
            $subject = trim((string) $request->input('etpl_subject', ''));
            $body = trim((string) $request->input('etpl_body', ''));
            $status = (int) $request->input('etpl_status', $existing->etpl_status ?? 1) === 1 ? 1 : 0;
            if ($name === '' || $subject === '' || $body === '') {
                return response()->json(['message' => 'Invalid request'], 422);
            }

            DB::table('tbl_email_templates')->updateOrInsert(
                ['etpl_code' => $code, 'etpl_lang_id' => $langId],
                [
                    'etpl_code' => $code,
                    'etpl_lang_id' => $langId,
                    'etpl_name' => $name,
                    'etpl_subject' => $subject,
                    'etpl_body' => $body,
                    'etpl_vars' => (string) ($existing->etpl_vars ?? ''),
                    'etpl_status' => $status,
                    'etpl_quick_send' => (int) ($existing->etpl_quick_send ?? 1),
                ],
            );

            if ($request->boolean('update_langs_data') && $langId === $this->defaultLangId()) {
                foreach ($this->siteLanguages() as $language) {
                    if ($language['id'] === $langId) {
                        continue;
                    }
                    $other = $this->template($code, $language['id']) ?? $existing;
                    DB::table('tbl_email_templates')->updateOrInsert(
                        ['etpl_code' => $code, 'etpl_lang_id' => $language['id']],
                        [
                            'etpl_code' => $code,
                            'etpl_lang_id' => $language['id'],
                            'etpl_name' => $name,
                            'etpl_subject' => $subject,
                            'etpl_body' => $body,
                            'etpl_vars' => (string) ($other->etpl_vars ?? $existing->etpl_vars ?? ''),
                            'etpl_status' => $status,
                            'etpl_quick_send' => (int) ($other->etpl_quick_send ?? $existing->etpl_quick_send ?? 1),
                        ],
                    );
                }
            }

            return response()->json(['message' => 'Setup successful']);
        });
    }

    public function updateStatus(Request $request, string $code): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $code) {
            if (! $this->template($code)) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            DB::table('tbl_email_templates')->where('etpl_code', $code)->update([
                'etpl_status' => $request->boolean('active') ? 1 : 0,
            ]);

            return response()->json(['message' => 'Action performed successfully']);
        });
    }

    public function preview(Request $request, string $code, int $langId): Response|JsonResponse
    {
        $admin = $request->user();
        if (! $admin || ! $this->privileges->canView($admin->admin_id, AdminPrivilegeService::SECTION_EMAIL_TEMPLATES)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $template = $this->template($code, $langId);
        if (! $template) {
            return response()->json(['message' => 'Email content not available for this language'], 404);
        }
        $layout = $this->template('emails_header_footer_layout', $langId) ?? $this->template('emails_header_footer_layout', $this->defaultLangId());
        $body = (string) $template->etpl_body;
        if ($layout) {
            $body = str_replace('{email_body}', $body, (string) $layout->etpl_body);
        }
        $body = strtr($body, $this->previewReplacements($request, $langId));

        return response($body, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    public function export(Request $request): StreamedResponse|JsonResponse
    {
        $admin = $request->user();
        if (! $admin || ! $this->privileges->canView($admin->admin_id, AdminPrivilegeService::SECTION_EMAIL_TEMPLATES)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $langId = (int) $request->query('lang_id', $this->defaultLangId());
        $query = DB::table('tbl_email_templates')
            ->where('etpl_lang_id', $langId)
            ->select(['etpl_name', 'etpl_subject', 'etpl_status'])
            ->orderByDesc('etpl_status')
            ->orderBy('etpl_name');
        $keyword = trim((string) $request->query('keyword', ''));
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('etpl_code', 'like', "%{$keyword}%")
                    ->orWhere('etpl_name', 'like', "%{$keyword}%")
                    ->orWhere('etpl_subject', 'like', "%{$keyword}%");
            });
        }
        $rows = $query->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Name', 'Subject', 'Status']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->etpl_name,
                    $row->etpl_subject,
                    ((int) $row->etpl_status === 1) ? 'Active' : 'Inactive',
                ]);
            }
            fclose($out);
        }, 'email-templates.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function template(string $code, int $langId = 0): ?object
    {
        $query = DB::table('tbl_email_templates')->where('etpl_code', $code);
        if ($langId > 0) {
            $query->where('etpl_lang_id', $langId);
        }

        return $query->orderBy('etpl_lang_id')->first();
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

    /** @return array<string, string> */
    private function previewReplacements(Request $request, int $langId): array
    {
        $config = DB::table('tbl_configurations')
            ->whereIn('conf_name', [
                'CONF_ACTIVE_THEME',
                'CONF_CONTACT_EMAIL',
                'CONF_DEFAULT_LANG',
                'CONF_FROM_EMAIL',
                "CONF_WEBSITE_NAME_{$langId}",
            ])
            ->pluck('conf_val', 'conf_name')
            ->all();
        $theme = DB::table('tbl_themes')
            ->where('theme_id', (int) ($config['CONF_ACTIVE_THEME'] ?? 0))
            ->first(['theme_primary_color', 'theme_secondary_color', 'theme_secondary_inverse_color']);
        $direction = $this->layoutDirection($langId);
        $websiteName = (string) ($config["CONF_WEBSITE_NAME_{$langId}"] ?? '');
        $email = (string) ($config['CONF_FROM_EMAIL'] ?? $config['CONF_CONTACT_EMAIL'] ?? '');

        return [
            '{website_url}' => url('/'),
            '{Company_Logo}' => $this->logoHtml($websiteName),
            '{website_name}' => $websiteName,
            '{contact_us_url}' => url('/contact'),
            '{notifcation_email}' => $email,
            '{notification_email}' => $email,
            '{social_media_icons}' => $this->socialMediaIcons(),
            '{current_date}' => date('M d, Y'),
            '{current_year}' => date('Y'),
            '{primary-color}' => '#' . (string) ($theme->theme_primary_color ?? '000000'),
            '{secondary-color}' => '#' . (string) ($theme->theme_secondary_color ?? '000000'),
            '{secondary-inverse-color}' => '#' . (string) ($theme->theme_secondary_inverse_color ?? 'ffffff'),
            '{dir}' => $direction,
            '{stylecls}' => $direction === 'rtl' ? 'left' : 'right',
        ];
    }

    private function logoHtml(string $websiteName): string
    {
        return '<img alt="' . e($websiteName) . '" src="' . e(url('/images/logo.svg')) . '" />';
    }

    private function socialMediaIcons(): string
    {
        return DB::table('tbl_social_platforms')
            ->where('splatform_active', 1)
            ->orderBy('splatform_order')
            ->orderBy('splatform_id')
            ->get(['splatform_identifier', 'splatform_url'])
            ->map(function ($row) {
                $name = strtolower((string) $row->splatform_identifier);
                $icon = $name === 'x' ? 'twitter' : $name;
                $url = (string) $row->splatform_url;
                if ($url === '') {
                    return '';
                }

                return '<a style="display:inline-block;vertical-align:top; width:35px; height:35px; margin:0 0 0 5px; border-radius:100%;" href="' . e($url) . '" target="_blank">'
                    . '<img src="' . e(url("/images/{$icon}.png")) . '" style="width: 25px;height: 25px; margin:5px auto 0; display:block;" /></a>';
            })
            ->implode('');
    }

    private function guardEdit(Request $request, callable $callback): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $admin || ! $this->privileges->canEdit($admin->admin_id, AdminPrivilegeService::SECTION_EMAIL_TEMPLATES)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $callback();
    }
}
