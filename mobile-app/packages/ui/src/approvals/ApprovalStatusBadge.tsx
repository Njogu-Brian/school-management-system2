import React from 'react';
import { StatusBadge } from '../primitives/StatusBadge';
import type { SemanticTone } from '../theme/tokens';
import type { ApprovalStatus } from './types';

const LABELS: Record<ApprovalStatus, string> = {
  pending: 'Pending',
  approved: 'Approved',
  rejected: 'Rejected',
  escalated: 'Escalated',
  expired: 'Expired',
};

function statusTone(status: ApprovalStatus): SemanticTone {
  switch (status) {
    case 'approved':
      return 'success';
    case 'rejected':
      return 'danger';
    case 'escalated':
      return 'warning';
    case 'expired':
      return 'info';
    default:
      return 'brand';
  }
}

export interface ApprovalStatusBadgeProps {
  status: ApprovalStatus;
  compact?: boolean;
}

export const ApprovalStatusBadge: React.FC<ApprovalStatusBadgeProps> = ({
  status,
  compact,
}) => {
  return <StatusBadge label={LABELS[status]} tone={statusTone(status)} compact={compact} />;
};
