/** Query key prefixes persisted to AsyncStorage for offline access. */
export const PERSISTED_QUERY_PREFIXES = [
  'dashboard',
  'students',
  'staff',
  'finance',
  'operations',
  'reports',
  'communication',
  'notifications',
  'search',
  'analytics',
  'academics',
] as const;

export function shouldPersistQuery(queryKey: readonly unknown[]): boolean {
  const root = String(queryKey[0] ?? '');
  return PERSISTED_QUERY_PREFIXES.includes(root as (typeof PERSISTED_QUERY_PREFIXES)[number]);
}
