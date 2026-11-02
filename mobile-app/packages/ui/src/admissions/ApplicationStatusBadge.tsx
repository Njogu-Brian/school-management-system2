import React from 'react';
import { StatusBadge } from '../primitives/StatusBadge';
import type { SemanticTone } from '../theme/tokens';
import type { ApplicationStatusFilter } from './types';

const STATUS_TONES: Record<string, SemanticTone> = {
  pending: 'warning',
  contacted: 'info',
  under_review: 'info',
  interviewed: 'brand',
  ready_to_enroll: 'success',
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
    case 'contacted':
      return 'Contacted';
    case 'under_review':
      return 'Under Review';
    case 'interviewed':
      return 'Interviewed';
    case 'ready_to_enroll':
      return 'Ready to Enroll';
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
