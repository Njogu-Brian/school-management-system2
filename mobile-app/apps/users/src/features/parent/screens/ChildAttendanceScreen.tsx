import {
  useStudentAttendanceCalendar,
  useStudentAttendanceTrend,
  useStudentDetail,
} from '@erp/core';
import {
  AcademicScreenHeader,
  EmptyState,
  FilterChip,
  FilterChipRow,
  ScreenContainer,
  SkeletonListRows,
  useTheme,
} from '@erp/ui';
import { useNavigation, useRoute, type RouteProp } from '@react-navigation/native';
import React, { useMemo, useState } from 'react';
import { Text, View } from 'react-native';
import type { ParentStackParamList } from '../../../navigation/parent/parentStackTypes';

export const ChildAttendanceScreen: React.FC = () => {
  const navigation = useNavigation();
  const route = useRoute<RouteProp<ParentStackParamList, 'ChildAttendance'>>();
  const { palette, spacing, typography, radius, colors } = useTheme();
  const studentId = route.params.studentId;
  const detail = useStudentDetail(studentId, { enabled: studentId > 0 });

  const now = useMemo(() => new Date(), []);
  const [year, setYear] = useState(now.getFullYear());
  const [month, setMonth] = useState(now.getMonth() + 1);

  const calendar = useStudentAttendanceCalendar(studentId, year, month);
  const trend = useStudentAttendanceTrend(studentId);

  const monthLabel = useMemo(
    () => new Date(year, month - 1, 1).toLocaleDateString('en-KE', { month: 'long', year: 'numeric' }),
    [year, month],
  );

  const shiftMonth = (delta: number) => {
    const d = new Date(year, month - 1 + delta, 1);
    setYear(d.getFullYear());
    setMonth(d.getMonth() + 1);
  };

  const statusCounts = useMemo(() => {
    const days = calendar.data ?? [];
    const counts: Record<string, number> = {};
    for (const day of days) {
      const key = (day.status || 'unknown').toLowerCase();
      counts[key] = (counts[key] ?? 0) + 1;
    }
    return counts;
  }, [calendar.data]);

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
      <AcademicScreenHeader
        title="Attendance"
        subtitle={detail.data?.fullName ?? undefined}
        onBack={() => navigation.goBack()}
      />

      <View
        style={{
          backgroundColor: palette.surface,
          borderColor: palette.border,
          borderWidth: 1,
          borderRadius: radius.lg,
          padding: spacing.md,
          marginBottom: spacing.md,
        }}
      >
        <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>This month</Text>
        <Text style={{ color: palette.textPrimary, fontSize: 28, fontWeight: '700', marginTop: 4 }}>
          {trend.summary.percentage != null ? `${trend.summary.percentage}%` : '—'}
        </Text>
        <Text style={{ color: palette.textSecondary, marginTop: spacing.xs, fontSize: typography.caption.fontSize }}>
          Present {trend.summary.present} · Absent {trend.summary.absent} · Late {trend.summary.late}
        </Text>
      </View>

      {trend.trend.length > 0 ? (
        <View style={{ marginBottom: spacing.md }}>
          <Text style={{ color: palette.textPrimary, fontWeight: '700', marginBottom: spacing.sm }}>Recent trend</Text>
          {trend.trend.map((point) => (
            <View
              key={point.label}
              style={{
                flexDirection: 'row',
                justifyContent: 'space-between',
                paddingVertical: spacing.xs,
                borderBottomWidth: 1,
                borderBottomColor: palette.borderSubtle ?? palette.border,
              }}
            >
              <Text style={{ color: palette.textSecondary }}>{point.label}</Text>
              <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>
                P{point.present} A{point.absent} L{point.late}
              </Text>
            </View>
          ))}
        </View>
      ) : null}

      <FilterChipRow label={monthLabel}>
        <FilterChip label="Prev" onPress={() => shiftMonth(-1)} />
        <FilterChip label="Next" onPress={() => shiftMonth(1)} />
      </FilterChipRow>

      {calendar.isLoading || trend.isLoading ? (
        <SkeletonListRows count={4} />
      ) : calendar.isError ? (
        <EmptyState
          title="Could not load attendance"
          message={calendar.error instanceof Error ? calendar.error.message : 'Try again later.'}
          icon="alert-circle-outline"
        />
      ) : (calendar.data ?? []).length === 0 ? (
        <EmptyState title="No records" message={`No attendance marked for ${monthLabel}.`} icon="calendar-outline" />
      ) : (
        <>
          <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm, marginBottom: spacing.md }}>
            {Object.entries(statusCounts).map(([status, count]) => (
              <View
                key={status}
                style={{
                  backgroundColor: `${colors.primary}12`,
                  borderRadius: radius.md,
                  paddingHorizontal: spacing.sm,
                  paddingVertical: spacing.xs,
                }}
              >
                <Text style={{ color: colors.primary, fontWeight: '600', fontSize: typography.caption.fontSize }}>
                  {status}: {count}
                </Text>
              </View>
            ))}
          </View>
          {(calendar.data ?? []).map((day) => (
            <View
              key={day.date}
              style={{
                flexDirection: 'row',
                justifyContent: 'space-between',
                paddingVertical: spacing.sm,
                borderBottomWidth: 1,
                borderBottomColor: palette.border,
              }}
            >
              <Text style={{ color: palette.textPrimary }}>{day.date}</Text>
              <Text style={{ color: palette.textSecondary, fontWeight: '600', textTransform: 'capitalize' }}>
                {day.status}
                {day.is_excused ? ' (excused)' : ''}
              </Text>
            </View>
          ))}
        </>
      )}
    </ScreenContainer>
  );
};
