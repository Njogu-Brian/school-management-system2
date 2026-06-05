import type { StaffAttendanceDay } from '@erp/core';
import { AttendanceDayListItem, StudentSummaryWidgets, type StudentSummaryWidgetData } from '@erp/ui';
import React, { useMemo } from 'react';
import { ActivityIndicator, Pressable, StyleSheet, Text, View } from 'react-native';
import { useTheme } from '@erp/ui';
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
  const { palette, colors, spacing, fontSizes } = useTheme();

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
      <View style={styles.centered}>
        <Text style={{ color: colors.error, fontSize: fontSizes.sm }}>
          Could not load attendance history.
        </Text>
        {onRetry ? (
          <Pressable onPress={onRetry}>
            <Text style={{ color: colors.primary, marginTop: spacing.sm, fontWeight: '600' }}>
              Retry
            </Text>
          </Pressable>
        ) : null}
      </View>
    );
  }

  return (
    <View>
      <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginBottom: spacing.sm }}>
        {rangeLabel}
      </Text>
      <StudentSummaryWidgets widgets={widgets} />

      {halfDay > 0 ? (
        <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: spacing.sm }}>
          Half days: {halfDay}
        </Text>
      ) : null}

      <Text
        style={[
          styles.section,
          { color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: spacing.lg },
        ]}
      >
        Daily log
      </Text>

      {days.length === 0 ? (
        <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm }}>
          No attendance marks in this period.
        </Text>
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
  section: { fontWeight: '700', letterSpacing: 0.4, textTransform: 'uppercase', marginBottom: 8 },
});
