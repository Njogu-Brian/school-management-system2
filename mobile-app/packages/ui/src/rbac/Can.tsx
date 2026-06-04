import React from 'react';
import { PermissionGate, type PermissionGateProps } from './PermissionGate';

export type CanProps = Omit<PermissionGateProps, 'allowed'> & {
  allowed: boolean;
};

/** Alias for `PermissionGate` (matches common RBAC naming). */
export const Can: React.FC<CanProps> = (props) => <PermissionGate {...props} />;
