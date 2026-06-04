import { useCan, type CanOptions } from '@erp/core';
import type { PermissionInput } from '@erp/core';
import { PermissionGate } from '@erp/ui';
import React from 'react';

export interface AppPermissionGateProps {
  permission: PermissionInput;
  requireAll?: boolean;
  fallback?: React.ReactNode;
  children: React.ReactNode;
}

/** App-bound gate: wires `useCan` from @erp/core to @erp/ui PermissionGate. */
export const AppPermissionGate: React.FC<AppPermissionGateProps> = ({
  permission,
  requireAll,
  fallback,
  children,
}) => {
  const options: CanOptions | undefined = requireAll ? { requireAll: true } : undefined;
  const allowed = useCan(permission, options);
  return (
    <PermissionGate allowed={allowed} fallback={fallback}>
      {children}
    </PermissionGate>
  );
};
