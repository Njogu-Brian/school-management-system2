import {
  useAdmissionsStats,
  useCan,
  useClassroomStreams,
  useClassrooms,
  useDashboardStats,
  useInfiniteStudentList,
  type StudentSummary,
} from '@erp/core';
import {
  countActiveFilters,
  DashboardHero,
  EmptyState,
  ListEmptyState,
  RegistryListLayout,
  ScreenContainer,
  SkeletonListRows,
  StudentFilters,
  StudentListItem,
  StudentSearchBar,
  useTheme,
} from '@erp/ui';
import type { StudentEnrollmentStatusFilter, StudentGenderFilter } from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React, { useCallback, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  RefreshControl,
  StyleSheet,
} from 'react-native';
import type { StudentsStackParamList } from '../../../navigation/studentsStackTypes';
import { useStudentRegistryState } from '../hooks/useStudentRegistryState';
import { summaryToListItem } from '../utils/mapToListItem';

export const StudentRegistryScreen: React.FC = () => {
  const canView = useCan('students.view');
  const navigation = useNavigation<StackNavigationProp<StudentsStackParamList>>();
  const { colors, spacing } = useTheme();
  const [filtersOpen, setFiltersOpen] = useState(false);

  const {
    searchInput,
    setSearchInput,
    gradeLevel,
    setGradeLevel,
    classroomId,
    setClassroomId,
    streamId,
    setStreamId,
    status,
    setStatus,
    gender,
    setGender,
    filters,
  } = useStudentRegistryState();

  const classroomsQuery = useClassrooms({ enabled: canView });
  const streamsQuery = useClassroomStreams(classroomId, { enabled: canView });
  const listQuery = useInfiniteStudentList(filters, { enabled: canView });
  const dashboardStats = useDashboardStats({ enabled: canView });
  const admissionsStats = useAdmissionsStats({ enabled: canView });

  const classrooms = classroomsQuery.data ?? [];

  const gradeOptions = useMemo(() => {
    const levels = new Map<string, { value: number | string; label: string }>();
    for (const c of classrooms) {
      if (c.level == null || c.level === '') continue;
      const key = String(c.level);
      if (!levels.has(key)) {
        levels.set(key, { value: c.level as number | string, label: `Grade ${c.level}` });
      }
    }
    return Array.from(levels.values()).sort((a, b) =>
      String(a.label).localeCompare(String(b.label)),
    );
  }, [classrooms]);

  const classOptions = useMemo(() => {
    let list = classrooms;
    if (gradeLevel != null && gradeLevel !== '') {
      list = list.filter((c) => String(c.level) === String(gradeLevel));
    }
    return list.map((c) => ({ value: c.id, label: c.name }));
  }, [classrooms, gradeLevel]);

  const streamOptions = useMemo(
    () => (streamsQuery.data ?? []).map((s) => ({ value: s.id, label: s.name })),
    [streamsQuery.data],
  );

  const students = useMemo(
    () => listQuery.data?.pages.flatMap((p) => p.items) ?? [],
    [listQuery.data],
  );

  const listTotal = listQuery.data?.pages[0]?.total;
  const enrolled = dashboardStats.data?.total_students;
  const newThisTerm = admissionsStats.data?.enrolled;

  const heroMeta = useMemo(() => {
    const parts: string[] = [];
    if (listTotal != null) parts.push(`${listTotal} active`);
    if (newThisTerm != null && newThisTerm > 0) parts.push(`+${newThisTerm} new this term`);
    return parts.length > 0 ? parts.join(' · ') : undefined;
  }, [listTotal, newThisTerm]);

  const activeFilterCount = countActiveFilters([
    gradeLevel,
    classroomId,
    streamId,
    status,
    gender,
  ]);

  const openDetail = useCallback(
    (summary: StudentSummary) => {
      navigation.navigate('StudentDetail', { studentId: summary.id, summary });
    },
    [navigation],
  );

  const clearFilters = useCallback(() => {
    setSearchInput('');
    setGradeLevel(null);
    setClassroomId(null);
    setStreamId(null);
    setStatus('all');
    setGender('all');
    setFiltersOpen(false);
  }, [setSearchInput, setGradeLevel, setClassroomId, setStreamId, setStatus, setGender]);

  const onGradeChange = useCallback(
    (value: number | string | null) => {
      setGradeLevel(value);
      setClassroomId(null);
      setStreamId(null);
    },
    [setGradeLevel, setClassroomId, setStreamId],
  );

  const onClassroomChange = useCallback(
    (value: number | null) => {
      setClassroomId(value);
      setStreamId(null);
    },
    [setClassroomId, setStreamId],
  );

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <EmptyState
          title="Access denied"
          message="You need students.view permission to open the registry."
          icon="lock-closed-outline"
        />
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer scroll={false} style={styles.flex}>
      <RegistryListLayout
        data={students}
        keyExtractor={(item) => String(item.id)}
        hero={
          <DashboardHero
            variant="students"
            title="Students"
            subtitle={enrolled != null ? `${enrolled} enrolled` : 'Student registry'}
            meta={heroMeta}
          />
        }
        searchBar={
          <StudentSearchBar
            value={searchInput}
            onChangeText={setSearchInput}
            placeholder="Search students…"
          />
        }
        activeFilterCount={activeFilterCount}
        filtersOpen={filtersOpen}
        onOpenFilters={() => setFiltersOpen(true)}
        onCloseFilters={() => setFiltersOpen(false)}
        onApplyFilters={() => setFiltersOpen(false)}
        onClearFilters={clearFilters}
        filterSheetTitle="Student filters"
        filterContent={
          <StudentFilters
            gradeLevel={gradeLevel}
            classroomId={classroomId}
            streamId={streamId}
            status={status as StudentEnrollmentStatusFilter}
            gender={gender as StudentGenderFilter}
            gradeOptions={gradeOptions}
            classOptions={classOptions}
            streamOptions={streamOptions}
            onGradeChange={onGradeChange}
            onClassroomChange={onClassroomChange}
            onStreamChange={setStreamId}
            onStatusChange={setStatus}
            onGenderChange={setGender}
          />
        }
        renderItem={({ item }) => (
          <StudentListItem student={summaryToListItem(item, () => openDetail(item))} />
        )}
        refreshControl={
          <RefreshControl
            refreshing={listQuery.isRefetching && !listQuery.isFetchingNextPage}
            onRefresh={() => void listQuery.refetch()}
            colors={[colors.primary]}
            tintColor={colors.primary}
          />
        }
        onEndReached={() => {
          if (listQuery.hasNextPage && !listQuery.isFetchingNextPage) {
            void listQuery.fetchNextPage();
          }
        }}
        onEndReachedThreshold={0.4}
        ListFooterComponent={
          listQuery.isFetchingNextPage ? (
            <ActivityIndicator color={colors.primary} style={{ marginVertical: spacing.md }} />
          ) : null
        }
        ListEmptyComponent={
          listQuery.isLoading ? (
            <SkeletonListRows variant="avatar" />
          ) : listQuery.isError ? (
            <ListEmptyState
              title="Could not load students"
              message={(listQuery.error as Error).message}
              icon="alert-circle-outline"
              actionLabel="Retry"
              onAction={() => void listQuery.refetch()}
            />
          ) : (
            <ListEmptyState
              entityName="students"
              icon="people-outline"
              onClearFilters={clearFilters}
            />
          )
        }
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  flex: { flex: 1 },
  denied: { flex: 1, justifyContent: 'center' },
});
