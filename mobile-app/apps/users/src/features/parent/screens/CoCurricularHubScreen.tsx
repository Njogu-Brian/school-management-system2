import { useInfiniteStudentList } from '@erp/core';
import {
  AcademicScreenHeader,
  EmptyState,
  ListRowCard,
  ScreenContainer,
  SkeletonListRows,
  useTheme,
} from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React, { useMemo } from 'react';
import type { ParentStackParamList } from '../../../navigation/parent/parentStackTypes';

type Nav = StackNavigationProp<ParentStackParamList>;

export const CoCurricularHubScreen: React.FC = () => {
  const navigation = useNavigation<Nav>();
  const { spacing } = useTheme();
  const listQuery = useInfiniteStudentList({
    search: '',
    classroomId: null,
    streamId: null,
    status: 'active',
    perPage: 40,
  });
  const students = useMemo(
    () => listQuery.data?.pages.flatMap((p) => p.items) ?? [],
    [listQuery.data],
  );

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md }}>
      <AcademicScreenHeader
        title="Co-curricular"
        subtitle="Ballet, skating, music, yoghurt and other programmes"
        onBack={() => navigation.goBack()}
      />
      {listQuery.isLoading ? (
        <SkeletonListRows count={3} />
      ) : students.length === 0 ? (
        <EmptyState
          title="No children linked"
          message="Link a child to see their activities."
          icon="people-outline"
        />
      ) : (
        students.map((child) => (
          <ListRowCard
            key={child.id}
            title={child.fullName}
            subtitle={[child.admissionNumber, child.className].filter(Boolean).join(' · ')}
            icon="sparkles-outline"
            glyph="activities"
            accent="brand"
            onPress={() => navigation.navigate('CoCurricularChild', { studentId: child.id })}
          />
        ))
      )}
    </ScreenContainer>
  );
};
