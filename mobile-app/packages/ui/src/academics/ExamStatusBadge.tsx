import React from 'react';
import { StatusBadge } from '../primitives/StatusBadge';
import type { SemanticTone } from '../theme/tokens';

function examTone(status: string): SemanticTone {
  const key = status.toLowerCase();
  switch (key) {
    case 'draft':
      return 'brand';
    case 'open':
    case 'marking':
      return 'info';
    case 'moderation':
      return 'warning';
    case 'approved':
    case 'published':
      return 'success';
    case 'locked':
      return 'danger';
    default:
      return 'brand';
  }
}

export interface ExamStatusBadgeProps {
  status: string;
  compact?: boolean;
}

export const ExamStatusBadge: React.FC<ExamStatusBadgeProps> = ({ status, compact }) => {
  const key = status.toLowerCase();
  const label = key.charAt(0).toUpperCase() + key.slice(1);
  return <StatusBadge label={label} tone={examTone(status)} compact={compact} />;
};
