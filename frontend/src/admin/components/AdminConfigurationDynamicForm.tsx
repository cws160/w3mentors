import { type FormEvent, useMemo, useState } from 'react';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import {
  ConfigCheckboxField,
  ConfigCheckboxesField,
  ConfigColorField,
  type ConfigFieldHelpNote,
  ConfigRadioField,
  ConfigSectionHeading,
  ConfigSelectField,
  ConfigTextareaField,
  ConfigTextField,
} from './AdminConfigurationFormFields';

export type ConfigurationFieldOption = {
  value: string;
  label?: string;
  label_key?: string;
  label_fallback?: string;
};

export type ConfigurationFormField = {
  name: string;
  type: string;
  label_key: string;
  label_fallback: string;
  help_key?: string;
  help_fallback?: string;
  help_leading_br?: boolean;
  extra_help?: Array<{
    help_key: string;
    help_fallback: string;
    variant?: 'default' | 'danger';
    leading_break?: boolean;
  }>;
  widget?: string;
  value: string | boolean | string[];
  options?: ConfigurationFieldOption[];
  disabled?: boolean;
  required?: boolean;
  password_configured?: boolean;
};

export type ConfigurationFormSection = {
  heading_key: string;
  heading_fallback: string;
  fields: ConfigurationFormField[];
};

export type ConfigurationDynamicForm = {
  form_type: number;
  has_lang_tabs?: boolean;
  lang_tab_mode?: string | null;
  lang_id?: number | null;
  layout_direction?: string;
  sections: ConfigurationFormSection[];
  meta?: Record<string, unknown>;
  pwa_icon_url?: string | null;
};

type AdminConfigurationDynamicFormProps = {
  form: ConfigurationDynamicForm;
  values: Record<string, string | boolean | string[]>;
  onChange: (name: string, value: string | boolean | string[]) => void;
  onSubmit: (event: FormEvent) => void;
  saving: boolean;
  canEdit: boolean;
  message: string | null;
  error: string | null;
  pwaIconUrl?: string | null;
  onPwaIconUpload?: (file: File) => Promise<void>;
  pwaIconUploading?: boolean;
  siteLangId?: number;
};

