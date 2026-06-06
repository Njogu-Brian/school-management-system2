import { ChartCard, useTheme } from '@erp/ui';
import React, { useMemo } from 'react';
import { StyleSheet, useWindowDimensions } from 'react-native';
import { BarChart } from 'react-native-chart-kit';

export interface AdmissionsFunnelChartProps {
  pending: number;
  underReview: number;
  waitlisted: number;
  enrolled: number;
  rejected: number;
}

/** Admissions funnel bar chart from existing stats data. */
export const AdmissionsFunnelChart: React.FC<AdmissionsFunnelChartProps> = ({
  pending,
  underReview,
  waitlisted,
  enrolled,
  rejected,
}) => {
  const { palette, spacing } = useTheme();
  const { width } = useWindowDimensions();
  const chartWidth = Math.min(width - spacing.md * 4, 360);

  const values = [pending, underReview, waitlisted, enrolled, rejected];
  const hasData = values.some((v) => v > 0);
  if (!hasData) return null;

  const chartConfig = useMemo(
    () => ({
      backgroundColor: palette.surfaceRaised,
      backgroundGradientFrom: palette.surfaceRaised,
      backgroundGradientTo: palette.surfaceRaised,
      decimalPlaces: 0,
      color: (opacity = 1) => `rgba(0, 74, 153, ${opacity})`,
      labelColor: () => palette.textSecondary,
      propsForBackgroundLines: { stroke: palette.borderSubtle, strokeWidth: 1 },
    }),
    [palette],
  );

  return (
    <ChartCard title="Admissions funnel" subtitle="Applications by stage">
      <BarChart
        data={{
          labels: ['Pending', 'Review', 'Wait', 'Enrolled', 'Rejected'],
          datasets: [{ data: values }],
        }}
        width={chartWidth}
        height={160}
        chartConfig={chartConfig}
        style={styles.chart}
        yAxisLabel=""
        yAxisSuffix=""
        fromZero
        showValuesOnTopOfBars
      />
    </ChartCard>
  );
};

const styles = StyleSheet.create({
  chart: { borderRadius: 12, marginLeft: -8 },
});
