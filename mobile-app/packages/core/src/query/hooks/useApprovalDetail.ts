import { useQuery } from '@tanstack/react-query';
import { approvalsApi } from '../../api/approvals.api';
import { lessonPlanToApprovalItem, parseCompositeId } from '../../approvals/normalize';
import type { ApprovalCompositeId, ApprovalItem } from '../../types/approval';
import { queryKeys } from '../queryKeys';

export function useApprovalDetail(
  compositeId: ApprovalCompositeId | undefined,
  initialItem?: ApprovalItem,
) {
  const parsed = compositeId ? parseCompositeId(compositeId) : null;

  return useQuery({
    queryKey: queryKeys.approvals.detail(compositeId ?? ''),
    queryFn: async (): Promise<ApprovalItem> => {
      if (!parsed) {
        throw new Error('Invalid approval id.');
      }
      if (parsed.sourceType === 'lesson_plan') {
        const res = await approvalsApi.getLessonPlan(parsed.sourceId);
        if (!res.success || !res.data) {
          throw new Error(res.message || 'Failed to load lesson plan.');
        }
        return lessonPlanToApprovalItem(res.data);
      }
      if (initialItem) {
        return initialItem;
      }
      throw new Error('Leave detail requires list context.');
    },
    enabled: Boolean(compositeId),
    initialData: initialItem,
    staleTime: 30_000,
  });
}
