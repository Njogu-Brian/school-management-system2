import React from 'react';

export type RoleGateProps = {
  allowed: boolean;
  fallback?: React.ReactNode;
  children: React.ReactNode;
};

/**
 * Low-level role gate — pass `allowed` from `useHasRole` / `useRbac().hasAnyRole()` in the app.
 */
export const RoleGate: React.FC<RoleGateProps> = ({ allowed, fallback = null, children }) =>
  allowed ? <>{children}</> : <>{fallback}</>;
