import {
  useAttendanceReasonCodes,
  useAttendanceReport,
  useCan,
  useClassrooms,
  useClassroomStreams,
  useConsecutiveAbsences,
  useMarkStudentsAttendance,
  type AttendanceMarkStatus,
  type AttendanceReasonFields,
  type AttendanceReportRow,
  type AttendanceReportStatus,
} from '@erp/core';
import {
  AcademicScreenHeader,
  AttendanceReasonSheet,
  DatePickerField,
  EmptyState,
  FilterChip,
  FilterChipRow,
  ListEmptyState,
  ScreenContainer,
  SkeletonListRows,
  StudentSearchBar,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useEffect, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Pressable,
  RefreshControl,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import type { StudentsStackParamList } from '../../../navigation/studentsStackTypes';
import { showError, showSuccess } from '../../shared/utils/feedback';

type Props = StackScreenProps<StudentsStackParamList, 'AttendanceReport'>;
type ReportTab = AttendanceReportStatus | 'consecutive';

function formatDateYmd(d: Date): string {
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${y}-${m}-${day}`;
}

function parseDate(value?: string): Date {
  if (!value) return new Date();
  const d = new Date(`${value}T00:00:00`);
  return Number.isNaN(d.getTime()) ? new Date() : d;
}

const TABS: Array<{ key: ReportTab; label: string }> = [
  { key: 'all', label: 'All' },
  { key: 'present', label: 'Present' },
  { key: 'absent', label: 'Absent' },
  { key: 'late', label: 'Late' },
  { key: 'unmarked', label: 'Unmarked' },
  { key: 'consecutive', label: 'Consecutive' },
];

function StatusButton({
  status,
  active,
  onPress,
}: {
  status: AttendanceMarkStatus;
  active: boolean;
  onPress: () => void;
}) {
  const { colors, palette, typography, radius } = useTheme();
  const label = status === 'present' ? 'P' : status === 'absent' ? 'A' : status === 'late' ? 'L' : 'U';
  const bg =
    status === 'present'
      ? colors.success
      : status === 'absent'
        ? colors.error
        : status === 'late'
          ? colors.warning
          : palette.textMuted;
  return (
    <Pressable
      onPress={onPress}
      accessibilityRole="button"
      accessibilityState={{ selected: active }}
      style={{
        minWidth: 34,
        height: 34,
        borderRadius: radius.sm,
        alignItems: 'center',
        justifyContent: 'center',
        backgroundColor: active ? bg : palette.surfaceMuted,
        marginLeft: 4,
      }}
    >
      <Text
        style={{
          color: active ? '#fff' : palette.textPrimary,
          fontWeight: '800',
          fontSize: typography.caption.fontSize,
        }}
      >
        {label}
      </Text>
    </Pressable>
  );
}

export const AttendanceReportScreen: React.FC<Props> = ({ navigation, route }) => {
  const canView = useCan(['students.view', 'academics.view', 'dashboard.view']);
  const { palette, colors, spacing, typography, radius } = useTheme();
  const [selectedDate, setSelectedDate] = useState(() => parseDate(route.params?.date));
  const dateStr = formatDateYmd(selectedDate);
  const initialTab = (route.params?.status as ReportTab | undefined) ?? 'all';
  const [tab, setTab] = useState<ReportTab>(initialTab);
  const [searchInput, setSearchInput] = useState('');
  const [search, setSearch] = useState('');
  const [classroomId, setClassroomId] = useState<number | null>(null);
  const [streamId, setStreamId] = useState<number | null>(null);
  const [reasonStudent, setReasonStudent] = useState<AttendanceReportRow | null>(null);
  const [pendingStatus, setPendingStatus] = useState<AttendanceMarkStatus | null>(null);

  useEffect(() => {
    const t = setTimeout(() => setSearch(searchInput.trim()), 400);
    return () => clearTimeout(t);
  }, [searchInput]);

  const classroomsQuery = useClassrooms({ enabled: canView });
  const streamsQuery = useClassroomStreams(classroomId, { enabled: canView });
  const reasonCodesQuery = useAttendanceReasonCodes({ enabled: canView });
  const markMutation = useMarkStudentsAttendance();

  const reportParams = {
    date: dateStr,
    status: tab === 'consecutive' ? undefined : tab,
    classroom_id: classroomId,
    stream_id: streamId,
    search: search || undefined,
  };
  const consecutiveParams = {
    date: dateStr,
    threshold: 3,
    classroom_id: classroomId,
    stream_id: streamId,
    search: search || undefined,
  };

  const reportQuery = useAttendanceReport(reportParams, {
    enabled: canView && tab !== 'consecutive',
  });
  const consecutiveQuery = useConsecutiveAbsences(consecutiveParams, {
    enabled: canView && tab === 'consecutive',
  });

  const activeQuery = tab === 'consecutive' ? consecutiveQuery : reportQuery;
  const rows = useMemo(
    () => activeQuery.data?.pages.flatMap((p) => p.data) ?? [],
    [activeQuery.data],
  );
  const summary = tab === 'consecutive' ? null : reportQuery.data?.pages[0]?.summary;
  const schoolDay = reportQuery.data?.pages[0];
  const consecutiveTotal = consecutiveQuery.data?.pages[0]?.total;

  const markStudent = async (
    row: AttendanceReportRow,
    status: AttendanceMarkStatus,
    reason?: AttendanceReasonFields,
  ) => {
    try {
      await markMutation.mutateAsync({
        date: dateStr,
        records: [{ student_id: row.student_id, status, ...reason }],
      });
      showSuccess('Attendance', `${row.full_name} marked ${status}.`);
    } catch (err) {
      showError('Attendance', (err as Error).message);
    }
  };

  const onStatusPress = (row: AttendanceReportRow, status: AttendanceMarkStatus) => {
    const next = row.status === status ? 'unmarked' : status;
    if (next === 'absent' || next === 'late') {
      setReasonStudent(row);
      setPendingStatus(next);
      return;
    }
    void markStudent(row, next);
  };

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <EmptyState
          title="Access denied"
          message="You need permission to view attendance."
          icon="lock-closed-outline"
        />
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer
      scroll
      contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
      scrollProps={{
        refreshControl: (
          <RefreshControl
            refreshing={activeQuery.isRefetching && !activeQuery.isFetchingNextPage}
            onRefresh={() => void activeQuery.refetch()}
            colors={[colors.primary]}
          />
        ),
      }}
    >
      <AcademicScreenHeader
        title="Attendance report"
        subtitle={dateStr}
        onBack={() => navigation.goBack()}
      />

      <DatePickerField value={selectedDate} onChange={setSelectedDate} label="Date" maximumDate={new Date()} />

      {schoolDay && !schoolDay.is_school_day ? (
        <Text style={{ color: colors.warning, marginBottom: spacing.sm, fontWeight: '600' }}>
          This is not a school day. You can still unmark records.
        </Text>
      ) : null}

      <StudentSearchBar
        value={searchInput}
        onChangeText={setSearchInput}
        placeholder="Search student or admission…"
      />

      <FilterChipRow label="Class" wrap>
        <FilterChip
          label="All classes"
          active={classroomId == null}
          onPress={() => {
            setClassroomId(null);
            setStreamId(null);
          }}
        />
        {(classroomsQuery.data ?? []).map((c) => (
          <FilterChip
            key={c.id}
            label={c.name}
            active={classroomId === c.id}
            onPress={() => {
              setClassroomId(c.id);
              setStreamId(null);
            }}
          />
        ))}
      </FilterChipRow>
      {classroomId != null && (streamsQuery.data ?? []).length > 0 ? (
        <FilterChipRow label="Stream" wrap>
          <FilterChip label="All streams" active={streamId == null} onPress={() => setStreamId(null)} />
          {(streamsQuery.data ?? []).map((s) => (
            <FilterChip
              key={s.id}
              label={s.name}
              active={streamId === s.id}
              onPress={() => setStreamId(s.id)}
            />
          ))}
        </FilterChipRow>
      ) : null}

      <FilterChipRow wrap>
        {TABS.map((item) => (
          <FilterChip
            key={item.key}
            label={
              item.key === 'consecutive'
                ? consecutiveTotal != null
                  ? `Consecutive (${consecutiveTotal})`
                  : item.label
                : summary
                  ? `${item.label} (${
                      item.key === 'all'
                        ? summary.total
                        : item.key === 'present'
                          ? summary.present
                          : item.key === 'absent'
                            ? summary.absent
                            : item.key === 'late'
                              ? summary.late
                              : summary.unmarked
                    })`
                  : item.label
            }
            active={tab === item.key}
            onPress={() => setTab(item.key)}
          />
        ))}
      </FilterChipRow>

      {activeQuery.isLoading ? (
        <SkeletonListRows count={6} />
      ) : activeQuery.isError ? (
        <EmptyState
          title="Could not load attendance"
          message={(activeQuery.error as Error).message}
          icon="alert-circle-outline"
          actionLabel="Retry"
          onAction={() => void activeQuery.refetch()}
        />
      ) : rows.length === 0 ? (
        <ListEmptyState
          title={tab === 'consecutive' ? 'No consecutive absences' : 'No students in this view'}
          message={
            tab === 'consecutive'
              ? 'Nobody currently has 3 or more days absent in a row.'
              : 'Try another date, class, or status filter.'
          }
          icon="people-outline"
        />
      ) : (
        rows.map((row) => (
          <View
            key={row.student_id}
            style={{
              backgroundColor: palette.surface,
              borderColor: palette.border,
              borderWidth: 1,
              borderRadius: radius.lg,
              padding: spacing.md,
              marginBottom: spacing.sm,
            }}
          >
            <View style={{ flexDirection: 'row', alignItems: 'flex-start' }}>
              <Pressable
                onPress={() =>
                  navigation.navigate('StudentDetail', {
                    studentId: row.student_id,
                    tab: 'attendance',
                  })
                }
                style={{ flex: 1, paddingRight: spacing.sm }}
              >
                <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>{row.full_name}</Text>
                <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: 2 }}>
                  {row.admission_number}
                  {row.classroom_name ? ` · ${row.classroom_name}` : ''}
                  {row.stream_name ? ` ${row.stream_name}` : ''}
                </Text>
                {(row.consecutive_absences ?? 0) >= 2 ? (
                  <Text style={{ color: colors.error, fontWeight: '700', marginTop: 4, fontSize: typography.caption.fontSize }}>
                    {row.consecutive_absences} consecutive absences
                  </Text>
                ) : null}
              </Pressable>
              <View style={{ flexDirection: 'row' }}>
                {(['present', 'absent', 'late', 'unmarked'] as AttendanceMarkStatus[]).map((status) => (
                  <StatusButton
                    key={status}
                    status={status}
                    active={row.status === status}
                    onPress={() => onStatusPress(row, status)}
                  />
                ))}
              </View>
            </View>
          </View>
        ))
      )}

      {activeQuery.hasNextPage ? (
        <Pressable
          onPress={() => void activeQuery.fetchNextPage()}
          style={{ alignItems: 'center', paddingVertical: spacing.md }}
        >
          {activeQuery.isFetchingNextPage ? (
            <ActivityIndicator color={colors.primary} />
          ) : (
            <Text style={{ color: colors.primary, fontWeight: '700' }}>Load more</Text>
          )}
        </Pressable>
      ) : null}

      <AttendanceReasonSheet
        visible={reasonStudent != null}
        studentName={reasonStudent?.full_name}
        statusLabel={pendingStatus ?? 'absent'}
        codes={reasonCodesQuery.data ?? []}
        onSkip={() => {
          if (reasonStudent && pendingStatus) {
            void markStudent(reasonStudent, pendingStatus);
          }
          setReasonStudent(null);
          setPendingStatus(null);
        }}
        onSave={(value) => {
          if (reasonStudent && pendingStatus) {
            void markStudent(reasonStudent, pendingStatus, value);
          }
          setReasonStudent(null);
          setPendingStatus(null);
        }}
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flexGrow: 1, justifyContent: 'center' },
});
