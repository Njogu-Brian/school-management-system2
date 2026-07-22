import { useAuth, useCurrentUser } from '@erp/core';
import { Button, EmptyState, ScreenContainer, useTheme } from '@erp/ui';
import React from 'react';
import { Linking, StyleSheet, View } from 'react-native';

/**
 * Shown when an Admin App role signs into the Users App.
 */
export const AccessDeniedScreen: React.FC = () => {
  const user = useCurrentUser();
  const { logout } = useAuth();
  const { spacing } = useTheme();

  const roleLabel = user?.roleName ?? 'Your account';

  const openAdminApp = (): void => {
    void Linking.openURL('royalkingsadmin://').catch(() => undefined);
  };

  return (
    <ScreenContainer edges={['top', 'bottom']} contentContainerStyle={styles.content}>
      <EmptyState
        title="Access denied"
        message={`${roleLabel} doesn’t have access to the Users App. This app is for teachers, parents, students, and drivers. Please use the Admin Console instead.`}
        icon="lock-closed-outline"
        actionLabel="Open Admin App"
        onAction={openAdminApp}
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
