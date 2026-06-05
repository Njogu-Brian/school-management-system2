/** Consistent date/currency formatting for admin app screens. */

export function formatDateLabel(iso?: string | null): string {
  if (!iso) return '—';
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return iso;
  return d.toLocaleDateString('en-KE', { year: 'numeric', month: 'short', day: 'numeric' });
}

export function formatDateTimeLabel(iso?: string | null): string {
  if (!iso) return '—';
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return iso;
  return d.toLocaleString('en-KE', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

export function formatKes(amount?: number | null): string {
  if (amount == null || Number.isNaN(amount)) return '—';
  return `KES ${amount.toLocaleString('en-KE', { maximumFractionDigits: 0 })}`;
}

export function capitalizeStatus(value?: string | null): string {
  if (!value) return '—';
  return value.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}
