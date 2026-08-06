export function formatQuizDuration(seconds: number | null | undefined): string {
  if (!seconds) return '—';
  const hrs = Math.floor(seconds / 3600);
  const min = Math.floor((seconds % 3600) / 60);
  if (hrs > 0) {
    return `${hrs}h ${min > 0 ? `${min}m` : ''}`.trim();
  }
  if (min > 0) {
    return `${min}m`;
  }
  return '00m';
}

export function formatPassPercent(value: number | null | undefined): string {
  if (value === null || value === undefined || value <= 0) return '—';
  return `${Number(value).toFixed(0)}%`;
}

export function formatLegacyDateTime(value: string | null): string {
  if (!value) return '—';
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return value;
  const date = d.toLocaleDateString('en-US', {
    month: '2-digit',
    day: '2-digit',
    year: 'numeric',
  });
  const time = d.toLocaleTimeString('en-GB', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: false,
  });
  return `${date}, ${time}`;
}
