import React from 'react';

/** Permission checks are evaluated in the app via @erp/core — UI only renders children. */
export type PermissionGateProps = {
  allowed: boolean;
  fallback?: React.ReactNode;
  children: React.ReactNode;
};

/**
 * Low-level gate — pass `allowed` from `useCan()` in the app layer.
 * Keeps @erp/ui free of @erp/core dependency.
 */
export const PermissionGate: React.FC<PermissionGateProps> = ({
  allowed,
  fallback = null,
  children,
}) => (allowed ? <>{children}</> : <>{fallback}</>);
