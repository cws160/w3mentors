<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class AdminConfigurationFormService
{
  private const YES = 1;

  private const NO = 0;

  private const MANAGE_PRICE_ADMIN = 1;

  private const MANAGE_PRICE_TEACHER = 0;

  private const VIDEO_CIPHER = 'VideoCipher';

  private const MUX = 'Mux';

  public function __construct(
    private AdminConfigurationService $configurations,
    private AdminConfigurationMediaService $media,
  ) {
  }

  /** @return array<string, mixed> */
  public function form(int $formType, int $langId, int $siteLangId): array
  {
    if ($formType === AdminConfigurationFormRegistry::FORM_MAINTAINANCE_AND_SSL && $langId > 0) {
      return $this->buildFilledForm(AdminConfigurationFormRegistry::maintenanceLangSchema($langId), $langId, $siteLangId);
    }

    $schema = AdminConfigurationFormRegistry::schema($formType);
    if ($schema === null) {
      throw new \InvalidArgumentException('Unsupported configuration form.');
    }

    return $this->buildFilledForm($schema, $langId, $siteLangId);
  }

  /** @param  array<string, mixed>  $values */
  public function update(int $formType, array $values, int $langId): void
  {
    if ($formType === AdminConfigurationFormRegistry::FORM_MAINTAINANCE_AND_SSL && $langId > 0) {
      $key = "CONF_MAINTENANCE_TEXT_{$langId}";
      $text = trim((string) ($values[$key] ?? ''));
      if ($text === '') {
        throw new \InvalidArgumentException('Maintenance text is required.');
      }
      $this->configurations->set($key, $text);

      return;
    }

    $schema = AdminConfigurationFormRegistry::schema($formType);
    if ($schema === null) {
      throw new \InvalidArgumentException('Unsupported configuration form.');
    }

    if ($this->isDemoSite() && $this->isDemoRestrictedForm($formType)) {
      throw new \InvalidArgumentException('Settings not allowed to be modified on demo version.');
    }

    $payload = $this->normalizePayload($formType, $schema, $values);

    if ($formType === AdminConfigurationFormRegistry::FORM_COMMON_SETTINGS) {
      $payload = $this->applyCommonSettingsSideEffects($payload);
    }

    if ($formType === AdminConfigurationFormRegistry::FORM_REFERRAL_SETTINGS) {
      $payload = $this->filterReferralPayload($payload);
    }

    if ($formType === AdminConfigurationFormRegistry::FORM_PWA_SETTINGS) {
      $payload = $this->buildPwaPayload($values);
    }

    if ($formType === AdminConfigurationFormRegistry::FORM_DASHBOARD_COURSES) {
      $this->validateMuxResolution($payload);
    }

    if ($formType === AdminConfigurationFormRegistry::FORM_EMAIL_AND_SMTPS) {
      $payload = $this->preserveSmtpPassword($payload);
    }

    $this->configurations->setMany($payload);
  }

  public function sendTestEmail(int $langId): string
  {
    if (! config('app.allow_emails', false)) {
      throw new \InvalidArgumentException('Email sending is disabled in server configuration.');
    }

    if ((string) $this->configurations->get('CONF_SEND_EMAIL', '0') !== '1') {
      throw new \InvalidArgumentException('Select Send Email as Yes to test email.');
    }

    $ownerEmail = trim((string) $this->configurations->get('CONF_SITE_OWNER_EMAIL', ''));
    if ($ownerEmail === '' || ! filter_var($ownerEmail, FILTER_VALIDATE_EMAIL)) {
      throw new \InvalidArgumentException('Site owner email is not configured.');
    }

    $template = DB::table('tbl_email_templates')
      ->where('etpl_code', 'test_email')
      ->where('etpl_lang_id', $langId)
      ->where('etpl_status', 1)
      ->first(['etpl_subject', 'etpl_body']);

    if ($template === null) {
      throw new \InvalidArgumentException('Email template not found.');
    }

    $siteName = (string) $this->configurations->get("CONF_WEBSITE_NAME_{$langId}", 'w3mentors');
    $fromEmail = (string) $this->configurations->get('CONF_FROM_EMAIL', config('mail.from.address', 'noreply@localhost'));
    $fromName = (string) $this->configurations->get("CONF_FROM_NAME_{$langId}", $siteName);

    $subject = str_replace('{primary-color}', '#0c9331', (string) $template->etpl_subject);
    $body = str_replace('{primary-color}', '#0c9331', (string) $template->etpl_body);

    try {
      Mail::html($body, function ($message) use ($ownerEmail, $subject, $fromEmail, $fromName) {
        $message->to($ownerEmail)->subject($subject);
        if ($fromEmail !== '') {
          $message->from($fromEmail, $fromName !== '' ? $fromName : null);
        }
      });
    } catch (\Throwable $e) {
      throw new \InvalidArgumentException($e->getMessage() !== '' ? $e->getMessage() : 'Unable to send test email.');
    }

    return 'Mail sent to - '.$ownerEmail;
  }

  /** @param  array<string, mixed>  $schema */
  private function buildFilledForm(array $schema, int $langId, int $siteLangId): array
  {
    $formType = (int) $schema['form_type'];
    $configKeys = AdminConfigurationFormRegistry::configKeys($formType);
    if ($formType === AdminConfigurationFormRegistry::FORM_PWA_SETTINGS) {
      $configKeys[] = 'CONF_PWA_SETTINGS';
    }
    if ($formType === AdminConfigurationFormRegistry::FORM_DASHBOARD_COURSES) {
      $videoTool = (string) $this->configurations->get('CONF_ACTIVE_VIDEO_TOOL', '');
      if ($videoTool === self::VIDEO_CIPHER) {
        $configKeys = array_merge($configKeys, ['CONF_VIDEO_CIPHER_API_KEY', 'CONF_VIDEO_CIPHER_FOLDER_ID']);
      } elseif ($videoTool === self::MUX) {
        $configKeys = array_merge($configKeys, [
          'CONF_MUX_ACCESS_TOKEN_ID',
          'CONF_MUX_SECRET_KEY',
          'CONF_MUX_ENCODING_TIER',
          'CONF_MUX_RESOLUTION',
          'CONF_MUX_WEBHOOK_SECRET_KEY',
        ]);
      }
    }

    $rawValues = $this->configurations->getMany(array_values(array_unique($configKeys)));
    $meta = $this->buildMeta($siteLangId);

    $sections = [];
    foreach ($schema['sections'] as $section) {
      $sections[] = [
        'heading_key' => $section['heading_key'],
        'heading_fallback' => $section['heading_fallback'],
        'fields' => array_map(
          fn (array $field) => $this->hydrateField($field, $rawValues, $meta),
          $section['fields'],
        ),
      ];
    }

    if ($formType === AdminConfigurationFormRegistry::FORM_DASHBOARD_COURSES && ! empty($schema['conditional_sections'])) {
      $videoTool = (string) ($rawValues['CONF_ACTIVE_VIDEO_TOOL'] ?? $this->configurations->get('CONF_ACTIVE_VIDEO_TOOL', ''));
      $conditionalKey = $videoTool === self::VIDEO_CIPHER ? 'video_cipher' : ($videoTool === self::MUX ? 'mux' : null);
      if ($conditionalKey && isset($schema['conditional_sections'][$conditionalKey])) {
        $conditional = $schema['conditional_sections'][$conditionalKey];
        $sections[] = [
          'heading_key' => $conditional['heading_key'],
          'heading_fallback' => $conditional['heading_fallback'],
          'fields' => array_map(
            fn (array $field) => $this->hydrateField($field, $rawValues, $meta),
            $conditional['fields'],
          ),
        ];
      }
    }

    if ($this->isDemoSite() && $this->isDemoRestrictedForm($formType)) {
      $sections = $this->applyDemoReadonlyMask($formType, $sections);
      $meta['demo_readonly'] = true;
    }

    $response = [
      'form_type' => $formType,
      'has_lang_tabs' => (bool) ($schema['has_lang_tabs'] ?? false),
      'lang_tab_mode' => $schema['lang_tab_mode'] ?? null,
      'lang_id' => $langId > 0 ? $langId : null,
      'layout_direction' => $langId > 0 ? $this->layoutDirection($langId) : 'ltr',
      'sections' => $sections,
      'meta' => $meta,
    ];

    if ($formType === AdminConfigurationFormRegistry::FORM_PWA_SETTINGS) {
      $response['pwa_icon_url'] = $this->media->pwaIconUrl();
    }

    return $response;
  }

  /** @param  array<string, mixed>  $field */
  /** @param  array<string, mixed>  $rawValues */
  /** @param  array<string, mixed>  $meta */
  private function hydrateField(array $field, array $rawValues, array $meta): array
  {
    $name = (string) $field['name'];
    $type = (string) $field['type'];
    $value = $this->readFieldValue($name, $type, $rawValues);

    $labelFallback = (string) $field['label_fallback'];
    if (($field['suffix'] ?? null) === 'currency_code' && ! empty($meta['currency_code'])) {
      $labelFallback .= ' ['.$meta['currency_code'].']';
    }

    $disabled = false;
    if (($field['disabled_when'] ?? null) === 'subscription_enabled' && ! empty($meta['subscription_enabled'])) {
      $disabled = true;
    }

    $optionsKey = (string) ($field['options'] ?? '');
    $options = $optionsKey !== '' ? $this->resolveOptions($optionsKey, $rawValues) : [];

    $helpKey = (string) ($field['help_key'] ?? '');
    $helpFallback = (string) ($field['help_fallback'] ?? '');
    if ($helpFallback === '' && $helpKey !== '') {
      $helpFallback = AdminConfigurationFormRegistry::helpFallback($helpKey);
    }

    $extraHelp = [];
    foreach (($field['extra_help'] ?? []) as $note) {
      $noteKey = (string) ($note['help_key'] ?? '');
      $extraHelp[] = [
        'help_key' => $noteKey,
        'help_fallback' => (string) ($note['help_fallback'] ?? AdminConfigurationFormRegistry::helpFallback($noteKey)),
        'variant' => $note['variant'] ?? 'default',
        'leading_break' => ! empty($note['leading_break']),
      ];
    }

    $passwordConfigured = false;
    if ($name === 'CONF_SMTP_PASSWORD' && trim((string) ($rawValues[$name] ?? '')) !== '') {
      $passwordConfigured = true;
      $value = '';
    }

    return [
      ...$field,
      'label_fallback' => $labelFallback,
      'help_fallback' => $helpFallback !== '' ? $helpFallback : null,
      'extra_help' => $extraHelp !== [] ? $extraHelp : null,
      'value' => $value,
      'options' => $options,
      'disabled' => $disabled,
      'password_configured' => $passwordConfigured ? true : null,
    ];
  }

  /** @param  array<string, mixed>  $rawValues */
  private function readFieldValue(string $name, string $type, array $rawValues): mixed
  {
    if (str_starts_with($name, 'pwa_settings.')) {
      $pwa = $this->decodePwaSettings((string) ($rawValues['CONF_PWA_SETTINGS'] ?? ''));

      return (string) ($pwa[str_replace('pwa_settings.', '', $name)] ?? '');
    }

    $stored = $rawValues[$name] ?? $this->configurations->get($name, '');

    if ($type === 'checkbox') {
      return (string) $stored === '1' || (string) $stored === (string) self::YES;
    }

    if ($type === 'checkboxes') {
      $raw = trim((string) $stored);
      if ($raw === '') {
        return [];
      }

      return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }

    return (string) $stored;
  }

  /** @return array<string, mixed> */
  private function buildMeta(int $siteLangId): array
  {
    $currencyId = (int) $this->configurations->get('CONF_SITE_CURRENCY', 0);
    $currencyCode = (string) DB::table('tbl_currencies')->where('currency_id', $currencyId)->value('currency_code');

    return [
      'currency_code' => $currencyCode !== '' ? $currencyCode : 'USD',
      'subscription_enabled' => (string) $this->configurations->get('CONF_ENABLE_SUBSCRIPTION_PLAN', '0') === '1',
      'site_owner_email' => (string) $this->configurations->get('CONF_SITE_OWNER_EMAIL', ''),
      'courses_enabled' => (string) $this->configurations->get('CONF_ENABLE_COURSES', '0') === '1',
      'group_classes_enabled' => (string) $this->configurations->get('CONF_ENABLE_GROUP_CLASSES', '1') === '1',
      'active_video_tool' => (string) $this->configurations->get('CONF_ACTIVE_VIDEO_TOOL', ''),
      'demo_readonly' => false,
    ];
  }

  private function isDemoSite(): bool
  {
    if (filter_var(config('app.demo_site', false), FILTER_VALIDATE_BOOL)) {
      return true;
    }

    $host = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));

    return $host === 'elearning.w3mentors.com';
  }

  private function isDemoRestrictedForm(int $formType): bool
  {
    return in_array($formType, [
      AdminConfigurationFormRegistry::FORM_THIRD_PARTY_APIS,
      AdminConfigurationFormRegistry::FORM_SEO_AND_GOOGLE_TAGS,
      AdminConfigurationFormRegistry::FORM_DASHBOARD_COURSES,
    ], true);
  }

  /**
   * @param  list<array<string, mixed>>  $sections
   * @return list<array<string, mixed>>
   */
  private function applyDemoReadonlyMask(int $formType, array $sections): array
  {
    $skipDisable = $formType === AdminConfigurationFormRegistry::FORM_SEO_AND_GOOGLE_TAGS
      ? ['CONF_SITE_TRACKER_CODE']
      : [];

    foreach ($sections as $sectionIndex => $section) {
      foreach ($section['fields'] as $fieldIndex => $field) {
        $name = (string) ($field['name'] ?? '');
        $type = (string) ($field['type'] ?? '');

        if (! in_array($name, $skipDisable, true)) {
          $sections[$sectionIndex]['fields'][$fieldIndex]['disabled'] = true;
        }

        $shouldMask = $type === 'textarea'
          || ($type === 'text' && ! in_array($name, $skipDisable, true))
          || $type === 'password';

        if ($shouldMask) {
          $sections[$sectionIndex]['fields'][$fieldIndex]['value'] = '***********';
        }
      }
    }

    return $sections;
  }

  /** @param  array<string, mixed>  $schema */
  /** @param  array<string, mixed>  $values */
  /** @return array<string, string> */
  private function normalizePayload(int $formType, array $schema, array $values): array
  {
    $payload = [];
    $fields = $this->allSchemaFields($schema, $formType);

    foreach ($fields as $field) {
      $name = (string) $field['name'];
      if (str_starts_with($name, 'pwa_settings.')) {
        continue;
      }

      if (! array_key_exists($name, $values)) {
        if (($field['type'] ?? '') === 'checkbox') {
          $payload[$name] = '0';
        }
        continue;
      }

      $value = $values[$name];
      $type = (string) ($field['type'] ?? 'text');

      if ($type === 'checkbox') {
        $payload[$name] = ! empty($value) ? '1' : '0';
        continue;
      }

      if ($type === 'checkboxes') {
        $selected = is_array($value) ? $value : [];
        $payload[$name] = implode(',', array_map('strval', $selected));
        continue;
      }

      $trimmed = trim((string) $value);
      if ($trimmed === '***********') {
        continue;
      }

      $payload[$name] = $trimmed;
    }

    return $payload;
  }

  /** @param  array<string, mixed>  $schema */
  /** @return list<array<string, mixed>> */
  private function allSchemaFields(array $schema, int $formType): array
  {
    $fields = [];
    foreach ($schema['sections'] as $section) {
      foreach ($section['fields'] as $field) {
        $fields[] = $field;
      }
    }

    if ($formType === AdminConfigurationFormRegistry::FORM_DASHBOARD_COURSES && ! empty($schema['conditional_sections'])) {
      foreach ($schema['conditional_sections'] as $conditional) {
        foreach ($conditional['fields'] as $field) {
          $fields[] = $field;
        }
      }
    }

    return $fields;
  }

  /** @param  array<string, string>  $payload */
  /** @return array<string, string> */
  private function preserveSmtpPassword(array $payload): array
  {
    $submitted = trim((string) ($payload['CONF_SMTP_PASSWORD'] ?? ''));
    if ($submitted === '') {
      unset($payload['CONF_SMTP_PASSWORD']);
    }

    return $payload;
  }

  /** @param  array<string, string>  $payload */
  /** @return array<string, string> */
  private function applyCommonSettingsSideEffects(array $payload): array
  {
    if (! empty($payload['CONF_EMAIL_VERIFICATION_REGISTRATION']) || ! empty($payload['CONF_ADMIN_APPROVAL_REGISTRATION'])) {
      $payload['CONF_AUTO_LOGIN_REGISTRATION'] = '0';
    }

    if (! empty($payload['CONF_ENABLE_SUBSCRIPTION_PLAN'])) {
      $payload['CONF_MANAGE_PRICES'] = (string) self::MANAGE_PRICE_ADMIN;
    }

    if ((string) $this->configurations->get('CONF_ENABLE_SUBSCRIPTION_PLAN', '0') === '1') {
      unset($payload['CONF_ENABLE_SUBSCRIPTION_PLAN'], $payload['CONF_MANAGE_PRICES']);
    }

    return $payload;
  }

  /** @param  array<string, string>  $payload */
  /** @return array<string, string> */
  private function filterReferralPayload(array $payload): array
  {
    $allowed = [
      'CONF_ENABLE_REFERRAL_REWARDS',
      'CONF_REWARD_POINT_MULTIPLIER',
      'CONF_REWARD_POINT_MINIMUM_USE',
      'CONF_REFERRER_REGISTER_REWARDS',
      'CONF_REFERENT_REGISTER_REWARDS',
      'CONF_REFERRER_PURCHASE_REWARDS',
      'CONF_REFERENT_PURCHASE_REWARDS',
    ];

    return array_intersect_key($payload, array_flip($allowed));
  }

  /** @param  array<string, mixed>  $values */
  /** @return array<string, string> */
  private function buildPwaPayload(array $values): array
  {
    $pwa = [];
    foreach ($values as $key => $value) {
      if (str_starts_with((string) $key, 'pwa_settings.')) {
        $pwa[str_replace('pwa_settings.', '', (string) $key)] = trim((string) $value);
      }
    }

    return [
      'CONF_ENABLE_PWA' => ! empty($values['CONF_ENABLE_PWA']) ? '1' : '0',
      'CONF_PWA_SETTINGS' => json_encode($pwa),
    ];
  }

  /** @param  array<string, string>  $payload */
  private function validateMuxResolution(array $payload): void
  {
    if ((string) $this->configurations->get('CONF_ACTIVE_VIDEO_TOOL', '') !== self::MUX) {
      return;
    }

    $tier = (string) ($payload['CONF_MUX_ENCODING_TIER'] ?? $this->configurations->get('CONF_MUX_ENCODING_TIER', 'baseline'));
    $resolution = (string) ($payload['CONF_MUX_RESOLUTION'] ?? '');
    $allowed = array_keys($this->muxResolutions($tier));
    if ($resolution !== '' && ! in_array($resolution, $allowed, true)) {
      throw new \InvalidArgumentException('Resolution is not allowed with the selected encoding type.');
    }
  }

  /** @return array<string, string> */
  private function resolveOptions(string $key, array $rawValues): array
  {
    return match ($key) {
      'yes_no' => [
        ['value' => (string) self::YES, 'label_key' => 'LBL_YES', 'label_fallback' => 'Yes'],
        ['value' => (string) self::NO, 'label_key' => 'LBL_NO', 'label_fallback' => 'No'],
      ],
      'smtp_secure' => [
        ['value' => 'tls', 'label_key' => 'LBL_tls', 'label_fallback' => 'TLS'],
        ['value' => 'ssl', 'label_key' => 'LBL_ssl', 'label_fallback' => 'SSL'],
      ],
      'manage_prices' => [
        ['value' => (string) self::MANAGE_PRICE_TEACHER, 'label_key' => 'LBL_TEACHER_MANAGEABLE_PRICING', 'label_fallback' => 'Teacher manageable pricing'],
        ['value' => (string) self::MANAGE_PRICE_ADMIN, 'label_key' => 'LBL_ADMIN_MANAGEABLE_PRICING', 'label_fallback' => 'Admin manageable pricing'],
      ],
      'review_status' => [
        ['value' => '0', 'label_key' => 'STATUS_PENDING', 'label_fallback' => 'Pending'],
        ['value' => '1', 'label_key' => 'STATUS_APPROVED', 'label_fallback' => 'Approved'],
      ],
      'course_refund_status' => [
        ['value' => '0', 'label_key' => 'LBL_REFUND_PENDING', 'label_fallback' => 'Refund pending'],
        ['value' => '1', 'label_key' => 'LBL_REFUND_APPROVED', 'label_fallback' => 'Refund approved'],
      ],
      'security_settings' => [
        ['value' => '0', 'label_key' => 'LBL_MODERATE', 'label_fallback' => 'Moderate'],
        ['value' => '1', 'label_key' => 'LBL_High', 'label_fallback' => 'High'],
      ],
      'booking_slots', 'group_class_slots' => array_map(
        fn (int $minutes) => [
          'value' => (string) $minutes,
          'label' => "{$minutes} Minutes",
        ],
        [15, 30, 45, 60, 90, 120],
      ),
      'pwa_orientation' => [
        ['value' => 'portrait', 'label_key' => 'LBL_PORTRAIT', 'label_fallback' => 'Portrait'],
        ['value' => 'landscape', 'label_key' => 'LBL_LANDSCAPE', 'label_fallback' => 'Landscape'],
      ],
      'pwa_display' => [
        ['value' => 'fullscreen', 'label_key' => 'LBL_FULL_SCREEN', 'label_fallback' => 'Full screen'],
        ['value' => 'standalone', 'label_key' => 'LBL_STANDALONE', 'label_fallback' => 'Standalone'],
        ['value' => 'minimal-ui', 'label_key' => 'LBL_MINIMAL_UI', 'label_fallback' => 'Minimal UI'],
        ['value' => 'browser', 'label_key' => 'LBL_BROWSER', 'label_fallback' => 'Browser'],
      ],
      'mux_encoding' => [
        ['value' => 'baseline', 'label_key' => 'LBL_BASELINE_ENCODING', 'label_fallback' => 'Baseline encoding'],
        ['value' => 'smart', 'label_key' => 'LBL_SMART_ENCODING', 'label_fallback' => 'Smart encoding'],
      ],
      'mux_resolution' => $this->muxResolutionOptions((string) ($rawValues['CONF_MUX_ENCODING_TIER'] ?? 'baseline')),
      default => [],
    };
  }

  /** @return array<string, string> */
  private function muxResolutions(string $encoding): array
  {
    return match ($encoding) {
      'smart' => [
        '1080p' => 'LBL_1080p',
        '1440p' => 'LBL_1440p',
        '2160p' => 'LBL_2160p',
      ],
      default => [
        '1080p' => 'LBL_1080p',
      ],
    };
  }

  /** @return array<string, string> */
  private function decodePwaSettings(string $json): array
  {
    if ($json === '') {
      return [];
    }

    $decoded = json_decode($json, true);

    return is_array($decoded) ? $decoded : [];
  }

  /** @return list<array<string, string>> */
  private function muxResolutionOptions(string $encoding): array
  {
    $options = [];
    foreach ($this->muxResolutions($encoding) as $value => $labelKey) {
      $options[] = [
        'value' => $value,
        'label_key' => $labelKey,
        'label_fallback' => $value,
      ];
    }

    return $options;
  }

  private function layoutDirection(int $langId): string
  {
    $code = (string) DB::table('tbl_languages')->where('language_id', $langId)->value('language_code');

    return in_array(strtolower($code), ['ar', 'he', 'fa', 'ur'], true) ? 'rtl' : 'ltr';
  }
}
