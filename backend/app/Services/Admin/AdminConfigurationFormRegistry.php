<?php

namespace App\Services\Admin;

/**
 * Field schema registry mirroring legacy ConfigurationsController::getForm().
 */
class AdminConfigurationFormRegistry
{
    public const FORM_MEDIA_AND_LOGOS = 2;

    public const FORM_THIRD_PARTY_APIS = 3;

    public const FORM_COMMON_SETTINGS = 4;

    public const FORM_EMAIL_AND_SMTPS = 5;

    public const FORM_DASHBOARD_LESSONS = 6;

    public const FORM_DASHBOARD_CLASSES = 7;

    public const FORM_DISCUSSION_FORUM = 8;

    public const FORM_SEO_AND_GOOGLE_TAGS = 9;

    public const FORM_MAINTAINANCE_AND_SSL = 10;

    public const FORM_REMEMBER_ME_SECURITY = 11;

    public const FORM_PWA_SETTINGS = 12;

    public const FORM_DASHBOARD_COURSES = 13;

    public const FORM_REFERRAL_SETTINGS = 14;

    public const FORM_OFFLINE_SESSIONS_SETTINGS = 15;

    public const FORM_AFFILIATE_SETTINGS = 16;

    /** @return list<int> */
    public static function dynamicFormTypes(): array
    {
        return [
            self::FORM_COMMON_SETTINGS,
            self::FORM_EMAIL_AND_SMTPS,
            self::FORM_DASHBOARD_LESSONS,
            self::FORM_DASHBOARD_CLASSES,
            self::FORM_DISCUSSION_FORUM,
            self::FORM_SEO_AND_GOOGLE_TAGS,
            self::FORM_MAINTAINANCE_AND_SSL,
            self::FORM_REMEMBER_ME_SECURITY,
            self::FORM_PWA_SETTINGS,
            self::FORM_DASHBOARD_COURSES,
            self::FORM_REFERRAL_SETTINGS,
            self::FORM_OFFLINE_SESSIONS_SETTINGS,
            self::FORM_AFFILIATE_SETTINGS,
        ];
    }

    public static function supportsDynamicForm(int $formType): bool
    {
        return in_array($formType, self::dynamicFormTypes(), true);
    }

    /** @return array<string, mixed>|null */
    public static function schema(int $formType): ?array
    {
        return match ($formType) {
            self::FORM_COMMON_SETTINGS => self::commonSettingsSchema(),
            self::FORM_EMAIL_AND_SMTPS => self::emailSchema(),
            self::FORM_DASHBOARD_LESSONS => self::lessonsSchema(),
            self::FORM_DASHBOARD_CLASSES => self::classesSchema(),
            self::FORM_DISCUSSION_FORUM => self::forumSchema(),
            self::FORM_SEO_AND_GOOGLE_TAGS => self::seoSchema(),
            self::FORM_MAINTAINANCE_AND_SSL => self::serverSchema(),
            self::FORM_REMEMBER_ME_SECURITY => self::securitySchema(),
            self::FORM_PWA_SETTINGS => self::pwaSchema(),
            self::FORM_DASHBOARD_COURSES => self::coursesSchema(),
            self::FORM_REFERRAL_SETTINGS => self::referralSchema(),
            self::FORM_OFFLINE_SESSIONS_SETTINGS => self::offlineSessionsSchema(),
            self::FORM_AFFILIATE_SETTINGS => self::affiliateSchema(),
            default => null,
        };
    }

    /** @return list<string> */
    public static function configKeys(int $formType): array
    {
        $keys = [];
        $schema = self::schema($formType);
        if ($schema === null) {
            return [];
        }

        foreach ($schema['sections'] as $section) {
            foreach ($section['fields'] as $field) {
                if (! empty($field['name'])) {
                    $keys[] = (string) $field['name'];
                }
            }
        }

        return array_values(array_unique($keys));
    }

