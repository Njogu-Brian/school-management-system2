/**
 * Resolve which child is active on parent Home.
 * Never invents IDs — only returns an id from the authenticated parent's accessible list.
 */
export function resolveSelectedChildId(
  availableIds: number[],
  preferredId: number | null | undefined,
): number | null {
  const ids = availableIds.filter((id) => Number.isFinite(id) && id > 0);
  if (ids.length === 0) return null;
  if (preferredId != null && ids.includes(preferredId)) return preferredId;
  return ids[0] ?? null;
}

/** True when preferredId is not in the accessible set (stale cache / cross-child leak attempt). */
export function isSelectedChildUnauthorized(
  availableIds: number[],
  preferredId: number | null | undefined,
): boolean {
  if (preferredId == null || preferredId <= 0) return false;
  return !availableIds.includes(preferredId);
}
