import {
  useCurrentUser,
  useStaffTeachingAssignments,
  useTeacherTimetable,
  type TimetableSlotRecord,
} from '@erp/core';
import {
  AcademicScreenHeader,
  EmptyState,
  ScreenContainer,
  SkeletonListRows,
  Soft3DIcon,
  useTheme,
} from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import React, { useMemo } from 'react';
import { RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';

const WEEKDAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'] as const;

function groupSlotsByDay(slots: TimetableSlotRecord[]): Record<string, TimetableSlotRecord[]> {
  const grouped: Record<string, TimetableSlotRecord[]> = {};
  for (const day of WEEKDAYS) {
    grouped[day] = [];
  }
  for (const slot of slots) {
    const day = slot.day?.trim() || 'Monday';
    if (!grouped[day]) grouped[day] = [];
    grouped[day].push(slot);
  }
  for (const day of Object.keys(grouped)) {
    grouped[day].sort((a, b) => a.start_time.localeCompare(b.start_time));
  }
  return grouped;
}

function formatTimeRange(start: string, end: string): string {
  const trim = (t: string) => (t.length >= 5 ? t.slice(0, 5) : t);
  return `${trim(start)} – ${trim(end)}`;
}

export const TimetableHubScreen: React.FC = () => {
  const navigation = useNavigation();
  const user = useCurrentUser();
  const { palette, spacing, typography, radius, colors } = useTheme();
  const staffId = user?.staffId ?? user?.teacherId ?? 0;

  const timetableQuery = useTeacherTimetable(staffId, { enabled: staffId > 0 });
  const assignmentsQuery = useStaffTeachingAssignments(staffId, staffId > 0);

  const slots = timetableQuery.data?.slots ?? [];
  const byDay = useMemo(() => groupSlotsByDay(slots), [slots]);
  const hasSlots = slots.length > 0;

  const assignmentRows = useMemo(() => {
    const data = assignmentsQuery.data;
    if (!data?.assignments?.slots?.length) return [];
    return data.assignments.slots.map((slot) => {
      const roles = [
        slot.is_class_teacher ? 'Class teacher' : null,
        slot.is_assistant_teacher ? 'Assistant' : null,
      ]
        .filter(Boolean)
        .join(' · ');
      return {
        key: slot.key,
        label: slot.key.replace(/_/g, ' '),
        roles,
      };
    });
  }, [assignmentsQuery.data]);

  if (staffId <= 0) {
    return (
      <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
        <AcademicScreenHeader title="My timetable" onBack={() => navigation.goBack()} />
        <EmptyState
          title="Staff profile not linked"
          message="Ask the school to link your login to a staff record (staff_id on /user)."
          icon="calendar-outline"
        />
      </ScreenContainer>
    );
  }

  const loading = timetableQuery.isLoading && assignmentsQuery.isLoading;
  const refreshing = timetableQuery.isRefetching || assignmentsQuery.isRefetching;

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={() => {
              void timetableQuery.refetch();
              void assignmentsQuery.refetch();
            }}
            tintColor={colors.primary}
          />
        }
      >
        <AcademicScreenHeader
          title="My timetable"
          subtitle={
            hasSlots
              ? 'Current term · pull to refresh'
              : 'Teaching assignments until slots are published'
          }
          onBack={() => navigation.goBack()}
        />

        {loading ? (
          <SkeletonListRows count={6} />
        ) : hasSlots ? (
          WEEKDAYS.map((day) => {
            const daySlots = byDay[day] ?? [];
            return (
              <View
                key={day}
                style={[
                  styles.dayCard,
                  {
                    backgroundColor: palette.surface,
                    borderColor: palette.border,
                    borderRadius: radius.lg,
                    padding: spacing.md,
                    marginBottom: spacing.sm,
                  },
                ]}
              >
                <View style={styles.dayHeader}>
                  <Soft3DIcon name="calendar-outline" tone="indigo" size={36} />
                  <Text
                    style={{
                      color: palette.textPrimary,
                      fontWeight: '700',
                      fontSize: typography.titleSmall.fontSize,
                      marginLeft: spacing.sm,
                      flex: 1,
                    }}
                  >
                    {day}
                  </Text>
                  <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize }}>
                    {daySlots.length} {daySlots.length === 1 ? 'slot' : 'slots'}
                  </Text>
                </View>
                {daySlots.length === 0 ? (
                  <Text
                    style={{
                      color: palette.textSecondary,
                      fontStyle: 'italic',
                      marginTop: spacing.sm,
                      fontSize: typography.caption.fontSize,
                    }}
                  >
                    No classes scheduled
                  </Text>
                ) : (
                  daySlots.map((slot) => (
                    <View
                      key={slot.id}
                      style={[
                        styles.slotRow,
                        {
                          borderLeftColor: colors.primary,
                          backgroundColor: palette.surfaceRaised ?? palette.background,
                          borderRadius: radius.md,
                          marginTop: spacing.sm,
                          padding: spacing.sm,
                        },
                      ]}
                    >
                      <Text
                        style={{
                          color: colors.primary,
                          fontWeight: '700',
                          fontSize: typography.caption.fontSize,
                          marginBottom: 2,
                        }}
                      >
                        {formatTimeRange(slot.start_time, slot.end_time)}
                      </Text>
                      <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>
                        {slot.subject_name || 'Subject'}
                      </Text>
                      {slot.room ? (
                        <Text
                          style={{
                            color: palette.textSecondary,
                            fontSize: typography.caption.fontSize,
                            marginTop: 2,
                          }}
                        >
                          {slot.room}
                        </Text>
                      ) : null}
                    </View>
                  ))
                )}
              </View>
            );
          })
        ) : assignmentRows.length > 0 ? (
          <>
            <Text
              style={{
                color: palette.textSecondary,
                fontSize: typography.caption.fontSize,
                marginBottom: spacing.md,
              }}
            >
              No timed slots yet — here are your class assignments for this term.
            </Text>
            {assignmentRows.map((row) => (
              <View
                key={row.key}
                style={{
                  flexDirection: 'row',
                  alignItems: 'center',
                  gap: spacing.md,
                  backgroundColor: palette.surface,
                  borderColor: palette.border,
                  borderWidth: 1,
                  borderRadius: radius.lg,
                  padding: spacing.md,
                  marginBottom: spacing.sm,
                }}
              >
                <Soft3DIcon name="school-outline" tone="cyan" size={40} />
                <View style={{ flex: 1 }}>
                  <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>{row.label}</Text>
                  {row.roles ? (
                    <Text
                      style={{
                        color: palette.textSecondary,
                        fontSize: typography.caption.fontSize,
                        marginTop: 2,
                      }}
                    >
                      {row.roles}
                    </Text>
                  ) : null}
                </View>
              </View>
            ))}
          </>
        ) : (
          <EmptyState
            title="No timetable yet"
            message="Teaching slots will appear once the school publishes your timetable."
            icon="grid-outline"
            actionLabel="Retry"
            onAction={() => {
              void timetableQuery.refetch();
              void assignmentsQuery.refetch();
            }}
          />
        )}

        {timetableQuery.isError ? (
          <Text
            style={{
              color: colors.error,
              fontSize: typography.caption.fontSize,
              marginTop: spacing.md,
              textAlign: 'center',
            }}
          >
            {(timetableQuery.error as Error).message}
          </Text>
        ) : null}
      </ScrollView>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  dayCard: { borderWidth: StyleSheet.hairlineWidth },
  dayHeader: { flexDirection: 'row', alignItems: 'center' },
  slotRow: { borderLeftWidth: 3 },
});
