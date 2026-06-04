import type { AttendanceTrendPoint } from '@erp/core';
import { StudentSummaryWidgets, type StudentSummaryWidgetData } from '@erp/ui';
import React, { useMemo } from 'react';
import { ActivityIndicator, StyleSheet, Text, View } from 'react-native';
import { useTheme } from '@erp/ui';

export interface AttendanceTabProps {
  isLoading: boolean;
  isError: boolean;
  onRetry?: () => void;
  present: number;
  absent: number;
  late: number;
  percentage: number | null;
  trend: AttendanceTrendPoint[];
}

export const AttendanceTab: React.FC<AttendanceTabProps> = ({
  isLoading,
  isError,
  onRetry,
  present,
  absent,
  late,
  percentage,
  trend,
}) => {
  const { palette, colors, spacing, fontSizes, radius } = useTheme();

  const widgets = useMemo(
    (): StudentSummaryWidgetData[] => [
      { id: 'p', label: 'Present', value: String(present), icon: 'checkmark-circle-outline' },
      { id: 'a', label: 'Absent', value: String(absent), icon: 'close-circle-outline' },
      { id: 'l', label: 'Late', value: String(late), icon: 'time-outline' },
      {
        id: 'pct',
        label: 'Rate (month)',
        value: percentage != null ? `${percentage.toFixed(1)}%` : '—',
        icon: 'stats-chart-outline',
      },
    ],
    [present, absent, late, percentage],
  );

  if (isLoading) {
    return (
      <View style={styles.centered}>
        <ActivityIndicator color={colors.primary} />
      </View>
    );
  }

  if (isError) {
    return (
      <View style={styles.centered}>
        <Text style={{ color: colors.error, fontSize: fontSizes.sm }}>Could not load attendance.</Text>
        {onRetry ? (
          <Text
            onPress={onRetry}
            style={{ color: colors.primary, marginTop: spacing.sm, fontWeight: '600' }}
          >
            Retry
          </Text>
        ) : null}
      </View>
    );
  }

  const maxBar = Math.max(1, ...trend.map((t) => t.present + t.absent + t.late));

  return (
    <View>
      <StudentSummaryWidgets widgets={widgets} />

      <Text
        style={[
          styles.section,
          { color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: spacing.lg },
        ]}
      >
        Attendance trend (weekly)
      </Text>
      {trend.length === 0 ? (
        <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm }}>
          Not enough attendance marks yet.
        </Text>
      ) : (
        trend.map((point) => {
          const total = point.present + point.absent + point.late;
          const height = Math.max(8, Math.round((total / maxBar) * 72));
          return (
            <View key={point.label} style={[styles.trendRow, { marginBottom: spacing.sm }]}>
              <Text style={{ width: 56, color: palette.textSecondary, fontSize: fontSizes.xs }}>
                {point.label}
              </Text>
              <View style={styles.barTrack}>
                <View
                  style={[
                    styles.bar,
                    {
                      height,
                      backgroundColor: colors.primary,
                      borderRadius: radius.sm,
                      width: `${Math.min(100, (point.present / maxBar) * 100)}%`,
                    },
                  ]}
                />
              </View>
              <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginLeft: 8 }}>
                P{point.present} A{point.absent} L{point.late}
              </Text>
            </View>
          );
        })
      )}
    </View>
  );
};

const styles = StyleSheet.create({
  centered: { paddingVertical: 32, alignItems: 'center' },
  section: { fontWeight: '700', letterSpacing: 0.4, textTransform: 'uppercase' },
  trendRow: { flexDirection: 'row', alignItems: 'center' },
  barTrack: { flex: 1, height: 72, justifyContent: 'flex-end' },
  bar: { minWidth: 4 },
});
