import { type FormEvent, useCallback, useEffect, useMemo, useState } from 'react';
import { Link, useLocation, useNavigate, useSearchParams } from 'react-router-dom';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import {
  AdminConfigurationDynamicForm,
  type ConfigurationDynamicForm,
  valuesFromConfigurationForm,
} from '../components/AdminConfigurationDynamicForm';
import {
  AdminConfigurationMediaForm,
  type ConfigurationMediaSlot,
} from '../components/AdminConfigurationMediaForm';
import { useAdminAuth } from '../context/AdminAuthContext';
import { useAdminPageMeta } from '../context/AdminPageMetaContext';
import {
  CONFIG_FORM_GENERAL_SETTINGS,
  CONFIG_FORM_MEDIA_AND_LOGOS,
  CONFIG_FORM_PWA_SETTINGS,
  CONFIG_FORM_THIRD_PARTY_APIS,
  configurationTabHasLangTabs,
  configurationTabLangOnly,
  resolveConfigurationTab,
  usesDynamicConfigurationForm,
  visibleConfigurationTabs,
} from '../config/adminConfigurationTabs';
import {
  ConfigCheckboxField,
  ConfigRadioField,
  ConfigSectionHeading,
  ConfigSelectField,
  ConfigTextareaField,
  ConfigTextField,
} from '../components/AdminConfigurationFormFields';

type GeneralSettingsValues = {
  site_owner_email: string;
  site_phone: string;
  default_lang: string;
  site_currency: string;
  country: string;
  frontend_time_format: string;
  privacy_policy_page: string;
  terms_and_conditions_page: string;
  cookies_button_link: string;
  enable_cookies: boolean;
};

type GeneralLangValues = {
  website_name: string;
  from_name: string;
  address: string;
  cookies_text: string;
};

type GeneralSettingsOptions = {
  languages: Array<{ id: number; name: string }>;
  currencies: Array<{ id: number; label: string }>;
  countries: Array<{ id: number; name: string }>;
  cms_pages: Array<{ id: number; title: string }>;
  time_formats: Array<{ value: string; label_key: string; label_fallback: string }>;
};

type ThirdPartyValues = {
  facebook_app_id: string;
  facebook_app_secret: string;
  apple_client_id: string;
  mailchimp_key: string;
  mailchimp_list_id: string;
  mailchimp_server_prefix: string;
  microsoft_translator_subscription_key: string;
  microsoft_translator_subscription_region: string;
  analytics_property_id: string;
  google_analytics_client_json: string;
  google_analytics_client_json_configured: boolean;
  recaptcha_sitekey: string;
  recaptcha_secretkey: string;
  google_client_json: string;
  google_client_json_configured: boolean;
  google_api_key: string;
  firebase_service_account_json: string;
  firebase_service_account_json_configured: boolean;
  share_this_property_id: string;
  live_chat_code: string;
  live_chat_code_configured: boolean;
  enable_live_chat: boolean;
};

const EMPTY_GENERAL_LANG: GeneralLangValues = {
  website_name: '',
  from_name: '',
  address: '',
  cookies_text: '',
};

const EMPTY_GENERAL: GeneralSettingsValues = {
  site_owner_email: '',
  site_phone: '',
  default_lang: '',
  site_currency: '',
  country: '',
  frontend_time_format: '',
  privacy_policy_page: '',
  terms_and_conditions_page: '',
  cookies_button_link: '',
  enable_cookies: false,
};

const EMPTY_THIRD_PARTY: ThirdPartyValues = {
  facebook_app_id: '',
  facebook_app_secret: '',
  apple_client_id: '',
  mailchimp_key: '',
  mailchimp_list_id: '',
  mailchimp_server_prefix: '',
  microsoft_translator_subscription_key: '',
  microsoft_translator_subscription_region: '',
  analytics_property_id: '',
  google_analytics_client_json: '',
  google_analytics_client_json_configured: false,
  recaptcha_sitekey: '',
  recaptcha_secretkey: '',
  google_client_json: '',
  google_client_json_configured: false,
  google_api_key: '',
  firebase_service_account_json: '',
  firebase_service_account_json_configured: false,
  share_this_property_id: '',
  live_chat_code: '',
  live_chat_code_configured: false,
  enable_live_chat: false,
};

function jsonPlaceholder(configured: boolean, fallback: string): string {
  return configured
    ? 'JSON is already saved. Paste new JSON only if you want to replace it.'
    : fallback;
}

