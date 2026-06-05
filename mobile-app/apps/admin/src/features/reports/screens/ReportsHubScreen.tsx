import { useBoardPack, useCan, useDashboardStats, useExpenseReportSummary, useWeeklyReports } from '@erp/core';
import { KpiCard, QuickAction, ScreenContainer, WidgetGrid, WidgetShell, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React from 'react';
import { Pressable, RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';
import type { ReportsStackParamList } from '../../../navigation/reportsStackTypes';
import { formatDateLabel } from '../../shared/utils/formatters';

type Props = StackScreenProps<ReportsStackParamList, 'ReportsHub'>;

export const ReportsHubScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('reports.view');
  const { palette, spacing, fontSizes } = useTheme();
  const statsQuery = useDashboardStats({ enabled: canView });
  const boardPackQuery = useBoardPack({ enabled: canView });
  const expenseQuery = useExpenseReportSummary({ enabled: canView });
  const weeklyQuery = useWeeklyReports({ enabled: canView });

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <Text style={{ color: palette.textSecondary, textAlign: 'center' }}>Access denied.</Text>
      </ScreenContainer>
    );
  }

  const stats = statsQuery.data as Record<string, number | undefined> | undefined;
  const boardPack = boardPackQuery.data;
  const expenses = expenseQuery.data;
  const weekly = weeklyQuery.data?.items ?? [];

  const refreshAll = () => {
    void statsQuery.refetch();
    void boardPackQuery.refetch();
    void expenseQuery.refetch();
    void weeklyQuery.refetch();
  };

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
        refreshControl={
          <RefreshControl
            refreshing={
              statsQuery.isRefetching ||
              boardPackQuery.isRefetching ||
              expenseQuery.isRefetching ||
              weeklyQuery.isRefetching
            }
            onRefresh={refreshAll}
          />
        }
      >
        <Text style={{ color: palette.textPrimary, fontSize: fontSizes.lg, fontWeight: '700', marginBottom: spacing.sm }}>
          Reports hub
        </Text>

        <WidgetGrid>
          {[
            { label: 'Enrolled students', value: String(stats?.total_students ?? stats?.enrolled_students ?? '—'), icon: 'people-outline' as const },
            { label: 'Fees collected', value: String(stats?.fees_collected ?? '—'), icon: 'cash-outline' as const },
            {
              label: 'Expenses MTD',
              value: expenses ? `KES ${expenses.total_expenses.toLocaleString()}` : '—',
              icon: 'receipt-outline' as const,
            },
            {
              label: 'Pending approvals',
              value: String(boardPack?.approvals.pending_total ?? '—'),
              icon: 'checkmark-circle-outline' as const,
            },
          ].map((kpi) => {
            const state = statsQuery.isLoading ? 'loading' : statsQuery.isError ? 'error' : 'success';
            return (
              <WidgetShell key={kpi.label} state={state} title={kpi.label} onRetry={refreshAll}>
                <KpiCard label={kpi.label} value={kpi.value} icon={kpi.icon} />
              </WidgetShell>
            );
          })}
        </WidgetGrid>

        <Text style={{ color: palette.textPrimary, fontWeight: '700', marginTop: spacing.lg, marginBottom: spacing.sm }}>
          Report workspaces
        </Text>
        <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm }}>
          <QuickAction label="Board pack" icon="briefcase-outline" onPress={() => navigation.navigate('BoardPack')} />
          <QuickAction label="Expense reports" icon="pie-chart-outline" onPress={() => navigation.navigate('ExpenseReports')} />
          <QuickAction label="Weekly reports" icon="calendar-outline" onPress={() => navigation.navigate('WeeklyReportsList')} />
        </View>

        {weekly.length > 0 ? (
          <>
            <Text style={{ color: palette.textPrimary, fontWeight: '700', marginTop: spacing.lg, marginBottom: spacing.sm }}>
              Recent weekly reports
            </Text>
            {weekly.slice(0, 5).map((item) => (
              <Pressable
                key={`${item.type}-${item.id}`}
                onPress={() => navigation.navigate('WeeklyReportDetail', { type: item.type, reportId: item.id })}
                style={{
                  marginBottom: spacing.xs,
                  padding: spacing.sm,
                  borderWidth: StyleSheet.hairlineWidth,
                  borderColor: palette.border,
                  borderRadius: 8,
                }}
              >
                <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>{item.title}</Text>
                <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs }}>
                  {[item.type.replace(/_/g, ' '), formatDateLabel(item.week_ending), item.subtitle].filter(Boolean).join(' · ')}
                </Text>
              </Pressable>
            ))}
          </>
        ) : null}
      </ScrollView>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
});