    /** @return array<string, mixed> */
    private static function commonSettingsSchema(): array
    {
        return [
            'form_type' => self::FORM_COMMON_SETTINGS,
            'has_lang_tabs' => false,
            'sections' => [
                self::section('LBL_MISCELLANEOUS_SETTINGS', 'Miscellaneous settings', [
                    self::field('CONF_ADMIN_PAGESIZE', 'integer', 'LBL_Default_Items_Per_Page', 'Default items per page', 'LBL_Set_number_of_records_shown_per_page_(Users,_orders,_etc)'),
                    self::field('MINIMUM_GIFT_CARD_AMOUNT', 'integer', 'LBL_MINIMUM_GIFTCARD_AMOUNT', 'Minimum giftcard amount', 'HAF_MINIMUM_GIFTCARD_AMOUNT', required: true),
                    self::field('CONF_CANCEL_ORDER_DURATION', 'integer', 'LBL_CANCEL_PENDING_ORDERS_AFTER_[IN_MINUTES]', 'Cancel pending orders after [in minutes]', 'HAF_CANCEL_ORDER_DURATION', required: true),
                    self::field('CONF_MANAGE_PRICES', 'radio', 'LBL_MANAGE_LANGUAGE_PRICES', 'Manage subject prices', 'HAF_MANAGE_LANGUAGE_PRICES', options: 'manage_prices', disabledWhen: 'subscription_enabled'),
                    self::field('CONF_ENABLE_FLASHCARD', 'checkbox', 'LBL_ENABLE_USER_NOTES', 'Enable user notes', 'HAF_ENABLE_USER_NOTES'),
                    self::field('CONF_ENABLE_NEWSLETTER_SUBSCRIPTION', 'checkbox', 'LBL_ENABLE_NEWSLETTER_SUBSCRIPTION', 'Enable newsletter subscription', 'HAF_ENABLE_NEWSLETTER_SUBSCRIPTION'),
                    self::field('CONF_ENABLE_FREE_TRIAL', 'checkbox', 'LBL_ENABLE_FREE_TRIAL', 'Enable free trial', 'HAF_ENABLE_FREE_TRIAL'),
                    self::field('CONF_ENABLE_COURSES', 'checkbox', 'LBL_ENABLE_COURSES', 'Enable courses', 'HAF_ENABLE_COURSES'),
                    self::field('CONF_ENABLE_SUBSCRIPTION_PLAN', 'checkbox', 'LBL_ENABLE_SUBSCRIPTION_PLAN', 'Enable subscription plan', 'HAF_ENABLE_SUBSCRIPTION_PLAN', disabledWhen: 'subscription_enabled', extraHelp: [
                        ['help_key' => 'NOTE_SETTINGS_CANT_BE_REVERTED_ONCE_ENABLED', 'help_fallback' => 'Note: This setting cannot be reverted once enabled.', 'variant' => 'danger'],
                    ]),
                    self::field('CONF_MAX_TEACHER_REQUEST_ATTEMPT', 'integer', 'LBL_APPLY_TO_TEACH_MAX_ATTEMPT', 'Apply to teach max attempt', 'HAF_APPLY_TO_TEACH_MAX_ATTEMPT'),
                ]),
                self::section('LBL_NEW_ACCOUNT_SETTINGS', 'New account settings', [
                    self::field('CONF_ADMIN_APPROVAL_REGISTRATION', 'checkbox', 'LBL_Activate_Admin_Approval_After_Registration_(Sign_Up)', 'Activate admin approval after registration', 'LBL_On_enabling_this_feature,_admin_need_to_approve_each_learner_after_registration_(Learner_cannot_login_until_admin_approves)'),
                    self::field('CONF_EMAIL_VERIFICATION_REGISTRATION', 'checkbox', 'LBL_Activate_Email_Verification_After_Registration', 'Activate email verification after registration', 'LBL_user_need_to_verify_their_email_address_provided_during_registration'),
                    self::field('CONF_AUTO_LOGIN_REGISTRATION', 'checkbox', 'LBL_Activate_Auto_Login_After_Registration', 'Activate auto login after registration', 'LBL_On_enabling_this_feature,_users_will_be_automatically_logged-in_after_registration'),
                    self::field('CONF_WELCOME_EMAIL_REGISTRATION', 'checkbox', 'LBL_Activate_Sending_Welcome_Mail_After_Registration', 'Activate sending welcome mail after registration', 'LBL_On_enabling_this_feature,_users_will_receive_a_welcome_mail_after_registration.'),
                ]),
                self::section('LBL_REPORT/ESCALATE_ISSUE_TIME', 'Report/escalate issue time', [
                    self::field('CONF_REPORT_ISSUE_HOURS_AFTER_COMPLETION', 'integer', 'CONF_REPORT_ISSUE_HOURS_AFTER_COMPLETION', 'Report issue hours after completion', 'htmlAfterField_REPORT_ISSUE_HOURS_AFTER_COMPLETION_TEXT'),
                    self::field('CONF_ESCALATED_ISSUE_HOURS_AFTER_RESOLUTION', 'integer', 'CONF_ESCALATED_ISSUE_HOURS_AFTER_RESOLUTION', 'Escalated issue hours after resolution', 'htmlAfterField_ESCALATED_ISSUE_HOURS_AFTER_RESOLUTION_TEXT', helpLeadingBr: true),
                ]),
                self::section('LBL_WALLET', 'Wallet', [
                    self::field('MINIMUM_WALLET_RECHARGE_AMOUNT', 'integer', 'LBL_MINIMUM_RECHARGE_AMOUNT', 'Minimum recharge amount', 'LBL_MINIMUM_AMOUNT_REQUIRED_TO_RECHARGE_WALLET', suffix: 'currency_code'),
                ]),
                self::section('LBL_Withdrawal', 'Withdrawal', [
                    self::field('CONF_MIN_WITHDRAW_LIMIT', 'integer', 'LBL_Minimum_Withdrawal_Amount', 'Minimum withdrawal amount', 'LBL_This_is_the_minimum_withdrawable_amount.', suffix: 'currency_code'),
                    self::field('CONF_MIN_INTERVAL_WITHDRAW_REQUESTS', 'integer', 'LBL_Minimum_Interval_[Days]', 'Minimum interval [days]', 'LBL_This_is_the_minimum_interval_in_days_between_two_withdrawal_requests.'),
                ]),
                self::section('LBL_REVIEWS', 'Reviews', [
                    self::field('CONF_ALLOW_REVIEWS', 'radio', 'LBL_ALLOW_REVIEWS', 'Allow reviews', 'HAF_ALLOW_REVIEWS', options: 'yes_no'),
                    self::field('CONF_DEFAULT_REVIEW_STATUS', 'radio', 'LBL_DEFAULT_REVIEW_STATUS', 'Default review status', 'LBL_SET_THE_DEFAULT_REVIEW_ORDER_STATUS_WHEN_A_NEW_REVIEW_IS_PLACED', options: 'review_status'),
                ]),
                self::section('LBL_Notifications', 'Notifications', [
                    self::field('CONF_ENABLE_UNREAD_MSG_NOTIFICATION', 'radio', 'LBL_Enable_Unread_Messages_Notifications', 'Enable unread messages notifications', 'LBL_Enable_Email_Notifications_For_Unread_Messages.', options: 'yes_no'),
                    self::field('CONF_UNREAD_MSG_NOTIFICATION_DURATION', 'integer', 'LBL_Unread_Messages_Notify_Duration[mins]', 'Unread messages notify duration [mins]', 'LBL_This_Is_The_Messages_Unread_Duration_After_Which_Users_Will_Get_Notification._Recommended_Duration:_10_Mins'),
                    self::field('CONF_DELETE_ATTACHMENT_ALLOWED_DURATION', 'integer', 'LBL_DELETE_ATTACHMENT_DURATION[MINS]', 'Delete attachment duration [mins]', 'LBL_THIS_IS_THE_DURATION_UNTIL_THE_USERS_ARE_ALLOWED_TO_DELETE_SENT_ATTACHMENTS_IN_MESSAGES'),
                ]),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function emailSchema(): array
    {
        return [
            'form_type' => self::FORM_EMAIL_AND_SMTPS,
            'has_lang_tabs' => false,
            'sections' => [
                self::section('LBL_EMAIL_AND_SMTPS', 'Email and SMTP', [
                    self::field('CONF_FROM_EMAIL', 'email', 'LBL_From_Email', 'From email', 'HAF_From_Email'),
                    self::field('CONF_SEND_EMAIL', 'radio', 'LBL_Send_Email', 'Send email', 'LBL_This_will_send_Test_Email_to_Site_Owner_Email', options: 'yes_no', widget: 'email_test'),
                    self::field('CONF_CONTACT_EMAIL', 'email', 'LBL_Contact_Email', 'Contact email', 'HAF_Contact_Email'),
                    self::field('CONF_SEND_SMTP_EMAIL', 'radio', 'LBL_Send_SMTP_Email', 'Send SMTP email', '', options: 'yes_no'),
                    self::field('CONF_SMTP_HOST', 'text', 'LBL_SMTP_Host', 'SMTP host'),
                    self::field('CONF_SMTP_PORT', 'text', 'LBL_SMTP_Port', 'SMTP port'),
                    self::field('CONF_SMTP_USERNAME', 'text', 'LBL_SMTP_Username', 'SMTP username'),
                    self::field('CONF_SMTP_PASSWORD', 'password', 'LBL_SMTP_Password', 'SMTP password'),
                    self::field('CONF_SMTP_SECURE', 'radio', 'LBL_SMTP_Secure', 'SMTP secure', '', options: 'smtp_secure'),
                ]),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function lessonsSchema(): array
    {
        return [
            'form_type' => self::FORM_DASHBOARD_LESSONS,
            'has_lang_tabs' => false,
            'sections' => [
                self::section('LBL_DASHBOARD_LESSONS', 'Dashboard lessons', [
                    self::field('CONF_PAID_LESSON_DURATION', 'checkboxes', 'LBL_ALLOWED_LESSON_SLOTS', 'Allowed lesson slots', 'HAF_ALLOWED_LESSON_SLOTS', options: 'booking_slots', serialized: 'comma'),
                    self::field('CONF_TRIAL_LESSON_DURATION', 'radio', 'LBL_ALLOWED_TRAIL_LESSON_SLOT', 'Allowed trial lesson slot', 'HAF_ALLOWED_TRIAL_LESSON_SLOTS', options: 'booking_slots', required: true),
                    self::field('CONF_LESSON_CANCEL_DURATION', 'integer', 'LBL_LESSON_CANCEL_DURATION', 'Lesson cancel duration', 'HAF_LESSON_CANCEL_DURATION'),
                    self::field('CONF_LESSON_RESCHEDULE_DURATION', 'integer', 'LBL_LESSON_RESCHEDULE_DURATION', 'Lesson reschedule duration', 'HAF_LESSON_RESCHEDULE_DURATION'),
                    self::field('CONF_LESSON_REFUND_DURATION', 'integer', 'LBL_LESSON_REFUND_DURATION', 'Lesson refund duration', 'HAF_LESSON_REFUND_DURATION'),
                    self::field('CONF_LESSON_REFUND_PERCENTAGE_BEFORE_DURATION', 'float', 'LBL_REFUND_BEFORE_REFUND_DURATION', 'Refund before refund duration', 'HAF_REFUND_BEFORE_REFUND_DURATION'),
                    self::field('CONF_LESSON_REFUND_PERCENTAGE_AFTER_DURATION', 'float', 'LBL_REFUND_AFTER_REFUND_DURATION', 'Refund after refund duration', 'HAF_REFUND_AFTER_REFUND_DURATION'),
                    self::field('CONF_ALLOW_TEACHER_END_LESSON', 'integer', 'LBL_END_LESSON_DURATION', 'End lesson duration', 'HAF_END_LESSON_DURATION'),
                    self::field('CONF_UNSCHEDULE_LESSON_REFUND_PERCENTAGE', 'float', 'LBL_UNSCHEDULE_LESSON_CANCEL_REFUND', 'Unschedule lesson cancel refund', 'HAF_UNSCHEDULE_LESSON_CANCEL_REFUND'),
                    self::field('CONF_AUTOCOMPLETE_LESSON_SESSION', 'integer', 'LBL_AUTO_COMPLETE_LESSON_AFTER_X_HOURS', 'Auto complete lesson after X hours', 'HAF_AUTO_COMPLETE_LESSON_AFTER_X_HOURS'),
                ]),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function classesSchema(): array
    {
        return [
            'form_type' => self::FORM_DASHBOARD_CLASSES,
            'has_lang_tabs' => false,
            'sections' => [
                self::section('LBL_DASHBOARD_CLASSES', 'Dashboard classes', [
                    self::field('CONF_GROUP_CLASS_DURATION', 'checkboxes', 'LBL_ALLOWED_CLASS_SLOTS', 'Allowed class slots', 'HAF_ALLOWED_CLASS_SLOTS', options: 'group_class_slots', serialized: 'comma'),
                    self::field('CONF_CLASS_CANCEL_DURATION', 'integer', 'LBL_CLASS_CANCEL_DURATION', 'Class cancel duration', 'HAF_CLASS_CANCEL_DURATION'),
                    self::field('CONF_CLASS_REFUND_DURATION', 'integer', 'LBL_CLASS_REFUND_DURATION', 'Class refund duration', 'HAF_CLASS_REFUND_DURATION'),
                    self::field('CONF_CLASS_REFUND_PERCENTAGE_BEFORE_DURATION', 'float', 'LBL_CLASS_REFUND_BEFORE_REFUND_DURATION', 'Class refund before refund duration', 'HAF_CLASS_REFUND_BEFORE_REFUND_DURATION'),
                    self::field('CONF_CLASS_REFUND_PERCENTAGE_AFTER_DURATION', 'float', 'LBL_CLASS_REFUND_AFTER_REFUND_DURATION', 'Class refund after refund duration', 'HAF_CLASS_REFUND_AFTER_REFUND_DURATION'),
                    self::field('CONF_ALLOW_TEACHER_END_CLASS', 'integer', 'LBL_END_CLASS_DURATION', 'End class duration', 'HAF_END_CLASS_DURATION'),
                    self::field('CONF_CLASS_BOOKING_GAP', 'integer', 'LBL_Class_Book_Before', 'Class book before', 'HAF_CLASS_BOOK_BEFORE'),
                    self::field('CONF_GROUP_CLASS_MAX_LEARNERS', 'integer', 'LBL_Class_Max_learners', 'Class max learners', 'HAF_CLASS_MAX_LEARNERS'),
                    self::field('CONF_AUTOCOMPLETE_CLASSES_SESSION', 'integer', 'LBL_AUTO_COMPLETE_CLASSES_AFTER_X_HOURS', 'Auto complete classes after X hours', 'HAF_AUTO_COMPLETE_CLASSES_AFTER_X_HOURS'),
                ]),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function forumSchema(): array
    {
        return [
            'form_type' => self::FORM_DISCUSSION_FORUM,
            'has_lang_tabs' => false,
            'sections' => [
                self::section('LBL_DISCUSSION_FORUM', 'Discussion forum', [
                    self::field('CONF_FORUM_SEND_EMAILS', 'radio', 'LBL_ENABLE_EMAIL_NOTIFICATIONS', 'Enable email notifications', 'HAF_ENABLE_FORUM_EMAIL_NOTIFICATIONS', options: 'yes_no'),
                    self::field('FORUM_SEND_SYSTEM_NOTIFICATIONS', 'radio', 'LBL_ENABLE_SYSTEM_NOTIFICATIONS', 'Enable system notifications', 'HAF_ENABLE_FORUM_SYSTEM_NOTIFICATIONS', options: 'yes_no'),
                ]),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function seoSchema(): array
    {
        return [
            'form_type' => self::FORM_SEO_AND_GOOGLE_TAGS,
            'has_lang_tabs' => false,
            'sections' => [
                self::section('LBL_SITE_TRACKING_SCRIPTS', 'Site Tracking Scripts', [
                    self::field(
                        'CONF_LANGCODE_URL',
                        'checkbox',
                        'LBL_ENABLE_LANGUAGE_CODE_TO_SITE_URLS',
                        'Add language code to site URLs',
                        'LBL_LANGUAGE_CODE_TO_SITE_URLS_EXAMPLES',
                        helpFallback: "Some examples of URLs with Language codes:\nwww.example.com/?lang=fr\nwww.example.com/about/?lang=en",
                    ),
                    self::field(
                        'CONF_SITE_TRACKER_CODE',
                        'textarea',
                        'LBL_Site_Tracker_Code',
                        'Site tracker code',
                        'LBL_This_is_the_site_tracker_script,_used_to_track_and_analyze_data_about_how_people_are_getting_to_your_website._e.g.,_Google_Analytics.',
                        helpFallback: 'This is the site tracker script, used to track and analyze data about how people are getting to your website. e.g., Google Analytics. http://www.google.com/analytics/',
                    ),
                ]),
                self::section('LBL_GOOGLE_TAG_MANAGER', 'Google Tag Manager', [
                    self::field('CONF_GOOGLE_TAG_MANAGER_HEAD_SCRIPT', 'textarea', 'LBL_HEAD_SCRIPT', 'Head script'),
                    self::field('CONF_GOOGLE_TAG_MANAGER_BODY_SCRIPT', 'textarea', 'LBL_BODY_SCRIPT', 'Body script'),
                ]),
                self::section('LBL_OTHER_SCRIPTS', 'Other Scripts', [
                    self::field('CONF_OTHER_HEAD_SCRIPT', 'textarea', 'LBL_HEAD_SCRIPT', 'Head script'),
                    self::field('CONF_OTHER_BODY_SCRIPT', 'textarea', 'LBL_BODY_SCRIPT', 'Body script'),
                ]),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function serverSchema(): array
    {
        return [
            'form_type' => self::FORM_MAINTAINANCE_AND_SSL,
            'has_lang_tabs' => true,
            'lang_tab_mode' => 'maintenance_lang',
            'sections' => [
                self::section('LBL_MAINTAINANCE_AND_SSL', 'Maintenance and SSL', [
                    self::field('CONF_USE_SSL', 'radio', 'LBL_ENABLE_SSL', 'Enable SSL', 'LBL_NOTE:_To_use_SSL,_check_with_your_host', options: 'yes_no'),
                    self::field('CONF_MAINTENANCE', 'radio', 'LBL_Maintenance_Mode', 'Maintenance mode', 'LBL_Enable_Maintenance_Mode_Text', options: 'yes_no'),
                ]),
            ],
        ];
    }

    /** @return array<string, mixed> */
    public static function maintenanceLangSchema(int $langId): array
    {
        return [
            'form_type' => self::FORM_MAINTAINANCE_AND_SSL,
            'has_lang_tabs' => true,
            'lang_tab_mode' => 'maintenance_lang',
            'lang_id' => $langId,
            'sections' => [
                self::section('LBL_MAINTAINANCE_AND_SSL', 'Maintenance and SSL', [
                    self::field("CONF_MAINTENANCE_TEXT_{$langId}", 'textarea', 'LBL_Maintenance_Text', 'Maintenance text', '', required: true),
                ]),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function securitySchema(): array
    {
        return [
            'form_type' => self::FORM_REMEMBER_ME_SECURITY,
            'has_lang_tabs' => false,
            'sections' => [
                self::section('LBL_REMEMBER_ME_SECURITY_SETTINGS', 'Remember me security settings', [
                    self::field('CONF_ADMIN_REMEMBER_ME_DAYS', 'integer', 'LBL_REMEMBER_ME_DAYS_FOR_ADMIN', 'Remember me days for admin', 'HAF_REMEMBER_ME_DAYS_FOR_ADMIN'),
                    self::field('CONF_ADMIN_REMEMBER_ME_IP_ENABLE', 'select', 'LBL_REMEMBER_ME_SECURITY_FOR_ADMIN', 'Remember me security for admin', 'HAF_REMEMBER_ME_SECURITY_FOR_ADMIN', options: 'security_settings', required: true),
                    self::field('CONF_USER_REMEMBER_ME_DAYS', 'integer', 'LBL_REMEMBER_ME_DAYS_FOR_USER', 'Remember me days for user', 'HAF_REMEMBER_ME_DAYS_FOR_USER'),
                    self::field('CONF_USER_REMEMBER_ME_IP_ENABLE', 'select', 'LBL_REMEMBER_ME_SECURITY_FOR_USER', 'Remember me security for user', 'HAF_REMEMBER_ME_SECURITY_FOR_USER', options: 'security_settings', required: true),
                ]),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function pwaSchema(): array
    {
        return [
            'form_type' => self::FORM_PWA_SETTINGS,
            'has_lang_tabs' => false,
            'sections' => [
                self::section('LBL_PWA_SETTINGS', 'PWA settings', [
                    self::field('CONF_ENABLE_PWA', 'checkbox', 'LBL_Enable_PWA', 'Enable PWA', 'HAF_Enable_PWA'),
                    self::field('pwa_settings.name', 'text', 'LBL_App_Name', 'App name', 'HAF_App_Name', required: true),
                    self::field('pwa_settings.short_name', 'text', 'LBL_App_Short_Name', 'App short name', 'HAF_App_Short_Name', required: true),
                    self::field('pwa_settings.description', 'text', 'LBL_PWA_Description', 'PWA description', 'HAF_PWA_Description'),
                    self::field('pwa_settings.background_color', 'color', 'LBL_Background_Color', 'Background color', 'HAF_PWA_Background_color', required: true),
                    self::field('pwa_settings.theme_color', 'color', 'LBL_Theme_Color', 'Theme color', 'HAF_PWA_Theme_Color', required: true),
                    self::field('pwa_settings.start_url', 'text', 'LBL_Start_Page', 'Start page', 'HAF_PWA_Start_Page', required: true),
                    self::field('pwa_settings.orientation', 'select', 'LBL_Orientation', 'Orientation', 'HAF_PWA_orientation', options: 'pwa_orientation', required: true),
                    self::field('pwa_settings.display', 'select', 'LBL_Display', 'Display', 'HAF_PWA_Display', options: 'pwa_display', required: true),
                ]),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function coursesSchema(): array
    {
        return [
            'form_type' => self::FORM_DASHBOARD_COURSES,
            'has_lang_tabs' => false,
            'sections' => [
                self::section('LBL_DASHBOARD_COURSE', 'Dashboard course', [
                    self::field('CONF_COURSE_CANCEL_DURATION', 'integer', 'LBL_COURSE_CANCELLATION_DURATION(DAYS)', 'Course cancellation duration (days)', 'htmlAfterField_COURSE_CANCELLATION_DURATION_TEXT'),
                    self::field('CONF_COURSE_DEFAULT_CANCELLATION_STATUS', 'radio', 'LBL_COURSE_DEFAULT_CANCELLATION_STATUS', 'Course default cancellation status', 'LBL_SET_THE_DEFAULT_STATUS_WHEN_A_COURSE_CANCELLATION_REQUEST_IS_PLACED', options: 'course_refund_status'),
                    self::field('CONF_COURSE_EDIT_DURATION', 'integer', 'LBL_COURSE_EDIT_DURATION(DAYS)', 'Course edit duration (days)', 'htmlAfterField_COURSE_EDIT_DURATION_TEXT'),
                    self::field('CONF_SEND_COURSE_EDIT_EMAILS', 'radio', 'LBL_SEND_COURSE_EDIT_EMAILS', 'Send course edit emails', 'htmlAfterField_COURSE_EDIT_EMAILS_SETTING_TEXT', options: 'yes_no'),
                ]),
            ],
            'conditional_sections' => [
                'video_cipher' => [
                    'heading_key' => 'LBL_VIDEO_CIPHER',
                    'heading_fallback' => 'Video cipher',
                    'fields' => [
                        self::field('CONF_VIDEO_CIPHER_API_KEY', 'text', 'LBL_VIDEO_CIPHER_API_KEY', 'Video cipher API key', 'LBL_VIDEO_CIPHER_API_KEY_MESSAGE'),
                        self::field('CONF_VIDEO_CIPHER_FOLDER_ID', 'text', 'LBL_VIDEO_CIPHER_FOLDER_ID', 'Video cipher folder ID', 'LBL_VIDEO_CIPHER_FOLDER_ID_MESSAGE'),
                    ],
                ],
                'mux' => [
                    'heading_key' => 'LBL_MUX_VIDEOS',
                    'heading_fallback' => 'Mux videos',
                    'fields' => [
                        self::field('CONF_MUX_ACCESS_TOKEN_ID', 'text', 'LBL_MUX_ACCESS_TOKEN_ID', 'Mux access token ID', 'LBL_ACCESS_TOKEN_ID_TO_AUTHENTICATE'),
                        self::field('CONF_MUX_SECRET_KEY', 'text', 'LBL_MUX_SECRET_KEY', 'Mux secret key', 'LBL_SECRET_KEY_TO_AUTHENTICATE'),
                        self::field('CONF_MUX_ENCODING_TIER', 'select', 'LBL_ENCODING_TIER', 'Encoding tier', 'LBL_ENCODING_TIER_INFORMS_THE_COST,_QUALITY,_AND_AVAILABLE_PLATFORM_FEATURES_FOR_THE_ASSET', options: 'mux_encoding'),
                        self::field('CONF_MUX_RESOLUTION', 'select', 'LBL_HIGHEST_VIDEO_RESOLUTION', 'Highest video resolution', 'LBL_HIGHEST_VIDEO_RESOLUTION_CAN_BE_UPLOADED', options: 'mux_resolution'),
                        self::field('CONF_MUX_WEBHOOK_SECRET_KEY', 'text', 'LBL_MUX_WEBHOOK_SECRET', 'Mux webhook secret', 'LBL_WEBHOOK_SECRET'),
                    ],
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function referralSchema(): array
    {
        return [
            'form_type' => self::FORM_REFERRAL_SETTINGS,
            'has_lang_tabs' => false,
            'sections' => [
                self::section('LBL_REFERRAL_SETTINGS', 'Referral settings', [
                    self::field('CONF_ENABLE_REFERRAL_REWARDS', 'checkbox', 'CONF_ENABLE_REFERRAL_REWARDS', 'Enable referral rewards', 'HAF_ENABLE/DISABLE_REFERRAL_MODULE'),
                    self::field('CONF_REWARD_POINT_MULTIPLIER', 'integer', 'CONF_REWARD_POINT_MULTIPLIER', 'Reward point multiplier', 'HAF_Rate_Of_Converstion_1_Currency_Unit_=_X_No_Of_Reward_Points'),
                    self::field('CONF_REWARD_POINT_MINIMUM_USE', 'integer', 'CONF_REWARD_POINT_MINIMUM_USE', 'Reward point minimum use', 'HAF_MINIMUM_REWARD_POINT_USE_LIMIT'),
                ]),
                self::section('LBL_REWARD_POINTS_ON_REGISTRATION:', 'Reward points on registration', [
                    self::field('CONF_REFERRER_REGISTER_REWARDS', 'integer', 'CONF_REFERRER_REGISTER_REWARDS', 'Referrer register rewards', 'HAF_REWARDS_TO_REFERRER_ON_REFEREE_SIGNUP'),
                    self::field('CONF_REFERENT_REGISTER_REWARDS', 'integer', 'CONF_REFEREE_REGISTER_REWARDS', 'Referee register rewards', 'HAF_REWARDS_TO_REFERREE_ON_REFERAL_SIGNUP'),
                ]),
                self::section('LBL_REWARD_POINTS_ON_FIRST_PURCHASE:', 'Reward points on first purchase', [
                    self::field('CONF_REFERRER_PURCHASE_REWARDS', 'integer', 'CONF_REFERRER_PURCHASE_REWARDS', 'Referrer purchase rewards', 'HAF_REWARDS_TO_REFERRER_ON_REFEREE_FIRST_PURCHASE'),
                    self::field('CONF_REFERENT_PURCHASE_REWARDS', 'integer', 'CONF_REFEREE_PURCHASE_REWARDS', 'Referee purchase rewards', 'HAF_REWARDS_TO_REFERREE_ON_FIRST_PURCHASE'),
                ]),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function offlineSessionsSchema(): array
    {
        return [
            'form_type' => self::FORM_OFFLINE_SESSIONS_SETTINGS,
            'has_lang_tabs' => false,
            'sections' => [
                self::section('LBL_OFFLINE_SESSIONS_SETTINGS', 'Offline sessions settings', [
                    self::field('CONF_ENABLE_OFFLINE_SESSIONS', 'checkbox', 'LBL_ENABLE_OFFLINE_SESSIONS', 'Enable offline sessions'),
                    self::field('CONF_DEFAULT_RADIUS_FOR_SEARCH', 'integer', 'LBL_DEFAULT_RADIUS_FOR_SEARCH(MILES)', 'Default radius for search (miles)', 'HAF_DEFAULT_RADIUS_SEARCH_INFO'),
                    self::field('CONF_TEACHER_END_SESSION_DURATION', 'integer', 'LBL_END_SESSION_DURATION(HOURS)', 'End session duration (hours)', 'HAF_TEACHER_SESSION_END_DURATION_INFO'),
                ]),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function affiliateSchema(): array
    {
        return [
            'form_type' => self::FORM_AFFILIATE_SETTINGS,
            'has_lang_tabs' => false,
            'sections' => [
                self::section('LBL_AFFILIATE_SETTINGS', 'Affiliate settings', [
                    self::field('CONF_ENABLE_AFFILIATE_MODULE', 'checkbox', 'CONF_ENABLE_AFFILIATE_MODULE', 'Enable affiliate module', 'HAF_ENABLE/DISABLE_AFFILIATE_MODULE'),
                    self::field('CONF_AFFILIATE_COMMISSION_ON_USER_REGISTRATION', 'integer', 'CONF_AFFILIATE_COMMISSION_ON_USER_REGISTRATION', 'Affiliate commission on user registration', 'HAF_AFFILIATE_COMMISSION_ON_USER_REGISTRATION'),
                ]),
            ],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $fields
     * @return array<string, mixed>
     */
    private static function section(string $headingKey, string $headingFallback, array $fields): array
    {
        return [
            'heading_key' => $headingKey,
            'heading_fallback' => $headingFallback,
            'fields' => $fields,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function field(
        string $name,
        string $type,
        string $labelKey,
        string $labelFallback,
        string $helpKey = '',
        string $options = '',
        bool $required = false,
        string $disabledWhen = '',
        string $serialized = '',
        string $suffix = '',
        string $helpFallback = '',
        array $extraHelp = [],
        bool $helpLeadingBr = false,
        string $widget = '',
    ): array {
        $resolvedHelpFallback = $helpFallback !== '' ? $helpFallback : ($helpKey !== '' ? self::helpFallback($helpKey) : '');

        return array_filter([
            'name' => $name,
            'type' => $type,
            'label_key' => $labelKey,
            'label_fallback' => $labelFallback,
            'help_key' => $helpKey !== '' ? $helpKey : null,
            'help_fallback' => $resolvedHelpFallback !== '' ? $resolvedHelpFallback : null,
            'help_leading_br' => $helpLeadingBr ? true : null,
            'extra_help' => $extraHelp !== [] ? $extraHelp : null,
            'widget' => $widget !== '' ? $widget : null,
            'options' => $options !== '' ? $options : null,
            'required' => $required,
            'disabled_when' => $disabledWhen !== '' ? $disabledWhen : null,
            'serialized' => $serialized !== '' ? $serialized : null,
            'suffix' => $suffix !== '' ? $suffix : null,
        ], fn ($value) => $value !== null);
    }

  /** @return array<string, string> */
  public static function helpFallbackMap(): array
  {
    return [
      'LBL_Set_number_of_records_shown_per_page_(Users,_orders,_etc)' => 'Set the number of records shown per page (users, orders, etc.).',
      'HAF_MINIMUM_GIFTCARD_AMOUNT' => 'Minimum amount allowed for gift cards.',
      'HAF_CANCEL_ORDER_DURATION' => 'Pending orders are cancelled automatically after this duration (in minutes).',
      'HAF_MANAGE_LANGUAGE_PRICES' => 'Choose whether teachers or admins manage subject pricing.',
      'HAF_ENABLE_USER_NOTES' => 'Allow learners to create and manage notes.',
      'HAF_ENABLE_NEWSLETTER_SUBSCRIPTION' => 'Show newsletter subscription option on the front-end.',
      'HAF_ENABLE_FREE_TRIAL' => 'Allow learners to book a free trial lesson.',
      'HAF_ENABLE_COURSES' => 'Enable the courses module across the platform.',
      'HAF_ENABLE_SUBSCRIPTION_PLAN' => 'Enable subscription plans for learners.',
      'HAF_APPLY_TO_TEACH_MAX_ATTEMPT' => 'Maximum number of attempts allowed when applying to teach.',
      'LBL_On_enabling_this_feature,_admin_need_to_approve_each_learner_after_registration_(Learner_cannot_login_until_admin_approves)' => 'On enabling this feature, admin needs to approve each learner after registration.',
      'LBL_user_need_to_verify_their_email_address_provided_during_registration' => 'Users need to verify their email address provided during registration.',
      'LBL_On_enabling_this_feature,_users_will_be_automatically_logged-in_after_registration' => 'Users will be automatically logged in after registration.',
      'LBL_On_enabling_this_feature,_users_will_receive_a_welcome_mail_after_registration.' => 'Users will receive a welcome email after registration.',
      'htmlAfterField_REPORT_ISSUE_HOURS_AFTER_COMPLETION_TEXT' => 'Hours after lesson completion when learners can report an issue.',
      'htmlAfterField_ESCALATED_ISSUE_HOURS_AFTER_RESOLUTION_TEXT' => 'Hours after issue resolution when escalated issues can be reported.',
      'LBL_MINIMUM_AMOUNT_REQUIRED_TO_RECHARGE_WALLET' => 'Minimum amount required to recharge the wallet.',
      'LBL_This_is_the_minimum_withdrawable_amount.' => 'This is the minimum withdrawable amount.',
      'LBL_This_is_the_minimum_interval_in_days_between_two_withdrawal_requests.' => 'Minimum interval in days between two withdrawal requests.',
      'HAF_ALLOW_REVIEWS' => 'Allow learners to submit reviews.',
      'LBL_SET_THE_DEFAULT_REVIEW_ORDER_STATUS_WHEN_A_NEW_REVIEW_IS_PLACED' => 'Default status when a new review is submitted.',
      'LBL_Enable_Email_Notifications_For_Unread_Messages.' => 'Send email notifications for unread messages.',
      'LBL_This_Is_The_Messages_Unread_Duration_After_Which_Users_Will_Get_Notification._Recommended_Duration:_10_Mins' => 'Unread message duration after which users get notified. Recommended: 10 minutes.',
      'LBL_THIS_IS_THE_DURATION_UNTIL_THE_USERS_ARE_ALLOWED_TO_DELETE_SENT_ATTACHMENTS_IN_MESSAGES' => 'Duration until users can delete sent message attachments.',
      'HAF_From_Email' => 'Email address used as the sender for system emails.',
      'LBL_This_will_send_Test_Email_to_Site_Owner_Email' => 'This will send a test email to the site owner email.',
      'HAF_Contact_Email' => 'Contact email displayed on the front-end.',
      'HAF_ALLOWED_LESSON_SLOTS' => 'Select lesson durations available for booking.',
      'HAF_ALLOWED_TRIAL_LESSON_SLOTS' => 'Select the trial lesson duration.',
      'HAF_LESSON_CANCEL_DURATION' => 'Hours before lesson start when cancellation is allowed.',
      'HAF_LESSON_RESCHEDULE_DURATION' => 'Hours before lesson start when rescheduling is allowed.',
      'HAF_LESSON_REFUND_DURATION' => 'Hours before lesson start when refund is applicable.',
      'HAF_REFUND_BEFORE_REFUND_DURATION' => 'Refund percentage before the refund duration.',
      'HAF_REFUND_AFTER_REFUND_DURATION' => 'Refund percentage after the refund duration.',
      'HAF_END_LESSON_DURATION' => 'Minutes before lesson end when teachers can end the lesson.',
      'HAF_UNSCHEDULE_LESSON_CANCEL_REFUND' => 'Refund percentage for unscheduled lesson cancellations.',
      'HAF_AUTO_COMPLETE_LESSON_AFTER_X_HOURS' => 'Auto-complete lessons after this many hours.',
      'HAF_ALLOWED_CLASS_SLOTS' => 'Select class durations available for booking.',
      'HAF_CLASS_CANCEL_DURATION' => 'Hours before class start when cancellation is allowed.',
      'HAF_CLASS_REFUND_DURATION' => 'Hours before class start when refund is applicable.',
      'HAF_CLASS_REFUND_BEFORE_REFUND_DURATION' => 'Refund percentage before the refund duration.',
      'HAF_CLASS_REFUND_AFTER_REFUND_DURATION' => 'Refund percentage after the refund duration.',
      'HAF_END_CLASS_DURATION' => 'Minutes before class end when teachers can end the class.',
      'HAF_CLASS_BOOK_BEFORE' => 'Minimum hours before class start when booking is allowed.',
      'HAF_CLASS_MAX_LEARNERS' => 'Maximum learners allowed per group class.',
      'HAF_AUTO_COMPLETE_CLASSES_AFTER_X_HOURS' => 'Auto-complete classes after this many hours.',
      'HAF_ENABLE_FORUM_EMAIL_NOTIFICATIONS' => 'Send email notifications for forum activity.',
      'HAF_ENABLE_FORUM_SYSTEM_NOTIFICATIONS' => 'Send system notifications for forum activity.',
      'LBL_LANGUAGE_CODE_TO_SITE_URLS_EXAMPLES' => "Some examples of URLs with Language codes:\nwww.example.com/?lang=fr\nwww.example.com/about/?lang=en",
      'LBL_This_is_the_site_tracker_script,_used_to_track_and_analyze_data_about_how_people_are_getting_to_your_website._e.g.,_Google_Analytics.' => 'This is the site tracker script, used to track and analyze data about how people are getting to your website. e.g., Google Analytics. http://www.google.com/analytics/',
      'LBL_NOTE:_To_use_SSL,_check_with_your_host' => 'To use SSL, check with your hosting provider.',
      'LBL_Enable_Maintenance_Mode_Text' => 'Message shown to visitors when maintenance mode is enabled.',
      'HAF_REMEMBER_ME_DAYS_FOR_ADMIN' => 'Number of days admin stay logged in with remember me.',
      'HAF_REMEMBER_ME_SECURITY_FOR_ADMIN' => 'Security level for admin remember me sessions.',
      'HAF_REMEMBER_ME_DAYS_FOR_USER' => 'Number of days users stay logged in with remember me.',
      'HAF_REMEMBER_ME_SECURITY_FOR_USER' => 'Security level for user remember me sessions.',
      'htmlAfterField_COURSE_CANCELLATION_DURATION_TEXT' => 'Days before course start when cancellation is allowed.',
      'LBL_SET_THE_DEFAULT_STATUS_WHEN_A_COURSE_CANCELLATION_REQUEST_IS_PLACED' => 'Default status when a course cancellation request is placed.',
      'htmlAfterField_COURSE_EDIT_DURATION_TEXT' => 'Days within which course edits are allowed.',
      'htmlAfterField_COURSE_EDIT_EMAILS_SETTING_TEXT' => 'Send emails when course edits are submitted.',
      'LBL_VIDEO_CIPHER_API_KEY_MESSAGE' => 'API key from your VideoCipher account.',
      'LBL_VIDEO_CIPHER_FOLDER_ID_MESSAGE' => 'Folder ID from your VideoCipher account.',
      'LBL_ACCESS_TOKEN_ID_TO_AUTHENTICATE' => 'Access token ID used to authenticate with Mux.',
      'LBL_SECRET_KEY_TO_AUTHENTICATE' => 'Secret key used to authenticate with Mux.',
      'LBL_ENCODING_TIER_INFORMS_THE_COST,_QUALITY,_AND_AVAILABLE_PLATFORM_FEATURES_FOR_THE_ASSET' => 'Encoding tier affects cost, quality, and available platform features.',
      'LBL_HIGHEST_VIDEO_RESOLUTION_CAN_BE_UPLOADED' => 'Highest video resolution that can be uploaded.',
      'LBL_WEBHOOK_SECRET' => 'Webhook secret from your Mux account.',
      'HAF_Enable_PWA' => 'Enable progressive web app support.',
      'HAF_App_Name' => 'Full application name for the PWA.',
      'HAF_App_Short_Name' => 'Short name shown on the device home screen.',
      'HAF_PWA_Description' => 'Description of the PWA application.',
      'HAF_PWA_Background_color' => 'Background color for the PWA splash screen.',
      'HAF_PWA_Theme_Color' => 'Theme color for the PWA browser toolbar.',
      'HAF_PWA_Start_Page' => 'Start page URL when the PWA is launched.',
      'HAF_PWA_orientation' => 'Preferred screen orientation for the PWA.',
      'HAF_PWA_Display' => 'Display mode for the PWA.',
      'HAF_ENABLE/DISABLE_REFERRAL_MODULE' => 'Enable or disable the referral rewards module.',
      'HAF_Rate_Of_Converstion_1_Currency_Unit_=_X_No_Of_Reward_Points' => 'Conversion rate: 1 currency unit equals X reward points.',
      'HAF_MINIMUM_REWARD_POINT_USE_LIMIT' => 'Minimum reward points required to redeem.',
      'HAF_REWARDS_TO_REFERRER_ON_REFEREE_SIGNUP' => 'Reward points given to referrer when referee signs up.',
      'HAF_REWARDS_TO_REFERREE_ON_REFERAL_SIGNUP' => 'Reward points given to referee on signup.',
      'HAF_REWARDS_TO_REFERRER_ON_REFEREE_FIRST_PURCHASE' => 'Reward points given to referrer on referee first purchase.',
      'HAF_REWARDS_TO_REFERREE_ON_FIRST_PURCHASE' => 'Reward points given to referee on first purchase.',
      'HAF_DEFAULT_RADIUS_SEARCH_INFO' => 'Default search radius in miles for offline sessions.',
      'HAF_TEACHER_SESSION_END_DURATION_INFO' => 'Hours after session start when teachers can end offline sessions.',
      'HAF_ENABLE/DISABLE_AFFILIATE_MODULE' => 'Enable or disable the affiliate module.',
      'HAF_AFFILIATE_COMMISSION_ON_USER_REGISTRATION' => 'Commission amount for affiliate on user registration.',
      'NOTE_SETTINGS_CANT_BE_REVERTED_ONCE_ENABLED' => 'Note: This setting cannot be reverted once enabled.',
      'NOTE_SETTINGS_NOT_ALLOWED_TO_BE_MODIFIED_ON_DEMO_VERSION' => 'This setting cannot be modified on the demo version.',
    ];
  }

  public static function helpFallback(string $key): string
  {
    return self::helpFallbackMap()[$key] ?? '';
  }
}
