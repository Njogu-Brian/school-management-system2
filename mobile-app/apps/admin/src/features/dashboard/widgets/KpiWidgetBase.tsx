import { Ionicons } from '@expo/vector-icons';
import { KpiCard, WidgetShell } from '@erp/ui';
import React from 'react';
import { KPI_METADATA } from '../config/kpiMetadata';
import { useKpiWidgetData } from '../hooks/useKpiWidgetData';
import type { DashboardWidgetId } from '../types/widget';

export interface KpiWidgetBaseProps {
  widgetId: DashboardWidgetId;
}

export const KpiWidgetBase: React.FC<KpiWidgetBaseProps> = ({ widgetId }) => {
  const { state, kpi, onRetry } = useKpiWidgetData(widgetId);
  const meta = KPI_METADATA[widgetId];

  return (
    <WidgetShell
      state={state}
      title={meta.label}
      onRetry={onRetry}
      emptyMessage="No data for the selected period"
      errorMessage="Unable to load this KPI"
    >
      {kpi && state === 'success' ? (
        <KpiCard
          label={kpi.label}
          value={kpi.value}
          delta={kpi.delta}
          deltaPositive={kpi.deltaPositive}
          icon={(kpi.icon ?? meta.icon) as keyof typeof Ionicons.glyphMap}
        />
      ) : null}
    </WidgetShell>
  );
};
