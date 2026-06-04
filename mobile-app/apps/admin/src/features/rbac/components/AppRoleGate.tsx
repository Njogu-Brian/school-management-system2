import { useRbac, UserRole } from '@erp/core';
import type { RolePreset } from '@erp/core';
import { RoleGate } from '@erp/ui';
import React from 'react';

export interface AppRoleGateProps {
  role?: RolePreset | UserRole | string;
  anyRoles?: Array<RolePreset | UserRole | string>;
  allRoles?: Array<RolePreset | UserRole | string>;
  fallback?: React.ReactNode;
  children: React.ReactNode;
}

export const AppRoleGate: React.FC<AppRoleGateProps> = ({
  role,
  anyRoles,
  allRoles,
  fallback,
  children,
}) => {
  const rbac = useRbac();
  let allowed = false;
  if (role) {
    allowed = rbac.hasRole(role);
  } else if (anyRoles?.length) {
    allowed = rbac.hasAnyRole(...anyRoles);
  } else if (allRoles?.length) {
    allowed = rbac.hasAllRoles(...allRoles);
  }
  return (
    <RoleGate allowed={allowed} fallback={fallback}>
      {children}
    </RoleGate>
  );
};
