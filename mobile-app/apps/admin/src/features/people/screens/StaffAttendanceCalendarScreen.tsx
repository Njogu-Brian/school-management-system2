import { useStaffAttendanceCalendar } from '@erp/core';
import {
  AcademicScreenHeader,
  AttendanceMonthCalendar,
  EmptyState,
  FinanceFieldSection,
  ScreenContainer,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useState } from 'react';
import { ActivityIndicator, RefreshControl, ScrollView, View } from 'react-native';
import type { PeopleStackParamList } from '../../../navigation/peopleStackTypes';

type Props = StackScreenProps<PeopleStackParamList, 'StaffAttendanceCalendar'>;

export const StaffAttendanceCalendarScreen: React.FC<Props> = ({ navigation }) => {
  const { colors, spacing } = useTheme();
  const now = new Date();
  const [year, setYear] = useState(now.getFullYear());
  const [month, setMonth] = useState(now.getMonth() + 1);
  const [selectedDate, setSelectedDate] = useState<string | null>(
    now.toISOString().slice(0, 10),
  );

  const calendarQuery = useStaffAttendanceCalendar(year, month);
  const days = calendarQuery.data?.days ?? [];
  const summary = calendarQuery.data?.summary;

  const shiftMonth = (delta: number) => {
    const next = new Date(year, month - 1 + delta, 1);
    setYear(next.getFullYear());
    setMonth(next.getMonth() + 1);
    setSelectedDate(null);
  };

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
        refreshControl={
          <RefreshControl
            refreshing={calendarQuery.isRefetching}
            onRefresh={() => void calendarQuery.refetch()}
            colors={[colors.primary]}
          />
        }
      >
        <AcademicScreenHeader
          title="Staff calendar"
          subtitle="Sign-in and sign-out by day"
          onBack={() => navigation.goBack()}
        />

        {calendarQuery.isLoading && !calendarQuery.data ? (
          <ActivityIndicator color={colors.primary} style={{ marginTop: 24 }} />
        ) : days.length === 0 ? (
          <EmptyState
            title="No calendar data"
            message="Attendance days will appear here once BioTime records sync."
            icon="calendar-outline"
          />
        ) : (
          <>
            {summary ? (
              <FinanceFieldSection
                title="This month"
                rows={[
                  { label: 'Present', value: String(summary.present) },
                  { label: 'Late', value: String(summary.late) },
                  { label: 'Absent', value: String(summary.absent) },
                  {
                    label: 'Attendance',
                    value:
                      summary.percentage != null ? `${Math.round(summary.percentage)}%` : '—',
                  },
                ]}
              />
            ) : null}

            <View style={{ marginTop: spacing.md }}>
              <AttendanceMonthCalendar
                year={year}
                month={month}
                days={days}
                selectedDate={selectedDate}
                onSelectDate={setSelectedDate}
                onShiftMonth={shiftMonth}
              />
            </View>
          </>
        )}
      </ScrollView>
    </ScreenContainer>
  );
};
