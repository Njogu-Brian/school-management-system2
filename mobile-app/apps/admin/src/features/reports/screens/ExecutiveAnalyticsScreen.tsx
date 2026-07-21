import { useCan, useExecutiveAnalytics, type AnalyticsPeriod, type ChartSeries } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  ChartCard,
  EmptyState,
  FilterChip,
  FilterChipRow,
  KpiCard,
  ScreenContainer,
  SkeletonWidgetGrid,
  WidgetGrid,
  WidgetShell,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useMemo, useState } from 'react';
import { RefreshControl, ScrollView, Share, StyleSheet, Text, useWindowDimensions } from 'react-native';
import { BarChart, LineChart, PieChart } from 'react-native-chart-kit';
import type { ReportsStackParamList } from '../../../navigation/reportsStackTypes';
import { formatKes } from '../../shared/utils/formatters';

type Props = StackScreenProps<ReportsStackParamList, 'ExecutiveAnalytics'>;

const PERIODS: AnalyticsPeriod[] = ['week', 'month', 'term', 'year'];

function safeSeries(series?: ChartSeries): { labels: string[]; data: number[] } {
  const labels = series?.labels?.length ? series.labels : ['—'];
  const data = series?.values?.length ? series.values : [0];
  return { labels, data };
}

function hexToRgba(hex: string, opacity = 1): string {
  const cleaned = hex.replace('#', '');
  const r = parseInt(cleaned.slice(0, 2), 16);
  const g = parseInt(cleaned.slice(2, 4), 16);
  const b = parseInt(cleaned.slice(4, 6), 16);
  return `rgba(${r}, ${g}, ${b}, ${opacity})`;
}

export const ExecutiveAnalyticsScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('reports.view');
  const { colors, palette, spacing, typography, radius } = useTheme();
  const { width } = useWindowDimensions();
  const [period, setPeriod] = useState<AnalyticsPeriod>('month');
  const query = useExecutiveAnalytics(period, { enabled: canView });

  const chartWidth = Math.min(width - spacing.md * 2, 420);
  const pieColors = useMemo(
    () => [colors.primary, colors.secondary, colors.info, colors.success, colors.warning, colors.primaryLight],
    [colors],
  );
  const chartConfig = useMemo(
    () => ({
      backgroundColor: palette.surfaceRaised,
      backgroundGradientFrom: palette.surfaceRaised,
      backgroundGradientTo: palette.surfaceRaised,
      decimalPlaces: 0,
      color: (opacity = 1) => hexToRgba(colors.primary, opacity),
      labelColor: () => palette.textSecondary,
      propsForDots: { r: '4', strokeWidth: '2', stroke: colors.primary },
      propsForBackgroundLines: { strokeDasharray: '', stroke: palette.borderSubtle, strokeWidth: 1 },
    }),
    [colors.primary, palette],
  );

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={[styles.denied, { padding: spacing.lg }]}>
        <EmptyState
          title="Access denied"
          message="You need reports.view permission to view executive analytics."
          icon="lock-closed-outline"
        />
      </ScreenContainer>
    );
  }

  const data = query.data;
  const state = query.isLoading ? 'loading' : query.isError ? 'error' : 'success';

  const shareReport = async () => {
    if (!data) return;
    const body = [
      `Executive analytics (${period})`,
      `Collected: ${formatKes(data.finance.monthly_collections)}`,
      `Outstanding: ${formatKes(data.finance.outstanding_balances)}`,
      `Inventory alerts: ${data.operations.inventory_alerts}`,
      `Fixed assets: ${data.operations.assets}`,
      `As of: ${data.as_of}`,
    ].join('\n');
    await Share.share({ message: body, title: 'Executive analytics' });
  };

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
        refreshControl={
          <RefreshControl
            refreshing={query.isRefetching}
            onRefresh={() => void query.refetch()}
            colors={[colors.primary]}
            tintColor={colors.primary}
          />
        }
      >
        <AcademicScreenHeader
          title="Executive analytics"
          subtitle="Cross-school performance"
          onBack={() => navigation.goBack()}
        />

        <FilterChipRow label="Period">
          {PERIODS.map((p) => (
            <FilterChip
              key={p}
              label={p.charAt(0).toUpperCase() + p.slice(1)}
              active={period === p}
              onPress={() => setPeriod(p)}
            />
          ))}
        </FilterChipRow>

        {query.isLoading ? (
          <SkeletonWidgetGrid count={4} />
        ) : query.isError ? (
          <EmptyState
            title="Could not load analytics"
            message={(query.error as Error).message}
            icon="alert-circle-outline"
            actionLabel="Retry"
            onAction={() => void query.refetch()}
          />
        ) : !data ? (
          <EmptyState
            title="No analytics data"
            message="There is no executive analytics data for this period yet."
            icon="stats-chart-outline"
          />
        ) : (
          <>
            <WidgetGrid>
              <WidgetShell state={state} title="Collected" onRetry={() => void query.refetch()}>
                <KpiCard
                  label="Collected (period)"
                  value={formatKes(data.finance.monthly_collections)}
                  icon="cash-outline"
                />
              </WidgetShell>
              <WidgetShell state={state} title="Outstanding">
                <KpiCard
                  label="Outstanding"
                  value={formatKes(data.finance.outstanding_balances)}
                  icon="wallet-outline"
                />
              </WidgetShell>
              <WidgetShell state={state} title="Inventory alerts">
                <KpiCard
                  label="Inventory alerts"
                  value={String(data.operations.inventory_alerts ?? '—')}
                  icon="warning-outline"
                />
              </WidgetShell>
              <WidgetShell state={state} title="Assets">
                <KpiCard
                  label="Fixed assets"
                  value={String(data.operations.assets ?? '—')}
                  icon="cube-outline"
                />
              </WidgetShell>
            </WidgetGrid>

            <ChartCard title="Fee collections" subtitle="Trend over selected period">
              <LineChart
                data={{
                  labels: safeSeries(data.finance.daily_collections).labels,
                  datasets: [{ data: safeSeries(data.finance.daily_collections).data }],
                }}
                width={chartWidth}
                height={180}
                chartConfig={chartConfig}
                bezier
                style={{ borderRadius: radius.md, marginLeft: -spacing.sm }}
                withInnerLines
                withOuterLines={false}
              />
            </ChartCard>

            <ChartCard title="Enrollment trend" subtitle="New enrollments">
              <BarChart
                data={{
                  labels: safeSeries(data.admissions.enrollment_trends).labels,
                  datasets: [{ data: safeSeries(data.admissions.enrollment_trends).data }],
                }}
                width={chartWidth}
                height={180}
                chartConfig={chartConfig}
                style={{ borderRadius: radius.md, marginLeft: -spacing.sm }}
                yAxisLabel=""
                yAxisSuffix=""
                fromZero
              />
            </ChartCard>

            <ChartCard title="Student attendance" subtitle="Attendance rate %">
              <LineChart
                data={{
                  labels: safeSeries(data.academics.attendance_trends).labels,
                  datasets: [{ data: safeSeries(data.academics.attendance_trends).data }],
                }}
                width={chartWidth}
                height={180}
                chartConfig={chartConfig}
                bezier
                style={{ borderRadius: radius.md, marginLeft: -spacing.sm }}
                yAxisSuffix="%"
                withOuterLines={false}
              />
            </ChartCard>

            <ChartCard title="Exam performance" subtitle="Average scores">
              <LineChart
                data={{
                  labels: safeSeries(data.academics.exam_trends).labels,
                  datasets: [{ data: safeSeries(data.academics.exam_trends).data }],
                }}
                width={chartWidth}
                height={180}
                chartConfig={chartConfig}
                bezier
                style={{ borderRadius: radius.md, marginLeft: -spacing.sm }}
                withOuterLines={false}
              />
            </ChartCard>

            <ChartCard title="Staff growth" subtitle="Headcount trend">
              <BarChart
                data={{
                  labels: safeSeries(data.hr.staff_growth).labels,
                  datasets: [{ data: safeSeries(data.hr.staff_growth).data }],
                }}
                width={chartWidth}
                height={180}
                chartConfig={chartConfig}
                style={{ borderRadius: radius.md, marginLeft: -spacing.sm }}
                yAxisLabel=""
                yAxisSuffix=""
                fromZero
              />
            </ChartCard>

            <ChartCard title="Visitors" subtitle="Front desk traffic">
              <BarChart
                data={{
                  labels: safeSeries(data.operations.visitors).labels,
                  datasets: [{ data: safeSeries(data.operations.visitors).data }],
                }}
                width={chartWidth}
                height={180}
                chartConfig={chartConfig}
                style={{ borderRadius: radius.md, marginLeft: -spacing.sm }}
                yAxisLabel=""
                yAxisSuffix=""
                fromZero
              />
            </ChartCard>

            {data.admissions.enrollment_pie.filter((s) => s.value > 0).length > 0 ? (
              <ChartCard title="Enrollment mix" subtitle="Distribution by category">
                <PieChart
                  data={data.admissions.enrollment_pie
                    .filter((s) => s.value > 0)
                    .map((s, index) => ({
                      name: s.name,
                      population: s.value,
                      color: pieColors[index % pieColors.length],
                      legendFontColor: palette.textSecondary,
                      legendFontSize: typography.caption.fontSize,
                    }))}
                  width={chartWidth}
                  height={180}
                  chartConfig={chartConfig}
                  accessor="population"
                  backgroundColor="transparent"
                  paddingLeft="8"
                  absolute
                />
              </ChartCard>
            ) : null}

            <Button
              label="Share summary"
              variant="secondary"
              onPress={() => void shareReport()}
              style={{ marginTop: spacing.sm }}
            />
            <Text
              style={{
                color: palette.textMuted,
                fontSize: typography.caption.fontSize,
                fontWeight: typography.caption.fontWeight,
                lineHeight: typography.caption.lineHeight,
                marginTop: spacing.sm,
                textAlign: 'center',
              }}
            >
              As of {data.as_of}
            </Text>
          </>
        )}
      </ScrollView>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center' },
});
