export function formatKes(amount: number | null | undefined): string {
  if (amount == null || Number.isNaN(amount)) return '—';
  return `KES ${amount.toLocaleString('en-KE', { minimumFractionDigits: 0, maximumFractionDigits: 2 })}`;
}
