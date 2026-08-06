<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminConfigurationFormRegistry;
use App\Services\Admin\AdminConfigurationFormService;
use App\Services\Admin\AdminConfigurationMediaService;
use App\Services\Admin\AdminConfigurationService;
use App\Services\Admin\AdminPrivilegeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminConfigurationController extends Controller
{
    public function __construct(
        private AdminConfigurationService $configurations,
        private AdminConfigurationFormService $configurationForms,
        private AdminConfigurationMediaService $configurationMedia,
        private AdminPrivilegeService $privileges
    ) {
    }

    public function generalSettings(Request $request): JsonResponse
    {
        $adminId = $this->adminId($request);
        if (! $this->privileges->canView($adminId, AdminPrivilegeService::SECTION_GENERAL_SETTINGS)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $langId = max(1, (int) $request->query('lang_id', 1));

        return response()->json($this->configurations->generalSettingsForm($langId));
    }

    public function updateGeneralSettings(Request $request): JsonResponse
    {
        $adminId = $this->adminId($request);
        if (! $this->privileges->canEdit($adminId, AdminPrivilegeService::SECTION_GENERAL_SETTINGS)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'site_owner_email' => ['required', 'email', 'max:255'],
            'site_phone' => ['nullable', 'string', 'max:64'],
            'default_lang' => ['nullable', 'string', 'max:16'],
            'site_currency' => ['nullable', 'string', 'max:16'],
            'country' => ['nullable', 'string', 'max:16'],
            'frontend_time_format' => ['nullable', 'string', 'max:8'],
            'privacy_policy_page' => ['nullable', 'string', 'max:16'],
            'terms_and_conditions_page' => ['nullable', 'string', 'max:16'],
            'cookies_button_link' => ['nullable', 'string', 'max:16'],
            'enable_cookies' => ['nullable', 'boolean'],
        ]);

        try {
            $this->configurations->updateGeneralSettings($validated);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $langId = max(1, (int) $request->query('lang_id', 1));

        return response()->json([
            'message' => 'Settings saved.',
            'form' => $this->configurations->generalSettingsForm($langId),
        ]);
    }

    public function generalSettingsLangForm(Request $request, int $formLangId): JsonResponse
    {
        $adminId = $this->adminId($request);
        if (! $this->privileges->canView($adminId, AdminPrivilegeService::SECTION_GENERAL_SETTINGS)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            return response()->json($this->configurations->generalSettingsLangForm($formLangId));
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function updateGeneralSettingsLangForm(Request $request, int $formLangId): JsonResponse
    {
        $adminId = $this->adminId($request);
        if (! $this->privileges->canEdit($adminId, AdminPrivilegeService::SECTION_GENERAL_SETTINGS)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'website_name' => ['nullable', 'string', 'max:255'],
            'from_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:250'],
            'cookies_text' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->configurations->updateGeneralSettingsLangForm($formLangId, $validated);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Settings saved.',
            'form' => $this->configurations->generalSettingsLangForm($formLangId),
        ]);
    }

    public function thirdPartyApis(Request $request): JsonResponse
    {
        $adminId = $this->adminId($request);
        if (! $this->privileges->canView($adminId, AdminPrivilegeService::SECTION_GENERAL_SETTINGS)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($this->configurations->thirdPartyApiSettings());
    }

    public function updateThirdPartyApis(Request $request): JsonResponse
    {
        $adminId = $this->adminId($request);
        if (! $this->privileges->canEdit($adminId, AdminPrivilegeService::SECTION_GENERAL_SETTINGS)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'facebook_app_id' => ['nullable', 'string', 'max:255'],
            'facebook_app_secret' => ['nullable', 'string', 'max:255'],
            'apple_client_id' => ['nullable', 'string', 'max:255'],
            'mailchimp_key' => ['nullable', 'string', 'max:255'],
            'mailchimp_list_id' => ['nullable', 'string', 'max:255'],
            'mailchimp_server_prefix' => ['nullable', 'string', 'max:64'],
            'microsoft_translator_subscription_key' => ['nullable', 'string', 'max:255'],
            'microsoft_translator_subscription_region' => ['nullable', 'string', 'max:64'],
            'analytics_property_id' => ['nullable', 'string', 'max:64'],
            'google_analytics_client_json' => ['nullable', 'string'],
            'recaptcha_sitekey' => ['nullable', 'string', 'max:255'],
            'recaptcha_secretkey' => ['nullable', 'string', 'max:255'],
            'google_client_json' => ['nullable', 'string'],
            'google_api_key' => ['nullable', 'string', 'max:255'],
            'firebase_service_account_json' => ['nullable', 'string'],
            'share_this_property_id' => ['nullable', 'string', 'max:255'],
            'live_chat_code' => ['nullable', 'string'],
            'enable_live_chat' => ['nullable', 'boolean'],
        ]);

        try {
            $this->configurations->updateThirdPartyApiSettings($validated);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Settings saved.',
            'settings' => $this->configurations->thirdPartyApiSettings(),
        ]);
    }

    public function configurationForm(Request $request, int $formType): JsonResponse
    {
        $adminId = $this->adminId($request);
        if (! $this->privileges->canView($adminId, AdminPrivilegeService::SECTION_GENERAL_SETTINGS)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (! AdminConfigurationFormRegistry::supportsDynamicForm($formType)
            && ! ($formType === AdminConfigurationFormRegistry::FORM_MAINTAINANCE_AND_SSL && (int) $request->query('lang_id', 0) > 0)) {
            return response()->json(['message' => 'Unsupported form type.'], 404);
        }

        $langId = max(0, (int) $request->query('lang_id', 0));
        $siteLangId = max(1, (int) $request->query('site_lang_id', 1));

        try {
            return response()->json($this->configurationForms->form($formType, $langId, $siteLangId));
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function updateConfigurationForm(Request $request, int $formType): JsonResponse
    {
        $adminId = $this->adminId($request);
        if (! $this->privileges->canEdit($adminId, AdminPrivilegeService::SECTION_GENERAL_SETTINGS)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (! AdminConfigurationFormRegistry::supportsDynamicForm($formType)
            && ! ($formType === AdminConfigurationFormRegistry::FORM_MAINTAINANCE_AND_SSL && (int) ($request->input('lang_id') ?? 0) > 0)) {
            return response()->json(['message' => 'Unsupported form type.'], 404);
        }

        $validated = $request->validate([
            'values' => ['required', 'array'],
            'lang_id' => ['nullable', 'integer', 'min:0'],
        ]);

        $langId = max(0, (int) ($validated['lang_id'] ?? 0));
        $siteLangId = max(1, (int) $request->query('site_lang_id', 1));

        try {
            $this->configurationForms->update($formType, $validated['values'], $langId);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Settings saved.',
            'form' => $this->configurationForms->form($formType, $langId, $siteLangId),
        ]);
    }

    public function configurationMedia(Request $request): JsonResponse
    {
        $adminId = $this->adminId($request);
        if (! $this->privileges->canView($adminId, AdminPrivilegeService::SECTION_GENERAL_SETTINGS)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $langId = max(1, (int) $request->query('lang_id', 1));
        $coursesEnabled = (string) $this->configurations->get('CONF_ENABLE_COURSES', '0') === '1';

        return response()->json($this->configurationMedia->mediaForm($langId, $coursesEnabled));
    }

    public function uploadConfigurationMedia(Request $request): JsonResponse
    {
        $adminId = $this->adminId($request);
        if (! $this->privileges->canEdit($adminId, AdminPrivilegeService::SECTION_GENERAL_SETTINGS)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'file_type' => ['required', 'integer'],
            'lang_id' => ['required', 'integer', 'min:0'],
            'file' => ['required', 'file', 'max:10240'],
        ]);

        $result = $this->configurationMedia->upload(
            (int) $validated['file_type'],
            (int) $validated['lang_id'],
            $request->file('file'),
        );

        if (! $result['ok']) {
            return response()->json(['message' => $result['message'] ?? 'Upload failed.'], 422);
        }

        return response()->json([
            'message' => 'File uploaded successfully.',
            'slot' => $result['slot'] ?? null,
        ]);
    }

    public function removeConfigurationMedia(Request $request): JsonResponse
    {
        $adminId = $this->adminId($request);
        if (! $this->privileges->canEdit($adminId, AdminPrivilegeService::SECTION_GENERAL_SETTINGS)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'file_type' => ['required', 'integer'],
            'lang_id' => ['required', 'integer', 'min:0'],
        ]);

        $result = $this->configurationMedia->remove((int) $validated['file_type'], (int) $validated['lang_id']);
        if (! $result['ok']) {
            return response()->json(['message' => $result['message'] ?? 'Delete failed.'], 422);
        }

        return response()->json(['message' => 'File deleted successfully.']);
    }

    public function uploadPwaIcon(Request $request): JsonResponse
    {
        $adminId = $this->adminId($request);
        if (! $this->privileges->canEdit($adminId, AdminPrivilegeService::SECTION_GENERAL_SETTINGS)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:2048'],
        ]);

        $result = $this->configurationMedia->uploadPwaIcon($request->file('file'));
        if (! $result['ok']) {
            return response()->json(['message' => $result['message'] ?? 'Upload failed.'], 422);
        }

        return response()->json([
            'message' => 'PWA icon uploaded successfully.',
            'icon_url' => $result['icon_url'] ?? null,
        ]);
    }

    public function testConfigurationEmail(Request $request): JsonResponse
    {
        $adminId = $this->adminId($request);
        if (! $this->privileges->canEdit($adminId, AdminPrivilegeService::SECTION_GENERAL_SETTINGS)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $langId = max(1, (int) $request->query('site_lang_id', 1));

        try {
            $message = $this->configurationForms->sendTestEmail($langId);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => $message]);
    }

    public function googleAnalytics(Request $request): JsonResponse
    {
        $adminId = $this->adminId($request);
        if (! $this->privileges->canView($adminId, AdminPrivilegeService::SECTION_GENERAL_SETTINGS)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($this->configurations->googleAnalyticsSettings());
    }

    public function updateGoogleAnalytics(Request $request): JsonResponse
    {
        $adminId = $this->adminId($request);
        if (! $this->privileges->canEdit($adminId, AdminPrivilegeService::SECTION_GENERAL_SETTINGS)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'property_id' => ['required', 'string', 'max:64'],
            'client_json' => ['nullable', 'string'],
        ]);

        try {
            $this->configurations->updateGoogleAnalyticsSettings($validated);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Google Analytics settings saved.',
            'settings' => $this->configurations->googleAnalyticsSettings(),
        ]);
    }

    private function adminId(Request $request): int
    {
        $admin = $request->user();

        return (int) ($admin->admin_id ?? 0);
    }
}
