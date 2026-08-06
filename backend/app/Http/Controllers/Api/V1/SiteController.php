<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Configuration;
use App\Models\LanguageLabel;
use App\Services\NavigationService;
use App\Services\ThemeService;
use App\Support\Branding;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SiteController extends Controller
{
    public function __construct(
        private NavigationService $navigation,
        private ThemeService $themeService
    ) {
    }

    public function bootstrap(Request $request): JsonResponse
    {
        $langId = $request->integer('lang_id', 1);

        $configKeys = [
            'CONF_WEBSITE_NAME_1',
            'CONF_CONTACT_EMAIL',
            'CONF_SITE_PHONE',
            'CONF_ADDRESS_1',
            'CONF_ACTIVE_THEME',
            'CONF_CURRENCY',
            'CONF_ENABLE_NEWSLETTER_SUBSCRIPTION',
            'CONF_FACEBOOK_URL',
            'CONF_TWITTER_URL',
            'CONF_INSTAGRAM_URL',
            'CONF_YOUTUBE_URL',
            'CONF_LINKEDIN_URL',
            'CONF_FACEBOOK_APP_ID',
            'CONF_FACEBOOK_APP_SECRET',
            'CONF_GOOGLE_CLIENT_JSON',
            'CONF_APPLE_CLIENT_ID',
            'CONF_TERMS_AND_CONDITIONS_PAGE',
            'CONF_PRIVACY_POLICY_PAGE',
            'CONF_ENABLE_COURSES',
            'CONF_GROUP_CLASSES_DISABLED',
        ];

        $config = Configuration::getMany($configKeys);
        $termsPageId = (int) ($config['CONF_TERMS_AND_CONDITIONS_PAGE'] ?? 2);
        $privacyPageId = (int) ($config['CONF_PRIVACY_POLICY_PAGE'] ?? 3);
        $themeId = (int) ($config['CONF_ACTIVE_THEME'] ?: 1);

        $themeRow = DB::table('tbl_themes')->where('theme_id', $themeId)->first(['theme_niche']);
        $social = array_filter([
            'facebook' => $config['CONF_FACEBOOK_URL'] ?? '',
            'twitter' => $config['CONF_TWITTER_URL'] ?? '',
            'instagram' => $config['CONF_INSTAGRAM_URL'] ?? '',
            'youtube' => $config['CONF_YOUTUBE_URL'] ?? '',
            'linkedin' => $config['CONF_LINKEDIN_URL'] ?? '',
        ]);

        $languages = DB::table('tbl_languages')
            ->where('language_active', 1)
            ->orderBy('language_id')
            ->get(['language_id as id', 'language_name as name', 'language_code as code']);

        $currencies = DB::table('tbl_currencies')
            ->where('currency_active', 1)
            ->orderBy('currency_order')
            ->get(['currency_id as id', 'currency_code as code', 'currency_symbol as symbol']);

        $demoLogin = $this->demoLoginPayload();
        $auth = $this->socialAuthPayload($config);

        return response()->json([
            'site' => [
                'name' => Branding::apply($config['CONF_WEBSITE_NAME_1'] ?? null) ?: Branding::NAME,
                'email' => $config['CONF_CONTACT_EMAIL'] ?? '',
                'phone' => $config['CONF_SITE_PHONE'] ?? '',
                'address' => $config['CONF_ADDRESS_1'] ?? '',
                'theme' => $themeRow?->theme_niche ?? 'onlinetutoring',
                'theme_id' => $themeId,
                'logo_url' => Branding::LOGO_URL,
                'currency_code' => $config['CONF_CURRENCY'] ?? 'USD',
                'auth' => $auth,
            ],
            'theme_css' => $this->themeService->cssRadiusVariables($themeId),
            'languages' => $languages,
            'currencies' => $currencies,
            'social' => $social,
            'navigation' => [
                'header' => $this->navigation->getHeader($langId),
                'footer' => $this->navigation->getFooter($langId),
            ],
            'labels' => LanguageLabel::forLanguage($langId),
            'legal_pages' => [
                'about' => 1,
                'terms' => $termsPageId > 0 ? $termsPageId : 2,
                'privacy' => $privacyPageId > 0 ? $privacyPageId : 3,
                'terms_url' => $this->navigation->cmsPagePath($termsPageId > 0 ? $termsPageId : 2),
                'privacy_url' => $this->navigation->cmsPagePath($privacyPageId > 0 ? $privacyPageId : 3),
            ],
            'demo_login' => $demoLogin,
            'modules' => [
                'courses' => ($config['CONF_ENABLE_COURSES'] ?? '1') === '1',
                'group_classes' => ($config['CONF_GROUP_CLASSES_DISABLED'] ?? '0') === '1',
            ],
            'search_filters' => $this->searchFilters($config),
            'locale' => [
                'lang_id' => $langId,
                'currency_id' => $request->integer('currency_id', 0) ?: null,
            ],
        ]);
    }

    /**
     * @param  array<string, string|null>  $config
     * @return array<int, string>
     */
    private function searchFilters(array $config): array
    {
        $filters = [
            0 => 'LBL_ALL',
            2 => 'LBL_COURSES',
            4 => 'LBL_LANGUAGES',
            3 => 'LBL_TEACHERS',
            1 => 'LBL_GROUP_CLASSES',
        ];

        if (($config['CONF_ENABLE_COURSES'] ?? '1') !== '1') {
            unset($filters[2]);
        }
        if (($config['CONF_GROUP_CLASSES_DISABLED'] ?? '0') !== '1') {
            unset($filters[1]);
        }

        return $filters;
    }

    /**
     * @param  array<string, string|null>  $config
     * @return array{legacy_origin: string, facebook_login: string|null, google_login: string|null, apple_login: string|null}
     */
    private function socialAuthPayload(array $config): array
    {
        $demo = (bool) config('demo.login_enabled');
        $legacyOrigin = (string) config('demo.legacy_origin', '');

        $facebook = ! empty($config['CONF_FACEBOOK_APP_ID']) && ! empty($config['CONF_FACEBOOK_APP_SECRET']);
        $google = ! empty($config['CONF_GOOGLE_CLIENT_JSON']);
        $apple = ! empty($config['CONF_APPLE_CLIENT_ID']);

        return [
            'legacy_origin' => $legacyOrigin,
            'facebook_login' => ($facebook || $demo) ? '/guest-user/facebook-login' : null,
            'google_login' => ($google || $demo) ? '/guest-user/google-login' : null,
            'apple_login' => $apple ? '/guest-user/apple-login' : null,
        ];
    }

    /**
     * w3mentors demo credentials (legacy sign-in form prefill on demo URLs).
     *
     * @return array<string, mixed>|null
     */
    private function demoLoginPayload(): ?array
    {
        if (! config('demo.login_enabled')) {
            return null;
        }

        $teacher = config('demo.teacher');
        $learner = config('demo.learner');

        return [
            'default_role' => 'teacher',
            'default' => [
                'email' => $teacher['email'],
                'password' => $teacher['password'],
            ],
            'accounts' => [
                [
                    'role' => 'teacher',
                    'label' => 'Teacher',
                    'email' => $teacher['email'],
                    'password' => $teacher['password'],
                ],
                [
                    'role' => 'learner',
                    'label' => 'Learner',
                    'email' => $learner['email'],
                    'password' => $learner['password'],
                ],
            ],
        ];
    }
}
