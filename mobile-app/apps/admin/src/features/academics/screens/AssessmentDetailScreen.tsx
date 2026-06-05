import { useCan } from '@erp/core';
import { AcademicScreenHeader, FinanceFieldSection, ScreenContainer, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useMemo } from 'react';
import { ScrollView, StyleSheet, Text } from 'react-native';
import type { AcademicsStackParamList } from '../../../navigation/academicsStackTypes';
import { formatPercent } from '../utils/formatters';

type Props = StackScreenProps<AcademicsStackParamList, 'AssessmentDetail'>;

export const AssessmentDetailScreen: React.FC<Props> = ({ route, navigation }) => {
  const { item, studentName } = route.params;
  const canView = useCan('academics.view');
  const { palette, spacing } = useTheme();

  const fields = useMemo(
    () => [
      { label: 'Student', value: studentName },
      { label: 'Type', value: item.typeLabel },
      { label: 'Title', value: item.title },
      { label: 'Subject', value: item.subjectName ?? '—' },
      { label: 'Date', value: item.assessedOn ?? '—' },
      { label: 'Score', value: item.scoreDisplay ?? formatPercent(item.scorePercent) },
      { label: 'Grade', value: item.gradeLabel ?? '—' },
      {
        label: 'Performance level',
        value: item.performanceLevel?.name ?? '—',
      },
      { label: 'Status', value: item.status },
      { label: 'Remark', value: item.remark ?? '—' },
      { label: 'Source', value: `${item.legacySource.table} #${item.legacySource.id}` },
    ],
    [item, studentName],
  );

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <Text style={{ color: palette.textSecondary }}>Access denied.</Text>
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
        <AcademicScreenHeader title="Assessment Detail" subtitle={item.title} onBack={() => navigation.goBack()} />
        <FinanceFieldSection title="Assessment" rows={fields} />
      </ScrollView>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
});
