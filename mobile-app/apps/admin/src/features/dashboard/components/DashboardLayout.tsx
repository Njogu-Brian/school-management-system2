import { useCan } from '@erp/core';
import { ScreenContainer, useTheme } from '@erp/ui';
import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import {
  AlertsSection,
  CriticalKpisSection,
  OperationalStatusSection,
  PendingApprovalsSection,
  QuickActionsSection,
} from '../sections';

/**
 * School Command Center shell — composes permission-gated sections.
 * KPIs and pending approvals use live APIs (Sprint 2 Batches 2–3).
 */
export const DashboardLayout: React.FC = () => {
  const canViewDashboard = useCan('dashboard.view');
  const { palette, spacing, fontSizes } = useTheme();

  if (!canViewDashboard) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <Text style={[styles.deniedText, { color: palette.textSecondary, fontSize: fontSizes.md }]}>
          You don&apos;t have permission to view the dashboard.
        </Text>
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer contentContainerStyle={styles.content}>
      <View style={[styles.hero, { marginBottom: spacing.lg }]}>
        <Text style={[styles.heroTitle, { color: palette.textPrimary, fontSize: fontSizes.xl }]}>
          School Command Center
        </Text>
        <Text style={[styles.heroSub, { color: palette.textSecondary, fontSize: fontSizes.sm }]}>
          Overview for your branch
        </Text>
      </View>

      <CriticalKpisSection />
      <PendingApprovalsSection />
      <QuickActionsSection />
      <AlertsSection />
      <OperationalStatusSection />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  content: {
    paddingHorizontal: 16,
    paddingTop: 8,
    paddingBottom: 32,
  },
  denied: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 24,
  },
  deniedText: { textAlign: 'center' },
  hero: {},
  heroTitle: { fontWeight: '700' },
  heroSub: { marginTop: 4 },
});
