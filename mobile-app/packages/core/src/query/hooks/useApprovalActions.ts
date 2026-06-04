import { useMutation, useQueryClient } from '@tanstack/react-query';
import { approvalsApi } from '../../api/approvals.api';
import {
  leaveToApprovalItem,
  lessonPlanToApprovalItem,
  parseCompositeId,
} from '../../approvals/normalize';
import type { ApprovalCompositeId } from '../../types/approval';
import { queryKeys } from '../queryKeys';

function invalidateApprovalQueries(client: ReturnType<typeof useQueryClient>) {
  void client.invalidateQueries({ queryKey: queryKeys.approvals.all });
  void client.invalidateQueries({ queryKey: queryKeys.dashboard.pendingApprovals() });
  void client.invalidateQueries({ queryKey: queryKeys.dashboard.all });
}

export function useApprovalActions() {
  const queryClient = useQueryClient();

  const approve = useMutation({
    mutationFn: async ({
      id,
      notes,
    }: {
      id: ApprovalCompositeId;
      notes?: string;
    }) => {
      const { sourceType, sourceId } = parseCompositeId(id);
      if (sourceType === 'leave_request') {
        const res = await approvalsApi.approveLeave(sourceId, notes);
        if (!res.success || !res.data) {
          throw new Error(res.message || 'Failed to approve leave.');
        }
        return leaveToApprovalItem(res.data);
      }
      const res = await approvalsApi.approveLessonPlan(sourceId, notes);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to approve lesson plan.');
      }
      return lessonPlanToApprovalItem(res.data);
    },
    onSuccess: () => invalidateApprovalQueries(queryClient),
  });

  const reject = useMutation({
    mutationFn: async ({
      id,
      reason,
    }: {
      id: ApprovalCompositeId;
      reason: string;
    }) => {
      const { sourceType, sourceId } = parseCompositeId(id);
      if (sourceType === 'leave_request') {
        const res = await approvalsApi.rejectLeave(sourceId, reason);
        if (!res.success || !res.data) {
          throw new Error(res.message || 'Failed to reject leave.');
        }
        return leaveToApprovalItem(res.data);
      }
      const res = await approvalsApi.rejectLessonPlan(sourceId, reason);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to reject lesson plan.');
      }
      return lessonPlanToApprovalItem(res.data);
    },
    onSuccess: () => invalidateApprovalQueries(queryClient),
  });

  return { approve, reject };
}
