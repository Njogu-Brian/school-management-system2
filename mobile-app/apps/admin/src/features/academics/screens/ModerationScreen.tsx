import { EmptyState, ScreenContainer } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React from 'react';
import type { AcademicsStackParamList } from '../../../navigation/academicsStackTypes';
import { navigateToDrawer } from '../../../navigation/navigateWorkspace';

type Props = StackScreenProps<AcademicsStackParamList, 'Moderation'>;

/**
 * Lesson plan review is canonical in the Approvals workspace (Sprint 8).
 * Academics links here redirect admins to the unified inbox.
 */
export const ModerationScreen: React.FC<Props> = ({ navigation }) => (
  <ScreenContainer contentContainerStyle={{ flex: 1, justifyContent: 'center' }}>
    <EmptyState
      title="Review in Approvals"
      message="Lesson plan moderation now lives in the Approvals workspace alongside leave and admissions. Use Pending / Approved / Rejected filters there."
      icon="checkbox-outline"
      actionLabel="Open Approvals"
      onAction={() => navigateToDrawer(navigation, 'Approvals', 'ApprovalsHome')}
    />
  </ScreenContainer>
);
