export function formatOrderId(id: number): string {
  return `O${String(id).padStart(6, '0')}`;
}
