import React from 'react';
import { StatusBadge } from '../primitives/StatusBadge';
import type { SemanticTone } from '../theme/tokens';
import type { ApprovalPriority } from './types';

const LABELS: Record<ApprovalPriority, string> = {
  critical: 'Critical',
  high: 'High',
  medium: 'Medium',
  low: 'Low',
};

function priorityTone(priority: ApprovalPriority): SemanticTone {
  switch (priority) {
    case 'critical':
      return 'danger';
    case 'high':
      return 'warning';
    case 'medium':
      return 'brand';
    default:
      return 'brand';
  }
}

export interface ApprovalPriorityBadgeProps {
  priority: ApprovalPriority;
  compact?: boolean;
}

export const ApprovalPriorityBadge: React.FC<ApprovalPriorityBadgeProps> = ({
  priority,
  compact,
}) => {
  return (
    <StatusBadge label={LABELS[priority]} tone={priorityTone(priority)} compact={compact} />
  );
};
