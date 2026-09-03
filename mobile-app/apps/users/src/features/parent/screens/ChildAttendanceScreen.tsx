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
import { Pressable, Text, View } from 'react-native';
import type { ParentStackParamList } from '../../../navigation/parent/parentStackTypes';

const WEEKDAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

function statusColor(status: string | null | undefined, isSchoolDay: boolean, colors: { success: string; error: string; warning: string }, muted: string): string {
  const s = (status ?? '').toLowerCase();
  if (s === 'present') return colors.success;
  if (s === 'absent') return colors.error;
  if (s === 'late') return colors.warning;
  if (!isSchoolDay) return muted;
  return 'transparent';
}

function statusLabel(status: string | null | undefined): string {
  const s = (status ?? '').toLowerCase();
  if (s === 'present') return 'Present';
  if (s === 'absent') return 'Absent';
  if (s === 'late') return 'Late';
  return '';
}

export const ChildAttendanceScreen: React.FC = () => {
  const navigation = useNavigation();
  const route = useRoute<RouteProp<ParentStackParamList, 'ChildAttendance'>>();
  const { palette, spacing, typography, radius, colors } = useTheme();
  const studentId = route.params.studentId;
  const detail = useStudentDetail(studentId, { enabled: studentId > 0 });

  const now = useMemo(() => new Date(), []);
  const [year, setYear] = useState(now.getFullYear());
  const [month, setMonth] = useState(now.getMonth() + 1);
  const [selectedDate, setSelectedDate] = useState<string | null>(null);

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
    setSelectedDate(null);
  };

  const days = calendar.data ?? [];
  const leadingBlanks = days[0]?.weekday ?? new Date(year, month - 1, 1).getDay();
  const selected = days.find((d) => d.date === selectedDate);

  const present = days.filter((d) => (d.status ?? '').toLowerCase() === 'present').length;
  const absent = days.filter((d) => (d.status ?? '').toLowerCase() === 'absent').length;
  const late = days.filter((d) => (d.status ?? '').toLowerCase() === 'late').length;

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
          {trend.summary.percentage != null ? `${trend.summary.percentage}% present` : '—'}
        </Text>
        <Text style={{ color: palette.textSecondary, marginTop: spacing.xs, fontSize: typography.caption.fontSize }}>
          {present} present · {absent} absent · {late} late
        </Text>
      </View>

      <FilterChipRow label={monthLabel}>
        <FilterChip label="Previous" onPress={() => shiftMonth(-1)} />
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
      ) : (
        <View
          style={{
            backgroundColor: palette.surface,
            borderColor: palette.border,
            borderWidth: 1,
            borderRadius: radius.lg,
            padding: spacing.sm,
            marginBottom: spacing.md,
          }}
        >
          <View style={{ flexDirection: 'row', marginBottom: spacing.xs }}>
            {WEEKDAYS.map((d) => (
              <Text
                key={d}
                style={{
                  flex: 1,
                  textAlign: 'center',
                  color: palette.textMuted,
                  fontSize: typography.caption.fontSize,
                  fontWeight: '700',
                }}
              >
                {d}
              </Text>
            ))}
          </View>
          <View style={{ flexDirection: 'row', flexWrap: 'wrap' }}>
            {Array.from({ length: leadingBlanks }).map((_, i) => (
              <View key={`pad-${i}`} style={{ width: '14.28%', aspectRatio: 1 }} />
            ))}
            {days.map((day) => {
              const school = day.is_school_day !== false;
              const marked = Boolean(day.status);
              const bg = statusColor(day.status, school, colors, palette.borderSubtle ?? '#E5E7EB');
              const isSelected = selectedDate === day.date;
              const dayNum = Number(day.date.slice(-2));
              return (
                <Pressable
                  key={day.date}
                  onPress={() => setSelectedDate(day.date)}
                  style={{
                    width: '14.28%',
                    aspectRatio: 1,
                    alignItems: 'center',
                    justifyContent: 'center',
                    padding: 2,
                  }}
                >
                  <View
                    style={{
                      width: '100%',
                      height: '100%',
                      borderRadius: 10,
                      alignItems: 'center',
                      justifyContent: 'center',
                      backgroundColor: school ? (marked ? `${bg}22` : palette.surfaceRaised ?? palette.surface) : `${palette.textMuted}18`,
                      borderWidth: isSelected ? 2 : marked ? 1 : 0,
                      borderColor: isSelected ? colors.primary : marked ? bg : 'transparent',
                      opacity: school ? 1 : 0.45,
                    }}
                  >
                    <Text
                      style={{
                        color: marked ? bg : school ? palette.textPrimary : palette.textMuted,
                        fontWeight: marked ? '800' : '600',
                        fontSize: 13,
                      }}
                    >
                      {dayNum}
                    </Text>
                    {marked ? (
                      <View style={{ width: 6, height: 6, borderRadius: 3, backgroundColor: bg, marginTop: 2 }} />
                    ) : null}
                  </View>
                </Pressable>
              );
            })}
          </View>

          <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: spacing.md, marginTop: spacing.md, paddingHorizontal: spacing.xs }}>
            {[
              { label: 'Present', color: colors.success },
              { label: 'Absent', color: colors.error },
              { label: 'Late', color: colors.warning },
              { label: 'Weekend / holiday', color: palette.textMuted },
            ].map((item) => (
              <View key={item.label} style={{ flexDirection: 'row', alignItems: 'center', gap: 6 }}>
                <View style={{ width: 10, height: 10, borderRadius: 5, backgroundColor: item.color }} />
                <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>{item.label}</Text>
              </View>
            ))}
          </View>
        </View>
      )}

      {selected ? (
        <View
          style={{
            backgroundColor: palette.surface,
            borderColor: palette.border,
            borderWidth: 1,
            borderRadius: radius.lg,
            padding: spacing.md,
          }}
        >
          <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>
            {new Date(selected.date).toLocaleDateString('en-KE', { weekday: 'long', day: 'numeric', month: 'long' })}
          </Text>
          <Text style={{ color: palette.textSecondary, marginTop: 4 }}>
            {!selected.is_school_day
              ? 'No school on this day.'
              : selected.status
                ? `${statusLabel(selected.status)}${selected.is_excused ? ' (excused)' : ''}`
                : 'School day — attendance not marked yet.'}
          </Text>
        </View>
      ) : null}
    </ScreenContainer>
  );
};