export function AdminConfigurationsPage() {
  const { lbl, modules, langId, languages: siteLanguages } = useSite();
  const { privileges } = useAdminAuth();
  const { setMeta, clearMeta } = useAdminPageMeta();
  const location = useLocation();
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();

  const activeTab = useMemo(
    () => resolveConfigurationTab(searchParams.get('tab'), location.pathname, modules),
    [location.pathname, modules, searchParams],
  );
  const formLangId = Number(searchParams.get('clang') ?? '0') || 0;
  const tabs = useMemo(() => visibleConfigurationTabs(modules), [modules]);
  const canEdit = Boolean(privileges.canEditGeneralSettings);
  const showGeneralLangForm = activeTab === CONFIG_FORM_GENERAL_SETTINGS && formLangId > 0;
  const showInlineLangTabs = configurationTabHasLangTabs(activeTab);
  const langTabsOnly = configurationTabLangOnly(activeTab);
  const effectiveMediaLangId =
    activeTab === CONFIG_FORM_MEDIA_AND_LOGOS
      ? formLangId > 0
        ? formLangId
        : siteLanguages[0]?.id ?? langId
      : formLangId;

  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  const [generalValues, setGeneralValues] = useState<GeneralSettingsValues>(EMPTY_GENERAL);
  const [generalLangValues, setGeneralLangValues] = useState<GeneralLangValues>(EMPTY_GENERAL_LANG);
  const [generalLangDirection, setGeneralLangDirection] = useState<'ltr' | 'rtl'>('ltr');
  const [generalOptions, setGeneralOptions] = useState<GeneralSettingsOptions>({
    languages: [],
    currencies: [],
    countries: [],
    cms_pages: [],
    time_formats: [],
  });
  const [thirdPartyValues, setThirdPartyValues] = useState<ThirdPartyValues>(EMPTY_THIRD_PARTY);
  const [dynamicForm, setDynamicForm] = useState<ConfigurationDynamicForm | null>(null);
  const [dynamicValues, setDynamicValues] = useState<Record<string, string | boolean | string[]>>({});
  const [mediaSlots, setMediaSlots] = useState<ConfigurationMediaSlot[]>([]);
  const [pwaIconUrl, setPwaIconUrl] = useState<string | null>(null);
  const [pwaIconUploading, setPwaIconUploading] = useState(false);

  useEffect(() => {
    setMeta({
      title: lbl('MSG_GENERAL_SETTINGS', 'General settings'),
    });

    let cancelled = false;
    void adminApi.pageText('configurations', langId).then((res) => {
      if (cancelled) {
        return;
      }
      const pageText = res.data.data ?? {};
      setMeta({
        title: pageText.title || lbl('MSG_GENERAL_SETTINGS', 'General settings'),
        summary: pageText.summary,
        warning: pageText.warning,
        recommendations: pageText.recommendations,
        helpingText: pageText.helping_text,
        plangId: pageText.plang_id,
      });
    });

    return () => {
      cancelled = true;
      clearMeta();
    };
  }, [clearMeta, lbl, langId, setMeta]);

  const loadTab = useCallback(() => {
    if (!privileges.canViewGeneralSettings) {
      setLoading(false);
      return;
    }

    setLoading(true);
    setError(null);
    setMessage(null);
    setDynamicForm(null);

    if (activeTab === CONFIG_FORM_MEDIA_AND_LOGOS && formLangId === 0 && siteLanguages[0]?.id) {
      const params = new URLSearchParams();
      params.set('tab', String(activeTab));
      params.set('clang', String(siteLanguages[0].id));
      navigate(`/admin/configurations?${params.toString()}`, { replace: true });
      return;
    }

    let request: Promise<{ data: Record<string, unknown> }>;
    if (activeTab === CONFIG_FORM_GENERAL_SETTINGS && formLangId > 0) {
      request = adminApi.generalSettingsLangForm(formLangId);
    } else if (activeTab === CONFIG_FORM_GENERAL_SETTINGS) {
      request = adminApi.generalSettingsForm(langId);
    } else if (activeTab === CONFIG_FORM_THIRD_PARTY_APIS) {
      request = adminApi.thirdPartyApiSettings();
    } else if (activeTab === CONFIG_FORM_MEDIA_AND_LOGOS) {
      request = adminApi.configurationMedia(effectiveMediaLangId);
    } else if (usesDynamicConfigurationForm(activeTab)) {
      request = adminApi.configurationForm(activeTab, formLangId, langId);
    } else {
      setLoading(false);
      return;
    }

    void request
      .then((res) => {
        if (activeTab === CONFIG_FORM_GENERAL_SETTINGS && formLangId > 0) {
          setGeneralLangValues((res.data.values as GeneralLangValues) ?? EMPTY_GENERAL_LANG);
          setGeneralLangDirection(res.data.layout_direction === 'rtl' ? 'rtl' : 'ltr');
        } else if (activeTab === CONFIG_FORM_GENERAL_SETTINGS) {
          setGeneralValues((res.data.values as GeneralSettingsValues) ?? EMPTY_GENERAL);
          setGeneralOptions(
            (res.data.options as GeneralSettingsOptions) ?? {
              languages: [],
              currencies: [],
              countries: [],
              cms_pages: [],
              time_formats: [],
            },
          );
        } else if (activeTab === CONFIG_FORM_THIRD_PARTY_APIS) {
          setThirdPartyValues((res.data as ThirdPartyValues) ?? EMPTY_THIRD_PARTY);
        } else if (activeTab === CONFIG_FORM_MEDIA_AND_LOGOS) {
          setMediaSlots((res.data.slots as ConfigurationMediaSlot[]) ?? []);
        } else if (usesDynamicConfigurationForm(activeTab)) {
          const form = res.data as ConfigurationDynamicForm;
          setDynamicForm(form);
          setDynamicValues(valuesFromConfigurationForm(form));
          setPwaIconUrl((form.pwa_icon_url as string | null | undefined) ?? null);
        }
      })
      .catch((err: unknown) => {
        const apiMessage =
          typeof err === 'object' &&
          err !== null &&
          'response' in err &&
          typeof (err as { response?: { data?: { message?: string } } }).response?.data?.message === 'string'
            ? (err as { response: { data: { message: string } } }).response.data.message
            : lbl('MSG_SOMETHING_WENT_WRONG', 'Unable to load settings');
        setError(apiMessage);
      })
      .finally(() => setLoading(false));
  }, [
    activeTab,
    effectiveMediaLangId,
    formLangId,
    langId,
    lbl,
    navigate,
    privileges.canViewGeneralSettings,
    siteLanguages,
  ]);

  useEffect(() => {
    loadTab();
  }, [loadTab]);

  const onTabChange = (tabId: number) => {
    navigate(`/admin/configurations?tab=${tabId}`, { replace: true });
  };

  const onFormLangChange = (clang: number) => {
    const params = new URLSearchParams();
    params.set('tab', String(activeTab));
    if (clang > 0) {
      params.set('clang', String(clang));
    }
    navigate(`/admin/configurations?${params.toString()}`, { replace: true });
  };

  const onSubmit = async (event: FormEvent) => {
    event.preventDefault();
    if (!canEdit) {
      return;
    }

    setSaving(true);
    setMessage(null);
    setError(null);

    try {
      if (activeTab === CONFIG_FORM_GENERAL_SETTINGS && formLangId > 0) {
        const res = await adminApi.updateGeneralSettingsLangForm(formLangId, generalLangValues);
        setGeneralLangValues((res.data.form?.values as GeneralLangValues) ?? generalLangValues);
        setMessage(res.data.message ?? lbl('MSG_Setup_Successful', 'Setup successful'));
      } else if (activeTab === CONFIG_FORM_GENERAL_SETTINGS) {
        const res = await adminApi.updateGeneralSettings(generalValues, langId);
        setGeneralValues((res.data.form?.values as GeneralSettingsValues) ?? generalValues);
        setMessage(res.data.message ?? lbl('MSG_Setup_Successful', 'Setup successful'));
      } else if (activeTab === CONFIG_FORM_THIRD_PARTY_APIS) {
        const res = await adminApi.updateThirdPartyApiSettings(thirdPartyValues);
        setThirdPartyValues((res.data.settings as ThirdPartyValues) ?? thirdPartyValues);
        setMessage(res.data.message ?? lbl('LBL_Record_Updated_Successfully', 'Record updated successfully'));
      } else if (usesDynamicConfigurationForm(activeTab)) {
        const res = await adminApi.updateConfigurationForm(
          activeTab,
          { values: dynamicValues, lang_id: formLangId > 0 ? formLangId : undefined },
          langId,
        );
        const form = res.data.form as ConfigurationDynamicForm;
        setDynamicForm(form);
        setDynamicValues(valuesFromConfigurationForm(form));
        setPwaIconUrl((form.pwa_icon_url as string | null | undefined) ?? pwaIconUrl);
        setMessage(res.data.message ?? lbl('MSG_Setup_Successful', 'Setup successful'));
      }
    } catch (err: unknown) {
      const apiMessage =
        typeof err === 'object' &&
        err !== null &&
        'response' in err &&
        typeof (err as { response?: { data?: { message?: string } } }).response?.data?.message === 'string'
          ? (err as { response: { data: { message: string } } }).response.data.message
          : lbl('MSG_SOMETHING_WENT_WRONG', 'Something went wrong');
      setError(apiMessage);
    } finally {
      setSaving(false);
    }
  };

  const onPwaIconUpload = async (file: File) => {
    setPwaIconUploading(true);
    setError(null);
    try {
      const res = await adminApi.uploadPwaIcon(file);
      setPwaIconUrl(res.data.icon_url ?? null);
      setMessage(res.data.message ?? lbl('MSG_UPLOADED_SUCCESSFULLY', 'Uploaded successfully'));
    } catch (err: unknown) {
      const apiMessage =
        typeof err === 'object' &&
        err !== null &&
        'response' in err &&
        typeof (err as { response?: { data?: { message?: string } } }).response?.data?.message === 'string'
          ? (err as { response: { data: { message: string } } }).response.data.message
          : lbl('MSG_SOMETHING_WENT_WRONG', 'Something went wrong');
      setError(apiMessage);
    } finally {
      setPwaIconUploading(false);
    }
  };

  if (!privileges.canViewGeneralSettings) {
    return (
      <main className="main">
        <div className="container">
          <div className="card">
            <div className="card-body">{lbl('MSG_UNAUTHORIZED_ACCESS!', 'Unauthorized access')}</div>
          </div>
        </div>
      </main>
    );
  }

  const cmsOptions = generalOptions.cms_pages.map((page) => ({
    value: String(page.id),
    label: page.title,
  }));

  return (
    <main className="main admin-configurations-page">
      <div className="container">
        <div className="breadcrumb-wrap">
          <ul className="breadcrumb">
            <li className="breadcrumb-item">
              <Link to="/admin">{lbl('LBL_Home', 'Home')}</Link>
            </li>
            <li className="breadcrumb-item">{lbl('LBL_CONFIGURATIONS', 'Configurations')}</li>
          </ul>
        </div>

        <div className="grid-layout">
          <div className="grid-layout-left">
            <div className="card card-sticky">
              <nav className="tab tab-vertical tabs-nav-js">
                <ul>
                  {tabs.map((tab) => (
                    <li key={tab.id}>
                      <a
                        href="javascript:void(0)"
                        className={activeTab === tab.id ? 'active' : ''}
                        onClick={() => onTabChange(tab.id)}
                      >
                        {lbl(tab.labelKey, tab.labelFallback)}
                      </a>
                    </li>
                  ))}
                </ul>
              </nav>
            </div>
          </div>

          <div className="grid-layout-right">
            <div className="card">
              {showInlineLangTabs ? (
                <div className="card-head py-0 overflow-auto">
                  <nav className="tab tab-inline">
                    <ul style={{ whiteSpace: 'nowrap' }}>
                      {!langTabsOnly ? (
                        <li>
                          <a
                            href="javascript:void(0)"
                            className={formLangId === 0 ? 'active' : ''}
                            onClick={() => onFormLangChange(0)}
                          >
                            Basic
                          </a>
                        </li>
                      ) : null}
                      {siteLanguages.map((language) => (
                        <li key={language.id}>
                          <a
                            href="javascript:void(0)"
                            className={
                              (langTabsOnly ? effectiveMediaLangId : formLangId) === language.id ? 'active' : ''
                            }
                            onClick={() => onFormLangChange(language.id)}
                          >
                            {language.name}
                          </a>
                        </li>
                      ))}
                    </ul>
                  </nav>
                </div>
              ) : null}
              {loading ? (
                <div className="card-body">
                  <div className="table-processing loaderJs">
                    <div className="spinner spinner--sm spinner--brand" />
                  </div>
                </div>
              ) : activeTab === CONFIG_FORM_MEDIA_AND_LOGOS ? (
                <AdminConfigurationMediaForm
                  langId={effectiveMediaLangId}
                  slots={mediaSlots}
                  canEdit={canEdit}
                  onUpdated={setMediaSlots}
                />
              ) : usesDynamicConfigurationForm(activeTab) ? (
                <div className="card-body">
                  {dynamicForm ? (
                    <AdminConfigurationDynamicForm
                      form={dynamicForm}
                      values={dynamicValues}
                      onChange={(name, value) => setDynamicValues((prev) => ({ ...prev, [name]: value }))}
                      onSubmit={onSubmit}
                      saving={saving}
                      canEdit={canEdit}
                      message={message}
                      error={error}
                      pwaIconUrl={pwaIconUrl}
                      onPwaIconUpload={activeTab === CONFIG_FORM_PWA_SETTINGS ? onPwaIconUpload : undefined}
                      pwaIconUploading={pwaIconUploading}
                      siteLangId={langId}
                    />
                  ) : (
                    <div className="alert alert-danger">
                      {error ?? lbl('MSG_SOMETHING_WENT_WRONG', 'Unable to load settings')}
                    </div>
                  )}
                </div>
              ) : (
                <div className="card-body">
                  <form
                    className="form form_horizontal"
                    dir={showGeneralLangForm ? generalLangDirection : undefined}
                    onSubmit={onSubmit}
                  >
                    {activeTab === CONFIG_FORM_GENERAL_SETTINGS && !showGeneralLangForm ? (
                      <>
                        <ConfigTextField
                          id="conf-site-owner-email"
                          label={lbl('LBL_Site_Owner_Email', 'Site owner email')}
                          value={generalValues.site_owner_email}
                          onChange={(value) => setGeneralValues((prev) => ({ ...prev, site_owner_email: value }))}
                          help={lbl(
                            'HAF_SITE_OWNER_EMAIL',
                            'All the system emails intended for the site owner are sent to this email address.',
                          )}
                          disabled={!canEdit}
                          type="email"
                          required
                        />
                        <ConfigTextField
                          id="conf-site-phone"
                          label={lbl('LBL_Telephone_Number', 'Telephone number')}
                          value={generalValues.site_phone}
                          onChange={(value) => setGeneralValues((prev) => ({ ...prev, site_phone: value }))}
                          help={lbl(
                            'HAF_TELEPHONE_NUMBER',
                            'Support contact number to be displayed on the platform footer.',
                          )}
                          disabled={!canEdit}
                        />
                        <ConfigSelectField
                          id="conf-default-lang"
                          label={lbl('LBL_Site_Language', 'Site language')}
                          value={generalValues.default_lang}
                          onChange={(value) => setGeneralValues((prev) => ({ ...prev, default_lang: value }))}
                          options={generalOptions.languages.map((row) => ({
                            value: String(row.id),
                            label: row.name,
                          }))}
                          help={lbl('HAF_SITE_LANGUAGE', 'Default language of the display throughout the site.')}
                          disabled={!canEdit}
                        />
                        <ConfigSelectField
                          id="conf-site-currency"
                          label={lbl('LBL_Site_Currency', 'Site currency')}
                          value={generalValues.site_currency}
                          onChange={(value) => setGeneralValues((prev) => ({ ...prev, site_currency: value }))}
                          options={generalOptions.currencies.map((row) => ({
                            value: String(row.id),
                            label: row.label,
                          }))}
                          help={lbl('HAF_SITE_CURRENCY', 'Default currency of display throughout the site.')}
                          disabled={!canEdit}
                        />
                        <ConfigSelectField
                          id="conf-country"
                          label={lbl('LBL_Site_Country', 'Site country')}
                          value={generalValues.country}
                          onChange={(value) => setGeneralValues((prev) => ({ ...prev, country: value }))}
                          options={generalOptions.countries.map((row) => ({
                            value: String(row.id),
                            label: row.name,
                          }))}
                          help={lbl('HAF_SITE_COUNTRY', 'Country where the business is based.')}
                          disabled={!canEdit}
                        />
                        <ConfigSelectField
                          id="conf-time-format"
                          label={lbl('LBL_Site_TIME_FORMAT', 'Site time format')}
                          value={generalValues.frontend_time_format}
                          onChange={(value) => setGeneralValues((prev) => ({ ...prev, frontend_time_format: value }))}
                          options={generalOptions.time_formats.map((row) => ({
                            value: row.value,
                            label: lbl(row.label_key, row.label_fallback),
                          }))}
                          help={lbl('HAF_SYSTEM_TIME_FORMAT', 'System time format')}
                          disabled={!canEdit}
                        />
                        <ConfigSelectField
                          id="conf-privacy-page"
                          label={lbl('LBL_Privacy_Policy', 'Privacy policy')}
                          value={generalValues.privacy_policy_page}
                          onChange={(value) => setGeneralValues((prev) => ({ ...prev, privacy_policy_page: value }))}
                          options={cmsOptions}
                          help={lbl(
                            'HAF_PRIVACY_POLICY',
                            'Selected CMS page is linked for Privacy policy details on the Login and Signup forms.',
                          )}
                          disabled={!canEdit}
                        />
                        <ConfigSelectField
                          id="conf-terms-page"
                          label={lbl('LBL_Terms_&_Conditions', 'Terms & conditions')}
                          value={generalValues.terms_and_conditions_page}
                          onChange={(value) =>
                            setGeneralValues((prev) => ({ ...prev, terms_and_conditions_page: value }))
                          }
                          options={cmsOptions}
                          help={lbl(
                            'HAF_TERMS_&_CONDITIONS',
                            'Selected CMS page is linked for Terms and conditions details on the Login and Signup forms.',
                          )}
                          disabled={!canEdit}
                        />
                        <ConfigSelectField
                          id="conf-cookies-page"
                          label={lbl('LBL_COOKIES_POLICIES', 'Cookies policies')}
                          value={generalValues.cookies_button_link}
                          onChange={(value) => setGeneralValues((prev) => ({ ...prev, cookies_button_link: value }))}
                          options={cmsOptions}
                          help={lbl(
                            'HAF_COOKIES_POLICIES',
                            'Selected CMS page is linked for cookies policy details in the cookies content box.',
                          )}
                          disabled={!canEdit}
                        />
                        <ConfigCheckboxField
                          id="conf-enable-cookies"
                          label={lbl('LBL_COOKIES_POLICIES_NOTICE', 'Cookies policies')}
                          checked={generalValues.enable_cookies}
                          onChange={(checked) => setGeneralValues((prev) => ({ ...prev, enable_cookies: checked }))}
                          help={lbl(
                            'LBL_COOKIES_POLICIES_SECTION_WILL_BE_SHOWN_ON_FRONTEND',
                            'Select to display cookies policies prompt on the front-end.',
                          )}
                          disabled={!canEdit}
                        />
                      </>
                    ) : null}

                    {activeTab === CONFIG_FORM_GENERAL_SETTINGS && showGeneralLangForm ? (
                      <>
                        <ConfigTextField
                          id="conf-website-name"
                          label={lbl('LBL_Site_Name', 'Site name')}
                          value={generalLangValues.website_name}
                          onChange={(value) => setGeneralLangValues((prev) => ({ ...prev, website_name: value }))}
                          help={lbl('HAF_SITE_NAME', "Site's name displayed on the platform footer.")}
                          disabled={!canEdit}
                        />
                        <ConfigTextField
                          id="conf-from-name"
                          label={lbl('LBL_EMAIL_FROM_NAME', 'Email from name')}
                          value={generalLangValues.from_name}
                          onChange={(value) => setGeneralLangValues((prev) => ({ ...prev, from_name: value }))}
                          help={lbl(
                            'HAF_EMAIL_FROM_NAME',
                            'Name displayed as the sender on system-generated emails.',
                          )}
                          disabled={!canEdit}
                        />
                        <ConfigTextareaField
                          id="conf-address"
                          label={lbl('LBL_ADDRESS', 'Address')}
                          value={generalLangValues.address}
                          onChange={(value) => setGeneralLangValues((prev) => ({ ...prev, address: value }))}
                          help={lbl('HAF_ADDRESS', 'Business address displayed on the front-end footer.')}
                          disabled={!canEdit}
                          rows={4}
                        />
                        <ConfigTextareaField
                          id="conf-cookies-text"
                          label={lbl('LBL_COOKIES_POLICIES_TEXT', 'Cookies policies')}
                          value={generalLangValues.cookies_text}
                          onChange={(value) => setGeneralLangValues((prev) => ({ ...prev, cookies_text: value }))}
                          help={lbl(
                            'HAF_COOKIES_POLICIES_TEXT',
                            'Text displayed in the cookies permission prompt on the front-end.',
                          )}
                          disabled={!canEdit}
                          rows={4}
                        />
                      </>
                    ) : null}

                    {activeTab === CONFIG_FORM_THIRD_PARTY_APIS ? (
                      <>
                        <ConfigSectionHeading>{lbl('MSG_LIVE_CHAT', 'Live chat')}</ConfigSectionHeading>
                        <ConfigTextareaField
                          id="conf-live-chat-code"
                          label={lbl('LBL_LIVE_CHAT_CODE', 'Live chat code')}
                          value={thirdPartyValues.live_chat_code}
                          onChange={(value) => setThirdPartyValues((prev) => ({ ...prev, live_chat_code: value }))}
                          help={lbl(
                            'LBL_THIS_IS_THE_LIVE_CHAT_SCRIPT/CODE_PROVIDED_BY_THE_3RD_PARTY_API_FOR_INTEGRATION.',
                            'Live chat script/code provided by the 3rd party API for integration.',
                          )}
                          placeholder={jsonPlaceholder(
                            thirdPartyValues.live_chat_code_configured,
                            'Paste the live chat embed script here',
                          )}
                          disabled={!canEdit}
                          rows={6}
                        />
                        <ConfigRadioField
                          id="conf-enable-live-chat"
                          label={lbl('LBL_ACTIVATE_LIVE_CHAT', 'Activate live chat')}
                          value={thirdPartyValues.enable_live_chat ? '1' : '0'}
                          onChange={(value) =>
                            setThirdPartyValues((prev) => ({ ...prev, enable_live_chat: value === '1' }))
                          }
                          options={[
                            { value: '1', label: lbl('LBL_YES', 'Yes') },
                            { value: '0', label: lbl('LBL_NO', 'No') },
                          ]}
                          help={lbl(
                            'LBL_ACTIVATE_3RD_PARTY_LIVE_CHAT.',
                            'Automated live chat functionality is available on the front-end only when this setting is enabled.',
                          )}
                          disabled={!canEdit}
                        />

                        <ConfigSectionHeading>{lbl('LBL_FACEBOOK_LOGIN', 'Facebook login')}</ConfigSectionHeading>
                        <ConfigTextField
                          id="conf-facebook-app-id"
                          label={lbl('LBL_Facebook_APP_ID', 'Facebook app ID')}
                          value={thirdPartyValues.facebook_app_id}
                          onChange={(value) => setThirdPartyValues((prev) => ({ ...prev, facebook_app_id: value }))}
                          disabled={!canEdit}
                        />
                        <ConfigTextField
                          id="conf-facebook-app-secret"
                          label={lbl('LBL_Facebook_App_Secret', 'Facebook app secret')}
                          value={thirdPartyValues.facebook_app_secret}
                          onChange={(value) => setThirdPartyValues((prev) => ({ ...prev, facebook_app_secret: value }))}
                          disabled={!canEdit}
                        />

                        <ConfigSectionHeading>{lbl('LBL_APPLE_LOGIN', 'Apple login')}</ConfigSectionHeading>
                        <ConfigTextField
                          id="conf-apple-client-id"
                          label={lbl('LBL_APPLE_CLIENT_ID', 'Apple client ID')}
                          value={thirdPartyValues.apple_client_id}
                          onChange={(value) => setThirdPartyValues((prev) => ({ ...prev, apple_client_id: value }))}
                          disabled={!canEdit}
                        />

                        <ConfigSectionHeading>
                          {lbl('LBL_NEWSLETTER_SUBSCRIPTION', 'Newsletter subscription')}
                        </ConfigSectionHeading>
                        <ConfigTextField
                          id="conf-mailchimp-key"
                          label={lbl('LBL_Mailchimp_Key', 'Mailchimp key')}
                          value={thirdPartyValues.mailchimp_key}
                          onChange={(value) => setThirdPartyValues((prev) => ({ ...prev, mailchimp_key: value }))}
                          disabled={!canEdit}
                        />
                        <ConfigTextField
                          id="conf-mailchimp-list-id"
                          label={lbl('LBL_Mailchimp_List_ID', 'Mailchimp list ID')}
                          value={thirdPartyValues.mailchimp_list_id}
                          onChange={(value) => setThirdPartyValues((prev) => ({ ...prev, mailchimp_list_id: value }))}
                          disabled={!canEdit}
                        />
                        <ConfigTextField
                          id="conf-mailchimp-server-prefix"
                          label={lbl('LBL_MAILCHIMP_SERVER_PREFIX', 'Mailchimp server prefix')}
                          value={thirdPartyValues.mailchimp_server_prefix}
                          onChange={(value) =>
                            setThirdPartyValues((prev) => ({ ...prev, mailchimp_server_prefix: value }))
                          }
                          disabled={!canEdit}
                        />

                        <ConfigSectionHeading>
                          {lbl('LBL_MICROSOFT_TEXT_TRANSLATOR', 'Microsoft text translator')}
                        </ConfigSectionHeading>
                        <ConfigTextField
                          id="conf-ms-translator-key"
                          label={lbl('LBL_SUBSCRIPTION_KEY', 'Subscription key')}
                          value={thirdPartyValues.microsoft_translator_subscription_key}
                          onChange={(value) =>
                            setThirdPartyValues((prev) => ({ ...prev, microsoft_translator_subscription_key: value }))
                          }
                          disabled={!canEdit}
                        />
                        <ConfigTextField
                          id="conf-ms-translator-region"
                          label={lbl('LBL_SUBSCRIPTION_REGION', 'Subscription region')}
                          value={thirdPartyValues.microsoft_translator_subscription_region}
                          onChange={(value) =>
                            setThirdPartyValues((prev) => ({ ...prev, microsoft_translator_subscription_region: value }))
                          }
                          disabled={!canEdit}
                        />

                        <ConfigSectionHeading>{lbl('LBL_Google_Analytics', 'Google Analytics')}</ConfigSectionHeading>
                        <ConfigTextField
                          id="conf-ga-property-id"
                          label={lbl('LBL_GOOGLE_ANALYTICS_PROPERTY_ID', 'Google Analytics property ID')}
                          value={thirdPartyValues.analytics_property_id}
                          onChange={(value) =>
                            setThirdPartyValues((prev) => ({ ...prev, analytics_property_id: value }))
                          }
                          disabled={!canEdit}
                        />
                        <ConfigTextareaField
                          id="conf-ga-client-json"
                          label={lbl('LBL_GOOGLE_ANALYTICS_CLIENT_JSON', 'Google Analytics client JSON')}
                          value={thirdPartyValues.google_analytics_client_json}
                          onChange={(value) =>
                            setThirdPartyValues((prev) => ({ ...prev, google_analytics_client_json: value }))
                          }
                          placeholder={jsonPlaceholder(
                            thirdPartyValues.google_analytics_client_json_configured,
                            'Paste the full service account JSON here',
                          )}
                          disabled={!canEdit}
                          rows={8}
                        />

                        <ConfigSectionHeading>{lbl('LBL_Google_Recaptcha', 'Google reCAPTCHA')}</ConfigSectionHeading>
                        <ConfigTextField
                          id="conf-recaptcha-sitekey"
                          label={lbl('LBL_Site_Key', 'Site key')}
                          value={thirdPartyValues.recaptcha_sitekey}
                          onChange={(value) => setThirdPartyValues((prev) => ({ ...prev, recaptcha_sitekey: value }))}
                          disabled={!canEdit}
                        />
                        <ConfigTextField
                          id="conf-recaptcha-secretkey"
                          label={lbl('LBL_Secret_Key', 'Secret key')}
                          value={thirdPartyValues.recaptcha_secretkey}
                          onChange={(value) => setThirdPartyValues((prev) => ({ ...prev, recaptcha_secretkey: value }))}
                          disabled={!canEdit}
                        />

                        <ConfigSectionHeading>{lbl('LBL_Google_Client_Json', 'Google client JSON')}</ConfigSectionHeading>
                        <ConfigTextareaField
                          id="conf-google-client-json"
                          label={lbl('LBL_Google_Client_Json', 'Google client JSON')}
                          value={thirdPartyValues.google_client_json}
                          onChange={(value) => setThirdPartyValues((prev) => ({ ...prev, google_client_json: value }))}
                          placeholder={jsonPlaceholder(
                            thirdPartyValues.google_client_json_configured,
                            'Paste the Google client JSON here',
                          )}
                          disabled={!canEdit}
                          rows={8}
                        />

                        <ConfigSectionHeading>{lbl('LBL_Google_Api_Key', 'Google API key')}</ConfigSectionHeading>
                        <ConfigTextField
                          id="conf-google-api-key"
                          label={lbl('LBL_Google_Api_Key', 'Google API key')}
                          value={thirdPartyValues.google_api_key}
                          onChange={(value) => setThirdPartyValues((prev) => ({ ...prev, google_api_key: value }))}
                          disabled={!canEdit}
                        />

                        <ConfigSectionHeading>
                          {lbl('LBL_FIREBASE_CONFIGURATION', 'Firebase configuration')}
                        </ConfigSectionHeading>
                        <ConfigTextareaField
                          id="conf-firebase-json"
                          label={lbl('LBL_SERVICE_ACCOUNT_JSON_FOR_FIREBASE', 'Service account JSON for Firebase')}
                          value={thirdPartyValues.firebase_service_account_json}
                          onChange={(value) =>
                            setThirdPartyValues((prev) => ({ ...prev, firebase_service_account_json: value }))
                          }
                          placeholder={jsonPlaceholder(
                            thirdPartyValues.firebase_service_account_json_configured,
                            'Paste the Firebase service account JSON here',
                          )}
                          disabled={!canEdit}
                          rows={8}
                        />

                        <ConfigSectionHeading>{lbl('LBL_SHARETHIS_PLUGIN', 'ShareThis plugin')}</ConfigSectionHeading>
                        <ConfigTextField
                          id="conf-sharethis-property-id"
                          label={lbl('LBL_SHARE_THIS_PLUGIN_PROPERTY_ID', 'ShareThis plugin property ID')}
                          value={thirdPartyValues.share_this_property_id}
                          onChange={(value) =>
                            setThirdPartyValues((prev) => ({ ...prev, share_this_property_id: value }))
                          }
                          disabled={!canEdit}
                        />
                      </>
                    ) : null}

                    {message ? <div className="alert alert-success">{message}</div> : null}
                    {error ? <div className="alert alert-danger">{error}</div> : null}

                    {canEdit ? (
                      <div className="row">
                        <div className="col-md-12">
                          <div className="field-set">
                            <div className="caption-wraper">
                              <label className="field_label" />
                            </div>
                            <div className="field-wraper form-buttons-group">
                              <div className="field_cover">
                                <button type="submit" className="btn btn-brand" disabled={saving}>
                                  {saving
                                    ? lbl('LBL_Saving', 'Saving...')
                                    : lbl('LBL_SAVE_CHANGES', 'Save changes')}
                                </button>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    ) : null}
                  </form>
                </div>
              )}
            </div>
          </div>
        </div>
      </div>
    </main>
  );
}
