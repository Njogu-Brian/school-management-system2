import { useCan, useDashboardStats } from '@erp/core';
import { KpiCard, QuickAction, ScreenContainer, WidgetGrid, WidgetShell, useTheme } from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import React from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';
import { navigateToDrawer, navigateToTab } from '../../../navigation/navigateWorkspace';

const LINKS = [
  { label: 'Finance statements', icon: 'document-text-outline' as const, action: 'finance-statements' as const },
  { label: 'Academics report cards', icon: 'ribbon-outline' as const, action: 'academics-report-cards' as const },
  { label: 'Exam analytics', icon: 'analytics-outline' as const, action: 'academics-exams' as const },
  { label: 'Operations transport', icon: 'bus-outline' as const, action: 'operations-trips' as const },
];

export const ReportsHubScreen: React.FC = () => {
  const canView = useCan('reports.view');
  const navigation = useNavigation();
  const { palette, spacing, fontSizes } = useTheme();
  const statsQuery = useDashboardStats({ enabled: canView });

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <Text style={{ color: palette.textSecondary, textAlign: 'center' }}>Access denied.</Text>
      </ScreenContainer>
    );
  }

  const stats = statsQuery.data as Record<string, number | undefined> | undefined;

  const onLink = (action: (typeof LINKS)[number]['action']) => {
    switch (action) {
      case 'finance-statements':
        navigateToTab(navigation, 'Finance', 'Statements');
        break;
      case 'academics-report-cards':
        navigateToDrawer(navigation, 'Academics', 'ReportCards');
        break;
      case 'academics-exams':
        navigateToDrawer(navigation, 'Academics', 'ExamsList');
        break;
      case 'operations-trips':
        navigateToDrawer(navigation, 'Operations', 'TripsList');
        break;
      default:
        break;
    }
  };

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
        <Text style={{ color: palette.textPrimary, fontSize: fontSizes.lg, fontWeight: '700', marginBottom: spacing.sm }}>
          Reports hub
        </Text>
        <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm, marginBottom: spacing.md }}>
          Cross-module reports and deep-links to live workspaces.
        </Text>

        <WidgetGrid>
          {[
            { label: 'Enrolled students', value: String(stats?.total_students ?? stats?.enrolled_students ?? '—'), icon: 'people-outline' as const },
            { label: 'Fees collected', value: String(stats?.fees_collected ?? '—'), icon: 'cash-outline' as const },
          ].map((kpi) => {
            const state = statsQuery.isLoading ? 'loading' : statsQuery.isError ? 'error' : 'success';
            return (
              <WidgetShell key={kpi.label} state={state} title={kpi.label}>
                <KpiCard label={kpi.label} value={kpi.value} icon={kpi.icon} />
              </WidgetShell>
            );
          })}
        </WidgetGrid>

        <Text style={{ color: palette.textPrimary, fontWeight: '700', marginTop: spacing.lg, marginBottom: spacing.sm }}>
          Open reports
        </Text>
        <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm }}>
          {LINKS.map((link) => (
            <QuickAction key={link.action} label={link.label} icon={link.icon} onPress={() => onLink(link.action)} />
          ))}
        </View>
      </ScrollView>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
});
