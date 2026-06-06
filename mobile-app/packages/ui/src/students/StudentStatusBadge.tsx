import React from 'react';
import { StatusBadge } from '../primitives/StatusBadge';
import type { StudentEnrollmentStatus, StudentFeeStatus } from './types';

export type StudentStatusKind = 'enrollment' | 'fee';

export interface StudentStatusBadgeProps {
  kind: StudentStatusKind;
  enrollmentStatus?: StudentEnrollmentStatus;
  feeStatus?: StudentFeeStatus;
  compact?: boolean;
}

export const StudentStatusBadge: React.FC<StudentStatusBadgeProps> = ({
  kind,
  enrollmentStatus = 'active',
  feeStatus,
  compact,
}) => {
  const label =
    kind === 'fee'
      ? feeStatus === 'pending'
        ? 'Fees pending'
        : feeStatus === 'cleared'
          ? 'Fees cleared'
          : 'Fees —'
      : enrollmentStatus === 'active'
        ? 'Active'
        : String(enrollmentStatus);

  const tone = (() => {
    if (kind === 'fee') {
      if (feeStatus === 'pending') return 'warning' as const;
      if (feeStatus === 'cleared') return 'success' as const;
      return 'brand' as const;
    }
    if (enrollmentStatus === 'active') return 'success' as const;
    return 'brand' as const;
  })();

  return <StatusBadge label={label} tone={tone} compact={compact} />;
};
