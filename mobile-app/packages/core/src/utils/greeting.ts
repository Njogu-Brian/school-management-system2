/** Time-of-day greeting with optional first name, e.g. "Good morning, Jane". */
export function timeOfDayGreeting(displayName?: string | null): string {
  const hour = new Date().getHours();
  const part = hour < 12 ? 'Good morning' : hour < 17 ? 'Good afternoon' : 'Good evening';
  const first = (displayName ?? '').trim().split(/\s+/)[0];
  return first ? `${part}, ${first}` : part;
}

/**
 * Role labels for dashboard heroes — sentence case per word
 * ("TEACHER" / "senior teacher" → "Teacher" / "Senior Teacher").
 */
export function formatRoleLabel(role?: string | null, fallback = 'User'): string {
  const raw = (role ?? '').trim() || fallback;
  return raw
    .toLowerCase()
    .split(/[\s_]+/)
    .filter(Boolean)
    .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
    .join(' ');
}
