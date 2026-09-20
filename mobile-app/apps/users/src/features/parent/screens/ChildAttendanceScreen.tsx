import {
  useParentAbsenceHistory,
  useStudentAttendanceCalendar,
  useStudentAttendanceTrend,
  useStudentDetail,
} from '@erp/core';
import {
  AcademicScreenHeader,
  AttendanceMonthCalendar,
  Button,
  EmptyState,
  ScreenContainer,
  SkeletonListRows,
  useTheme,
} from '@erp/ui';
import { useNavigation, useRoute, type RouteProp } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React, { useMemo, useState } from 'react';
import { RefreshControl, Text, View } from 'react-native';
import type { ParentStackParamList } from '../../../navigation/parent/parentStackTypes';

export const ChildAttendanceScreen: React.FC = () => {
  const navigation = useNavigation<StackNavigationProp<ParentStackParamList>>();
  const route = useRoute<RouteProp<ParentStackParamList, 'ChildAttendance'>>();
  const { palette, spacing, typography, radius, colors } = useTheme();
  const studentId = route.params.studentId;
  const detail = useStudentDetail(studentId, { enabled: studentId > 0 });
  const history = useParentAbsenceHistory(studentId, { enabled: studentId > 0 });

  const now = useMemo(() => new Date(), []);
  const [year, setYear] = useState(now.getFullYear());
  const [month, setMonth] = useState(now.getMonth() + 1);
  const [selectedDate, setSelectedDate] = useState<string | null>(null);

  const calendar = useStudentAttendanceCalendar(studentId, year, month);
  const trend = useStudentAttendanceTrend(studentId);

  const shiftMonth = (delta: number) => {
    const d = new Date(year, month - 1 + delta, 1);
    setYear(d.getFullYear());
    setMonth(d.getMonth() + 1);
    setSelectedDate(null);
  };

  const days = calendar.data ?? [];

  const present = days.filter((d) => (d.status ?? '').toLowerCase() === 'present').length;
  const absent = days.filter((d) => (d.status ?? '').toLowerCase() === 'absent').length;
  const late = days.filter((d) => (d.status ?? '').toLowerCase() === 'late').length;

  return (
    <ScreenContainer
      scroll
      contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
      scrollProps={{
        refreshControl: (
          <RefreshControl
            refreshing={calendar.isRefetching || history.isRefetching || trend.isRefetching}
            onRefresh={() => {
              void calendar.refetch();
              void history.refetch();
              void trend.refetch();
              void detail.refetch();
            }}
            colors={[colors.primary]}
          />
        ),
      }}
    >
      <AcademicScreenHeader
        title="Attendance"
        subtitle={detail.data?.fullName ?? undefined}
        onBack={() => navigation.goBack()}
      />

      <Button
        label="Report absence"
        onPress={() => navigation.navigate('ReportAbsence', { studentId })}
        style={{ marginBottom: spacing.md }}
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
          {trend.summary.percentage != null ? `${trend.summary.percentage}% present` : '—'}
        </Text>
        <Text style={{ color: palette.textSecondary, marginTop: spacing.xs, fontSize: typography.caption.fontSize }}>
          {present} present · {absent} absent · {late} late
        </Text>
      </View>

      {calendar.isLoading || trend.isLoading ? (
        <SkeletonListRows count={4} />
      ) : calendar.isError ? (
        <EmptyState
          title="Could not load attendance"
          message={calendar.error instanceof Error ? calendar.error.message : 'Try again later.'}
          icon="alert-circle-outline"
          actionLabel="Retry"
          onAction={() => void calendar.refetch()}
        />
      ) : (
        <AttendanceMonthCalendar
          year={year}
          month={month}
          days={days}
          selectedDate={selectedDate}
          onSelectDate={setSelectedDate}
          onShiftMonth={shiftMonth}
        />
      )}

      <Text style={{ color: palette.textPrimary, fontWeight: '700', marginBottom: spacing.sm }}>
        Reported absences
      </Text>
      {history.isLoading ? (
        <SkeletonListRows count={2} />
      ) : history.isError ? (
        <EmptyState
          title="Could not load history"
          message={history.error instanceof Error ? history.error.message : 'Try again.'}
          icon="alert-circle-outline"
          actionLabel="Retry"
          onAction={() => void history.refetch()}
        />
      ) : (history.data ?? []).length === 0 ? (
        <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize }}>
          No parent-reported absences yet.
        </Text>
      ) : (
        (history.data ?? []).slice(0, 12).map((row) => (
          <View
            key={row.id}
            style={{
              paddingVertical: spacing.sm,
              borderBottomWidth: 1,
              borderBottomColor: palette.border,
            }}
          >
            <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>{row.date}</Text>
            <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
              {row.excuse_notes || row.reason || 'Excused absence'}
              {row.reason_code ? ` · ${row.reason_code}` : ''}
            </Text>
          </View>
        ))
      )}
    </ScreenContainer>
  );
};
