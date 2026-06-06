import { useExecutiveAnalytics, type AnalyticsPeriod } from '@erp/core';
import { Button, DashboardSection, FilterChip, FilterChipRow, KpiCard, WidgetGrid, WidgetShell, useTheme } from '@erp/ui';
import React, { useState } from 'react';
import { Share, View } from 'react-native';
import { formatKes } from '../../shared/utils/formatters';
import { ExecutiveCharts } from '../components/ExecutiveCharts';

const PERIODS: AnalyticsPeriod[] = ['week', 'month', 'term', 'year'];

export const ExecutiveDashboardSection: React.FC = () => {
  const { spacing } = useTheme();
  const [period, setPeriod] = useState<AnalyticsPeriod>('month');
  const analyticsQuery = useExecutiveAnalytics(period);

  const state = analyticsQuery.isLoading ? 'loading' : analyticsQuery.isError ? 'error' : 'success';
  const data = analyticsQuery.data;

  const shareReport = async () => {
    if (!data) {
      return;
    }
    const body = [
      `Royal Kings ERP — Executive summary (${period})`,
      `Collected: ${formatKes(data.finance.monthly_collections)}`,
      `Outstanding: ${formatKes(data.finance.outstanding_balances)}`,
      `Inventory alerts: ${data.operations.inventory_alerts}`,
      `Assets: ${data.operations.assets}`,
    ].join('\n');
    await Share.share({ message: body, title: 'Executive analytics' });
  };

  return (
    <DashboardSection title="Executive analytics" headerRight={<Button label="Share" variant="ghost" onPress={() => void shareReport()} />}>
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

      <WidgetGrid>
        <WidgetShell state={state} title="Finance" onRetry={() => void analyticsQuery.refetch()}>
          <KpiCard
            label="Collected (period)"
            value={formatKes(data?.finance.monthly_collections)}
            icon="cash-outline"
          />
        </WidgetShell>
        <WidgetShell state={state} title="Outstanding">
          <KpiCard
            label="Outstanding"
            value={formatKes(data?.finance.outstanding_balances)}
            icon="wallet-outline"
          />
        </WidgetShell>
        <WidgetShell state={state} title="Operations">
          <KpiCard
            label="Inventory alerts"
            value={String(data?.operations.inventory_alerts ?? '—')}
            icon="warning-outline"
          />
        </WidgetShell>
        <WidgetShell state={state} title="Assets">
          <KpiCard label="Fixed assets" value={String(data?.operations.assets ?? '—')} icon="cube-outline" />
        </WidgetShell>
      </WidgetGrid>

      {data ? (
        <View style={{ marginTop: spacing.md }}>
          <ExecutiveCharts
            collections={data.finance.daily_collections}
            enrollmentPie={data.admissions.enrollment_pie}
            attendance={data.academics.attendance_trends}
          />
        </View>
      ) : null}
    </DashboardSection>
  );
};
