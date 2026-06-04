import { useCan } from '@erp/core';

export function useCanViewApprovals(): boolean {
  return useCan(['dashboard.approvals.view', 'dashboard.view']);
}
