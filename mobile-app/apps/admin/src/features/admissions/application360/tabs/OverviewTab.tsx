import {
  applicationStatusLabel,
  useAdmissionActions,
  type ApplicationDetail,
} from '@erp/core';
import { ApplicationFieldSection, Button, useFloatingTabBarClearance, useTheme } from '@erp/ui';
import React from 'react';
import { ScrollView, Text, View } from 'react-native';
import { confirmAction, showError, showSuccess } from '../../../shared/utils/feedback';

export interface OverviewTabProps {
  application: ApplicationDetail;
}

export const OverviewTab: React.FC<OverviewTabProps> = ({ application }) => {
  const { colors, spacing, typography } = useTheme();
  const tabClearance = useFloatingTabBarClearance();
  const { updateStatus, waitlist, reject } = useAdmissionActions(application.id);

  const status = application.applicationStatus;
  const canAct =
    !application.enrolled && status !== 'rejected' && status !== 'enrolled';

  const showUnderReview = canAct && status !== 'under_review';
  const showWaitlist = canAct && status !== 'waitlisted';
  const showReject = canAct;

  const runAction = (label: string, action: () => Promise<unknown>, successMessage: string) => {
    confirmAction(
      label,
      `Confirm: ${label.toLowerCase()} for ${application.fullName}?`,
      label,
      () => {
        void action()
          .then(() => showSuccess(label, successMessage))
          .catch((err: Error) => showError('Action failed', err.message));
      },
      label === 'Reject application',
    );
  };

  const busy = updateStatus.isPending || waitlist.isPending || reject.isPending;
  const actionError =
    (updateStatus.error as Error | null)?.message ??
    (waitlist.error as Error | null)?.message ??
    (reject.error as Error | null)?.message;

  return (
    <ScrollView
      showsVerticalScrollIndicator={false}
      contentContainerStyle={{ paddingBottom: tabClearance }}
    >
      <ApplicationFieldSection
        title="Application"
        rows={[
          { label: 'Status', value: applicationStatusLabel(status) },
          { label: 'Source', value: application.applicationSource },
          { label: 'Applied on', value: application.applicationDate },
          { label: 'Reviewer', value: application.reviewedByName },
          { label: 'Review date', value: application.reviewDate },
          { label: 'Review notes', value: application.reviewNotes },
        ]}
      />
      <ApplicationFieldSection
        title="Placement"
        rows={[
          { label: 'Preferred class', value: application.preferredClassName },
          { label: 'Assigned class', value: application.className },
          { label: 'Stream', value: application.streamName },
          {
            label: 'Waitlist position',
            value:
              status === 'waitlisted' && application.waitlistPosition != null
                ? `#${application.waitlistPosition}`
                : status === 'waitlisted'
                  ? 'On waitlist'
                  : null,
          },
        ]}
      />

      {canAct ? (
        <View style={{ gap: spacing.sm, paddingBottom: spacing.xl }}>
          <Text
            style={{
              color: colors.primary,
              fontSize: typography.label.fontSize,
              fontWeight: typography.label.fontWeight,
            }}
          >
            Quick actions
          </Text>
          {status === 'waitlisted' ? (
            <Text style={{ color: colors.success, fontSize: typography.body.fontSize }}>
              On waitlist
              {application.waitlistPosition != null ? ` · position #${application.waitlistPosition}` : ''}.
              Use Enrollment to enroll, or mark under review / reject below.
            </Text>
          ) : null}
          {showUnderReview ? (
            <Button
              label="Mark under review"
              variant="secondary"
              onPress={() =>
                runAction(
                  'Mark under review',
                  () => updateStatus.mutateAsync({ application_status: 'under_review' }),
                  'Status is now Under Review. Open Waitlisted filter on the workspace to see waitlisted apps only.',
                )
              }
              loading={updateStatus.isPending}
              disabled={busy}
            />
          ) : null}
          {showWaitlist ? (
            <Button
              label="Add to waitlist"
              variant="secondary"
              onPress={() =>
                runAction(
                  'Add to waitlist',
                  () => waitlist.mutateAsync(null),
                  'Application is waitlisted. Open Admissions → Waitlisted to find it.',
                )
              }
              loading={waitlist.isPending}
              disabled={busy}
            />
          ) : null}
          {showReject ? (
            <Button
              label="Reject application"
              variant="ghost"
              onPress={() =>
                runAction(
                  'Reject application',
                  () => reject.mutateAsync(),
                  'Application rejected.',
                )
              }
              loading={reject.isPending}
              disabled={busy}
            />
          ) : null}
          {actionError ? (
            <Text style={{ color: colors.error, fontSize: typography.body.fontSize }}>
              {actionError}
            </Text>
          ) : null}
        </View>
      ) : null}
    </ScrollView>
  );
};
