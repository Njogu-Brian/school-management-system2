import { useClassrooms, useInfiniteStudentList } from '@erp/core';
import {
  AcademicScreenHeader,
  EmptyState,
  FilterChip,
  FilterChipRow,
  ScreenContainer,
  SkeletonListRows,
  useTheme,
} from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React, { useMemo, useState } from 'react';
import { FlatList, Pressable, Text, View } from 'react-native';
import type { TeacherStackParamList } from '../../../navigation/teacher/teacherStackTypes';

type Nav = StackNavigationProp<TeacherStackParamList>;

export const TeacherClassesScreen: React.FC = () => {
  const { palette, spacing, typography, radius } = useTheme();
  const navigation = useNavigation<Nav>();
  const [classroomId, setClassroomId] = useState<number | null>(null);
  const classroomsQuery = useClassrooms();
  const listQuery = useInfiniteStudentList({
    search: '',
    classroomId,
    streamId: null,
    status: 'active',
    perPage: 40,
  });

  const classrooms = classroomsQuery.data ?? [];
  const students = useMemo(
    () => listQuery.data?.pages.flatMap((p) => p.items) ?? [],
    [listQuery.data],
  );

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }} edges={['bottom']}>
      <View style={{ paddingHorizontal: spacing.md, paddingTop: spacing.md }}>
        <AcademicScreenHeader
          title="My students"
          subtitle="Students in your assigned classes (class-teacher & subject scope)"
        />
        {classrooms.length > 0 ? (
          <FilterChipRow label="Class">
            <FilterChip label="All" active={classroomId == null} onPress={() => setClassroomId(null)} />
            {classrooms.map((c) => (
              <FilterChip
                key={c.id}
                label={c.name}
                active={classroomId === c.id}
                onPress={() => setClassroomId(c.id)}
              />
            ))}
          </FilterChipRow>
        ) : null}
      </View>
      {listQuery.isLoading ? (
        <SkeletonListRows count={8} />
      ) : students.length === 0 ? (
        <EmptyState
          title="No students"
          message="Students assigned to your classes will appear here."
          icon="school-outline"
        />
      ) : (
        <FlatList
          data={students}
          keyExtractor={(item) => String(item.id)}
          contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
          onEndReached={() => {
            if (listQuery.hasNextPage && !listQuery.isFetchingNextPage) {
              void listQuery.fetchNextPage();
            }
          }}
          renderItem={({ item }) => (
            <Pressable
              onPress={() => navigation.navigate('StudentDetail', { studentId: item.id })}
              style={{
                backgroundColor: palette.surface,
                borderColor: palette.border,
                borderWidth: 1,
                borderRadius: radius.md,
                padding: spacing.md,
                marginBottom: spacing.sm,
              }}
            >
              <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>{item.fullName}</Text>
              <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: 2 }}>
                {[item.admissionNumber, item.className, item.streamName].filter(Boolean).join(' · ')}
              </Text>
            </Pressable>
          )}
        />
      )}
    </ScreenContainer>
  );
};
