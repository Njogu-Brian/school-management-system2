import type {
  ExamListRecord,
  ExamSummary,
  LessonPlanSummary,
  MarkListRecord,
  MarkRowSummary,
} from '../types/academics';
import type { LessonPlanRecord } from '../types/approval';

export function normalizeExamSummary(row: ExamListRecord): ExamSummary {
  return {
    id: row.id,
    name: row.name,
    examTypeName: row.exam_type_name,
    academicYearId: row.academic_year_id,
    termId: row.term_id,
    classroomId: row.classroom_id,
    classroomName: row.classroom_name ?? null,
    streamId: row.stream_id,
    subjectId: row.subject_id,
    subjectName: row.subject_name ?? null,
    startDate: row.start_date,
    endDate: row.end_date,
    status: row.status,
    totalMarks: row.total_marks,
  };
}

export function normalizeMarkRow(row: MarkListRecord): MarkRowSummary {
  return {
    id: row.id,
    studentId: row.student_id,
    studentName: row.student_name,
    marks: row.marks,
    totalMarks: row.total_marks,
    percentage: row.percentage,
    remarks: row.remarks,
  };
}

export function normalizeLessonPlanSummary(row: LessonPlanRecord): LessonPlanSummary {
  return {
    id: row.id,
    teacherName: row.teacher_name ?? null,
    subjectName: row.subject_name ?? null,
    className: row.class_name ?? null,
    topic: row.topic ?? 'Lesson plan',
    plannedDate: row.date ?? null,
    submissionStatus: row.submission_status ?? row.status ?? 'draft',
    isLate: Boolean(row.is_late),
  };
}

export function examStatusLabel(status: string): string {
  const labels: Record<string, string> = {
    draft: 'Draft',
    open: 'Open',
    marking: 'Marking',
    moderation: 'Moderation',
    approved: 'Approved',
    published: 'Published',
    locked: 'Locked',
  };
  return labels[status] ?? status;
}
