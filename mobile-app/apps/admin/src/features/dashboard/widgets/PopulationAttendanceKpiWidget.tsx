import { Ionicons } from '@expo/vector-icons';
import { KpiCard, WidgetShell } from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import React, { useCallback } from 'react';
import { KPI_METADATA } from '../config/kpiMetadata';
import { useKpiWidgetData } from '../hooks/useKpiWidgetData';
import { navigateToTab } from '../../../navigation/navigateWorkspace';

function todayYmd(): string {
  const d = new Date();
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${y}-${m}-${day}`;
}

export const PopulationAttendanceKpiWidget: React.FC = () => {
  const navigation = useNavigation();
  const { state, kpi, onRetry } = useKpiWidgetData('population_attendance_kpi');
  const meta = KPI_METADATA.population_attendance_kpi;

  const openReport = useCallback(
    (status?: 'all' | 'present' | 'absent' | 'unmarked') => {
      navigateToTab(navigation, 'Students', 'AttendanceReport', {
        date: todayYmd(),
        status: status && status !== 'all' ? status : undefined,
      });
    },
    [navigation],
  );

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
          stats={kpi.stats}
          icon={(kpi.icon ?? meta.icon) as keyof typeof Ionicons.glyphMap}
          onPress={() => openReport('all')}
          onStatPress={(stat) => {
            const label = stat.label.toLowerCase();
            if (label.includes('present')) openReport('present');
            else if (label.includes('absent')) openReport('absent');
            else if (label.includes('unmarked')) openReport('unmarked');
            else openReport('all');
          }}
        />
      ) : null}
    </WidgetShell>
  );
};
