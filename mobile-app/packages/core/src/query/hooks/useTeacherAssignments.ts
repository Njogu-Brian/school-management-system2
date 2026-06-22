import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
  teacherAssignmentsApi,
  type StaffTeachingAssignments,
  type TeacherStreamSlot,
} from '../../api/teacherAssignments.api';

export function useTeacherStreamSlots(enabled = true) {
  return useQuery({
    queryKey: ['teacher-assignments', 'stream-slots'],
    queryFn: async () => {
      const res = await teacherAssignmentsApi.getStreamSlots();
      return res.data ?? [];
    },
    enabled,
  });
}

export function useStaffTeachingAssignments(staffId: number, enabled = true) {
  return useQuery({
    queryKey: ['staff', staffId, 'teaching-assignments'],
    queryFn: async () => {
      const res = await teacherAssignmentsApi.getForStaff(staffId);
      return res.data;
    },
    enabled: enabled && staffId > 0,
  });
}

export function useSaveStaffTeachingAssignments(staffId: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (slots: Parameters<typeof teacherAssignmentsApi.saveForStaff>[1]) =>
      teacherAssignmentsApi.saveForStaff(staffId, slots),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['staff', staffId, 'teaching-assignments'] });
    },
  });
}

export type { StaffTeachingAssignments, TeacherStreamSlot };
