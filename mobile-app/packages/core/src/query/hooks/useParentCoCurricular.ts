import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
  parentCoCurricularApi,
  type CoCurricularAction,
} from '../../api/parentCoCurricular.api';
import { queryKeys } from '../queryKeys';

export function useParentCoCurricular(
  studentId: number,
  period?: { year?: number; term?: number },
  options?: { enabled?: boolean },
) {
  return useQuery({
    queryKey: queryKeys.coCurricular.student(studentId, period?.year, period?.term),
    enabled: (options?.enabled ?? true) && studentId > 0,
    queryFn: async () => {
      const res = await parentCoCurricularApi.show(studentId, period);
      if (!res.success || !res.data) throw new Error(res.message || 'Failed to load activities.');
      return res.data;
    },
  });
}

export function useRequestCoCurricularChange(studentId: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (payload: {
      votehead_id: number;
      action: CoCurricularAction;
      year: number;
      term: number;
      note?: string;
    }) => {
      const res = await parentCoCurricularApi.requestChange(studentId, payload);
      if (!res.success) throw new Error(res.message || 'Could not send the request.');
      return res;
    },
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: queryKeys.coCurricular.all });
    },
  });
}

export function useCancelCoCurricularRequest(studentId: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (requestId: number) => {
      const res = await parentCoCurricularApi.cancelRequest(studentId, requestId);
      if (!res.success) throw new Error(res.message || 'Could not cancel the request.');
      return res;
    },
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: queryKeys.coCurricular.all });
    },
  });
}
