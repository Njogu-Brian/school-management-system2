import type { ApprovalCompositeId, ApprovalItem } from '@erp/core';

export type ApprovalsStackParamList = {
  ApprovalsHome: undefined;
  ApprovalDetail: {
    id: ApprovalCompositeId;
    item: ApprovalItem;
  };
};
