import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
  parentProfileReviewApi,
  type ProfileReviewData,
  type ProfileReviewUpdatePayload,
} from '../../api/parentProfileReview.api';

const PROFILE_REVIEW_KEY = ['parent', 'profile-review'] as const;

export function useParentProfileReview(options?: { enabled?: boolean }) {
  return useQuery<ProfileReviewData>({
    queryKey: PROFILE_REVIEW_KEY,
    enabled: options?.enabled !== false,
    queryFn: async () => {
      const res = await parentProfileReviewApi.get();
      if (!res.success || !res.data) throw new Error(res.message || 'Failed to load your details.');
      return res.data;
    },
  });
}

export function useUpdateParentProfileReview() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (payload: ProfileReviewUpdatePayload) => {
      const res = await parentProfileReviewApi.update(payload);
      if (!res.success) throw new Error(res.message || 'Could not save your details.');
      return res.message ?? '';
    },
    onSuccess: () => void qc.invalidateQueries({ queryKey: PROFILE_REVIEW_KEY }),
  });
}

export function useCompleteParentProfileReview() {
  return useMutation({
    mutationFn: async () => {
      const res = await parentProfileReviewApi.complete();
      if (!res.success) throw new Error(res.message || 'Could not finish review.');
      return res.data ?? null;
    },
  });
}
