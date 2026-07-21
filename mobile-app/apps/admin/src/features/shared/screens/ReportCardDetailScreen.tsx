import { useCan, useReportCardDetail } from '@erp/core';
import { AcademicScreenHeader, FinanceFieldSection, ScreenContainer, useTheme } from '@erp/ui';
import React, { useMemo } from 'react';
import { ActivityIndicator, Pressable, ScrollView, Text, View } from 'react-native';
import { formatPercent } from '../../academics/utils/formatters';

export interface SharedReportCardDetailProps {
  reportCardId: number;
  studentName?: string;
  onBack: () => void;
}

/** Canonical report card detail — reused by Students and Academics stacks. */
export const SharedReportCardDetailScreen: React.FC<SharedReportCardDetailProps> = ({
  reportCardId,
  studentName,
  onBack,
}) => {
  const canView = useCan('academics.view') && useCan('report_cards.view');
  const { colors, palette, spacing, typography } = useTheme();
  const detailQuery = useReportCardDetail(reportCardId, { enabled: canView });

  const card = detailQuery.data;

  const summaryFields = useMemo(() => {
    if (!card) return [];
    return [
      { label: 'Student', value: studentName ?? card.student_name ?? '—' },
      { label: 'Class', value: card.class_name ?? '—' },
      { label: 'Overall grade', value: card.overall_grade ?? '—' },
      { label: 'Overall %', value: formatPercent(card.overall_percentage) },
      { label: 'Position', value: card.overall_position != null ? String(card.overall_position) : '—' },
      { label: 'Class position', value: card.class_position != null ? String(card.class_position) : '—' },
      { label: 'Status', value: card.status },
      { label: 'Teacher remark', value: card.teacher_comment ?? '—' },
      { label: 'Principal remark', value: card.principal_comment ?? '—' },
    ];
  }, [card, studentName]);

  const subjectFields = useMemo(
    () =>
      (card?.subjects ?? []).map((s) => ({
        label: s.subject_name,
        value: `${s.marks}/${s.total_marks} · ${s.grade} · ${formatPercent(s.percentage)}${s.remarks ? ` — ${s.remarks}` : ''}`,
      })),
    [card],
  );

  if (!canView) {
    return (
      <ScreenContainer>
        <Text style={{ color: palette.textSecondary }}>Access denied.</Text>
      </ScreenContainer>
    );
  }

  if (detailQuery.isLoading) {
    return (
      <ScreenContainer contentContainerStyle={{ flex: 1, justifyContent: 'center', alignItems: 'center' }}>
        <ActivityIndicator color={colors.primary} />
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
        <AcademicScreenHeader title="Report Card" subtitle={studentName} onBack={onBack} />
        {detailQuery.isError ? (
          <Pressable onPress={() => void detailQuery.refetch()}>
            <Text style={{ color: colors.error }}>{(detailQuery.error as Error).message}</Text>
          </Pressable>
        ) : card ? (
          <>
            <FinanceFieldSection title="Summary" rows={summaryFields} />
            {subjectFields.length > 0 ? (
              <View style={{ marginTop: spacing.md }}>
                <Text
                  style={{
                    color: palette.textPrimary,
                    fontSize: typography.titleSmall.fontSize,
                    fontWeight: '700',
                    marginBottom: spacing.sm,
                  }}
                >
                  Subjects
                </Text>
                <FinanceFieldSection title="Subject breakdown" rows={subjectFields} />
              </View>
            ) : null}
          </>
        ) : null}
      </ScrollView>
    </ScreenContainer>
  );
};
