import type { StudentSummary } from '@erp/core';
import type { StudentListItemData } from '@erp/ui';

export function summaryToListItem(
  student: StudentSummary,
  onPress?: () => void,
): StudentListItemData {
  return {
    id: student.id,
    fullName: student.fullName,
    admissionNumber: student.admissionNumber,
    classLabel: student.className ?? 'Unassigned',
    streamName: student.streamName,
    gender: student.gender,
    feeStatus: student.feeStatus,
    enrollmentStatus: student.enrollmentStatus,
    avatarUrl: student.avatarUrl,
    onPress,
  };
}
