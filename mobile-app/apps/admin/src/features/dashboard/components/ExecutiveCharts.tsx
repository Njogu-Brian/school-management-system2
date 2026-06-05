import { useTheme } from '@erp/ui';
import React, { useMemo } from 'react';
import { StyleSheet, Text, useWindowDimensions, View } from 'react-native';
import { BarChart, LineChart, PieChart } from 'react-native-chart-kit';

interface ChartSeries {
  labels: string[];
  values: number[];
}

interface PieSlice {
  name: string;
  value: number;
  color: string;
}

interface ExecutiveChartsProps {
  collections: ChartSeries;
  enrollmentPie: PieSlice[];
  attendance: ChartSeries;
}

export const ExecutiveCharts: React.FC<ExecutiveChartsProps> = ({
  collections,
  enrollmentPie,
  attendance,
}) => {
  const { colors, palette, spacing, fontSizes } = useTheme();
  const { width } = useWindowDimensions();
  const chartWidth = Math.min(width - spacing.md * 2, 380);

  const chartConfig = useMemo(
    () => ({
      backgroundColor: palette.surface,
      backgroundGradientFrom: palette.surface,
      backgroundGradientTo: palette.surface,
      decimalPlaces: 0,
      color: (opacity = 1) => `rgba(37, 99, 235, ${opacity})`,
      labelColor: () => palette.textSecondary,
      propsForDots: { r: '3', strokeWidth: '2', stroke: colors.primary },
    }),
    [colors.primary, palette],
  );

  const pieData = enrollmentPie.filter((s) => s.value > 0);

  return (
    <View style={{ gap: spacing.md }}>
      <View style={[styles.card, { borderColor: palette.border, backgroundColor: palette.surface }]}>
        <Text style={[styles.title, { color: palette.textPrimary, fontSize: fontSizes.sm }]}>Fee collections</Text>
        <LineChart
          data={{ labels: collections.labels, datasets: [{ data: collections.values.length ? collections.values : [0] }] }}
          width={chartWidth}
          height={180}
          chartConfig={chartConfig}
          bezier
          style={styles.chart}
        />
      </View>

      <View style={[styles.card, { borderColor: palette.border, backgroundColor: palette.surface }]}>
        <Text style={[styles.title, { color: palette.textPrimary, fontSize: fontSizes.sm }]}>Attendance %</Text>
        <BarChart
          data={{ labels: attendance.labels, datasets: [{ data: attendance.values.length ? attendance.values : [0] }] }}
          width={chartWidth}
          height={180}
          chartConfig={chartConfig}
          style={styles.chart}
          yAxisLabel=""
          yAxisSuffix="%"
          fromZero
        />
      </View>

      {pieData.length > 0 ? (
        <View style={[styles.card, { borderColor: palette.border, backgroundColor: palette.surface }]}>
          <Text style={[styles.title, { color: palette.textPrimary, fontSize: fontSizes.sm }]}>Enrollment mix</Text>
          <PieChart
            data={pieData.map((s) => ({
              name: s.name,
              population: s.value,
              color: s.color,
              legendFontColor: palette.textSecondary,
              legendFontSize: 11,
            }))}
            width={chartWidth}
            height={160}
            chartConfig={chartConfig}
            accessor="population"
            backgroundColor="transparent"
            paddingLeft="8"
            absolute
          />
        </View>
      ) : null}
    </View>
  );
};

const styles = StyleSheet.create({
  card: { borderWidth: StyleSheet.hairlineWidth, borderRadius: 10, padding: 12 },
  title: { fontWeight: '700', marginBottom: 8 },
  chart: { borderRadius: 8, marginLeft: -8 },
});
