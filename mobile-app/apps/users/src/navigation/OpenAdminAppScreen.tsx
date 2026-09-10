import { EmptyState, ScreenContainer, Button, useTheme } from '@erp/ui';
import { useAppMode } from '@erp/core';
import React from 'react';
import { Linking, View } from 'react-native';
import { AppModeSwitch } from '../features/shared/components/AppModeSwitch';

/**
 * Director/Admin/Superadmin Work mode inside the Users binary.
 * Administrative work belongs in the Admin app — do not duplicate teacher/admin UI here.
 */
export const OpenAdminAppScreen: React.FC = () => {
  const { spacing } = useTheme();
  const { setMode } = useAppMode();

  return (
    <ScreenContainer edges={['top', 'bottom']} contentContainerStyle={{ padding: spacing.md }}>
      <View style={{ marginBottom: spacing.md }}>
        <AppModeSwitch />
      </View>
      <EmptyState
        title="Work mode uses the Admin app"
        message="Your administrative tools are in the Admin app. Switch back to Home to manage your own child, or open Admin for school work."
        icon="briefcase-outline"
        actionLabel="Open Admin app"
        onAction={() => void Linking.openURL('royalkingsadmin://')}
      />
      <Button
        label="Back to Home"
        variant="secondary"
        onPress={() => void setMode('home')}
        style={{ marginTop: spacing.md }}
      />
    </ScreenContainer>
  );
};
