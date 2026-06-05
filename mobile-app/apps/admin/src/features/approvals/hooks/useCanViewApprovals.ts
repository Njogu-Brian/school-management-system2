import { useCan } from '@erp/core';

export function useCanViewApprovals(): boolean {
  return useCan(['approvals.view', 'dashboard.approvals.view', 'dashboard.view']);
}
