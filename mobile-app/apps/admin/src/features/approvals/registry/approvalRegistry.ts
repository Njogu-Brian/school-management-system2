import type { ApprovalSourceType } from '@erp/core';
import type { PermissionInput } from '@erp/core';

export interface ApprovalSourceDefinition {
  type: ApprovalSourceType;
  label: string;
  permissions: PermissionInput;
}

/** Registered approval sources for Batch 3 (extend with new workflows later). */
export const APPROVAL_SOURCE_REGISTRY: ApprovalSourceDefinition[] = [
  {
    type: 'leave_request',
    label: 'Leave request',
    permissions: ['dashboard.approvals.view', 'dashboard.view'],
  },
  {
    type: 'lesson_plan',
    label: 'Lesson plan',
    permissions: ['dashboard.approvals.view', 'dashboard.view'],
  },
  {
    type: 'online_admission',
    label: 'Admission application',
    permissions: ['admissions.view', 'approvals.view'],
  },
  {
    type: 'staff_advance',
    label: 'Staff advance',
    permissions: ['finance.view', 'people.view', 'staff.view', 'dashboard.approvals.view'],
  },
];

export function getSourceLabel(type: ApprovalSourceType): string {
  return APPROVAL_SOURCE_REGISTRY.find((s) => s.type === type)?.label ?? type;
}
