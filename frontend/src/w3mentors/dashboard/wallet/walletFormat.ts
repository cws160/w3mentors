export function formatTxnId(id: number): string {
  return `TXN-${String(id).padStart(7, '0')}`;
}

export function formatWithdrawalId(id: number): string {
  return `#${String(id).padStart(7, '0')}`;
}
