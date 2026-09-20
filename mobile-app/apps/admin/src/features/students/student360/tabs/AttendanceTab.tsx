import type { AttendanceTrendPoint } from '@erp/core';
import { useStudentAttendanceCalendar } from '@erp/core';
import {
  AttendanceMonthCalendar,
  EmptyState,
  StudentSummaryWidgets,
  type StudentSummaryWidgetData,
  useTheme,
} from '@erp/ui';
import React, { useMemo, useState } from 'react';
import { ActivityIndicator, StyleSheet, Text, View } from 'react-native';

export interface AttendanceTabProps {
  studentId: number;
  isLoading: boolean;
  isError: boolean;
  onRetry?: () => void;
  present: number;
  absent: number;
  late: number;
  percentage: number | null;
  consecutiveAbsences?: number | null;
  trend: AttendanceTrendPoint[];
}

export const AttendanceTab: React.FC<AttendanceTabProps> = ({
  studentId,
  isLoading,
  isError,
  onRetry,
  present,
  absent,
  late,
  percentage,
  consecutiveAbsences,
  trend,
}) => {
  const { palette, colors, spacing, typography, radius } = useTheme();
  const now = useMemo(() => new Date(), []);
  const [year, setYear] = useState(now.getFullYear());
  const [month, setMonth] = useState(now.getMonth() + 1);
  const [selectedDate, setSelectedDate] = useState<string | null>(null);
  const calendar = useStudentAttendanceCalendar(studentId, year, month, {
    enabled: studentId > 0,
  });

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

  const shiftMonth = (delta: number) => {
    const d = new Date(year, month - 1 + delta, 1);
    setYear(d.getFullYear());
    setMonth(d.getMonth() + 1);
    setSelectedDate(null);
  };

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

      {(consecutiveAbsences ?? 0) >= 2 ? (
        <View
          style={{
            marginTop: spacing.sm,
            padding: spacing.sm,
            borderRadius: radius.md,
            backgroundColor: `${colors.error}14`,
          }}
        >
          <Text style={{ color: colors.error, fontWeight: '700' }}>
            {consecutiveAbsences} consecutive absences
          </Text>
        </View>
      ) : null}

      <Text
        style={[
          styles.section,
          {
            color: palette.textSub,
            fontSize: typography.overline.fontSize,
            letterSpacing: typography.overline.letterSpacing,
            marginTop: spacing.lg,
            marginBottom: spacing.sm,
          },
        ]}
      >
        Attendance calendar
      </Text>
      {calendar.isLoading ? (
        <ActivityIndicator color={colors.primary} style={{ marginVertical: spacing.md }} />
      ) : calendar.isError ? (
        <EmptyState
          title="Could not load calendar"
          message={calendar.error instanceof Error ? calendar.error.message : 'Try again later.'}
          icon="alert-circle-outline"
          actionLabel="Retry"
          onAction={() => void calendar.refetch()}
        />
      ) : (
        <AttendanceMonthCalendar
          year={year}
          month={month}
          days={calendar.data ?? []}
          selectedDate={selectedDate}
          onSelectDate={setSelectedDate}
          onShiftMonth={shiftMonth}
        />
      )}

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
