/** Count non-default filter values for the "Filters (n)" badge. */
export function countActiveFilters(
  values: Array<string | number | null | undefined | boolean>,
): number {
  return values.filter((v) => v != null && v !== '' && v !== 'all' && v !== false).length;
}
