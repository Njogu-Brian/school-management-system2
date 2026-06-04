import { Ionicons } from '@expo/vector-icons';
import { KpiCard, WidgetShell, type WidgetDisplayState } from '@erp/ui';
import React from 'react';
import { KPI_PLACEHOLDERS, WIDGET_DEMO_STATES } from '../data/placeholders';
import type { DashboardWidgetId } from '../types/widget';

export interface KpiWidgetBaseProps {
  widgetId: DashboardWidgetId;
  state?: WidgetDisplayState;
  onRetry?: () => void;
}

export const KpiWidgetBase: React.FC<KpiWidgetBaseProps> = ({
  widgetId,
  state: stateProp,
  onRetry,
}) => {
  const data = KPI_PLACEHOLDERS[widgetId];
  const state = stateProp ?? WIDGET_DEMO_STATES[widgetId] ?? 'success';

  return (
    <WidgetShell
      state={state}
      title={data.label}
      onRetry={onRetry}
      emptyMessage="No data for the selected period"
      errorMessage="Unable to load this KPI"
    >
      <KpiCard
        label={data.label}
        value={data.value}
        delta={data.delta}
        deltaPositive={data.deltaPositive}
        icon={data.icon as keyof typeof Ionicons.glyphMap}
      />
    </WidgetShell>
  );
};
