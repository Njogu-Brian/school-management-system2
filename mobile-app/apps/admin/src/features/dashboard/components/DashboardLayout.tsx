import { formatRoleLabel, timeOfDayGreeting, useAuth, useCan } from '@erp/core';
import {
  Button,
  DashboardHero,
  EmptyState,
  ScreenContainer,
  useTheme,
} from '@erp/ui';
import React, { useMemo } from 'react';
import { StyleSheet, View } from 'react-native';
import { confirmAction } from '../../shared/utils/feedback';
import { CriticalKpisSection, OperationalStatusSection, QuickActionsSection } from '../sections';
import { QuickActionFab } from './QuickActionFab';

export const DashboardLayout: React.FC = () => {
  const canViewDashboard = useCan('dashboard.view');
  const { spacing, palette, colors } = useTheme();
  const { user, logout } = useAuth();

  const greeting = useMemo(() => timeOfDayGreeting(), []);
  const displayName = (user?.name ?? 'Admin').split(' ')[0];

  const contentStyle = useMemo(
    () => ({
      paddingHorizontal: spacing.md,
      paddingTop: spacing.sm,
      paddingBottom: spacing.md,
      backgroundColor: palette.background,
    }),
    [spacing, palette.background],
  );

  if (!canViewDashboard) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <EmptyState
          title="Access denied"
          message="You don't have permission to view the dashboard."
          icon="lock-closed-outline"
        />
      </ScreenContainer>
    );
  }

  return (
    <View style={{ flex: 1, backgroundColor: palette.background }}>
      <ScreenContainer contentContainerStyle={contentStyle}>
        <DashboardHero
          variant="default"
          greeting={greeting}
          userName={displayName}
          roleLabel={formatRoleLabel(user?.roleName ?? user?.role, 'Admin')}
          title="Overview"
          subtitle="Population, attendance, and school pulse"
          meta="Tap present, absent, or unmarked to open today's list"
        />

        <CriticalKpisSection />
        <QuickActionsSection />
        <OperationalStatusSection />

        <Button
          label="Sign out"
          variant="ghost"
          onPress={() =>
            confirmAction('Sign out', 'Are you sure you want to sign out?', 'Sign out', () => void logout(), true)
          }
          style={{ marginTop: spacing.lg, marginBottom: spacing.md, borderColor: colors.error, borderWidth: 1 }}
        />
      </ScreenContainer>
      <QuickActionFab />
    </View>
  );
};

const styles = StyleSheet.create({
  denied: {
    flexGrow: 1,
    justifyContent: 'center',
  },
});
