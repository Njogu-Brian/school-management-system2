import {
  applicationStatusLabel,
  useAdmissionActions,
  type ApplicationDetail,
} from '@erp/core';
import { ApplicationFieldSection, Button } from '@erp/ui';
import React from 'react';
import { ScrollView, Text, View } from 'react-native';
import { useTheme } from '@erp/ui';
import { confirmAction, showError } from '../../../shared/utils/feedback';

export interface OverviewTabProps {
  application: ApplicationDetail;
}

export const OverviewTab: React.FC<OverviewTabProps> = ({ application }) => {
  const { colors, spacing, typography } = useTheme();
  const { updateStatus, waitlist, reject } = useAdmissionActions(application.id);

  const canAct =
    !application.enrolled &&
    application.applicationStatus !== 'rejected' &&
    application.applicationStatus !== 'enrolled';

  const runAction = (label: string, action: () => Promise<unknown>) => {
    confirmAction(
      label,
      `Confirm: ${label.toLowerCase()} for ${application.fullName}?`,
      label,
      () => {
        void action().catch((err: Error) => showError('Action failed', err.message));
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
    <ScrollView showsVerticalScrollIndicator={false}>
      <ApplicationFieldSection
        title="Application"
        rows={[
          { label: 'Status', value: applicationStatusLabel(application.applicationStatus) },
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
          { label: 'Waitlist position', value: application.waitlistPosition?.toString() ?? null },
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
          <Button
            label="Mark under review"
            variant="secondary"
            onPress={() =>
              runAction('Mark under review', () =>
                updateStatus.mutateAsync({ application_status: 'under_review' }),
              )
            }
            loading={updateStatus.isPending}
            disabled={busy}
          />
          <Button
            label="Add to waitlist"
            variant="secondary"
            onPress={() => runAction('Add to waitlist', () => waitlist.mutateAsync(null))}
            loading={waitlist.isPending}
            disabled={busy}
          />
          <Button
            label="Reject application"
            variant="ghost"
            onPress={() => runAction('Reject application', () => reject.mutateAsync())}
            loading={reject.isPending}
            disabled={busy}
          />
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
