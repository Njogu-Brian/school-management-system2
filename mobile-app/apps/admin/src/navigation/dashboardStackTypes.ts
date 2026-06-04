import type { ApprovalCompositeId, ApprovalItem } from '@erp/core';

export type DashboardStackParamList = {
  DashboardHome: undefined;
  ApprovalCenter: undefined;
  ApprovalDetail: {
    id: ApprovalCompositeId;
    item: ApprovalItem;
  };
};
