import axios from 'axios';

/** User-facing hint when Vite cannot reach Laravel on :8000 */
export const API_OFFLINE_MESSAGE =
  'Cannot reach the API. Start the backend in another terminal: cd backend then php artisan serve — keep that window open while using the site.';

export function isApiOfflineError(error: unknown): boolean {
  if (!axios.isAxiosError(error)) {
    return true;
  }
  if (!error.response) {
    return true;
  }
  const status = error.response.status;
  return status === 502 || status === 503 || status === 504;
}

export function apiErrorMessage(error: unknown, fallback: string): string {
  return isApiOfflineError(error) ? API_OFFLINE_MESSAGE : fallback;
}
