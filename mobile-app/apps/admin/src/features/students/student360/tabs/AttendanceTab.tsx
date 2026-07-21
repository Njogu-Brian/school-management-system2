import type { AttendanceTrendPoint } from '@erp/core';
import { EmptyState, StudentSummaryWidgets, type StudentSummaryWidgetData, useTheme } from '@erp/ui';
import React, { useMemo } from 'react';
import { ActivityIndicator, StyleSheet, Text, View } from 'react-native';

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
  const { palette, colors, spacing, typography, radius } = useTheme();

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
      <View style={[styles.centered, { paddingVertical: spacing.xl }]}>
        <ActivityIndicator color={colors.primary} />
      </View>
    );
  }

  if (isError) {
    return (
      <EmptyState
        title="Could not load attendance"
        message="Pull to refresh or retry to load attendance marks."
        icon="alert-circle-outline"
        actionLabel={onRetry ? 'Retry' : undefined}
        onAction={onRetry}
      />
    );
  }

  const maxBar = Math.max(1, ...trend.map((t) => t.present + t.absent + t.late));

  return (
    <View>
      <StudentSummaryWidgets widgets={widgets} />

      <Text
        style={[
          styles.section,
          {
            color: palette.textSub,
            fontSize: typography.overline.fontSize,
            letterSpacing: typography.overline.letterSpacing,
            marginTop: spacing.lg,
          },
        ]}
      >
        Attendance trend (weekly)
      </Text>
      {trend.length === 0 ? (
        <EmptyState
          title="No trend yet"
          message="Not enough attendance marks to chart a weekly trend."
          icon="stats-chart-outline"
        />
      ) : (
        trend.map((point) => {
          const total = point.present + point.absent + point.late;
          const height = Math.max(8, Math.round((total / maxBar) * 72));
          return (
            <View key={point.label} style={[styles.trendRow, { marginBottom: spacing.sm }]}>
              <Text
                style={{
                  width: 56,
                  color: palette.textSub,
                  fontSize: typography.caption.fontSize,
                }}
              >
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
              <Text
                style={{
                  color: palette.textSub,
                  fontSize: typography.caption.fontSize,
                  marginLeft: spacing.sm,
                }}
              >
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
  centered: { alignItems: 'center' },
  section: { fontWeight: '700', textTransform: 'uppercase' },
  trendRow: { flexDirection: 'row', alignItems: 'center' },
  barTrack: { flex: 1, height: 72, justifyContent: 'flex-end' },
  bar: { minWidth: 4 },
});
