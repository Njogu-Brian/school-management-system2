import { useCan, useIncomeStatement } from '@erp/core';
import {
  AcademicScreenHeader,
  ChartCard,
  EmptyState,
  FilterChip,
  FilterChipRow,
  KpiCard,
  ListEmptyState,
  ScreenContainer,
  SkeletonWidgetGrid,
  WidgetGrid,
  WidgetShell,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useMemo, useState } from 'react';
import { RefreshControl, ScrollView, StyleSheet, Text, useWindowDimensions, View } from 'react-native';
import { BarChart } from 'react-native-chart-kit';
import type { ReportsStackParamList } from '../../../navigation/reportsStackTypes';
import { formatKes } from '../../shared/utils/formatters';

type Props = StackScreenProps<ReportsStackParamList, 'IncomeStatement'>;

const RANGE_OPTIONS = [
  { months: 3, label: '3 months' },
  { months: 6, label: '6 months' },
  { months: 12, label: '12 months' },
];

function hexToRgba(hex: string, opacity = 1): string {
  const cleaned = hex.replace('#', '');
  const r = parseInt(cleaned.slice(0, 2), 16);
  const g = parseInt(cleaned.slice(2, 4), 16);
  const b = parseInt(cleaned.slice(4, 6), 16);
  return `rgba(${r}, ${g}, ${b}, ${opacity})`;
}

