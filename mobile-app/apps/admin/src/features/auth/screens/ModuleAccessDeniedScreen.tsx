import type { AdminAreaKey } from '@erp/core';
import { getNavArea } from '@erp/core';
import { useAuth } from '@erp/core';
import { EmptyState, ScreenContainer } from '@erp/ui';
import React from 'react';
import { StyleSheet } from 'react-native';

export interface ModuleAccessDeniedScreenProps {
  areaKey?: AdminAreaKey;
}

/**
 * Shown when the user navigates to a module they lack permission for
 * (route-level protection, Batch 3).
 */
export const ModuleAccessDeniedScreen: React.FC<ModuleAccessDeniedScreenProps> = ({
  areaKey,
}) => {
  const { logout } = useAuth();
  const moduleLabel = areaKey ? getNavArea(areaKey).label : 'this module';

  return (
    <ScreenContainer edges={['top', 'bottom']} contentContainerStyle={styles.content}>
      <EmptyState
        title="Access denied"
        message={`You don't have permission to open ${moduleLabel}. Contact your administrator if you need access.`}
        icon="lock-closed-outline"
        actionLabel="Sign out"
        onAction={logout}
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  content: {
    alignItems: 'center',
    justifyContent: 'center',
  },
});
