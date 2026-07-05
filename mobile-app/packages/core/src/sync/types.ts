/** Known sync queue operation kinds. */
export const SYNC_KINDS = {
  FINANCE_ASSIGN: 'finance.assign',
  FINANCE_SHARE: 'finance.share',
  ATTENDANCE_MARK: 'attendance.mark',
  EXAM_MARKS_BATCH: 'exam-marks.batch',
  EXAM_MARKS_MATRIX: 'exam-marks.matrix.batch',
} as const;

export type SyncKind = (typeof SYNC_KINDS)[keyof typeof SYNC_KINDS];

export interface SyncConflictRow {
  id: string | number;
  label: string;
  serverValue: string;
  localValue: string;
  baseValue?: string;
}

export interface AttendanceSyncPayload {
  date: string;
  class_id: number;
  stream_id?: number | null;
  class_label?: string;
  records: Array<{ student_id: number; status: string; student_name?: string }>;
  baseSnapshot: Record<number, string>;
}

export interface ExamMarksSyncPayload {
  exam_id: number;
  subject_id: number;
  classroom_id: number;
  label?: string;
  marks: Array<{ student_id: number; marks: number; remarks?: string; student_name?: string }>;
  baseSnapshot: Record<number, { marks: string; remarks: string }>;
}

export interface ExamMarksMatrixSyncPayload {
  exam_type_id: number;
  classroom_id: number;
  stream_id?: number;
  label?: string;
  entries: Array<{ student_id: number; exam_id: number; marks?: number; remarks?: string }>;
  baseSnapshot: Record<string, { marks: string; remarks: string }>;
}

export function syncKindLabel(kind: string): string {
  switch (kind) {
    case SYNC_KINDS.FINANCE_ASSIGN:
      return 'Assign transaction';
    case SYNC_KINDS.FINANCE_SHARE:
      return 'Share transaction';
    case SYNC_KINDS.ATTENDANCE_MARK:
      return 'Attendance';
    case SYNC_KINDS.EXAM_MARKS_BATCH:
      return 'Exam marks';
    case SYNC_KINDS.EXAM_MARKS_MATRIX:
      return 'Marks matrix';
    default:
      return kind;
  }
}
