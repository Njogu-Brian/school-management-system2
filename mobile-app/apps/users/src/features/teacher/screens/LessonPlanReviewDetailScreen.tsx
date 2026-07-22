import { useLessonPlanDetail, useLessonPlanModerationActions } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  EmptyState,
  ScreenContainer,
  TextField,
  useTheme,
} from '@erp/ui';
import type { RouteProp } from '@react-navigation/native';
import { useNavigation, useRoute } from '@react-navigation/native';
import React, { useState } from 'react';
import { ActivityIndicator, ScrollView, Text, View } from 'react-native';
import type { TeacherStackParamList } from '../../../navigation/teacher/teacherStackTypes';
import { confirmAction, showError, showSuccess } from '../../shared/utils/feedback';

type Route = RouteProp<TeacherStackParamList, 'LessonPlanReviewDetail'>;

export const LessonPlanReviewDetailScreen: React.FC = () => {
  const navigation = useNavigation();
  const route = useRoute<Route>();
  const { lessonPlanId, topic } = route.params;
  const { colors, palette, spacing, typography } = useTheme();
  const detailQuery = useLessonPlanDetail(lessonPlanId);
  const { approve, reject } = useLessonPlanModerationActions();
  const [notes, setNotes] = useState('');

  const plan = detailQuery.data;
  const title = plan?.topic ?? topic ?? `Lesson plan #${lessonPlanId}`;

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

  if (detailQuery.isLoading && !plan) {
    return (
      <ScreenContainer contentContainerStyle={{ flex: 1, justifyContent: 'center', alignItems: 'center' }}>
        <ActivityIndicator color={colors.primary} />
      </ScreenContainer>
    );
  }

  if (detailQuery.isError && !plan) {
    return (
      <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
        <AcademicScreenHeader title="Review" onBack={() => navigation.goBack()} />
        <EmptyState
          title="Could not load plan"
          message={(detailQuery.error as Error)?.message ?? 'Something went wrong.'}
          icon="alert-circle-outline"
          actionLabel="Retry"
          onAction={() => void detailQuery.refetch()}
        />
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
        <AcademicScreenHeader title="Lesson plan review" subtitle={title} onBack={() => navigation.goBack()} />

        <View style={{ gap: spacing.xs, marginBottom: spacing.md }}>
          {[
            ['Teacher', plan?.teacher_name],
            ['Class', plan?.class_name],
            ['Subject', plan?.subject_name],
            ['Date', plan?.date],
            ['Status', plan?.submission_status ?? plan?.status],
            ['Submitted', plan?.submitted_at],
          ].map(([label, value]) => (
            <Text key={String(label)} style={{ color: palette.textSecondary, fontSize: typography.body.fontSize }}>
              <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>{label}: </Text>
              {value ?? '—'}
            </Text>
          ))}
        </View>

        <TextField
          label="Notes"
          value={notes}
          onChangeText={setNotes}
          placeholder="Optional approve notes · required to reject"
          multiline
        />
        <Button
          label="Approve"
          onPress={runApprove}
          loading={approve.isPending}
          style={{ marginTop: spacing.md }}
        />
        <Button
          label="Reject"
          variant="ghost"
          onPress={runReject}
          loading={reject.isPending}
          style={{ marginTop: spacing.sm }}
        />
      </ScrollView>
    </ScreenContainer>
  );
};
