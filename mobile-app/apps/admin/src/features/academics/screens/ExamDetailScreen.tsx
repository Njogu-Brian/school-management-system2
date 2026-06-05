import { useCan, useExamDetail, useExamMarkingOptions } from '@erp/core';
import {
  AcademicScreenHeader,
  ExamStatusBadge,
  FinanceFieldSection,
  ScreenContainer,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useMemo } from 'react';
import { ActivityIndicator, Pressable, ScrollView, Text, View } from 'react-native';
import type { AcademicsStackParamList } from '../../../navigation/academicsStackTypes';

type Props = StackScreenProps<AcademicsStackParamList, 'ExamDetail'>;

export const ExamDetailScreen: React.FC<Props> = ({ route, navigation }) => {
  const { examId, summary } = route.params;
  const canView = useCan('academics.view') && useCan('exams.view');
  const { colors, palette, spacing } = useTheme();
  const detailQuery = useExamDetail(examId, { enabled: canView });
  const optionsQuery = useExamMarkingOptions(examId, { enabled: canView });

  const exam = detailQuery.data ?? summary;

  const fields = useMemo(() => {
    if (!exam) return [];
    return [
      { label: 'Name', value: exam.name },
      { label: 'Type', value: exam.examTypeName ?? '—' },
      { label: 'Status', value: exam.status },
      { label: 'Class', value: exam.classroomName ?? '—' },
      { label: 'Subject', value: exam.subjectName ?? '—' },
      { label: 'Academic year', value: exam.academicYearId != null ? String(exam.academicYearId) : '—' },
      { label: 'Term', value: exam.termId != null ? String(exam.termId) : '—' },
      { label: 'Start', value: exam.startDate ?? '—' },
      { label: 'End', value: exam.endDate ?? '—' },
      { label: 'Total marks', value: String(exam.totalMarks) },
    ];
  }, [exam]);

  if (!canView) {
    return (
      <ScreenContainer>
        <Text style={{ color: palette.textSecondary }}>Access denied.</Text>
      </ScreenContainer>
    );
  }

  if (detailQuery.isLoading && !exam) {
    return (
      <ScreenContainer contentContainerStyle={{ flex: 1, justifyContent: 'center', alignItems: 'center' }}>
        <ActivityIndicator color={colors.primary} />
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
        <AcademicScreenHeader
          title={exam?.name ?? `Exam #${examId}`}
          subtitle="Exam detail (read-only)"
          onBack={() => navigation.goBack()}
        />
        {exam ? (
          <View style={{ marginBottom: spacing.sm }}>
            <ExamStatusBadge status={exam.status} />
          </View>
        ) : null}
        {detailQuery.isError ? (
          <Pressable onPress={() => void detailQuery.refetch()}>
            <Text style={{ color: colors.error }}>{(detailQuery.error as Error).message}</Text>
          </Pressable>
        ) : (
          <>
            <FinanceFieldSection title="Exam" rows={fields} />
            {(optionsQuery.data ?? []).length > 0 ? (
              <>
                <Text style={{ color: palette.textPrimary, fontWeight: '700', marginTop: spacing.md, marginBottom: spacing.sm }}>
                  Enter marks
                </Text>
                {(optionsQuery.data ?? []).map((opt) => (
                  <Pressable
                    key={`${opt.classroom_id}-${opt.subject_id}`}
                    onPress={() =>
                      navigation.navigate('MarksEntry', {
                        examId,
                        classroomId: opt.classroom_id,
                        subjectId: opt.subject_id,
                        classroomName: opt.classroom_name,
                        subjectName: opt.subject_name,
                      })
                    }
                    style={{
                      borderWidth: 1,
                      borderColor: palette.border,
                      borderRadius: 8,
                      padding: spacing.sm,
                      marginBottom: spacing.xs,
                    }}
                  >
                    <Text style={{ color: colors.primary, fontWeight: '600' }}>
                      {opt.classroom_name} · {opt.subject_name}
                    </Text>
                  </Pressable>
                ))}
              </>
            ) : optionsQuery.isLoading ? (
              <ActivityIndicator color={colors.primary} style={{ marginTop: spacing.md }} />
            ) : null}
          </>
        )}
      </ScrollView>
    </ScreenContainer>
  );
};