export function AdminConfigurationDynamicForm({
  form,
  values,
  onChange,
  onSubmit,
  saving,
  canEdit,
  message,
  error,
  pwaIconUrl,
  onPwaIconUpload,
  pwaIconUploading = false,
  siteLangId = 1,
}: AdminConfigurationDynamicFormProps) {
  const { lbl } = useSite();
  const [testEmailMessage, setTestEmailMessage] = useState<string | null>(null);
  const [testEmailError, setTestEmailError] = useState<string | null>(null);
  const [testEmailSending, setTestEmailSending] = useState(false);

  const fieldLabel = (field: ConfigurationFormField) =>
    lbl(field.label_key, field.label_fallback);

  const fieldHelp = (field: ConfigurationFormField) => {
    if (!field.help_key && !field.help_fallback) {
      return undefined;
    }

    return lbl(field.help_key ?? '', field.help_fallback ?? '');
  };

  const fieldExtraHelp = (field: ConfigurationFormField): ConfigFieldHelpNote[] | undefined => {
    if (!field.extra_help?.length) {
      return undefined;
    }

    return field.extra_help.map((note) => ({
      text: lbl(note.help_key, note.help_fallback),
      variant: note.variant,
      leadingBreak: note.leading_break,
    }));
  };

  const selectOptions = (field: ConfigurationFormField) =>
    (field.options ?? []).map((option) => ({
      value: option.value,
      label: option.label ?? lbl(option.label_key ?? '', option.label_fallback ?? option.value),
    }));

  const renderedSections = useMemo(() => form.sections, [form.sections]);
  const siteOwnerEmail = String(form.meta?.site_owner_email ?? '');

  const onTestEmail = async () => {
    setTestEmailMessage(null);
    setTestEmailError(null);
    setTestEmailSending(true);

    try {
      const res = await adminApi.testConfigurationEmail(siteLangId);
      setTestEmailMessage(res.data.message ?? lbl('MSG_Setup_Successful', 'Setup successful'));
    } catch (err: unknown) {
      const apiMessage =
        typeof err === 'object' &&
        err !== null &&
        'response' in err &&
        typeof (err as { response?: { data?: { message?: string } } }).response?.data?.message === 'string'
          ? (err as { response: { data: { message: string } } }).response.data.message
          : lbl('MSG_SOMETHING_WENT_WRONG', 'Something went wrong');
      setTestEmailError(apiMessage);
    } finally {
      setTestEmailSending(false);
    }
  };

  const renderFieldHelp = (field: ConfigurationFormField) => {
    if (field.widget === 'email_test') {
      return (
        <small>
          <a
            href="javascript:void(0)"
            id="testMail-js"
            onClick={() => {
              if (!testEmailSending) {
                void onTestEmail();
              }
            }}
          >
            {lbl('LBL_Click_Here_to_test_email', 'Click here to test email')}
          </a>
          . {lbl('LBL_This_will_send_Test_Email_to_Site_Owner_Email', 'This will send a test email to the site owner email')}
          {siteOwnerEmail ? ` - ${siteOwnerEmail}` : ''}
        </small>
      );
    }

    const help = fieldHelp(field);
    if (!help) {
      return undefined;
    }

    return field.help_leading_br ? (
      <>
        <br />
        <small>{help}</small>
      </>
    ) : (
      help
    );
  };

  return (
    <form
      className="form form_horizontal"
      dir={form.layout_direction === 'rtl' ? 'rtl' : undefined}
      onSubmit={onSubmit}
    >
      {renderedSections.map((section) => (
        <div key={section.heading_key}>
          <ConfigSectionHeading>{lbl(section.heading_key, section.heading_fallback)}</ConfigSectionHeading>
          {section.fields.map((field) => {
            const id = `conf-${field.name.replace(/[^a-zA-Z0-9]+/g, '-')}`;
            const label = fieldLabel(field);
            const help = renderFieldHelp(field);
            const extraHelp = fieldExtraHelp(field);
            const disabled = !canEdit || Boolean(field.disabled);
            const value = values[field.name] ?? field.value;

            switch (field.type) {
              case 'checkbox':
                return (
                  <ConfigCheckboxField
                    key={field.name}
                    id={id}
                    label={label}
                    checked={Boolean(value)}
                    onChange={(checked) => onChange(field.name, checked)}
                    help={help}
                    extraHelp={extraHelp}
                    disabled={disabled}
                  />
                );
              case 'checkboxes':
                return (
                  <ConfigCheckboxesField
                    key={field.name}
                    id={id}
                    label={label}
                    values={Array.isArray(value) ? value.map(String) : []}
                    onChange={(next) => onChange(field.name, next)}
                    options={selectOptions(field)}
                    help={help}
                    extraHelp={extraHelp}
                    disabled={disabled}
                  />
                );
              case 'radio':
                return (
                  <ConfigRadioField
                    key={field.name}
                    id={id}
                    label={label}
                    value={String(value ?? '')}
                    onChange={(next) => onChange(field.name, next)}
                    options={selectOptions(field)}
                    help={help}
                    extraHelp={extraHelp}
                    disabled={disabled}
                  />
                );
              case 'select':
                return (
                  <ConfigSelectField
                    key={field.name}
                    id={id}
                    label={label}
                    value={String(value ?? '')}
                    onChange={(next) => onChange(field.name, next)}
                    options={selectOptions(field)}
                    help={help}
                    extraHelp={extraHelp}
                    disabled={disabled}
                  />
                );
              case 'textarea':
                return (
                  <ConfigTextareaField
                    key={field.name}
                    id={id}
                    label={label}
                    value={String(value ?? '')}
                    onChange={(next) => onChange(field.name, next)}
                    help={help}
                    extraHelp={extraHelp}
                    disabled={disabled}
                    rows={8}
                  />
                );
              case 'color':
                return (
                  <ConfigColorField
                    key={field.name}
                    id={id}
                    label={label}
                    value={String(value ?? '')}
                    onChange={(next) => onChange(field.name, next)}
                    help={help}
                    extraHelp={extraHelp}
                    disabled={disabled}
                    required={field.required}
                  />
                );
              case 'password':
                return (
                  <ConfigTextField
                    key={field.name}
                    id={id}
                    label={label}
                    value={String(value ?? '')}
                    onChange={(next) => onChange(field.name, next)}
                    help={help}
                    extraHelp={extraHelp}
                    disabled={disabled}
                    type="password"
                    placeholder={
                      field.password_configured
                        ? lbl('LBL_PASSWORD_IS_ALREADY_SAVED', 'Password is already saved. Leave blank to keep it.')
                        : undefined
                    }
                  />
                );
              case 'email':
                return (
                  <ConfigTextField
                    key={field.name}
                    id={id}
                    label={label}
                    value={String(value ?? '')}
                    onChange={(next) => onChange(field.name, next)}
                    help={help}
                    extraHelp={extraHelp}
                    disabled={disabled}
                    type="email"
                  />
                );
              case 'integer':
              case 'float':
                return (
                  <ConfigTextField
                    key={field.name}
                    id={id}
                    label={label}
                    value={String(value ?? '')}
                    onChange={(next) => onChange(field.name, next)}
                    help={help}
                    extraHelp={extraHelp}
                    disabled={disabled}
                    required={field.required}
                  />
                );
              default:
                return (
                  <ConfigTextField
                    key={field.name}
                    id={id}
                    label={label}
                    value={String(value ?? '')}
                    onChange={(next) => onChange(field.name, next)}
                    help={help}
                    extraHelp={extraHelp}
                    disabled={disabled}
                    required={field.required}
                  />
                );
            }
          })}
        </div>
      ))}

      {form.form_type === 12 ? (
        <div className="row">
          <div className="col-md-12">
            <div className="field-set">
              <div className="caption-wraper">
                <label className="field_label">{lbl('LBL_App_Icon', 'App icon')}</label>
              </div>
              <div className="field-wraper">
                <div className="field_cover">
                  {pwaIconUrl ? (
                    <img
                      src={pwaIconUrl}
                      alt={lbl('LBL_App_Icon', 'App icon')}
                      style={{ height: 80, border: '1px solid #AAA', marginBottom: 12 }}
                    />
                  ) : null}
                  {canEdit && onPwaIconUpload ? (
                    <input
                      type="file"
                      accept="image/png"
                      disabled={pwaIconUploading}
                      onChange={(event) => {
                        const file = event.target.files?.[0];
                        if (file) {
                          void onPwaIconUpload(file);
                        }
                        event.currentTarget.value = '';
                      }}
                    />
                  ) : null}
                  <small>{lbl('HAF_PWA_App_Icon', 'Upload a PNG icon for the PWA app.')}</small>
                </div>
              </div>
            </div>
          </div>
        </div>
      ) : null}

      {message ? <div className="alert alert-success">{message}</div> : null}
      {testEmailMessage ? <div className="alert alert-success">{testEmailMessage}</div> : null}
      {error ? <div className="alert alert-danger">{error}</div> : null}
      {testEmailError ? <div className="alert alert-danger">{testEmailError}</div> : null}

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
                    {saving ? lbl('LBL_Saving', 'Saving...') : lbl('LBL_SAVE_CHANGES', 'Save changes')}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      ) : null}

      {form.meta?.demo_readonly ? (
        <div className="text--center">
          <span className="spn_must_field">
            {lbl(
              'NOTE_SETTINGS_NOT_ALLOWED_TO_BE_MODIFIED_ON_DEMO_VERSION',
              'Settings not allowed to be modified on demo version',
            )}
          </span>
        </div>
      ) : null}
    </form>
  );
}

export function valuesFromConfigurationForm(form: ConfigurationDynamicForm): Record<string, string | boolean | string[]> {
  const values: Record<string, string | boolean | string[]> = {};
  for (const section of form.sections) {
    for (const field of section.fields) {
      values[field.name] = field.value;
    }
  }
  return values;
}
