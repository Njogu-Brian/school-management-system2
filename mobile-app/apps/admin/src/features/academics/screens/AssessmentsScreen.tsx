import { useCan, useInfiniteStudentList } from '@erp/core';
import { AcademicScreenHeader, AcademicSearchBar, ScreenContainer, StudentListItem, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useEffect, useMemo, useState } from 'react';
import { ActivityIndicator, ScrollView, StyleSheet, Text } from 'react-native';
import type { AcademicsStackParamList } from '../../../navigation/academicsStackTypes';
import { summaryToListItem } from '../../students/utils/mapToListItem';

type Props = StackScreenProps<AcademicsStackParamList, 'Assessments'>;

export const AssessmentsScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('academics.view');
  const { colors, palette, spacing, fontSizes } = useTheme();
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
        <Text style={{ color: palette.textSecondary, textAlign: 'center' }}>Access denied.</Text>
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
        <AcademicScreenHeader
          title="Assessments"
          subtitle="Search student assessment history"
          onBack={() => navigation.goBack()}
        />
        <AcademicSearchBar
          value={searchInput}
          onChangeText={setSearchInput}
          placeholder="Search student by name or admission #…"
        />
        {debouncedSearch.length === 0 ? (
          <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm }}>
            Type to search for a student and view their assessment history.
          </Text>
        ) : listQuery.isLoading ? (
          <ActivityIndicator color={colors.primary} style={{ marginTop: spacing.lg }} />
        ) : students.length === 0 ? (
          <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm }}>No students found.</Text>
        ) : (
          students.map((s) => (
            <StudentListItem
              key={s.id}
              student={summaryToListItem(s, () =>
                navigation.navigate('AssessmentHistory', {
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
