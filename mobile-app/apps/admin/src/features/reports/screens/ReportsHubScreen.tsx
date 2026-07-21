import { useBoardPack, useCan, useDashboardStats, useExpenseReportSummary, useWeeklyReports } from '@erp/core';
import {
  DashboardHero,
  DashboardSection,
  EmptyState,
  KpiCard,
  QuickAction,
  ScreenContainer,
  WidgetGrid,
  WidgetShell,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React from 'react';
import { Pressable, RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';
import type { ReportsStackParamList } from '../../../navigation/reportsStackTypes';
import { formatDateLabel } from '../../shared/utils/formatters';

type Props = StackScreenProps<ReportsStackParamList, 'ReportsHub'>;

export const ReportsHubScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('reports.view');
  const { colors, palette, spacing, typography, radius, elevation } = useTheme();
  const statsQuery = useDashboardStats({ enabled: canView });
  const boardPackQuery = useBoardPack({ enabled: canView });
  const expenseQuery = useExpenseReportSummary({ enabled: canView });
  const weeklyQuery = useWeeklyReports({ enabled: canView });

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={[styles.denied, { padding: spacing.lg }]}>
        <EmptyState
          title="Access denied"
          message="You need reports.view permission to open this workspace."
          icon="lock-closed-outline"
        />
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

  const hubError =
    statsQuery.isError && boardPackQuery.isError && expenseQuery.isError && weeklyQuery.isError;

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
            colors={[colors.primary]}
            tintColor={colors.primary}
          />
        }
      >
        <DashboardHero
          variant="reports"
          title="Reports"
          subtitle="Executive, finance & operational reporting"
          meta={weekly.length > 0 ? `${weekly.length} weekly reports this period` : undefined}
        />

        {hubError ? (
          <EmptyState
            title="Could not load reports"
            message="Pull to refresh or try again."
            icon="alert-circle-outline"
            actionLabel="Retry"
            onAction={refreshAll}
          />
        ) : (
          <>
            <WidgetGrid>
              {[
                {
                  label: 'Enrolled students',
                  value: String(stats?.total_students ?? stats?.enrolled_students ?? '—'),
                  icon: 'people-outline' as const,
                },
                {
                  label: 'Fees collected',
                  value: String(stats?.fees_collected ?? '—'),
                  icon: 'cash-outline' as const,
                },
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

            <DashboardSection title="Report workspaces">
              <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm }}>
                <QuickAction
                  label="Executive analytics"
                  icon="trending-up-outline"
                  onPress={() => navigation.navigate('ExecutiveAnalytics')}
                />
                <QuickAction
                  label="Board pack"
                  icon="briefcase-outline"
                  onPress={() => navigation.navigate('BoardPack')}
                />
                <QuickAction
                  label="Expense reports"
                  icon="pie-chart-outline"
                  onPress={() => navigation.navigate('ExpenseReports')}
                />
                <QuickAction
                  label="All expenses"
                  icon="wallet-outline"
                  onPress={() => navigation.navigate('ExpensesList')}
                />
                <QuickAction
                  label="Income statement"
                  icon="stats-chart-outline"
                  onPress={() => navigation.navigate('IncomeStatement')}
                />
                <QuickAction
                  label="Balance sheet"
                  icon="scale-outline"
                  onPress={() => navigation.navigate('BalanceSheet')}
                />
                <QuickAction
                  label="General ledger"
                  icon="book-outline"
                  onPress={() => navigation.navigate('Ledger')}
                />
                <QuickAction
                  label="Weekly reports"
                  icon="calendar-outline"
                  onPress={() => navigation.navigate('WeeklyReportsList')}
                />
              </View>
            </DashboardSection>

            <DashboardSection title="Recent weekly reports">
              {weekly.length > 0 ? (
                weekly.slice(0, 5).map((item) => (
                  <Pressable
                    key={`${item.type}-${item.id}`}
                    onPress={() =>
                      navigation.navigate('WeeklyReportDetail', { type: item.type, reportId: item.id })
                    }
                    style={({ pressed }) => [
                      elevation[1],
                      {
                        marginBottom: spacing.sm,
                        padding: spacing.md,
                        borderWidth: StyleSheet.hairlineWidth,
                        borderColor: palette.borderSubtle,
                        backgroundColor: palette.surfaceRaised,
                        borderRadius: radius.card,
                        opacity: pressed ? 0.9 : 1,
                      },
                    ]}
                  >
                    <Text
                      style={{
                        color: palette.textPrimary,
                        fontSize: typography.titleSmall.fontSize,
                        fontWeight: typography.titleSmall.fontWeight,
                        lineHeight: typography.titleSmall.lineHeight,
                      }}
                    >
                      {item.title}
                    </Text>
                    <Text
                      style={{
                        color: palette.textSecondary,
                        fontSize: typography.caption.fontSize,
                        fontWeight: typography.caption.fontWeight,
                        lineHeight: typography.caption.lineHeight,
                        marginTop: spacing.xs,
                      }}
                    >
                      {[item.type.replace(/_/g, ' '), formatDateLabel(item.week_ending), item.subtitle]
                        .filter(Boolean)
                        .join(' · ')}
                    </Text>
                  </Pressable>
                ))
              ) : (
                <EmptyState
                  title="No weekly reports"
                  message="Weekly submissions will appear here once filed."
                  icon="calendar-outline"
                />
              )}
            </DashboardSection>
          </>
        )}
      </ScrollView>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center' },
});
