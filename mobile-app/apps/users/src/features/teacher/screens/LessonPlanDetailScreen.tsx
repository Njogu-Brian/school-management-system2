import { useLessonPlanDetail, useSubmitLessonPlan } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  EmptyState,
  ScreenContainer,
  SkeletonListRows,
  StatusBadge,
  useTheme,
} from '@erp/ui';
import { useNavigation, useRoute, type RouteProp } from '@react-navigation/native';
import React from 'react';
import { Text, View } from 'react-native';
import type { TeacherStackParamList } from '../../../navigation/teacher/teacherStackTypes';
import { confirmAction, showError, showSuccess } from '../../shared/utils/feedback';

type Route = RouteProp<TeacherStackParamList, 'LessonPlanDetail'>;

function statusTone(status?: string | null): 'success' | 'danger' | 'warning' | 'info' {
  switch (status) {
    case 'approved':
      return 'success';
    case 'rejected':
      return 'danger';
    case 'submitted':
      return 'warning';
    default:
      return 'info';
  }
}

export const LessonPlanDetailScreen: React.FC = () => {
  const navigation = useNavigation();
  const route = useRoute<Route>();
  const { lessonPlanId, topic } = route.params;
  const { palette, spacing, typography } = useTheme();
  const detailQuery = useLessonPlanDetail(lessonPlanId);
  const submitMutation = useSubmitLessonPlan();
  const plan = detailQuery.data;
  const status = plan?.submission_status ?? plan?.status;
  const isDraft = !status || status === 'draft';

  const submit = () => {
    confirmAction('Submit for review', 'Send this lesson plan to a senior teacher for approval?', 'Submit', () => {
      void submitMutation
        .mutateAsync(lessonPlanId)
        .then(() => {
          showSuccess('Submitted', 'Lesson plan is awaiting review.');
          void detailQuery.refetch();
        })
        .catch((err: unknown) => {
          showError('Submit failed', err instanceof Error ? err.message : 'Could not submit.');
        });
    });
  };

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
      <AcademicScreenHeader title="Lesson plan" subtitle={plan?.topic ?? topic} onBack={() => navigation.goBack()} />

      {detailQuery.isLoading ? (
        <SkeletonListRows count={4} />
      ) : detailQuery.isError || !plan ? (
        <EmptyState
          title="Could not load plan"
          message={detailQuery.error instanceof Error ? detailQuery.error.message : 'Try again.'}
          icon="alert-circle-outline"
          actionLabel="Retry"
          onAction={() => void detailQuery.refetch()}
        />
      ) : (
        <View
          style={{
            backgroundColor: palette.surface,
            borderColor: palette.border,
            borderWidth: 1,
            borderRadius: 16,
            padding: spacing.md,
            gap: spacing.sm,
          }}
        >
          <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' }}>
            <Text
              style={{
                color: palette.textPrimary,
                fontWeight: '700',
                fontSize: typography.headline.fontSize,
                flex: 1,
              }}
            >
              {plan.topic ?? `Lesson plan #${lessonPlanId}`}
            </Text>
            {status ? <StatusBadge label={status} tone={statusTone(status)} compact /> : null}
          </View>

          {[
            ['Class', plan.class_name],
            ['Subject', plan.subject_name],
            ['Date', plan.date],
            ['Submitted', plan.submitted_at],
          ].map(([label, value]) => (
            <Text key={String(label)} style={{ color: palette.textSecondary, fontSize: typography.body.fontSize }}>
              <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>{label}: </Text>
              {value ?? '—'}
            </Text>
          ))}

          {plan.approval_notes ? (
            <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: spacing.sm }}>
              <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>Approval notes: </Text>
              {plan.approval_notes}
            </Text>
          ) : null}
          {plan.rejection_notes ? (
            <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: spacing.sm }}>
              <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>Rejection notes: </Text>
              {plan.rejection_notes}
            </Text>
          ) : null}

          {isDraft ? (
            <Button
              label="Submit for review"
              onPress={submit}
              loading={submitMutation.isPending}
              style={{ marginTop: spacing.md }}
            />
          ) : null}
        </View>
      )}
    </ScreenContainer>
  );
};
