import React, { useMemo } from 'react';
import { Pressable, Text, View } from 'react-native';
import { FilterChip, FilterChipRow } from '../primitives/FilterChip';
import { useTheme } from '../theme/ThemeContext';

const WEEKDAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

export interface AttendanceCalendarDayView {
  date: string;
  status?: string | null;
  is_excused?: boolean;
  is_school_day?: boolean;
  weekday?: number;
  check_in_time?: string | null;
  check_out_time?: string | null;
}

export interface AttendanceMonthCalendarProps {
  year: number;
  month: number;
  days: AttendanceCalendarDayView[];
  selectedDate?: string | null;
  onSelectDate?: (date: string) => void;
  onShiftMonth?: (delta: number) => void;
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
  if (s === 'late') return colors.warning;
  if (!isSchoolDay) return muted;
  return 'transparent';
}

function formatClockTime(value: string | null | undefined): string {
  if (!value) return '—';
  return value.slice(0, 5);
}

function statusLabel(status: string | null | undefined): string {
  const s = (status ?? '').toLowerCase();
  if (s === 'present') return 'Present';
  if (s === 'absent') return 'Absent';
  if (s === 'late') return 'Late';
  if (s === 'half_day') return 'Half day';
  return '';
}

export const AttendanceMonthCalendar: React.FC<AttendanceMonthCalendarProps> = ({
  year,
  month,
  days,
  selectedDate,
  onSelectDate,
  onShiftMonth,
}) => {
  const { palette, spacing, typography, radius, colors } = useTheme();

  const monthLabel = useMemo(
    () => new Date(year, month - 1, 1).toLocaleDateString('en-KE', { month: 'long', year: 'numeric' }),
    [year, month],
  );

  const leadingBlanks = days[0]?.weekday ?? new Date(year, month - 1, 1).getDay();
  const selected = days.find((d) => d.date === selectedDate);

  return (
    <View>
      {onShiftMonth ? (
        <FilterChipRow label={monthLabel}>
          <FilterChip label="Previous" onPress={() => onShiftMonth(-1)} />
          <FilterChip label="Next" onPress={() => onShiftMonth(1)} />
        </FilterChipRow>
      ) : null}

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
                onPress={() => onSelectDate?.(day.date)}
                accessibilityRole="button"
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
            { label: 'Late', color: colors.warning },
            { label: 'Weekend / holiday', color: palette.textMuted },
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
          <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>
            {new Date(selected.date).toLocaleDateString('en-KE', {
              weekday: 'long',
              day: 'numeric',
              month: 'long',
            })}
          </Text>
          <Text style={{ color: palette.textSecondary, marginTop: 4 }}>
            {!selected.is_school_day
              ? 'No school on this day.'
              : selected.status
                ? `${statusLabel(selected.status)}${selected.is_excused ? ' (excused)' : ''}`
                : 'School day — attendance not marked yet.'}
          </Text>
          {selected.is_school_day && (selected.check_in_time || selected.check_out_time) ? (
            <Text style={{ color: palette.textPrimary, marginTop: 8, fontWeight: '600' }}>
              Sign in {formatClockTime(selected.check_in_time)} → Sign out{' '}
              {formatClockTime(selected.check_out_time)}
            </Text>
          ) : null}
        </View>
      ) : null}
    </View>
  );
};
