import { useCan, useInfiniteStudentList } from '@erp/core';
import {
  AcademicScreenHeader,
  AcademicSearchBar,
  EmptyState,
  ScreenContainer,
  SkeletonListRows,
  StudentListItem,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useEffect, useMemo, useState } from 'react';
import { ScrollView, StyleSheet } from 'react-native';
import type { AcademicsStackParamList } from '../../../navigation/academicsStackTypes';
import { summaryToListItem } from '../../students/utils/mapToListItem';

type Props = StackScreenProps<AcademicsStackParamList, 'ReportCards'>;

export const ReportCardsScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('academics.view') && useCan('report_cards.view');
  const { spacing } = useTheme();
  const [searchInput, setSearchInput] = useState('');
  const [debouncedSearch, setDebouncedSearch] = useState('');

  useEffect(() => {
    const t = setTimeout(() => setDebouncedSearch(searchInput.trim()), 350);
    return () => clearTimeout(t);
  }, [searchInput]);

  const listQuery = useInfiniteStudentList(
    { search: debouncedSearch || undefined, perPage: 15 },
    { enabled: canView && debouncedSearch.length > 0 },
  );

  const students = useMemo(
    () => listQuery.data?.pages.flatMap((p) => p.items) ?? [],
    [listQuery.data],
  );

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <EmptyState
          title="Access denied"
          message="You need report_cards.view permission to open report cards."
          icon="lock-closed-outline"
        />
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
        <AcademicScreenHeader
          title="Report Cards"
          subtitle="Student report card history"
          onBack={() => navigation.goBack()}
        />
        <AcademicSearchBar
          value={searchInput}
          onChangeText={setSearchInput}
          placeholder="Search student by name or admission #…"
        />
        {debouncedSearch.length === 0 ? (
          <EmptyState
            title="Find a student"
            message="Type to search for a student and view their report cards."
            icon="search-outline"
          />
        ) : listQuery.isLoading ? (
          <SkeletonListRows variant="avatar" count={5} />
        ) : listQuery.isError ? (
          <EmptyState
            title="Could not search students"
            message={(listQuery.error as Error).message}
            icon="alert-circle-outline"
            actionLabel="Retry"
            onAction={() => void listQuery.refetch()}
          />
        ) : students.length === 0 ? (
          <EmptyState
            title="No students found"
            message="No students match your search."
            icon="people-outline"
          />
        ) : (
          students.map((s) => (
            <StudentListItem
              key={s.id}
              student={summaryToListItem(s, () =>
                navigation.navigate('ReportCardHistory', {
                  studentId: s.id,
                  studentName: s.fullName,
                }),
              )}
            />
          ))
        )}
      </ScrollView>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
});
