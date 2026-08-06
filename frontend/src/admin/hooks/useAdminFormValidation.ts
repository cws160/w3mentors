import { useEffect } from 'react';

const ERROR_CLASS = 'field-error admin-native-error';
const CONTROL_SELECTOR = 'input, select, textarea';

function isFormControl(element: Element): element is HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement {
  return element instanceof HTMLInputElement || element instanceof HTMLSelectElement || element instanceof HTMLTextAreaElement;
}

function isSkippableControl(control: HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement): boolean {
  return (
    control.disabled ||
    control.readOnly ||
    (control instanceof HTMLInputElement && ['hidden', 'button', 'submit', 'reset'].includes(control.type))
  );
}

function getFieldLabel(control: HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement): string {
  const fieldSet = control.closest('.field-set');
  const label =
    fieldSet?.querySelector('.field_label')?.textContent ??
    control.closest('label')?.textContent ??
    control.getAttribute('aria-label') ??
    control.getAttribute('placeholder') ??
    control.name ??
    'Field';

  return label.replace('*', '').trim() || 'Field';
}

function getErrorText(control: HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement): string {
  const label = getFieldLabel(control);
  const isRtl = control.closest('[dir="rtl"]') !== null;

  if (control.validity.valueMissing) {
    return isRtl ? `Is mandatory ${label}` : `${label} Is mandatory`;
  }

  if (control.validity.typeMismatch) {
    return isRtl ? `Is invalid ${label}` : `${label} Is invalid`;
  }

  if (control.validity.patternMismatch) {
    return isRtl ? `Is invalid ${label}` : `${label} Is invalid`;
  }

  if (control.validity.tooShort) {
    return isRtl ? `Is too short ${label}` : `${label} Is too short`;
  }

  return control.validationMessage || (isRtl ? `Is invalid ${label}` : `${label} Is invalid`);
}

function getErrorHost(control: HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement): HTMLElement {
  return (
    (control.closest('.field_cover') as HTMLElement | null) ??
    (control.closest('.field-wraper') as HTMLElement | null) ??
    control.parentElement ??
    control
  );
}

function removeControlError(control: HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement): void {
  control.classList.remove('error', 'is-invalid');
  const host = getErrorHost(control);
  host.querySelectorAll(':scope > .admin-native-error').forEach((error) => error.remove());
}

function showControlError(control: HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement): void {
  removeControlError(control);
  control.classList.add('error');

  const error = document.createElement('div');
  error.className = ERROR_CLASS;
  error.textContent = getErrorText(control);
  getErrorHost(control).appendChild(error);
}

function getInvalidControls(form: HTMLFormElement): Array<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement> {
  return Array.from(form.querySelectorAll(CONTROL_SELECTOR))
    .filter(isFormControl)
    .filter((control) => !isSkippableControl(control) && !control.validity.valid);
}

export function useAdminFormValidation(): void {
  useEffect(() => {
    const syncAdminForms = () => {
      document.querySelectorAll('body.is-admin form').forEach((form) => {
        if (form instanceof HTMLFormElement) form.noValidate = true;
      });
    };

    const onSubmit = (event: SubmitEvent) => {
      const form = event.target instanceof HTMLFormElement ? event.target : null;
      if (!form?.closest('body.is-admin')) return;

      form.querySelectorAll(CONTROL_SELECTOR).forEach((element) => {
        if (isFormControl(element)) removeControlError(element);
      });

      const invalidControls = getInvalidControls(form);
      if (invalidControls.length === 0) return;

      event.preventDefault();
      event.stopPropagation();
      invalidControls.forEach(showControlError);
      invalidControls[0]?.focus({ preventScroll: false });
    };

    const onInvalid = (event: Event) => {
      const control = event.target instanceof Element && isFormControl(event.target) ? event.target : null;
      if (!control?.closest('body.is-admin')) return;

      event.preventDefault();
      showControlError(control);
    };

    const onInput = (event: Event) => {
      const control = event.target instanceof Element && isFormControl(event.target) ? event.target : null;
      if (!control?.closest('body.is-admin')) return;
      if (control.validity.valid) removeControlError(control);
    };

    document.addEventListener('submit', onSubmit, true);
    document.addEventListener('invalid', onInvalid, true);
    document.addEventListener('input', onInput, true);
    document.addEventListener('change', onInput, true);

    syncAdminForms();
    const observer = new MutationObserver(syncAdminForms);
    observer.observe(document.body, { childList: true, subtree: true });

    return () => {
      observer.disconnect();
      document.removeEventListener('submit', onSubmit, true);
      document.removeEventListener('invalid', onInvalid, true);
      document.removeEventListener('input', onInput, true);
      document.removeEventListener('change', onInput, true);
    };
  }, []);
}
