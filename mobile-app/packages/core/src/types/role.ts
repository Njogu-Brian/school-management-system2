import type { UserRole } from '../config/roles';
import type { PermissionName } from './permission';

/**
 * Canonical role. `slug` is the normalized identifier the app reasons about
 * (a `UserRole` when recognized); `name` is the backend display name
 * (e.g. "Super Admin"). The permission-first model treats a role as a bundle of
 * permissions (build plan §7.7).
 */
export interface Role {
  id?: number;
  slug: UserRole | string;
  name: string;
  permissions?: PermissionName[];
}
