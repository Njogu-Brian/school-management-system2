import React from 'react';
import { StatusBadge } from '../primitives/StatusBadge';
import type { SemanticTone } from '../theme/tokens';
import type { ApplicationStatusFilter } from './types';

const STATUS_TONES: Record<string, SemanticTone> = {
  pending: 'warning',
  under_review: 'info',
  waitlisted: 'brand',
  enrolled: 'success',
  rejected: 'danger',
};

export interface ApplicationStatusBadgeProps {
  status: ApplicationStatusFilter | string;
  compact?: boolean;
}

export function applicationStatusLabel(status: string): string {
  switch (status) {
    case 'pending':
      return 'Pending';
    case 'under_review':
      return 'Under Review';
    case 'waitlisted':
      return 'Waitlisted';
    case 'enrolled':
      return 'Enrolled';
    case 'rejected':
      return 'Rejected';
    default:
      return status;
  }
}

export const ApplicationStatusBadge: React.FC<ApplicationStatusBadgeProps> = ({
  status,
  compact,
}) => {
  const tone = STATUS_TONES[status] ?? 'brand';
  return (
    <StatusBadge label={applicationStatusLabel(status)} tone={tone} compact={compact} />
  );
};
