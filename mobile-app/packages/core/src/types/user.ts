import type { UserRole } from '../config/roles';
import type { Branch } from './branch';
import type { PermissionName } from './permission';

/**
 * Canonical authenticated user (camelCase domain model). Mapped from the backend
 * snake_case payload by `mapApiUser` so the rest of the app never touches raw API shapes.
 */
export interface User {
  id: number;
  name: string;
  email: string | null;
  phone?: string | null;
  avatarUrl?: string | null;

  /**
   * Normalized role. `null` means the backend role was unrecognized — under the
   * explicit-deny policy (build plan §5.1) such a user is denied app access.
   */
  role: UserRole | null;
  /** Original backend role display name (e.g. "Super Admin"). */
  roleName: string | null;
  /** Flat permission names granted to the user (drives future RBAC, build plan §7). */
  permissions: PermissionName[];

  /** Tenant/branch scope — optional until the backend ships multi-tenancy (E1/E2). */
  schoolId?: number | null;
  branchId?: number | null;
  branches?: Branch[];

  /** Role-specific linkage when present in the payload. */
  staffId?: number | null;
  teacherId?: number | null;
  parentId?: number | null;
  studentId?: number | null;

  /** Set after Google sign-in when the ID token is decoded client-side. */
  googleId?: string | null;
  googleEmail?: string | null;
}
