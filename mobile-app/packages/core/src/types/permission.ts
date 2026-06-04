/**
 * Permission name as returned by the backend (Spatie permission name, e.g.
 * `view_students`). The future permission-first RBAC engine (build plan §7) keys off
 * these strings; richer metadata is optional.
 */
export type PermissionName = string;

export interface Permission {
  name: PermissionName;
  /** Human label for settings UIs (optional). */
  label?: string;
  /** Grouping bucket, e.g. "finance" (optional). */
  group?: string;
}
