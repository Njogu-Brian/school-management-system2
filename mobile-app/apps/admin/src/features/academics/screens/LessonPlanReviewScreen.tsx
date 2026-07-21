import { useCan, useLessonPlanDetail, useLessonPlanModerationActions } from '@erp/core';
import { AcademicScreenHeader, Button, FinanceFieldSection, ScreenContainer, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import type { AcademicsStackParamList } from '../../../navigation/academicsStackTypes';
import { confirmAction, showError, showSuccess } from '../../shared/utils/feedback';

type Props = StackScreenProps<AcademicsStackParamList, 'LessonPlanReview'>;

export const LessonPlanReviewScreen: React.FC<Props> = ({ route, navigation }) => {
  const { lessonPlanId, summary } = route.params;
  const canView = useCan('academics.view') && useCan('lesson_plans.view');
  const { colors, palette, spacing, typography, radius } = useTheme();
  const detailQuery = useLessonPlanDetail(lessonPlanId, { enabled: canView });
  const { approve, reject } = useLessonPlanModerationActions();
  const [notes, setNotes] = useState('');

  const plan = detailQuery.data;
  const title = plan?.topic ?? summary?.topic ?? `Lesson plan #${lessonPlanId}`;

  const fields = useMemo(() => {
    if (!plan) return [];
    return [
      { label: 'Teacher', value: plan.teacher_name ?? '—' },
      { label: 'Class', value: plan.class_name ?? '—' },
      { label: 'Subject', value: plan.subject_name ?? '—' },
      { label: 'Planned date', value: plan.date ?? '—' },
      { label: 'Status', value: plan.submission_status ?? plan.status ?? '—' },
      { label: 'Late', value: plan.is_late ? 'Yes' : 'No' },
      { label: 'Submitted', value: plan.submitted_at ?? '—' },
    ];
  }, [plan]);

  const runApprove = () => {
    confirmAction('Approve lesson plan', 'Approve this submitted lesson plan?', 'Approve', () => {
      void approve
        .mutateAsync({ id: lessonPlanId, notes: notes.trim() || undefined })
        .then(() => {
          showSuccess('Approved', 'Lesson plan approved.');
          navigation.goBack();
        })
        .catch((e: Error) => showError('Failed', e.message));
    });
  };

  const runReject = () => {
    if (!notes.trim()) {
      showError('Rejection reason required', 'Enter rejection notes before rejecting.');
      return;
    }
    confirmAction(
      'Reject lesson plan',
      'Reject this lesson plan?',
      'Reject',
      () => {
        void reject
          .mutateAsync({ id: lessonPlanId, notes: notes.trim() })
          .then(() => {
            showSuccess('Rejected', 'Lesson plan rejected.');
            navigation.goBack();
          })
          .catch((e: Error) => showError('Failed', e.message));
      },
      true,
    );
  };

  if (!canView) {
    return (
      <ScreenContainer>
        <Text style={{ color: palette.textSecondary }}>Access denied.</Text>
      </ScreenContainer>
    );
  }

  if (detailQuery.isLoading && !plan) {
    return (
      <ScreenContainer contentContainerStyle={{ flex: 1, justifyContent: 'center', alignItems: 'center' }}>
        <ActivityIndicator color={colors.primary} />
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
        <AcademicScreenHeader title="Lesson Plan Review" subtitle={title} onBack={() => navigation.goBack()} />
        {detailQuery.isError ? (
          <Pressable onPress={() => void detailQuery.refetch()}>
            <Text style={{ color: colors.error }}>{(detailQuery.error as Error).message}</Text>
          </Pressable>
        ) : plan ? (
          <>
            <FinanceFieldSection title="Lesson plan" rows={fields} />
            <Text style={{ color: palette.textPrimary, fontWeight: '600', marginTop: spacing.md, marginBottom: spacing.xs }}>
              Review notes
            </Text>
            <TextInput
              value={notes}
              onChangeText={setNotes}
              placeholder="Approval notes or rejection reason…"
              placeholderTextColor={palette.textSecondary}
              multiline
              style={{
                borderWidth: StyleSheet.hairlineWidth,
                borderColor: palette.border,
                borderRadius: radius.md,
                padding: spacing.sm,
                minHeight: 80,
                color: palette.textPrimary,
                fontSize: typography.body.fontSize,
                backgroundColor: palette.surface,
              }}
            />
            <View style={[styles.actions, { marginTop: spacing.md, gap: spacing.sm }]}>
              <Button label="Approve" onPress={runApprove} loading={approve.isPending} />
              <Button label="Reject" variant="ghost" onPress={runReject} loading={reject.isPending} />
            </View>
          </>
        ) : null}
      </ScrollView>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  actions: {},
});
