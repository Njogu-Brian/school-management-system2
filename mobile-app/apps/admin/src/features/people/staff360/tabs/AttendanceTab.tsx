import type { StaffAttendanceDay } from '@erp/core';
import {
  AttendanceDayListItem,
  EmptyState,
  StudentSummaryWidgets,
  type StudentSummaryWidgetData,
  useTheme,
} from '@erp/ui';
import React, { useMemo } from 'react';
import { ActivityIndicator, StyleSheet, Text, View } from 'react-native';
import { formatPercent } from '../utils/formatters';

export interface AttendanceTabProps {
  isLoading: boolean;
  isError: boolean;
  onRetry?: () => void;
  present: number;
  absent: number;
  late: number;
  halfDay: number;
  percentage: number | null;
  rangeLabel: string;
  days: StaffAttendanceDay[];
}

export const AttendanceTab: React.FC<AttendanceTabProps> = ({
  isLoading,
  isError,
  onRetry,
  present,
  absent,
  late,
  halfDay,
  percentage,
  rangeLabel,
  days,
}) => {
  const { palette, colors, spacing, typography } = useTheme();

  const widgets = useMemo(
    (): StudentSummaryWidgetData[] => [
      { id: 'p', label: 'Present', value: String(present), icon: 'checkmark-circle-outline' },
      { id: 'a', label: 'Absent', value: String(absent), icon: 'close-circle-outline' },
      { id: 'l', label: 'Late', value: String(late), icon: 'time-outline' },
      {
        id: 'pct',
        label: 'Rate',
        value: formatPercent(percentage),
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
      <EmptyState
        title="Could not load attendance"
        message="Attendance history failed to load."
        icon="alert-circle-outline"
        actionLabel={onRetry ? 'Retry' : undefined}
        onAction={onRetry}
      />
    );
  }

  return (
    <View>
      <Text
        style={{
          color: palette.textSecondary,
          fontSize: typography.overline.fontSize,
          marginBottom: spacing.sm,
        }}
      >
        {rangeLabel}
      </Text>
      <StudentSummaryWidgets widgets={widgets} />

      {halfDay > 0 ? (
        <Text
          style={{
            color: palette.textSecondary,
            fontSize: typography.overline.fontSize,
            marginTop: spacing.sm,
          }}
        >
          Half days: {halfDay}
        </Text>
      ) : null}

      <Text
        style={[
          styles.section,
          {
            color: palette.textMuted,
            fontSize: typography.overline.fontSize,
            letterSpacing: typography.overline.letterSpacing,
            marginTop: spacing.lg,
          },
        ]}
      >
        Daily log
      </Text>

      {days.length === 0 ? (
        <EmptyState
          title="No attendance marks"
          message="No attendance marks in this period."
          icon="calendar-outline"
        />
      ) : (
        days.map((d) => (
          <AttendanceDayListItem
            key={d.id}
            item={{
              id: d.id,
              date: d.date,
              status: d.status,
              checkInTime: d.checkInTime,
              checkOutTime: d.checkOutTime,
              source: d.source,
            }}
          />
        ))
      )}
    </View>
  );
};

const styles = StyleSheet.create({
  centered: { paddingVertical: 32, alignItems: 'center' },
  section: { fontWeight: '700', textTransform: 'uppercase', marginBottom: 8 },
});
