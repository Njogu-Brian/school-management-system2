/**
 * Format a person as First Middle Last, omitting blank parts.
 */
export function formatPersonName(
  first?: string | null,
  middle?: string | null,
  last?: string | null,
): string {
  return [first, middle, last]
    .map((part) => (typeof part === 'string' ? part.trim() : ''))
    .filter((part) => part.length > 0)
    .join(' ');
}

/**
 * Prefer API full_name when present; otherwise First Middle Last from parts.
 */
export function resolvePersonFullName(raw: {
  full_name?: string | null;
  first_name?: string | null;
  middle_name?: string | null;
  last_name?: string | null;
}): string {
  const fromApi = typeof raw.full_name === 'string' ? raw.full_name.trim() : '';
  if (fromApi) return fromApi;
  return formatPersonName(raw.first_name, raw.middle_name, raw.last_name);
}
