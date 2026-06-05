import {
  useCan,
  useClassroomStreams,
  useClassrooms,
  useInfiniteStudentList,
  type StudentSummary,
} from '@erp/core';
import {
  ScreenContainer,
  StudentFilters,
  StudentListItem,
  StudentSearchBar,
  useTheme,
} from '@erp/ui';
import type { StudentEnrollmentStatusFilter, StudentGenderFilter } from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React, { useCallback, useMemo } from 'react';
import {
  ActivityIndicator,
  FlatList,
  Pressable,
  RefreshControl,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import type { StudentsStackParamList } from '../../../navigation/studentsStackTypes';
import { useStudentRegistryState } from '../hooks/useStudentRegistryState';
import { summaryToListItem } from '../utils/mapToListItem';

export const StudentRegistryScreen: React.FC = () => {
  const canView = useCan('students.view');
  const navigation = useNavigation<StackNavigationProp<StudentsStackParamList>>();
  const { colors, palette, spacing, fontSizes } = useTheme();

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

  const openDetail = useCallback(
    (summary: StudentSummary) => {
      navigation.navigate('StudentDetail', { studentId: summary.id, summary });
    },
    [navigation],
  );

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
        <Text style={{ color: palette.textSecondary, fontSize: fontSizes.md, textAlign: 'center' }}>
          You need students.view permission to open the registry.
        </Text>
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer scroll={false} style={styles.container}>
      <StudentSearchBar value={searchInput} onChangeText={setSearchInput} />

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

      {listQuery.isLoading ? (
        <View style={styles.centered}>
          <ActivityIndicator color={colors.primary} />
        </View>
      ) : null}

      {listQuery.isError ? (
        <View style={styles.centered}>
          <Text style={{ color: colors.error, fontSize: fontSizes.sm }}>
            {(listQuery.error as Error).message}
          </Text>
          <Pressable onPress={() => void listQuery.refetch()} style={{ marginTop: spacing.sm }}>
            <Text style={{ color: colors.primary, fontWeight: '600' }}>Retry</Text>
          </Pressable>
        </View>
      ) : null}

      <FlatList
        data={students}
        keyExtractor={(item) => String(item.id)}
        renderItem={({ item }) => (
          <StudentListItem
            student={summaryToListItem(item, () => openDetail(item))}
          />
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
            <ActivityIndicator color={colors.primary} style={{ marginVertical: 16 }} />
          ) : null
        }
        ListEmptyComponent={
          !listQuery.isLoading && !listQuery.isError ? (
            <Text
              style={{
                color: palette.textSecondary,
                textAlign: 'center',
                marginTop: spacing.lg,
                fontSize: fontSizes.sm,
              }}
            >
              No students match your filters.
            </Text>
          ) : null
        }
        contentContainerStyle={{ paddingBottom: spacing.xl }}
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  container: { flex: 1, paddingHorizontal: 16 },
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
  centered: { paddingVertical: 24, alignItems: 'center' },
});
