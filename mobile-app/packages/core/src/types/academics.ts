/** Workspace academics types (Sprint 7) — mirrors Sanctum API payloads. */

export type ExamStatus =
  | 'draft'
  | 'open'
  | 'marking'
  | 'moderation'
  | 'approved'
  | 'published'
  | 'locked'
  | string;

export interface ExamListRecord {
  id: number;
  name: string;
  exam_type_id: number | null;
  exam_type_name: string | null;
  academic_year_id: number | null;
  term_id: number | null;
  classroom_id: number | null;
  stream_id: number | null;
  subject_id: number | null;
  start_date: string | null;
  end_date: string | null;
  status: ExamStatus;
  total_marks: number;
  created_at: string;
  updated_at: string;
  classroom_name?: string | null;
  subject_name?: string | null;
}

export interface ExamMarkingOption {
  classroom_id: number;
  classroom_name: string;
  subject_id: number;
  subject_name: string;
}

export interface ExamListFilters {
  status?: string;
  search?: string;
  academic_year_id?: number;
  term_id?: number;
  classroom_id?: number;
  page?: number;
  per_page?: number;
}

export interface MarkListRecord {
  id: number;
  exam_id: number;
  student_id: number;
  student_name: string;
  subject_id: number;
  marks: number;
  total_marks: number;
  remarks: string | null;
  percentage: number;
  created_at: string;
  updated_at: string;
}

export interface MarksListFilters {
  exam_id: number;
  subject_id: number;
  classroom_id: number;
}

export interface MarksMatrixStudent {
  id: number;
  full_name: string;
  admission_number: string;
  classroom_id: number;
  stream_id: number | null;
}

export interface MarksMatrixExam {
  id: number;
  name: string;
  subject_id: number;
  subject_name: string | null;
  max_marks: number;
  min_marks: number;
}

export interface MarksMatrixExistingMark {
  student_id: number;
  exam_id: number;
  marks: number | null;
  remarks: string | null;
}

export interface MarksMatrixContext {
  exam_types: { id: number; name: string; code?: string }[];
  classrooms: { id: number; name: string }[];
  streams: { id: number; name: string; classroom_id: number }[];
}

export interface MarksMatrixFilters {
  exam_type_id: number;
  classroom_id: number;
  stream_id?: number;
}

export interface ExamTrendPoint {
  exam_id: number;
  exam: string;
  mean: number;
  pass_rate?: number;
  delta_mean?: number | null;
}

export interface LessonPlanQueueFilters {
  classroom_id?: number;
  teacher_id?: number;
  date_from?: string;
  date_to?: string;
  search?: string;
  page?: number;
  per_page?: number;
}

export interface AcademicDashboardKpis {
  examsDraft: number;
  examsMarking: number;
  examsModeration: number;
  examsPublished: number;
  lessonPlansPendingReview: number;
  examStatusBreakdown: Record<string, number>;
  trendSummary: ExamTrendPoint[];
  trendClassroomId: number | null;
  trendTermId: number | null;
  trendAcademicYearId: number | null;
}

export interface ExamSummary {
  id: number;
  name: string;
  examTypeName: string | null;
  academicYearId: number | null;
  termId: number | null;
  classroomId: number | null;
  classroomName: string | null;
  streamId: number | null;
  subjectId: number | null;
  subjectName: string | null;
  startDate: string | null;
  endDate: string | null;
  status: ExamStatus;
  totalMarks: number;
}

export interface MarkRowSummary {
  id: number;
  studentId: number;
  studentName: string;
  marks: number;
  totalMarks: number;
  percentage: number;
  remarks: string | null;
}

export interface LessonPlanSummary {
  id: number;
  teacherName: string | null;
  subjectName: string | null;
  className: string | null;
  topic: string;
  plannedDate: string | null;
  submissionStatus: string;
  isLate: boolean;
}
