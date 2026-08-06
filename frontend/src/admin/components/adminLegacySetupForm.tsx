import type { ReactNode } from 'react';

type LegacySetupField = {
  name: string;
  type: string;
  label_key: string;
  value: string | number | boolean;
  placeholder: string;
  helptext: string;
  options: Record<string, string> | Array<string | number | { value?: string; label?: string }>;
};

const SETUP_LABEL_FALLBACKS: Record<string, string> = {
  LBL_api_key: 'API Key',
  LBL_api_id: 'API ID',
  LBL_Recording: 'Recording',
  LBL_Organisation: 'Organisation',
  LBL_chat_auth: 'Chat Auth',
};

/** Matches manager form.php info blocks (`htmlBeforeField` on submit). */
export function renderLegacySetupInfoHtml(info: string): string {
  const trimmed = info.trim();
  if (!trimmed) {
    return '';
  }
  if (/<[a-z][\s\S]*>/i.test(trimmed)) {
    return trimmed;
  }

  return trimmed.replace(/\r?\n/g, '<br>');
}

export function legacySetupFieldLabel(
  field: Pick<LegacySetupField, 'name' | 'label_key' | 'placeholder' | 'helptext'>,
  lbl: (key: string, fallback?: string) => string,
): string {
  if (field.label_key) {
    const fallback =
      SETUP_LABEL_FALLBACKS[field.label_key] ??
      (field.placeholder || field.helptext || humanizeFieldName(field.name));
    return lbl(field.label_key, fallback);
  }

  return field.placeholder || field.helptext || humanizeFieldName(field.name);
}

export function legacySetupSelectOptions(
  field: Pick<LegacySetupField, 'options'>,
): Array<{ value: string; label: string }> {
  if (Array.isArray(field.options)) {
    return field.options.map((opt, index) => {
      if (typeof opt === 'string' || typeof opt === 'number') {
        return { value: String(index), label: String(opt) };
      }

      return {
        value: String(opt.value ?? index),
        label: String(opt.label ?? opt.value ?? index),
      };
    });
  }

  return Object.entries(field.options ?? {}).map(([value, label]) => ({
    value,
    label: String(label),
  }));
}

export function legacySetupFieldRequired(field: Pick<LegacySetupField, 'type'>): boolean {
  return field.type === 'text' || field.type === 'textarea';
}

function humanizeFieldName(name: string): string {
  return name
    .split('_')
    .filter(Boolean)
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join(' ');
}

type LegacySetupFormBodyProps = {
  loading?: boolean;
  children: ReactNode;
};

/** Matches legacy popup `form-edit-body` wrapper. */
export function AdminLegacySetupFormBody({
  loading = false,
  children,
}: LegacySetupFormBodyProps) {
  return (
    <div className="form-edit-body">
      {loading ? (
        <div className="table-processing loaderJs p-5">
          <div className="spinner spinner--sm spinner--brand" />
        </div>
      ) : (
        children
      )}
    </div>
  );
}

type LegacySetupInfoProps = {
  info: string;
};

export function AdminLegacySetupInfo({ info }: LegacySetupInfoProps) {
  const html = renderLegacySetupInfoHtml(info);
  if (!html) {
    return null;
  }

  return <p dangerouslySetInnerHTML={{ __html: `${html}<br>` }} />;
}

export type { LegacySetupField };
