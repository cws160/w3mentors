import type { ReactNode } from 'react';

export function ConfigSectionHeading({ children }: { children: ReactNode }) {
  return <h3 className="form__heading">{children}</h3>;
}

export type ConfigFieldHelpNote = {
  text: string;
  variant?: 'default' | 'danger';
  leadingBreak?: boolean;
};

type ConfigHorizontalRowProps = {
  label: ReactNode;
  htmlFor?: string;
  help?: ReactNode;
  extraHelp?: ConfigFieldHelpNote[];
  children: ReactNode;
};

export function ConfigHorizontalRow({ label, htmlFor, help, extraHelp, children }: ConfigHorizontalRowProps) {
  const hasLabel = label !== '' && label !== null && label !== undefined;

  return (
    <div className="row">
      <div className="col-md-12">
        <div className="field-set">
          <div className="caption-wraper">
            <label className="field_label" {...(hasLabel && htmlFor ? { htmlFor } : {})}>
              {label}
            </label>
          </div>
          <div className="field-wraper">
            <div className="field_cover">
              {children}
              {help ? <small className="field-help-text">{help}</small> : null}
              {extraHelp?.map((note) => (
                <small
                  key={note.text}
                  className="field-help-text"
                  style={note.variant === 'danger' ? { color: 'var(--bs-red)' } : undefined}
                >
                  {note.leadingBreak ? <br /> : null}
                  {note.text}
                </small>
              ))}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

type ConfigTextFieldProps = {
  id: string;
  label: string;
  value: string;
  onChange: (value: string) => void;
  help?: ReactNode;
  extraHelp?: ConfigFieldHelpNote[];
  disabled?: boolean;
  type?: 'text' | 'email' | 'password';
  required?: boolean;
  placeholder?: string;
};

export function ConfigTextField({
  id,
  label,
  value,
  onChange,
  help,
  extraHelp,
  disabled,
  type = 'text',
  required = false,
  placeholder,
}: ConfigTextFieldProps) {
  return (
    <ConfigHorizontalRow
      label={
        <>
          {label}
          {required ? <span className="spn_must_field">*</span> : null}
        </>
      }
      htmlFor={id}
      help={help}
      extraHelp={extraHelp}
    >
      <input
        id={id}
        type={type}
        className="form-control"
        value={value}
        placeholder={placeholder}
        onChange={(e) => onChange(e.target.value)}
        disabled={disabled}
      />
    </ConfigHorizontalRow>
  );
}

type ConfigTextareaFieldProps = {
  id: string;
  label: string;
  value: string;
  onChange: (value: string) => void;
  help?: ReactNode;
  extraHelp?: ConfigFieldHelpNote[];
  disabled?: boolean;
  rows?: number;
  placeholder?: string;
};

export function ConfigTextareaField({
  id,
  label,
  value,
  onChange,
  help,
  extraHelp,
  disabled,
  rows = 8,
  placeholder,
}: ConfigTextareaFieldProps) {
  return (
    <ConfigHorizontalRow label={label} htmlFor={id} help={help} extraHelp={extraHelp}>
      <textarea
        id={id}
        className="form-control"
        rows={rows}
        value={value}
        placeholder={placeholder}
        onChange={(e) => onChange(e.target.value)}
        disabled={disabled}
      />
    </ConfigHorizontalRow>
  );
}

type SelectOption = { value: string; label: string };

type ConfigSelectFieldProps = {
  id: string;
  label: string;
  value: string;
  onChange: (value: string) => void;
  options: SelectOption[];
  help?: ReactNode;
  extraHelp?: ConfigFieldHelpNote[];
  disabled?: boolean;
};

export function ConfigSelectField({
  id,
  label,
  value,
  onChange,
  options,
  help,
  extraHelp,
  disabled,
}: ConfigSelectFieldProps) {
  return (
    <ConfigHorizontalRow label={label} htmlFor={id} help={help} extraHelp={extraHelp}>
      <select
        id={id}
        className="form-control"
        value={value}
        onChange={(e) => onChange(e.target.value)}
        disabled={disabled}
      >
        <option value="" />
        {options.map((option) => (
          <option key={option.value} value={option.value}>
            {option.label}
          </option>
        ))}
      </select>
    </ConfigHorizontalRow>
  );
}

type ConfigCheckboxFieldProps = {
  id: string;
  label: string;
  checked: boolean;
  onChange: (checked: boolean) => void;
  help?: ReactNode;
  extraHelp?: ConfigFieldHelpNote[];
  disabled?: boolean;
};

export function ConfigCheckboxField({
  id,
  label,
  checked,
  onChange,
  help,
  extraHelp,
  disabled,
}: ConfigCheckboxFieldProps) {
  return (
    <ConfigHorizontalRow label="" help={help} extraHelp={extraHelp}>
      <label htmlFor={id}>
        <span className="checkbox">
          <input
            id={id}
            type="checkbox"
            checked={checked}
            onChange={(e) => onChange(e.target.checked)}
            disabled={disabled}
          />
        </span>
        {label}
      </label>
    </ConfigHorizontalRow>
  );
}

type ConfigRadioFieldProps = {
  id: string;
  label: string;
  value: string;
  onChange: (value: string) => void;
  options: SelectOption[];
  help?: ReactNode;
  extraHelp?: ConfigFieldHelpNote[];
  disabled?: boolean;
};

export function ConfigRadioField({
  id,
  label,
  value,
  onChange,
  options,
  help,
  extraHelp,
  disabled,
}: ConfigRadioFieldProps) {
  return (
    <ConfigHorizontalRow label={label} help={help} extraHelp={extraHelp}>
      <ul className="list-inline">
        {options.map((option) => (
          <li key={option.value} className="list-inline-item">
            <label htmlFor={`${id}-${option.value}`}>
              <span className="radio">
                <input
                  id={`${id}-${option.value}`}
                  type="radio"
                  name={id}
                  value={option.value}
                  checked={value === option.value}
                  onChange={() => onChange(option.value)}
                  disabled={disabled}
                />
              </span>
              {option.label}
            </label>
          </li>
        ))}
      </ul>
    </ConfigHorizontalRow>
  );
}

type ConfigCheckboxesFieldProps = {
  id: string;
  label: string;
  values: string[];
  onChange: (values: string[]) => void;
  options: SelectOption[];
  help?: ReactNode;
  extraHelp?: ConfigFieldHelpNote[];
  disabled?: boolean;
};

export function ConfigCheckboxesField({
  id,
  label,
  values,
  onChange,
  options,
  help,
  extraHelp,
  disabled,
}: ConfigCheckboxesFieldProps) {
  const toggle = (optionValue: string, checked: boolean) => {
    if (checked) {
      onChange([...values, optionValue]);
      return;
    }
    onChange(values.filter((value) => value !== optionValue));
  };

  return (
    <ConfigHorizontalRow label={label} help={help} extraHelp={extraHelp}>
      <ul className="list-inline form__list--check">
        {options.map((option) => (
          <li key={option.value} className="list-inline-item">
            <label htmlFor={`${id}-${option.value}`}>
              <span className="checkbox">
                <input
                  id={`${id}-${option.value}`}
                  type="checkbox"
                  checked={values.includes(option.value)}
                  onChange={(e) => toggle(option.value, e.target.checked)}
                  disabled={disabled}
                />
              </span>
              {option.label}
            </label>
          </li>
        ))}
      </ul>
    </ConfigHorizontalRow>
  );
}

type ConfigColorFieldProps = {
  id: string;
  label: string;
  value: string;
  onChange: (value: string) => void;
  help?: ReactNode;
  extraHelp?: ConfigFieldHelpNote[];
  disabled?: boolean;
  required?: boolean;
};

export function ConfigColorField({
  id,
  label,
  value,
  onChange,
  help,
  extraHelp,
  disabled,
  required = false,
}: ConfigColorFieldProps) {
  return (
    <ConfigHorizontalRow
      label={
        <>
          {label}
          {required ? <span className="spn_must_field">*</span> : null}
        </>
      }
      htmlFor={id}
      help={help}
      extraHelp={extraHelp}
    >
      <input
        id={id}
        type="color"
        className="form-control"
        value={value || '#000000'}
        onChange={(e) => onChange(e.target.value)}
        disabled={disabled}
      />
    </ConfigHorizontalRow>
  );
}
