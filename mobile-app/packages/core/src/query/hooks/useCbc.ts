import { useQuery } from '@tanstack/react-query';
import { cbcApi } from '../../api/cbc.api';
import { queryKeys } from '../queryKeys';

export function useCbcLearningAreas(options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.cbc.learningAreas(),
    queryFn: async () => {
      const res = await cbcApi.listLearningAreas();
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load learning areas.');
      }
      return res.data;
    },
    enabled: options?.enabled !== false,
    staleTime: 300_000,
  });
}

export function useCbcStrands(learningAreaId: number, options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.cbc.strands(learningAreaId),
    queryFn: async () => {
      const res = await cbcApi.listStrands({ learning_area_id: learningAreaId });
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load strands.');
      }
      return res.data;
    },
    enabled: (options?.enabled !== false) && learningAreaId > 0,
    staleTime: 300_000,
  });
}

export function useCbcSubstrands(strandId: number, options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.cbc.substrands(strandId),
    queryFn: async () => {
      const res = await cbcApi.listSubstrands({ strand_id: strandId });
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load sub-strands.');
      }
      return res.data;
    },
    enabled: (options?.enabled !== false) && strandId > 0,
    staleTime: 300_000,
  });
}

export function useCbcSubstrand(id: number, options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.cbc.substrand(id),
    queryFn: async () => {
      const res = await cbcApi.getSubstrand(id);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load sub-strand.');
      }
      return res.data;
    },
    enabled: (options?.enabled !== false) && id > 0,
    staleTime: 300_000,
  });
}
