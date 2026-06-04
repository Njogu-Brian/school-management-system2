import { useCan } from '@erp/core';
import { ScreenContainer, useTheme } from '@erp/ui';
import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import {
  AlertsSection,
  CriticalKpisSection,
  OperationalStatusSection,
  QuickActionsSection,
} from '../sections';

/**
 * School Command Center shell — composes permission-gated sections.
 * No API calls; placeholder data only (Sprint 2 Batch 1).
 */
export const DashboardLayout: React.FC = () => {
  const canViewDashboard = useCan('dashboard.view');
  const { palette, spacing, fontSizes, colors } = useTheme();

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
          Overview for your branch · placeholder metrics
        </Text>
        <View
          style={[
            styles.badge,
            {
              backgroundColor: `${colors.primary}14`,
              borderColor: `${colors.primary}33`,
              marginTop: spacing.sm,
            },
          ]}
        >
          <Text style={{ color: colors.primary, fontSize: fontSizes.xs, fontWeight: '600' }}>
            Framework preview — live data in a later batch
          </Text>
        </View>
      </View>

      <CriticalKpisSection />
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
  badge: {
    alignSelf: 'flex-start',
    borderWidth: 1,
    borderRadius: 8,
    paddingHorizontal: 10,
    paddingVertical: 4,
  },
});