export const IncomeStatementScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('reports.view');
  const { colors, palette, spacing, typography, radius } = useTheme();
  const { width } = useWindowDimensions();
  const [months, setMonths] = useState(6);
  const query = useIncomeStatement({ enabled: canView, months });

  const chartWidth = Math.min(width - spacing.md * 2, 420);
  const chartConfig = useMemo(
    () => ({
      backgroundColor: palette.surfaceRaised,
      backgroundGradientFrom: palette.surfaceRaised,
      backgroundGradientTo: palette.surfaceRaised,
      decimalPlaces: 0,
      color: (opacity = 1) => hexToRgba(colors.primary, opacity),
      labelColor: () => palette.textSecondary,
      propsForBackgroundLines: { strokeDasharray: '', stroke: palette.borderSubtle, strokeWidth: 1 },
    }),
    [colors.primary, palette],
  );

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={[styles.denied, { padding: spacing.lg }]}>
        <EmptyState
          title="Access denied"
          message="You need reports.view permission to view the income statement."
          icon="lock-closed-outline"
        />
      </ScreenContainer>
    );
  }

  const data = query.data;
  const state = query.isLoading ? 'loading' : query.isError ? 'error' : 'success';
  const netPositive = (data?.totals.net ?? 0) >= 0;

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
          title="Income statement"
          subtitle="Fee collections vs approved expenses"
          onBack={() => navigation.goBack()}
        />

        <FilterChipRow label="Range">
          {RANGE_OPTIONS.map((opt) => (
            <FilterChip
              key={opt.months}
              label={opt.label}
              active={months === opt.months}
              onPress={() => setMonths(opt.months)}
            />
          ))}
        </FilterChipRow>

        {query.isLoading ? (
          <SkeletonWidgetGrid count={3} />
        ) : query.isError ? (
          <ListEmptyState
            title="Could not load income statement"
            message={(query.error as Error).message}
            icon="alert-circle-outline"
            actionLabel="Retry"
            onAction={() => void query.refetch()}
          />
        ) : !data || data.months.length === 0 ? (
          <EmptyState
            title="No income data"
            message="There is no income statement data for the selected range."
            icon="stats-chart-outline"
          />
        ) : (
          <>
            <WidgetGrid>
              <WidgetShell state={state} title="Income">
                <KpiCard label="Income" value={formatKes(data.totals.income)} icon="trending-up-outline" />
              </WidgetShell>
              <WidgetShell state={state} title="Expenses">
                <KpiCard label="Expenses" value={formatKes(data.totals.expenses)} icon="trending-down-outline" />
              </WidgetShell>
              <WidgetShell state={state} title="Net">
                <KpiCard
                  label={netPositive ? 'Net surplus' : 'Net deficit'}
                  value={formatKes(Math.abs(data.totals.net))}
                  icon={netPositive ? 'checkmark-circle-outline' : 'warning-outline'}
                />
              </WidgetShell>
            </WidgetGrid>

            <ChartCard title="Monthly income" subtitle="Fee collections per month">
              <BarChart
                data={{
                  labels: data.months.map((m) => m.label.split(' ')[0]),
                  datasets: [{ data: data.months.map((m) => m.income) }],
                }}
                width={chartWidth}
                height={200}
                chartConfig={chartConfig}
                fromZero
                yAxisLabel=""
                yAxisSuffix=""
                withInnerLines
                style={{ borderRadius: radius.md, marginLeft: -spacing.sm }}
              />
            </ChartCard>

            <ChartCard title="Monthly expenses" subtitle="Approved & paid expenses per month">
              <BarChart
                data={{
                  labels: data.months.map((m) => m.label.split(' ')[0]),
                  datasets: [{ data: data.months.map((m) => m.expenses) }],
                }}
                width={chartWidth}
                height={200}
                chartConfig={chartConfig}
                fromZero
                yAxisLabel=""
                yAxisSuffix=""
                withInnerLines
                style={{ borderRadius: radius.md, marginLeft: -spacing.sm }}
              />
            </ChartCard>

            <Text
              style={{
                color: palette.textMuted,
                fontSize: typography.overline.fontSize,
                fontWeight: typography.overline.fontWeight,
                letterSpacing: typography.overline.letterSpacing,
                lineHeight: typography.overline.lineHeight,
                marginTop: spacing.lg,
                marginBottom: spacing.xs,
              }}
            >
              MONTH BY MONTH
            </Text>
            {data.months.map((m) => (
              <View
                key={m.month}
                style={{
                  borderWidth: StyleSheet.hairlineWidth,
                  borderColor: palette.borderSubtle,
                  backgroundColor: palette.surfaceRaised,
                  borderRadius: radius.card,
                  padding: spacing.md,
                  marginBottom: spacing.sm,
                }}
              >
                <View style={styles.monthHeader}>
                  <Text
                    style={{
                      color: palette.textPrimary,
                      fontSize: typography.titleSmall.fontSize,
                      fontWeight: typography.titleSmall.fontWeight,
                      lineHeight: typography.titleSmall.lineHeight,
                    }}
                  >
                    {m.label}
                  </Text>
                  <Text
                    style={{
                      color: m.net >= 0 ? colors.success ?? colors.primary : colors.error,
                      fontSize: typography.titleSmall.fontSize,
                      fontWeight: typography.titleSmall.fontWeight,
                    }}
                  >
                    {m.net >= 0 ? '+' : '−'}
                    {formatKes(Math.abs(m.net))}
                  </Text>
                </View>
                <View style={[styles.monthRow, { marginTop: spacing.sm }]}>
                  <Text
                    style={{
                      color: palette.textSecondary,
                      fontSize: typography.caption.fontSize,
                      fontWeight: typography.caption.fontWeight,
                      lineHeight: typography.caption.lineHeight,
                    }}
                  >
                    Income {formatKes(m.income)}
                  </Text>
                  <Text
                    style={{
                      color: palette.textSecondary,
                      fontSize: typography.caption.fontSize,
                      fontWeight: typography.caption.fontWeight,
                      lineHeight: typography.caption.lineHeight,
                    }}
                  >
                    Expenses {formatKes(m.expenses)}
                  </Text>
                </View>
              </View>
            ))}

            <Text
              style={{
                color: palette.textMuted,
                fontSize: typography.caption.fontSize,
                fontWeight: typography.caption.fontWeight,
                lineHeight: typography.caption.lineHeight,
                marginTop: spacing.sm,
              }}
            >
              Income = fee payments received. Expenses = approved & paid expense vouchers. Period {data.from} to{' '}
              {data.to}.
            </Text>
          </>
        )}
      </ScrollView>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center' },
  monthHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  monthRow: { flexDirection: 'row', justifyContent: 'space-between' },
});
