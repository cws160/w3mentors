import type { MouseEvent } from 'react';

export function togglePasswordField(
  e: MouseEvent<HTMLAnchorElement>,
  inputName = 'password'
): void {
  const link = e.currentTarget;
  const input = document.querySelector<HTMLInputElement>(`input[name="${inputName}"]`);
  if (!input) return;

  const show = link.getAttribute('data-show-caption') ?? 'Show password';
  const hide = link.getAttribute('data-hide-caption') ?? 'Hide password';

  if (input.type === 'text') {
    input.type = 'password';
    link.textContent = show;
  } else {
    input.type = 'text';
    link.textContent = hide;
  }
}
