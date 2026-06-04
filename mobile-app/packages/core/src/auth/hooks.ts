import type { UserRole } from '../config/roles';
import type { AppTarget, Branch, User } from '../types';
import { useAuth } from './AuthContext';
import { canAccessApp } from './roleUtils';

/** The current authenticated user, or null. */
export function useCurrentUser(): User | null {
  return useAuth().user;
}

/** The current user's normalized role, or null (unauthenticated / unrecognized). */
export function useCurrentRole(): UserRole | null {
  return useAuth().user?.role ?? null;
}

/**
 * The active branch for the current user. Branch switching arrives in a later batch;
 * for now this resolves to the user's primary branch (or null until the backend
 * ships branch scoping).
 */
export function useCurrentBranch(): Branch | null {
  const user = useAuth().user;
  if (!user) {
    return null;
  }
  const branches = user.branches ?? [];
  if (user.branchId != null) {
    return branches.find((b) => b.id === user.branchId) ?? branches[0] ?? null;
  }
  return branches[0] ?? null;
}

/** Whether the current user may enter the given app binary. */
export function useCanAccessApp(target: AppTarget): boolean {
  return canAccessApp(useAuth().user, target);
}
