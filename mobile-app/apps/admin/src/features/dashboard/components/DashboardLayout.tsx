import { useAuth, useCan } from '@erp/core';
import {
  DashboardHero,
  EmptyState,
  ScrollableTabBar,
  ScreenContainer,
  useTheme,
} from '@erp/ui';
import React, { useMemo, useState } from 'react';
import { StyleSheet, View } from 'react-native';
import {
  AlertsSection,
  CriticalKpisSection,
  ExecutiveDashboardSection,
  OperationalStatusSection,
  PendingApprovalsSection,
  QuickActionsSection,
} from '../sections';
import { QuickActionFab } from './QuickActionFab';

type DashboardTab = 'overview' | 'executive' | 'approvals' | 'alerts';

const DASHBOARD_TABS = [
  { key: 'overview' as const, label: 'Overview' },
  { key: 'executive' as const, label: 'Executive' },
  { key: 'approvals' as const, label: 'Approvals' },
  { key: 'alerts' as const, label: 'Alerts' },
];

function greetingForHour(hour: number): string {
  if (hour < 12) return 'Good morning';
  if (hour < 17) return 'Good afternoon';
  return 'Good evening';
}

export const DashboardLayout: React.FC = () => {
  const canViewDashboard = useCan('dashboard.view');
  const { spacing, palette } = useTheme();
  const { user } = useAuth();
  const [tab, setTab] = useState<DashboardTab>('overview');

  const greeting = useMemo(() => greetingForHour(new Date().getHours()), []);
  const displayName = (user?.name ?? 'Admin').split(' ')[0];

  const contentStyle = useMemo(
    () => ({
      paddingHorizontal: spacing.md,
      paddingTop: spacing.sm,
      paddingBottom: spacing['5xl'] + spacing['3xl'],
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
          title="Command Center"
          subtitle="Live pulse across your school"
          meta="KPIs · Approvals · Alerts"
        />

        <ScrollableTabBar
          variant="segmented"
          tabs={DASHBOARD_TABS}
          activeTab={tab}
          onTabChange={setTab}
        />

        {tab === 'overview' ? (
          <>
            <CriticalKpisSection />
            <QuickActionsSection />
            <OperationalStatusSection />
          </>
        ) : null}
        {tab === 'executive' ? <ExecutiveDashboardSection /> : null}
        {tab === 'approvals' ? <PendingApprovalsSection /> : null}
        {tab === 'alerts' ? <AlertsSection /> : null}
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
