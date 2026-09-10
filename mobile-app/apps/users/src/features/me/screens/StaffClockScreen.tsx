import { useStaffAttendanceCalendar, useStaffClockToday } from '@erp/core';
import {
  AcademicScreenHeader,
  EmptyState,
  FilterChip,
  FilterChipRow,
  FinanceFieldSection,
  ScreenContainer,
  SkeletonListRows,
  useTheme,
} from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import React, { useMemo, useState } from 'react';
import { Pressable, RefreshControl, ScrollView, Text, View } from 'react-native';

const WEEKDAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

function formatClockTime(value: string | null | undefined): string {
  if (!value) return '—';
  return value.slice(0, 5);
}

function statusColor(
  status: string | null | undefined,
  isSchoolDay: boolean,
  colors: { success: string; error: string; warning: string },
  muted: string,
): string {
  const s = (status ?? '').toLowerCase();
  if (s === 'present') return colors.success;
  if (s === 'absent') return colors.error;
  if (s === 'late' || s === 'half_day') return colors.warning;
  if (!isSchoolDay) return muted;
  return 'transparent';
}

function statusLabel(status: string | null | undefined): string {
  const s = (status ?? '').toLowerCase();
  if (s === 'present') return 'Present';
  if (s === 'absent') return 'Absent';
  if (s === 'late') return 'Late';
  if (s === 'half_day') return 'Half day';
  return 'No record';
}

export const StaffClockScreen: React.FC = () => {
  const navigation = useNavigation();
  const { colors, palette, spacing, typography, radius } = useTheme();
  const todayQuery = useStaffClockToday();

  const now = useMemo(() => new Date(), []);
  const [year, setYear] = useState(now.getFullYear());
  const [month, setMonth] = useState(now.getMonth() + 1);
  const [selectedDate, setSelectedDate] = useState<string | null>(null);

  const calendarQuery = useStaffAttendanceCalendar(year, month);
  const days = calendarQuery.data?.days ?? [];
  const summary = calendarQuery.data?.summary;

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

  const leadingBlanks = days[0]?.weekday ?? new Date(year, month - 1, 1).getDay();
  const selected = days.find((d) => d.date === selectedDate);

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
        refreshControl={
          <RefreshControl
            refreshing={todayQuery.isRefetching || calendarQuery.isRefetching}
            onRefresh={() => {
              void todayQuery.refetch();
              void calendarQuery.refetch();
            }}
            colors={[colors.primary]}
          />
        }
      >
        <AcademicScreenHeader
          title="My attendance"
          subtitle="Gate sign-in calendar"
          onBack={navigation.canGoBack() ? () => navigation.goBack() : undefined}
        />

        <FinanceFieldSection
          title="Today"
          rows={[
            { label: 'Status', value: todayQuery.data?.status?.replace(/_/g, ' ') ?? 'No record yet' },
            { label: 'Check in', value: formatClockTime(todayQuery.data?.check_in_time) },
            { label: 'Check out', value: formatClockTime(todayQuery.data?.check_out_time) },
          ]}
        />
        <Text
          style={{
            color: palette.textSecondary,
            fontSize: typography.caption.fontSize,
            marginTop: spacing.sm,
            marginBottom: spacing.lg,
          }}
        >
          Sign in and out at the campus gate. GPS clock-in is turned off. If a punch is missing, ask HR to correct
          it.
        </Text>

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
            {summary?.percentage != null ? `${summary.percentage}% present` : '—'}
          </Text>
          <Text style={{ color: palette.textSecondary, marginTop: spacing.xs, fontSize: typography.caption.fontSize }}>
            {summary?.present ?? 0} present · {summary?.absent ?? 0} absent · {summary?.late ?? 0} late/half-day
          </Text>
        </View>

        <FilterChipRow label={monthLabel}>
          <FilterChip label="Previous" onPress={() => shiftMonth(-1)} />
          <FilterChip label="Next" onPress={() => shiftMonth(1)} />
        </FilterChipRow>

        {calendarQuery.isLoading ? (
          <SkeletonListRows count={4} />
        ) : calendarQuery.isError ? (
          <EmptyState
            title="Could not load attendance"
            message={calendarQuery.error instanceof Error ? calendarQuery.error.message : 'Try again later.'}
            icon="alert-circle-outline"
            actionLabel="Retry"
            onAction={() => void calendarQuery.refetch()}
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
                        backgroundColor: school
                          ? marked
                            ? `${bg}22`
                            : palette.surfaceRaised ?? palette.surface
                          : `${palette.textMuted}18`,
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

            <View
              style={{
                flexDirection: 'row',
                flexWrap: 'wrap',
                gap: spacing.md,
                marginTop: spacing.md,
                paddingHorizontal: spacing.xs,
              }}
            >
              {[
                { label: 'Present', color: colors.success },
                { label: 'Absent', color: colors.error },
                { label: 'Late / half day', color: colors.warning },
                { label: 'Weekend', color: palette.textMuted },
              ].map((item) => (
                <View key={item.label} style={{ flexDirection: 'row', alignItems: 'center', gap: 6 }}>
                  <View style={{ width: 10, height: 10, borderRadius: 5, backgroundColor: item.color }} />
                  <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
                    {item.label}
                  </Text>
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
              marginBottom: spacing.md,
            }}
          >
            <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>{selected.date}</Text>
            <Text style={{ color: palette.textSecondary, marginTop: 4 }}>
              {statusLabel(selected.status)}
              {selected.is_school_day === false ? ' · Weekend' : ''}
            </Text>
            <Text style={{ color: palette.textMuted, marginTop: spacing.sm, fontSize: typography.caption.fontSize }}>
              Check in {formatClockTime(selected.check_in_time)} → Check out{' '}
              {formatClockTime(selected.check_out_time)}
            </Text>
          </View>
        ) : (
          <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize }}>
            Tap a day to see check-in and check-out times.
          </Text>
        )}
      </ScrollView>
    </ScreenContainer>
  );
};
