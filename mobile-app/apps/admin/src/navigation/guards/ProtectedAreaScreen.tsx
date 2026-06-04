import type { AdminAreaKey } from '@erp/core';
import { useRbac } from '@erp/core';
import React from 'react';
import { ModuleAccessDeniedScreen } from '../../features/auth/screens/ModuleAccessDeniedScreen';

export interface ProtectedAreaScreenProps {
  areaKey: AdminAreaKey;
  children: React.ReactNode;
}

/**
 * Route-level guard: unauthorized module access shows Access Denied (in-module).
 */
export const ProtectedAreaScreen: React.FC<ProtectedAreaScreenProps> = ({
  areaKey,
  children,
}) => {
  const { canAccessArea } = useRbac();
  const allowed = canAccessArea(areaKey);

  if (!allowed) {
    return <ModuleAccessDeniedScreen areaKey={areaKey} />;
  }

  return <>{children}</>;
};

/** Factory for navigator `component` props. */
export function withAreaGuard(
  areaKey: AdminAreaKey,
  Screen: React.ComponentType,
): React.FC {
  const Guarded: React.FC = () => (
    <ProtectedAreaScreen areaKey={areaKey}>
      <Screen />
    </ProtectedAreaScreen>
  );
  Guarded.displayName = `Guarded(${Screen.displayName ?? Screen.name ?? areaKey})`;
  return Guarded;
}
