import type { StaffSummary } from '@erp/core';
import type { StaffListItemData } from '@erp/ui';

export function summaryToListItem(
  summary: StaffSummary,
  onPress: () => void,
): StaffListItemData {
  return {
    id: summary.id,
    fullName: summary.fullName,
    employeeNumber: summary.employeeNumber,
    departmentName: summary.departmentName,
    jobTitle: summary.jobTitle,
    systemRole: summary.systemRole,
    employmentStatus: summary.employmentStatus,
    avatarUrl: summary.avatarUrl,
    onPress,
  };
}
