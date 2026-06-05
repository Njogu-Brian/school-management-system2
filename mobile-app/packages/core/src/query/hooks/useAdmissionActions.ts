import { useMutation, useQueryClient } from '@tanstack/react-query';
import { admissionsApi } from '../../api/admissions.api';
import { normalizeApplicationDetail } from '../../admissions/normalize';
import type {
  EnrollApplicationPayload,
  UpdateApplicationStatusPayload,
} from '../../types/admissions';
import { queryKeys } from '../queryKeys';

function invalidateAdmissionsCaches(queryClient: ReturnType<typeof useQueryClient>, applicationId: number) {
  void queryClient.invalidateQueries({ queryKey: queryKeys.admissions.stats() });
  void queryClient.invalidateQueries({ queryKey: queryKeys.admissions.detail(applicationId) });
  void queryClient.invalidateQueries({ queryKey: queryKeys.admissions.all });
}

export function useAdmissionActions(applicationId: number) {
  const queryClient = useQueryClient();

  const updateStatus = useMutation({
    mutationFn: async (payload: UpdateApplicationStatusPayload) => {
      const res = await admissionsApi.updateStatus(applicationId, payload);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to update application status.');
      }
      return normalizeApplicationDetail(res.data);
    },
    onSuccess: () => invalidateAdmissionsCaches(queryClient, applicationId),
  });

  const waitlist = useMutation({
    mutationFn: async (reviewNotes?: string | null) => {
      const res = await admissionsApi.waitlist(applicationId, reviewNotes);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to add application to waitlist.');
      }
      return normalizeApplicationDetail(res.data);
    },
    onSuccess: () => invalidateAdmissionsCaches(queryClient, applicationId),
  });

  const reject = useMutation({
    mutationFn: async () => {
      const res = await admissionsApi.reject(applicationId);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to reject application.');
      }
      return normalizeApplicationDetail(res.data);
    },
    onSuccess: () => invalidateAdmissionsCaches(queryClient, applicationId),
  });

  const enroll = useMutation({
    mutationFn: async (payload: EnrollApplicationPayload) => {
      const res = await admissionsApi.enroll(applicationId, payload);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to enroll student.');
      }
      return {
        student: res.data.student,
        application: normalizeApplicationDetail(res.data.application),
      };
    },
    onSuccess: () => {
      invalidateAdmissionsCaches(queryClient, applicationId);
      void queryClient.invalidateQueries({ queryKey: queryKeys.students.all });
    },
  });

  return { updateStatus, waitlist, reject, enroll };
}
