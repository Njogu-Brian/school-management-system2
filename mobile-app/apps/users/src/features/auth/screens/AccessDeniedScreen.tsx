import { isAdminAppRole, useAuth, useCurrentUser, UserRole, effectiveRole } from '@erp/core';
import { Button, EmptyState, ScreenContainer, useTheme } from '@erp/ui';
import React, { useState } from 'react';
import { Linking, StyleSheet, View } from 'react-native';
import { ParentClaimFlow } from './ParentClaimFlow';

/**
 * Shown when an Admin App role signs into the Users App without a linked parent profile,
 * or when another unrecognized role lands here.
 */
export const AccessDeniedScreen: React.FC = () => {
  const user = useCurrentUser();
  const { logout } = useAuth();
  const { spacing } = useTheme();
  const [showClaim, setShowClaim] = useState(false);

  const role = effectiveRole(user);
  const roleLabel = user?.roleName ?? 'Your account';
  const needsParentLink =
    (role === UserRole.DIRECTOR || isAdminAppRole(role)) && !user?.parentId && !user?.canHomeMode;

  const openAdminApp = (): void => {
    void Linking.openURL('royalkingsadmin://').catch(() => undefined);
  };

  if (showClaim) {
    return <ParentClaimFlow onExit={() => setShowClaim(false)} />;
  }

  const message = needsParentLink
    ? `${roleLabel} can use the Users App with the same login once a child is linked. Use “Link a child” below with your school phone or email and a child’s admission number.`
    : `${roleLabel} doesn’t have access to the Users App. This app is for teachers, parents, students, and drivers. Please use the Admin Console instead.`;

  return (
    <ScreenContainer edges={['top', 'bottom']} contentContainerStyle={styles.content}>
      <EmptyState
        title="Access denied"
        message={message}
        icon="lock-closed-outline"
        actionLabel={needsParentLink ? 'Link a child' : 'Open Admin App'}
        onAction={needsParentLink ? () => setShowClaim(true) : openAdminApp}
      />
      <View style={{ marginTop: spacing.sm, alignSelf: 'stretch', paddingHorizontal: spacing.lg, gap: spacing.sm }}>
        {needsParentLink ? (
          <Button label="Open Admin App" variant="secondary" onPress={openAdminApp} />
        ) : null}
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
