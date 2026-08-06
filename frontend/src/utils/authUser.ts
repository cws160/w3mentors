import type { User } from '../api/client';

/** Unwrap Laravel JsonResource `{ data: { ... } }` and validate shape. */
export function normalizeUser(raw: unknown): User | null {
  if (!raw || typeof raw !== 'object') {
    return null;
  }

  const record = raw as Record<string, unknown>;
  const inner =
    record.data && typeof record.data === 'object'
      ? (record.data as Record<string, unknown>)
      : record;

  const id = Number(inner.id);
  const firstName = String(inner.first_name ?? inner.full_name ?? 'User').trim();

  if (!Number.isFinite(id) || id <= 0) {
    return null;
  }

  return {
    id,
    first_name: firstName,
    last_name: inner.last_name ? String(inner.last_name) : undefined,
    full_name: String(inner.full_name ?? firstName).trim(),
    email: String(inner.email ?? ''),
    is_teacher: Boolean(inner.is_teacher),
  };
}

export function readStoredUser(): User | null {
  try {
    const stored = localStorage.getItem('auth_user');
    return stored ? normalizeUser(JSON.parse(stored)) : null;
  } catch {
    return null;
  }
}
