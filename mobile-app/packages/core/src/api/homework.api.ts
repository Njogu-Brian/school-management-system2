import type { ApiResponse, PaginatedResponse } from '../types/api';
import { apiClient } from './client';

export type HomeworkAttachmentType = 'photo' | 'video' | 'document' | 'link' | 'text';

export interface HomeworkAttachment {
  type: HomeworkAttachmentType;
  url?: string | null;
  name?: string | null;
  path?: string | null;
  mime?: string | null;
  size?: number | null;
  text?: string | null;
}

export type HomeworkDiaryStatusValue =
  | 'pending'
  | 'in_progress'
  | 'completed'
  | 'submitted'
  | 'marked';

export interface HomeworkDiaryStatus {
  id?: number;
  homework_id: number;
  student_id: number;
  student_name?: string | null;
  status: HomeworkDiaryStatusValue;
  completed_at?: string | null;
  submitted_at?: string | null;
  notes?: string | null;
}

export interface HomeworkDiaryRosterRow {
  student_id: number;
  student_name?: string | null;
  admission_number?: string | null;
  status: HomeworkDiaryStatusValue | string;
  completed_at?: string | null;
  notes?: string | null;
}

export interface HomeworkDiaryRoster {
  homework_id: number;
  title: string;
  total: number;
  completed: number;
  pending: number;
  students: HomeworkDiaryRosterRow[];
}

export interface HomeworkAssignment {
  id: number;
  title: string;
  description?: string | null;
  instructions?: string | null;
  due_date?: string | null;
  classroom_id?: number | null;
  class_id?: number | null;
  class_name?: string | null;
  stream_id?: number | null;
  stream_name?: string | null;
  subject_id?: number | null;
  subject_name?: string | null;
  teacher_id?: number | null;
  teacher_name?: string | null;
  max_score?: number | null;
  total_marks?: number | null;
  allow_late_submission?: boolean | null;
  attachments?: HomeworkAttachment[] | null;
  status?: string | null;
  created_at?: string | null;
}

/** File to upload with a new assignment (React Native file descriptor). */
export interface HomeworkFileInput {
  uri: string;
  name: string;
  type: string;
}

export interface HomeworkLinkInput {
  url: string;
  label?: string;
}

export interface CreateHomeworkPayload {
  title: string;
  instructions?: string;
  due_date: string;
  classroom_id: number;
  stream_id?: number | null;
  subject_id: number;
  target_scope?: 'class' | 'stream';
  max_score?: number;
  allow_late_submission?: boolean;
  files?: HomeworkFileInput[];
  links?: HomeworkLinkInput[];
  instructionBlocks?: string[];
}

function buildHomeworkFormData(payload: CreateHomeworkPayload): FormData {
  const form = new FormData();
  form.append('title', payload.title);
  if (payload.instructions) form.append('instructions', payload.instructions);
  form.append('due_date', payload.due_date);
  form.append('classroom_id', String(payload.classroom_id));
  if (payload.stream_id != null) form.append('stream_id', String(payload.stream_id));
  form.append('subject_id', String(payload.subject_id));
  if (payload.target_scope) form.append('target_scope', payload.target_scope);
  if (payload.max_score != null) form.append('max_score', String(payload.max_score));
  if (payload.allow_late_submission != null) {
    form.append('allow_late_submission', payload.allow_late_submission ? '1' : '0');
  }
  (payload.files ?? []).forEach((file) => {
    form.append('files[]', {
      uri: file.uri,
      name: file.name,
      type: file.type,
    } as unknown as Blob);
  });
  (payload.links ?? []).forEach((link, index) => {
    if (!link.url?.trim()) return;
    form.append(`links[${index}][url]`, link.url.trim());
    if (link.label?.trim()) form.append(`links[${index}][label]`, link.label.trim());
  });
  const blocks = (payload.instructionBlocks ?? []).map((b) => b.trim()).filter(Boolean);
  if (blocks.length > 0) {
    form.append('instruction_blocks', JSON.stringify(blocks));
  }
  return form;
}

export const homeworkApi = {
  list(params?: {
    classroom_id?: number;
    class_id?: number;
    subject_id?: number;
    teacher_id?: number;
    status?: string;
    search?: string;
    page?: number;
    per_page?: number;
  }): Promise<ApiResponse<PaginatedResponse<HomeworkAssignment>>> {
    return apiClient.get('/assignments', params);
  },

  get(id: number): Promise<ApiResponse<HomeworkAssignment>> {
    return apiClient.get(`/assignments/${id}`);
  },

  create(payload: CreateHomeworkPayload): Promise<ApiResponse<HomeworkAssignment>> {
    return apiClient.postMultipart('/assignments', buildHomeworkFormData(payload));
  },

  getStatus(id: number, studentId: number): Promise<ApiResponse<HomeworkDiaryStatus>> {
    return apiClient.get(`/assignments/${id}/status`, { student_id: studentId });
  },

  complete(
    id: number,
    payload: { student_id: number; notes?: string },
  ): Promise<ApiResponse<HomeworkDiaryStatus>> {
    return apiClient.post(`/assignments/${id}/complete`, payload);
  },

  uncomplete(
    id: number,
    payload: { student_id: number },
  ): Promise<ApiResponse<HomeworkDiaryStatus>> {
    return apiClient.post(`/assignments/${id}/uncomplete`, payload);
  },

  listDiary(id: number): Promise<ApiResponse<HomeworkDiaryRoster>> {
    return apiClient.get(`/assignments/${id}/diary`);
  },
};
