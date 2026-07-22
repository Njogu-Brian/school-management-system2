import { useAuth, useCurrentUser } from '@erp/core';
import { Button, EmptyState, ScreenContainer, useTheme } from '@erp/ui';
import React from 'react';
import { Linking, StyleSheet, View } from 'react-native';

/**
 * Shown when a user authenticates successfully but their role is not an Admin App role
 * (e.g. a Teacher signing into the Admin Console). The session is valid; access to this
 * binary is denied. The symmetric case (Admin → Users App) is enforced by the Users App.
 */
export const AccessDeniedScreen: React.FC = () => {
  const user = useCurrentUser();
  const { logout } = useAuth();
  const { spacing } = useTheme();

  const roleLabel = user?.roleName ?? 'Your account';

  const openUsersApp = (): void => {
    // Best-effort deep link into the Users App; falls back silently if not installed.
    void Linking.openURL('royalkingsusers://').catch(() => undefined);
  };

  return (
    <ScreenContainer edges={['top', 'bottom']} contentContainerStyle={styles.content}>
      <EmptyState
        title="Access denied"
        message={`${roleLabel} doesn’t have access to the Admin Console. This app is for school administrators. Please use the Users App instead.`}
        icon="lock-closed-outline"
        actionLabel="Open Users App"
        onAction={openUsersApp}
      />
      <View style={{ marginTop: spacing.sm, alignSelf: 'stretch', paddingHorizontal: spacing.lg }}>
        <Button label="Sign in with a different account" variant="ghost" onPress={logout} />
      </View>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  content: {
    alignItems: 'center',
    justifyContent: 'center',
  },
});
