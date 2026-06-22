import type { ApiResponse } from '../types/api';
import { apiClient } from './client';

export interface TeacherStreamSlot {
  key: string;
  classroom_id: number;
  stream_id: number | null;
  label: string;
  subjects: Array<{
    id: number;
    subject_id: number;
    name: string;
    code: string | null;
  }>;
}

export interface TeachingAssignmentSlot {
  key: string;
  classroom_id: number;
  stream_id: number | null;
  is_class_teacher: boolean;
  is_assistant_teacher: boolean;
  subject_ids: number[];
}

export interface StaffTeachingAssignments {
  staff_id: number;
  staff_name: string;
  has_teaching_role: boolean;
  assignments: {
    slots: TeachingAssignmentSlot[];
    class_teacher_slots: string[];
    assistant_teacher_slots: string[];
  };
}

export const teacherAssignmentsApi = {
  getStreamSlots(): Promise<ApiResponse<TeacherStreamSlot[]>> {
    return apiClient.get<TeacherStreamSlot[]>('/teacher-assignments/stream-slots');
  },

  getForStaff(staffId: number): Promise<ApiResponse<StaffTeachingAssignments>> {
    return apiClient.get<StaffTeachingAssignments>(`/staff/${staffId}/teaching-assignments`);
  },

  saveForStaff(
    staffId: number,
    slots: Array<{
      classroom_id: number;
      stream_id?: number | null;
      is_class_teacher?: boolean;
      is_assistant_teacher?: boolean;
      subject_ids?: number[];
    }>,
  ): Promise<ApiResponse<StaffTeachingAssignments['assignments']>> {
    return apiClient.put<StaffTeachingAssignments['assignments']>(
      `/staff/${staffId}/teaching-assignments`,
      { slots },
    );
  },
};
