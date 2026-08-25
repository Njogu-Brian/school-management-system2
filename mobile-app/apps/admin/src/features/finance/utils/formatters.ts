export function formatKes(amount: number | null | undefined): string {
  if (amount == null || Number.isNaN(amount)) return '—';
  return `KES ${amount.toLocaleString('en-KE', { minimumFractionDigits: 0, maximumFractionDigits: 2 })}`;
}

/** Statement / C2B phone for display. Never show a Daraja SHA-256 MSISDN hash. */
export function formatTransactionPhone(
  phone?: string | null,
  msisdn?: string | null,
): string | null {
  const raw = (phone && phone.trim()) || (msisdn && msisdn.trim()) || '';
  if (!raw) return null;
  if (/^[a-f0-9]{64}$/i.test(raw)) return null;
  return raw;
}
